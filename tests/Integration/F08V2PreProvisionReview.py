"""Offline review only: never connects to MySQL or asserts owner approval."""
import hashlib
import json
import pathlib
import sys

root = pathlib.Path(__file__).resolve().parents[2]
proposal = pathlib.Path(sys.argv[1]) if len(sys.argv) == 2 else root / 'tests/Integration/F08V2_manifest_review_draft.json'
draft = json.loads(proposal.read_text(encoding='utf-8'))
if draft.get('status') != 'DRAFT_NOT_APPROVED':
    raise SystemExit('BLOCKED: expected an unapproved draft')
files = {p.stem: hashlib.sha256(p.read_bytes()).hexdigest() for p in (root / 'database/migrations').glob('*.php')}
if not files or files != draft.get('migrations'):
    raise SystemExit('BLOCKED: migration hash mismatch')
for key, rel in [('registry_ddl_sha256','tests/Integration/F08V2RegistrySchema.sql'),
                 ('fixture_source_sha256','tests/Integration/F08V2SyntheticFixture.php')]:
    if hashlib.sha256((root / rel).read_bytes()).hexdigest() != draft.get(key):
        raise SystemExit('BLOCKED: reviewed source hash mismatch: ' + key)
print('OFFLINE_HASH_CHECK_PASS')
print('BLOCKED: schema absence, FK manifest, permissions, metadata visibility and owner approval are not established.')
raise SystemExit(2)
