<#
OWNER-RUN: review the script and F08RealDbReadOnly.php before use.
Only aggregate SELECT queries are performed. This script does not open the application or export PDFs.
Provide credentials for a separate SELECT-only MySQL account, then explicitly acknowledge the scope:
  cd C:\laragon\www\mediway
  $env:F08_READONLY_DB_USERNAME = '<read-only user>'
  $env:F08_READONLY_DB_PASSWORD = '<read-only password>'
  powershell -NoProfile -ExecutionPolicy Bypass -File .\tests\Integration\RUN_F08_REAL_DB_READONLY.ps1 -AcknowledgeReadOnlyScope
This does not change the machine-wide execution policy. Never put passwords in arguments or logs.
#>
[CmdletBinding()]
param([switch]$AcknowledgeReadOnlyScope)

$ErrorActionPreference = 'Stop'
$root = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..'))
$expected = [IO.Path]::GetFullPath('C:\laragon\www\mediway')
$id = (Get-Date -Format 'yyyyMMdd-HHmmss') + '-' + [guid]::NewGuid().ToString('N').Substring(0, 8)
$out = Join-Path $root "storage\app\f08-real-db-readonly\$id"
$report = [ordered]@{
    aggregate_read = 'BLOCKED'
    application_get_routes = 'UNVERIFIED: not called; session and middleware side effects require separate review.'
    responsive_and_click = 'UNVERIFIED: no browser or screenshots are started.'
    receipts_and_pdf = 'UNVERIFIED: no patient data, receipt or PDF is exported.'
    financial_writes = 'BLOCKED: not performed on real database.'
    acceptance = 'F08_NOT_READY: read-only aggregates cannot establish full acceptance.'
}
$failed = $true
try {
    if (-not $root.Equals($expected, [StringComparison]::OrdinalIgnoreCase)) { throw 'Unexpected project root.' }
    if (-not $AcknowledgeReadOnlyScope) { throw 'Pass -AcknowledgeReadOnlyScope after reviewing both scripts and the database account.' }
    if (-not $env:F08_READONLY_DB_USERNAME -or $null -eq $env:F08_READONLY_DB_PASSWORD) { throw 'Separate SELECT-only database credentials are required in environment variables.' }
    if (-not (Get-Command php -ErrorAction SilentlyContinue)) { throw 'PHP is not available.' }
    foreach ($path in @((Join-Path $root '.env'), (Join-Path $root 'vendor\autoload.php'), (Join-Path $root 'tests\Integration\F08RealDbReadOnly.php'))) {
        if (-not (Test-Path -LiteralPath $path -PathType Leaf)) { throw "Required file missing: $path" }
    }
    if (Test-Path -LiteralPath (Join-Path $root 'bootstrap\cache\config.php')) { throw 'Laravel config cache exists; inspect actual configuration separately. This script will not clear it.' }
    New-Item -ItemType Directory -Path $out | Out-Null
    Write-Host 'Read-only scope: connection preflight and aggregate SELECT counts only.'
    Write-Host 'No HTTP requests, sessions, fixtures, migrations, screenshots, PDF exports or payment operations.'
    $payload = & php (Join-Path $root 'tests\Integration\F08RealDbReadOnly.php') 2>&1 | ForEach-Object { "$_" }
    $code = $LASTEXITCODE
    if ($code -ne 0) { throw 'Read-only preflight or SELECT failed. No database error details are logged.' }
    $parsed = ($payload -join "`n") | ConvertFrom-Json
    if ($parsed.status -ne 'PASS') { throw 'Read-only audit did not return its success marker.' }
    $parsed | ConvertTo-Json -Depth 8 | Out-File -LiteralPath (Join-Path $out 'aggregate-counts.json') -Encoding utf8
    $report.aggregate_read = 'PASS: aggregate SELECT queries completed with a separate read-only account and read-only transaction.'
    $failed = $false
} catch {
    # Script-controlled messages only; PHP database errors and account details are deliberately not printed.
    Write-Host 'BLOCKED: check root, explicit acknowledgement, read-only account, environment and schema privately.'
} finally {
    if (Test-Path -LiteralPath $out -PathType Container) {
        $report | ConvertTo-Json -Depth 5 | Out-File -LiteralPath (Join-Path $out 'summary.json') -Encoding utf8
        Write-Host "Result directory: $out"
    }
}
if ($failed) { exit 1 }
exit 0
