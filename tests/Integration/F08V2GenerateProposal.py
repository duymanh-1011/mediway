"""Offline draft generator. Never connects to a database or approves a schema."""
import hashlib
import json
import pathlib
import re
import sys

root = pathlib.Path(__file__).resolve().parents[2]
files = sorted((root / 'database/migrations').glob('*.php'))
if not files:
    raise SystemExit('BLOCKED: no migrations')
tables = {'migrations', 'acceptance_runs', 'acceptance_fixtures'}
migrations = {}
manual = []
for path in files:
    data = path.read_bytes()
    text = data.decode('utf-8-sig')
    migrations[path.stem] = hashlib.sha256(data).hexdigest()
    tables.update(re.findall(r"Schema::create\(\s*['\"]([a-z0-9_]+)['\"]", text))
    if re.search(r'DB::(?:statement|unprepared)|Schema::table|foreign\(', text):
        manual.append(path.stem)
proposal = {
    'status': 'DRAFT_NOT_APPROVED',
    'purpose': 'F08_V2_READONLY_PREFLIGHT',
    'schema': None, 'run_id': None, 'reviewed_by': None,
    'fixture_account': 'f08_runner_v2', 'worker_account': 'f08_worker_v2',
    'tables': sorted(tables), 'migrations': migrations,
    'registry_ddl_sha256': hashlib.sha256((root / 'tests/Integration/F08V2RegistrySchema.sql').read_bytes()).hexdigest(),
    'fixture_source_sha256': hashlib.sha256((root / 'tests/Integration/F08V2SyntheticFixture.php').read_bytes()).hexdigest(),
    'foreign_keys': [],
    'manual_review': {
        'foreign_keys': 'Incomplete: review every migration and registry DDL; do not approve empty list.',
        'dynamic_migrations': manual,
        'database_absence': 'Not checked; requires a separate read-only owner preflight.',
        'approval': 'Hash is integrity evidence only, not an owner signature.',
    },
}
output = pathlib.Path(sys.argv[1]) if len(sys.argv) == 2 else root / 'tests/Integration/F08V2_manifest_proposal.json'
with output.open('x', encoding='utf-8', newline='\n') as stream:
    json.dump(proposal, stream, ensure_ascii=False, indent=2)
    stream.write('\n')
print('DRAFT created:', output, 'migrations:', len(migrations), 'tables:', len(tables), 'FK review: INCOMPLETE')
