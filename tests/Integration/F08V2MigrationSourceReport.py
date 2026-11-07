"""Conservative offline source inventory, not a PHP interpreter or approval."""
import hashlib
import json
import pathlib
import re
import sys

ROOT = pathlib.Path(__file__).resolve().parents[2]
OUT = pathlib.Path(sys.argv[1]) if len(sys.argv) == 2 else ROOT / 'tests/Integration/F08V2_migration_source_report.json'
files = sorted((ROOT / 'database/migrations').glob('*.php'))
if not files:
    raise SystemExit('BLOCKED: no migration files')
schema_start = re.compile(r"Schema::(create|table)\(\s*(['\"])([a-z0-9_]+)\2")
dynamic_schema = re.compile(r'Schema::(?:create|table)\(\s*\$')
operation = re.compile(r'\$table\s*->|Schema::(?:create|table|drop)|DB::(?:statement|unprepared|table|connection|select)|Schema::connection|\b(?:CREATE\s+(?:TRIGGER|PROCEDURE|FUNCTION|EVENT|VIEW)|USE\s+[`\w])', re.I)
column = re.compile(r"\$table\s*->\s*(\w+)\s*\(\s*['\"]([a-z0-9_]+)['\"]")
records=[]
for path in files:
    data=path.read_bytes(); text=data.decode('utf-8-sig'); lines=text.splitlines()
    findings=[]; context='UNKNOWN'
    for index,line in enumerate(lines,1):
        match=schema_start.search(line)
        if match: context=match.group(3)
        elif dynamic_schema.search(line): context='UNKNOWN'
        if not operation.search(line): continue
        c=column.search(line)
        findings.append({'line':index,'table':context,'operation':line.strip(),
                         'column':c.group(2) if c else 'UNKNOWN',
                         'type_or_operation':c.group(1) if c else 'UNKNOWN',
                         'interpretation':'SOURCE_ONLY_REVIEW_REQUIRED'})
    records.append({'order':len(records)+1,'file':path.name,'sha256':hashlib.sha256(data).hexdigest(),
                    'findings':findings})
report={'status':'INCOMPLETE_REVIEW_REQUIRED','note':'Line inventory only. Multi-line chains, dynamic identifiers, defaults, FK targets and down/up scope are UNKNOWN until manually reviewed.',
        'migration_count':len(records),'migrations':records}
with OUT.open('x',encoding='utf-8',newline='\n') as f:
    json.dump(report,f,ensure_ascii=False,indent=2);f.write('\n')
print('SOURCE_REPORT_CREATED',len(records),'migrations; UNKNOWN entries require manual review')
