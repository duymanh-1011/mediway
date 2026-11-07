"""Concurrent F08 transactions against the explicitly isolated test database."""
import concurrent.futures,json,os,pathlib,subprocess,time
ROOT=pathlib.Path(__file__).resolve().parents[2]
ENV=dict(os.environ,APP_ENV='testing',DB_CONNECTION='mysql',DB_DATABASE='mediway_f08_isolated_20261105_5978f4ba',DB_URL='')
def fixture(*args):
    return json.loads(subprocess.check_output(['php','tests/Integration/F08HttpConcurrencyFixture.php',*map(str,args)],cwd=ROOT,env=ENV,text=True))
ids=[fixture('create')['id'] for _ in range(12)]
start=time.time()+3
def worker(pair):
    index,payable=pair
    result=subprocess.check_output(['php','tests/Support/f08_mysql_worker.php',str(payable),str(1 if index%2 else 4),str(start)],cwd=ROOT,env=ENV,text=True)
    return json.loads(result)
with concurrent.futures.ThreadPoolExecutor(max_workers=12) as pool: results=list(pool.map(worker,enumerate(ids)))
assert all(r['status']=='success' for r in results), results
codes=[r['code'] for r in results]
assert len(set(codes))==len(codes)==12 and all(c.startswith('GD') and len(c)==28 for c in codes)
for payable in ids:
    state=fixture('check',payable)
    assert state['status']=='PAID' and state['transactions']==1 and state['result']=='SUCCESS' and state['audit']==1, state
print('12 workers: 12 unique GD ULIDs, 12 PAID, one transaction and audit each.')
