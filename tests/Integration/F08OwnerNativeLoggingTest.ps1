$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot 'F08OwnerNativeLogging.ps1')
$root = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..'))
$out = Join-Path $root ('storage\app\f08-logging-test-' + [guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $out | Out-Null
$env:PYTHONPATH = (Join-Path $root 'storage\app\f08-test-deps')
$dependencyLog = Join-Path $out 'dependencies.log'
$dependency = Invoke-F08NativeLogged -Command 'python' -Arguments @((Join-Path $PSScriptRoot 'F08OwnerDependencies.py')) -LogPath $dependencyLog
$text = Get-Content -Raw -LiteralPath $dependencyLog
if ($dependency -ne 0 -or $text -notmatch 'Python dependencies OK' -or $text -notmatch 'Exit code: 0') { throw 'Dependencies check failed; inspect the test log.' }
$failureLog = Join-Path $out 'failure.log'
$env:DB_PASSWORD = 'f08-test-redaction-token'
$failure = Invoke-F08NativeLogged -Command 'python' -Arguments @((Join-Path $PSScriptRoot 'F08OwnerLoggingFixture.py'), '--fail') -LogPath $failureLog
$text = Get-Content -Raw -LiteralPath $failureLog
if ($failure -eq 0 -or $text -notmatch 'fixture stdout' -or $text -notmatch 'fixture stderr' -or $text -notmatch 'Traceback' -or $text -notmatch 'fixture traceback' -or $text -notmatch "Exit code: $failure" -or $text -notmatch '\[REDACTED\]' -or $text.Contains($env:DB_PASSWORD)) { throw 'Native stderr/traceback or redaction logging failed.' }
Write-Host 'PASS: dependency invocation and complete failure logging'
