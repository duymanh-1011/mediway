"""Exhaust real Laravel HTTP retries on an isolated MySQL fixture."""
import http.cookiejar
import json
import os
import pathlib
import re
import socket
import subprocess
import time
import urllib.parse
import urllib.request
import uuid

ROOT = pathlib.Path(__file__).resolve().parents[2]
marker = ROOT / 'storage/app' / ('f08-http-lock-'+uuid.uuid4().hex)
attempt_file = str(marker)+'.attempts'
env = dict(os.environ, APP_ENV='testing', DB_CONNECTION='mysql', DB_DATABASE='mediway_f08_isolated_20261105_5978f4ba', DB_URL='', SESSION_DRIVER='cookie', F08_HTTP_ATTEMPTS_PATH=attempt_file)

def fixture(*args):
    return json.loads(subprocess.check_output(['php', 'tests/Integration/F08HttpConcurrencyFixture.php', *map(str, args)], cwd=ROOT, env=env, text=True))

def request(opener, url, data=None, referer=None):
    headers = {'Referer': referer} if referer else {}
    req = urllib.request.Request(url, data=urllib.parse.urlencode(data).encode() if data else None, headers=headers)
    with opener.open(req, timeout=20) as response:
        return response.status, response.geturl(), response.read().decode('utf-8')

def token(html):
    return re.search(r'name="_token"\s+value="([^"]+)"', html).group(1)

server = locker = None
try:
    with socket.socket() as probe:
        probe.bind(('127.0.0.1', 0))
        port = probe.getsockname()[1]
    info = fixture('create')
    server = subprocess.Popen(['php', '-S', '127.0.0.1:'+str(port), 'tests/Support/f08_http_timeout_router.php'], cwd=ROOT, env=env, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, creationflags=getattr(subprocess, 'CREATE_NO_WINDOW', 0))
    base = 'http://127.0.0.1:'+str(port)
    for _ in range(100):
        try:
            urllib.request.urlopen(base+'/up', timeout=1).close()
            break
        except Exception:
            time.sleep(.1)
    else:
        raise RuntimeError('Test server did not start')
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    _, _, login_page = request(opener, base+'/tai-khoan/dang-nhap')
    text = (ROOT / 'tests/Feature/Portal/PatientPortalTest.php').read_text(encoding='utf-8')
    password = re.search(r"'password'\s*=>\s*'([^']+)'", text).group(1)
    _, login_url, _ = request(opener, base+'/tai-khoan/dang-nhap', {'_token': token(login_page), 'phone': info['cashier_phone'], 'password': password})
    assert '/dang-nhap' not in login_url, 'Synthetic login failed'
    collect = base+'/nhan-vien/thanh-toan/%s/ghi-nhan' % info['id']
    _, _, form = request(opener, collect)
    locker = subprocess.Popen(['php', 'tests/Support/f08_http_timeout_locker.php', str(info['appointment']), str(marker)], cwd=ROOT, env=env, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, creationflags=getattr(subprocess, 'CREATE_NO_WINDOW', 0))
    for _ in range(1000):
        if pathlib.Path(str(marker)+'.ready').exists():
            break
        if locker.poll() is not None:
            raise RuntimeError('Locker stopped before acquiring lock')
        time.sleep(.01)
    else:
        raise RuntimeError('Locker timeout')
    status, url, body = request(opener, collect, {'_token': token(form), 'payment_method': 'CASH', 'confirm_payment': '1'}, collect)
    expected = 'Khoản thu đang được xử lý bởi giao dịch khác. Vui lòng kiểm tra lại trạng thái và thử lại.'
    assert status == 200 and url.endswith('/nhan-vien/thanh-toan/'+str(info['id'])), (status, url)
    assert body.count(expected) == 1 and 'SQLSTATE[' not in body and 'QueryException' not in body, 'Missing, duplicated or unsafe error message'
    assert pathlib.Path(attempt_file).read_text().splitlines() == ['begin']*3, 'Expected exactly three transaction attempts'
    pathlib.Path(str(marker)+'.done').write_text('done')
    assert locker.wait(timeout=5) == 0, 'Locker failed to release'
    check = fixture('check', info['id'])
    assert check['status'] == 'UNPAID' and check['transactions'] == 0 and check['audit'] == 0 and check['queue'] == 0, check
    # A fresh GET after releasing the lock confirms the route remains usable.
    assert request(opener, base+'/nhan-vien/thanh-toan/'+str(info['id']))[0] == 200
    print('HTTP retry exhaustion: three attempts; one safe message; UNPAID; no transaction, audit or queue; subsequent GET succeeds.')
finally:
    pathlib.Path(str(marker)+'.done').write_text('done')
    if locker and locker.poll() is None:
        locker.wait(timeout=5)
    if server:
        server.terminate()
        server.wait(timeout=5)
