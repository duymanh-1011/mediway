import base64, concurrent.futures, http.cookiejar, json, os, pathlib, re, socket, subprocess, sys, time, urllib.parse, urllib.request, websocket
ROOT=pathlib.Path(__file__).resolve().parents[2]
ARTIFACTS=pathlib.Path(os.environ.get('F08_ARTIFACT_DIR',ROOT/'storage/app')).resolve()
ARTIFACTS.mkdir(parents=True,exist_ok=True)
ENV=dict(os.environ,APP_ENV='testing',DB_CONNECTION='mysql',DB_DATABASE='mediway_f08_isolated_20261105_5978f4ba',DB_URL='',SESSION_DRIVER='cookie')
flags=getattr(subprocess,'CREATE_NO_WINDOW',0)
def fixture(*args):
    result=subprocess.run(['php','tests/Integration/F08HttpConcurrencyFixture.php',*map(str,args)],cwd=ROOT,env=ENV,text=True,capture_output=True)
    try:
        payload=json.loads(result.stdout)
    except json.JSONDecodeError as exc:
        raise RuntimeError('BLOCKED_BY_ENVIRONMENT: fixture did not return JSON; check the isolated MySQL connection and PHP configuration privately.') from exc
    if result.returncode!=0 or not isinstance(payload,dict):
        raise RuntimeError('BLOCKED_BY_ENVIRONMENT: isolated fixture failed; inspect its configuration privately.')
    return payload
