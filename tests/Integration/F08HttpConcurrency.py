"""Two real HTTP servers against the explicitly isolated synthetic F08 database."""
import concurrent.futures
import http.cookiejar
import json
import os
import pathlib
import re
import socket
import subprocess
import sys
import time
import urllib.parse
import urllib.request
import urllib.error

ROOT = pathlib.Path(__file__).resolve().parents[2]
ENV = dict(os.environ, APP_ENV='local' if '--csrf' in sys.argv else 'testing', DB_CONNECTION='mysql', DB_DATABASE='mediway_f08_isolated_20261105_5978f4ba', DB_URL='', SESSION_DRIVER='cookie')
PHP = 'php'

def fixture(*args):
    return json.loads(subprocess.check_output([PHP, 'tests/Integration/F08HttpConcurrencyFixture.php', *map(str, args)], cwd=ROOT, env=ENV, text=True))

def client():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

def get(opener, url):
    with opener.open(url, timeout=20) as response:
        return response.status, response.geturl(), response.read().decode('utf-8')

def post(opener, url, data, referer=None):
    request = urllib.request.Request(url, data=urllib.parse.urlencode(data).encode(), method='POST', headers={'Referer': referer or url})
    with opener.open(request, timeout=30) as response:
        return response.status, response.geturl(), response.read().decode('utf-8')

def token(html):
    match = re.search(r'name="_token"\s+value="([^"]+)"', html)
    if not match:
        raise RuntimeError('CSRF token missing')
    return match.group(1)

def login(opener, base, phone, password):
    _, _, page = get(opener, base + '/tai-khoan/dang-nhap')
    status, url, _ = post(opener, base + '/tai-khoan/dang-nhap', {'_token': token(page), 'phone': phone, 'password': password})
    if status != 200 or '/dang-nhap' in url:
        raise RuntimeError('Synthetic account login failed')

servers = []
try:
    ports = []
    for _ in range(2):
        with socket.socket() as probe:
            probe.bind(('127.0.0.1', 0))
            ports.append(probe.getsockname()[1])
    info = fixture('create')
    # Existing test-only seed password; never print or persist it in this test.
    content = (ROOT / 'tests/Feature/Portal/PatientPortalTest.php').read_text(encoding='utf-8')
    password = re.search(r"'password'\s*=>\s*'([^']+)'", content).group(1)
    force_cashier = '--cashier-wins' in sys.argv
    for index, port in enumerate(ports):
        router = 'tests/Support/f08_http_admin_delay_router.php' if force_cashier and index == 0 else 'public/index.php'
        servers.append(subprocess.Popen([PHP, '-S', '127.0.0.1:'+str(port), '-t', 'public', router], cwd=ROOT, env=ENV, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, creationflags=getattr(subprocess, 'CREATE_NO_WINDOW', 0)))
    bases = ['http://127.0.0.1:'+str(port) for port in ports]
    for base in bases:
        for attempt in range(100):
            try:
                urllib.request.urlopen(base+'/up', timeout=1).close()
                break
            except Exception:
                time.sleep(.1)
        else:
            raise RuntimeError('Test server did not start')
    admin, cashier = client(), client()
    login(admin, bases[0], info['admin_phone'], password)
    login(cashier, bases[1], info['cashier_phone'], password)
    admin_url = bases[0] + '/admin/payables/%s/payment-transactions' % info['id']
    cashier_url = bases[1] + '/nhan-vien/thanh-toan/%s/ghi-nhan' % info['id']
    _, _, admin_page = get(admin, admin_url+'/create')
    _, _, cashier_page = get(cashier, cashier_url)
    if '--csrf' in sys.argv:
        try:
            post(cashier, cashier_url, {'payment_method': 'CASH', 'confirm_payment': '1'}, cashier_url)
            raise AssertionError('CSRF-less payment request unexpectedly succeeded')
        except urllib.error.HTTPError as error:
            assert error.code == 419, 'Expected HTTP 419 without CSRF token'
        before = fixture('check', info['id'])
        assert before['status'] == 'UNPAID' and before['transactions'] == 0 and before['audit'] == 0 and before['queue'] == 0, before
        print('CSRF: HTTP 419; no transaction, audit or queue; UNPAID.')
    common = {'payment_method': 'CASH', 'confirm_payment': '1'}
    admin_data = dict(common, _token=token(admin_page), collected_by_employee_id=info['admin_collector'])
    cashier_data = dict(common, _token=token(cashier_page))
    with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
        first = pool.submit(post, admin, admin_url, admin_data, admin_url+'/create')
        second = pool.submit(post, cashier, cashier_url, cashier_data, cashier_url)
        responses = [first.result(), second.result()]
    db = fixture('check', info['id'])
    assert db['status'] == 'PAID' and db['transactions'] == 1 and db['result'] == 'SUCCESS' and db['audit'] == 1 and db['queue'] == 0, db
    assert int(db['collector']) in (int(info['admin_collector']), int(info['cashier_collector'])), db
    for status, url, body in responses:
        assert status == 200 and 'SQLSTATE[' not in body and 'QueryException' not in body, (status, url)
    winner = 'ADMIN' if int(db['collector']) == int(info['admin_collector']) else 'CASHIER'
    if force_cashier:
        assert winner == 'CASHIER', 'Controlled CASHIER-first run did not select CASHIER'
    assert int(db['audit_user']) == int(info['admin_user' if winner == 'ADMIN' else 'cashier_user']), 'Wrong audit actor'
    loser_body = responses[1 if winner == 'ADMIN' else 0][2]
    loser_url = responses[1 if winner == 'ADMIN' else 0][1]
    assert any(term in loser_body for term in ('đã có giao dịch thành công', 'không còn đủ điều kiện', 'không ở trạng thái có thể thanh toán', 'đã được thanh toán')), 'Rejection was not visible at '+loser_url
    print('HTTP Admin + CASHIER: one SUCCESS; PAID; winner=' + winner + '; one audit; zero queue; no SQL disclosure.')
finally:
    for server in servers:
        server.terminate()
        server.wait(timeout=10)
