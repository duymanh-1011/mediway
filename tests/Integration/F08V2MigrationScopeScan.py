"""Offline source inventory, not a proof that migration execution is safe."""
from pathlib import Path
import hashlib
import json
import re

root = Path(__file__).resolve().parents[2]
patterns = {
    'connection': r'(?:DB|Schema)::connection\s*\(',
    'raw_sql': r'DB::(?:statement|unprepared|select|insert|update|delete)\s*\(',
    'external_execution': r'\b(?:shell_exec|exec|system|passthru|proc_open|popen|file_get_contents|Artisan::call)\s*\(|\b(?:include|require)(?:_once)?\s*\(?[\s\x27\x22$]|\bProcess::',
    'sql_objects_or_truncate': r'\b(?:TRUNCATE|CREATE\s+(?:TRIGGER|PROCEDURE|FUNCTION|EVENT)|USE\s+[`\w])\b',
    'drop': r'Schema::drop(?:IfExists)?\s*\(|\bDROP\s+(?:TABLE|DATABASE|SCHEMA|CHECK|INDEX|CONSTRAINT)\b',
    'dynamic_table_or_metadata': r'(?:DB|Schema)::(?:table|create|hasTable)\s*\(\s*\$|information_schema',
}
compiled = {key: re.compile(value, re.IGNORECASE) for key, value in patterns.items()}
files = sorted((root / 'database/migrations').glob('*.php'))
result = []
for path in files:
    raw = path.read_bytes()
    hits = []
    for number, line in enumerate(raw.decode('utf-8-sig').splitlines(), 1):
        for name, pattern in compiled.items():
            if pattern.search(line):
                hits.append({'line': number, 'kind': name, 'source': line.strip()})
    result.append({'file': path.name, 'sha256': hashlib.sha256(raw).hexdigest(), 'hits': hits})
print(json.dumps({'notice': 'Static triage only; no runtime or safety certification', 'count': len(files), 'migrations': result}, ensure_ascii=False, indent=2))