info=fixture('create')
with socket.socket() as s: s.bind(('127.0.0.1',0)); port=s.getsockname()[1]
base='http://127.0.0.1:'+str(port)
ENV['APP_URL']=base
server=subprocess.Popen(['php','-S','127.0.0.1:'+str(port),'-t','public','tests/Support/f08_browser_acceptance_router.php'],cwd=ROOT,env=ENV,stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL,creationflags=flags)
edge=r'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
browser=None
try:
    for i in range(100):
        try: urllib.request.urlopen(base+'/up',timeout=1).close();break
        except Exception: time.sleep(.1)
    else: raise RuntimeError('server unavailable')
    jar=http.cookiejar.CookieJar(); client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    page=client.open(base+'/tai-khoan/dang-nhap',timeout=15).read().decode('utf8')
    token=re.search(r'name="_token"\s+value="([^"]+)"',page).group(1)
    source=(ROOT/'tests/Feature/Portal/PatientPortalTest.php').read_text(encoding='utf8')
    password=re.search(r"'password'\s*=>\s*'([^']+)'",source).group(1)
    data=urllib.parse.urlencode({'_token':token,'phone':info['cashier_phone'],'password':password}).encode()
    response=client.open(urllib.request.Request(base+'/tai-khoan/dang-nhap',data=data,method='POST'),timeout=15)
    if '/dang-nhap' in response.geturl(): raise RuntimeError('test cashier login failed')
    browser=subprocess.Popen([edge,'--headless','--disable-gpu','--allow-file-access-from-files','--remote-debugging-port=19486','--remote-allow-origins=*',r'--user-data-dir=C:\laragon\www\mediway\storage\app\f08-acceptance-browser-profile','about:blank'],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL,creationflags=flags)
    for i in range(50):
        try: tabs=json.load(urllib.request.urlopen('http://127.0.0.1:19486/json',timeout=1));break
        except Exception: time.sleep(.2)
    else: raise RuntimeError('CDP unavailable')
    ws=websocket.create_connection(tabs[0]['webSocketDebuggerUrl'],timeout=10,suppress_origin=True); seq=0; issues=[]
    def call(method,params={}):
        global seq
        seq+=1; n=seq; ws.send(json.dumps({'id':n,'method':method,'params':params}))
        while True:
            r=json.loads(ws.recv())
            if r.get('method')=='Runtime.exceptionThrown': issues.append('js_exception')
            if r.get('method')=='Runtime.consoleAPICalled' and r['params'].get('type')=='error': issues.append('console_error')
            if r.get('method')=='Network.loadingFailed' and not r['params'].get('canceled'): issues.append('network_failure:'+r['params'].get('errorText',''))
            if r.get('method')=='Network.responseReceived' and '/build/' in r['params']['response']['url'] and r['params']['response']['status']>=400: issues.append('asset_http_'+str(r['params']['response']['status']))
            if r.get('id')==n:
                if 'error' in r: raise RuntimeError(r['error'])
                return r['result']
    def val(js): return call('Runtime.evaluate',{'expression':js,'returnByValue':True})['result'].get('value')
    call('Page.enable');call('Network.enable');call('Runtime.enable')
    for cookie in jar: call('Network.setCookie',{'name':cookie.name,'value':cookie.value,'url':base,'path':cookie.path})
    paths={'index':base+'/nhan-vien/thanh-toan','detail':base+'/nhan-vien/thanh-toan/'+str(info['id']),'collect':base+'/nhan-vien/thanh-toan/'+str(info['id'])+'/ghi-nhan'}
    for name in ('exam','service','medicine'): paths[name]='file:///C:/laragon/www/mediway/storage/app/f08-acceptance-final-'+name+'.html'
    summary={}
    for name,url in paths.items():
        call('Page.navigate',{'url':url});time.sleep(.65)
        if name in ('index','detail','collect') and '/dang-nhap' in str(val('location.href')): raise RuntimeError('browser auth missing')
        summary[name]={}
        for w in (320,375,390,768,1024,1440):
            call('Emulation.setDeviceMetricsOverride',{'width':w,'height':1000,'deviceScaleFactor':1,'mobile':False});time.sleep(.1)
            metrics=val('JSON.stringify({viewport:innerWidth,document:document.documentElement.scrollWidth,assets:Array.from(document.querySelectorAll("link[rel=stylesheet]")).length})')
            measured=json.loads(metrics)
            assert measured['document']<=measured['viewport'] and measured['assets']>0, (name,w,measured)
            layout=call('Page.getLayoutMetrics');h=min(12000,max(1000,int(layout['cssContentSize']['height']+1)))
            shot=call('Page.captureScreenshot',{'format':'png','captureBeyondViewport':True,'clip':{'x':0,'y':0,'width':w,'height':h,'scale':1}})['data']
            (ARTIFACTS/('f08-qa-final-'+name+'-'+str(w)+'.png')).write_bytes(base64.b64decode(shot))
            summary[name][str(w)]=measured
        duplicates=val("(()=>{const a=[...document.scripts].map(x=>x.src).filter(Boolean);return a.length-new Set(a).size})()")
        assert duplicates==0, (name,'duplicate JS')
        if name=='index':
            call('Emulation.setDeviceMetricsOverride',{'width':320,'height':1000,'deviceScaleFactor':1,'mobile':False});time.sleep(.1)
            # Use the freshly created UNPAID fixture, not the first arbitrary row.
            filtered=paths['index']+'?'+urllib.parse.urlencode({'q':info['code']})
            for action,selector,expected in (
                ('detail','.f08-detail-btn','/nhan-vien/thanh-toan/'+str(info['id'])),
                ('pay','.f08-pay-btn','/nhan-vien/thanh-toan/'+str(info['id'])+'/ghi-nhan'),
            ):
                try:
                    call('Page.navigate',{'url':filtered});time.sleep(.7)
                    check=val("(()=>{const w=document.querySelector('.f08-tablewrap'), rows=[...document.querySelectorAll('.f08-tablewrap tbody tr')];const row=rows.find(r=>r.textContent.includes("+json.dumps(info['code'])+"));const a=row?.querySelector("+json.dumps(selector)+");if(!w||!row||!a)return JSON.stringify({found:false,row:!!row,wrapper:!!w,text:row?.innerText});a.scrollIntoView({block:'center',inline:'center',behavior:'instant'});return JSON.stringify({found:true,href:a.href})})()")
                    check=json.loads(check)
                    assert check['found'], (action,'fixture action missing; verify fixture status/date/permissions',check)
                    assert urllib.parse.urlparse(check['href']).path==expected, (action,check)
                    last=None; stable=0
                    for _ in range(30):
                        position=val("(()=>{const w=document.querySelector('.f08-tablewrap'),a=[...w.querySelectorAll('tbody tr')].find(r=>r.textContent.includes("+json.dumps(info['code'])+"))?.querySelector("+json.dumps(selector)+");const x=a.getBoundingClientRect(),y=w.getBoundingClientRect(),c=getComputedStyle(a),px=(x.left+x.right)/2,py=(x.top+x.bottom)/2,hit=document.elementFromPoint(px,py);return JSON.stringify({scroll:w.scrollLeft,rect:[x.left,x.top,x.right,x.bottom],wrapper:[y.left,y.right],viewport:[innerWidth,innerHeight],display:c.display,visibility:c.visibility,opacity:c.opacity,hit:!!hit&&(hit===a||a.contains(hit)),point:[px,py]})})()")
                        position=json.loads(position)
                        current=(position['scroll'],*position['rect'])
                        stable=stable+1 if current==last else 0
                        last=current
                        if stable>=3: break
                        time.sleep(.1)
                    assert stable>=3, (action,'scroll did not stabilize',position)
                    x1,y1,x2,y2=position['rect']; left,right=position['wrapper']; width,height=position['viewport']
                    assert position['display']!='none' and position['visibility']=='visible' and float(position['opacity'])>0, (action,position)
                    assert x2>x1 and y2>y1 and x1>=max(0,left)-1 and x2<=min(width,right)+1 and y1>=0 and y2<=height, (action,'clipped',position)
                    assert position['hit'], (action,'center covered by another element',position)
                    x,y=position['point']
                    call('Input.dispatchMouseEvent',{'type':'mouseMoved','x':x,'y':y})
                    call('Input.dispatchMouseEvent',{'type':'mousePressed','x':x,'y':y,'button':'left','clickCount':1})
                    call('Input.dispatchMouseEvent',{'type':'mouseReleased','x':x,'y':y,'button':'left','clickCount':1})
                    for _ in range(40):
                        if urllib.parse.urlparse(val('location.href')).path==expected: break
                        time.sleep(.1)
                    assert urllib.parse.urlparse(val('location.href')).path==expected, (action,'click did not navigate',val('location.href'))
                    print('index_mobile_'+action+'_click PASS',flush=True)
                except Exception:
                    shot=call('Page.captureScreenshot',{'format':'png'})['data']
                    (ARTIFACTS/('f08-mobile-'+action+'-failure.png')).write_bytes(base64.b64decode(shot))
                    raise
        print(name,json.dumps(summary[name]),flush=True)
    call('Page.navigate',{'url':paths['index']+'?q=fixtureA'});time.sleep(.7)
    initial=val("document.querySelector('#f08-payable-filter [name=q]').value")
    val("history.pushState({},'', '?q=fixtureB'); document.querySelector('#f08-payable-filter [name=q]').value='stale'; history.back()")
    time.sleep(.9); back=val("location.search+' '+document.querySelector('#f08-payable-filter [name=q]').value")
    val('history.forward()');time.sleep(.9); forward=val("location.search+' '+document.querySelector('#f08-payable-filter [name=q]').value")
    assert initial=='fixtureA' and back=='?q=fixtureA fixtureA' and forward=='?q=fixtureB fixtureB', (initial,back,forward)
    print('filter_back_forward PASS',flush=True)
    call('Page.navigate',{'url':paths['collect']});time.sleep(.7)
    formResult=val("(()=>{const f=document.querySelector('#f08-payment-form'),b=document.querySelector('#f08-submit'); f.querySelector('[name=confirm_payment]').checked=true; f.addEventListener('submit', e=>e.preventDefault()); f.requestSubmit(); const sent=b.disabled&&f.dataset.submitting==='1'; window.dispatchEvent(new PageTransitionEvent('pageshow')); return JSON.stringify({sent,reset:!b.disabled&&f.dataset.submitting==='0'});})()")
    formResult=json.loads(formResult); assert formResult['sent'] and formResult['reset'], formResult
    print('form_js_submit_guard PASS',flush=True)
    call('Page.navigate',{'url':paths['collect']});time.sleep(.7)
    val("document.querySelector('#f08-payment-form [name=confirm_payment]').checked=true; document.querySelector('#f08-payment-form').requestSubmit()")
    time.sleep(1.2)
    assert val('location.href').endswith('/nhan-vien/thanh-toan/'+str(info['id'])), 'payment did not return to detail'
    body=val('document.body.innerText')
    assert 'Đã ghi nhận thanh toán' in body and 'Đã thanh toán' in body, 'payment success not shown'
    state=fixture('check',info['id'])
    assert state['status']=='PAID' and state['transactions']==1 and state['result']=='SUCCESS' and int(state['collector'])==int(info['cashier_collector']) and state['audit']==1 and int(state['audit_user'])==int(info['cashier_user']) and state['queue']==0, state
    shot=call('Page.captureScreenshot',{'format':'png'})['data']
    (ARTIFACTS/'f08-qa-final-cashier-success.png').write_bytes(base64.b64decode(shot))
    print('cashier_browser_payment PASS: PAID, one SUCCESS, collector and audit verified',flush=True)
    print('asset_or_js_issues',json.dumps(sorted(set(issues))),flush=True)
    assert not issues, sorted(set(issues))
    ws.close()
finally:
    if browser: browser.terminate()
    server.terminate()
