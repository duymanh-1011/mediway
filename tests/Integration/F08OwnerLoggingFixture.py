import os
import sys

print('fixture stdout')
print('fixture stderr', file=sys.stderr)
print('fixture credential: '+os.environ.get('DB_PASSWORD', ''), file=sys.stderr)
if '--fail' in sys.argv:
    raise RuntimeError('fixture traceback')
