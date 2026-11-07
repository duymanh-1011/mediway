"""Real HTTP DateGuard checks on a freshly-created isolated MySQL fixture."""
import datetime,http.cookiejar,json,os,pathlib,re,socket,subprocess,time,urllib.parse,urllib.request
from zoneinfo import ZoneInfo
ROOT=pathlib.Path(__file__).resolve().parents[2]
ENV=dict(os.environ,APP_ENV='testing',DB_CONNECTION='mysql',DB_DATABASE='mediway_f08_isolated_20261105_5978f4ba',DB_URL='',SESSION_DRIVER='cookie')
def fixture(*args): return json.loads(subprocess.check_output(['php','tests/Integration/F08HttpConcurrencyFixture.php',*map(str,args)],cwd=ROOT,env=ENV,text=True))
info=fixture('create')
with socket.socket() as probe: probe.bind(('127.0.0.1',0));port=probe.getsockname()[1]
base='http://127.0.0.1:'+str(port);ENV['APP_URL']=base
server=subprocess.Popen(['php','-S','127.0.0.1:'+str(port),'-t','public','tests/Support/f08_browser_acceptance_router.php'],cwd=ROOT,env=ENV,stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
def get(client,url):
    with client.open(url,timeout=15) as r:return r.status,r.geturl(),r.read().decode('utf8')
def post(client,url,data,referer=None):
    req=urllib.request.Request(url,data=urllib.parse.urlencode(data).encode(),method='POST',headers={'Referer':referer or url})
    with client.open(req,timeout=15) as r:return r.status,r.geturl(),r.read().decode('utf8')
def login(phone):
    client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    page=get(client,base+'/tai-khoan/dang-nhap')[2]
    token=re.search(r'name="_token"\s+value="([^"]+)"',page).group(1)
    text=(ROOT/'tests/Feature/Portal/PatientPortalTest.php').read_text(encoding='utf8')
    password=re.search(r"'password'\s*=>\s*'([^']+)'",text).group(1)
    _,url,_=post(client,base+'/tai-khoan/dang-nhap',{'_token':token,'phone':phone,'password':password})
    assert '/dang-nhap' not in url
    return client
try:
    for _ in range(100):
        try: urllib.request.urlopen(base+'/up',timeout=1).close();break
        except Exception:time.sleep(.1)
    else:raise RuntimeError('server unavailable')
    admin=login(info['admin_phone']);cashier=login(info['cashier_phone']);ident=info['id']
    for offset in (-1,1):
        # A future appointment means the request is before its date, and vice versa.
        date=(datetime.datetime.now(datetime.timezone(datetime.timedelta(hours=7))).date()+datetime.timedelta(days=offset)).isoformat()
        subprocess.check_call(['php','tests/Integration/F08HttpDateFixture.php',str(ident),date],cwd=ROOT,env=ENV,stdout=subprocess.DEVNULL)
        expected='\u0110\u00e3 qu\u00e1 ng\u00e0y h\u1eb9n kh\u00e1m' if offset<0 else 'Ch\u01b0a \u0111\u1ebfn ng\u00e0y h\u1eb9n kh\u00e1m'
        admin_create=base+f'/admin/payables/{ident}/payment-transactions/create'
        cash_create=base+f'/nhan-vien/thanh-toan/{ident}/ghi-nhan'
        tokens={}
        for client,url in ((admin,admin_create),(cashier,cash_create)):
            status,destination,body=get(client,url)
            assert status==200 and not destination.endswith('/create') and expected in body,(status,destination,expected)
            tokens[client]=re.search(r'name="_token"\s+value="([^"]+)"',body).group(1)
        for client,url,data in ((admin,base+f'/admin/payables/{ident}/payment-transactions',{'payment_method':'CASH','confirm_payment':'1','collected_by_employee_id':info['admin_collector']}),(cashier,cash_create,{'payment_method':'CASH','confirm_payment':'1'})):
            data['_token']=tokens[client]
            status,destination,body=post(client,url,data,base+(f'/admin/payables/{ident}' if client is admin else f'/nhan-vien/thanh-toan/{ident}'))
            assert status==200 and expected in body,(status,destination,expected)
        state=fixture('check',ident)
        assert state['status']=='UNPAID' and state['transactions']==0 and state['audit']==0 and state['queue']==0,state
        print(('past' if offset<0 else 'future')+': GET/POST Admin and CASHIER blocked; UNPAID, no transaction/audit/queue',flush=True)
finally:
    server.terminate();server.wait(timeout=10)
