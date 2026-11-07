<#
Chạy tại PowerShell do chủ dự án mở, sau khi tự xác minh database bên dưới chỉ chứa dữ liệu thử nghiệm:
  cd C:\laragon\www\mediway
  $env:DB_USERNAME = '<user-test>'
  $env:DB_PASSWORD = '<password-test>'
  powershell -NoProfile -ExecutionPolicy Bypass -File .\tests\Integration\RUN_F08_OWNER_ACCEPTANCE.ps1 -ConfirmIsolatedDatabase
  # Với mật khẩu test rỗng trên Windows PowerShell 5.1:
  # powershell -NoProfile -ExecutionPolicy Bypass -File .\tests\Integration\RUN_F08_OWNER_ACCEPTANCE.ps1 -ConfirmIsolatedDatabase -ConfirmEmptyTestPassword

Script KHÔNG cài dependency, không thay đổi ExecutionPolicy toàn máy, không chạy migration.
Browser fixture ghi dữ liệu và thanh toán thử vào MySQL cô lập; dữ liệu fixture được giữ lại.
Các file HTML hóa đơn hiện có là fixture đã render trước đó, không chứng minh dữ liệu live mới nhất.
#>
[CmdletBinding()]
param([switch]$ConfirmIsolatedDatabase, [switch]$ConfirmEmptyTestPassword)

# The legacy harness reuses records of unverified provenance. Keep it disabled
# until the V2 seed, run ownership and isolated schema checks are implemented.
throw 'BLOCKED: legacy F08 acceptance is disabled; use only an approved, fully verified V2 harness.'

$ErrorActionPreference = 'Stop'
$root = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..'))
$expectedRoot = [IO.Path]::GetFullPath('C:\laragon\www\mediway')
$database = 'mediway_f08_isolated_20261105_5978f4ba'
$runId = (Get-Date -Format 'yyyyMMdd-HHmmss') + '-' + [guid]::NewGuid().ToString('N').Substring(0, 8)
$out = Join-Path $root "storage\app\f08-owner-acceptance\$runId"
$summary = [ordered]@{}
$failed = $false
. (Join-Path $PSScriptRoot 'F08OwnerCredentialCheck.ps1')
. (Join-Path $PSScriptRoot 'F08OwnerNativeLogging.ps1')

function Record([string]$Name, [string]$Status, [string]$Reason) {
    $script:summary[$Name] = [ordered]@{ status = $Status; detail = $Reason }
    Write-Host ("{0}: {1} - {2}" -f $Name, $Status, $Reason)
}
function NativeStep([string]$Name, [string]$Command, [string[]]$Arguments) {
    Write-Host "Running: $Name"
    $log = Join-Path $out ($Name + '.log')
    # Command output is kept in the run directory; never print environment variables or credentials.
    $code = Invoke-F08NativeLogged -Command $Command -Arguments $Arguments -LogPath $log
    if ($code -ne 0) { Record $Name 'FAIL' "exit $code; see $log"; $script:failed = $true; return $false }
    Record $Name 'PASS' "exit 0; see $log"
    return $true
}
function RequireFile([string]$Path) {
    if (-not (Test-Path -LiteralPath $Path -PathType Leaf)) { throw "Missing required file: $Path" }
}
function AssertFreePort([int]$Port) {
    $listener = [Net.Sockets.TcpListener]::new([Net.IPAddress]::Loopback, $Port)
    try { $listener.Start() } finally { $listener.Stop() }
}

try {
    New-Item -ItemType Directory -Path $out -ErrorAction Stop | Out-Null
    if (-not $root.Equals($expectedRoot, [StringComparison]::OrdinalIgnoreCase)) { throw 'Unexpected project root; stopping.' }
    if (-not $ConfirmIsolatedDatabase) { throw "Missing -ConfirmIsolatedDatabase. Independently verify $database is isolated before confirming." }
    $passwordMode = Get-F08PasswordMode -Environment ([Environment]::GetEnvironmentVariables('Process')) -ConfirmEmpty $ConfirmEmptyTestPassword.IsPresent
    if ($passwordMode -eq 'empty') { $env:F08_REQUIRE_EMPTY_DB_PASSWORD = '1' } else { $env:F08_REQUIRE_EMPTY_DB_PASSWORD = '0' }
    foreach ($name in @('php', 'python', 'npm.cmd')) { if (-not (Get-Command $name -ErrorAction SilentlyContinue)) { throw "Missing dependency: $name" } }
    $edge = 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
    foreach ($path in @($edge, (Join-Path $root 'artisan'), (Join-Path $root 'phpunit.xml'), (Join-Path $root 'vendor\autoload.php'),
        (Join-Path $root 'tests\Integration\F08BrowserAcceptance.py'), (Join-Path $root 'tests\Integration\F08HttpConcurrencyFixture.php'), (Join-Path $root 'tests\Integration\F08OwnerDependencies.py'),
        (Join-Path $root 'tests\Feature\Portal\PatientPortalTest.php'), (Join-Path $root 'tests\Support\f08_browser_acceptance_router.php'),
        (Join-Path $root 'tests\Integration\F08MysqlSchemaAudit.php'), (Join-Path $root 'tests\Integration\F08OwnerMysqlGrantCheck.php'), (Join-Path $root 'tests\Integration\F08MysqlGrantParser.php'), (Join-Path $root 'storage\app\f08-print-extracted-check.py'),
        (Join-Path $root 'storage\app\f08-extracted-service.html'))) { RequireFile $path }
    if (Test-Path -LiteralPath (Join-Path $root 'bootstrap\cache\config.php')) { throw 'Laravel config cache exists. Inspect it and clear it yourself only after verifying impact; script will not change it.' }
    [xml]$phpunit = Get-Content -Raw -LiteralPath (Join-Path $root 'phpunit.xml')
    foreach ($required in @(@('DB_CONNECTION','sqlite'), @('DB_DATABASE',':memory:'), @('DB_URL',''))) {
        $entry = @($phpunit.phpunit.php.env | Where-Object { $_.name -eq $required[0] })
        if ($entry.Count -ne 1 -or $entry[0].value -ne $required[1] -or $entry[0].force -ne 'true') {
            throw "phpunit.xml must force $($required[0]) to the expected isolated SQLite value."
        }
    }
    foreach ($kind in @('exam', 'service', 'medicine')) { RequireFile (Join-Path $root "storage\app\f08-acceptance-final-$kind.html") }
    AssertFreePort 19486
    AssertFreePort 19478
    $probe = [Net.Sockets.TcpListener]::new([Net.IPAddress]::Loopback, 0)
    try { $probe.Start() } finally { $probe.Stop() }
    $env:APP_ENV = 'testing'; $env:DB_CONNECTION = 'mysql'; $env:DB_HOST = '127.0.0.1'
    $env:DB_PORT = '3306'; $env:DB_DATABASE = $database; $env:DB_URL = ''; $env:DB_SOCKET = ''
    $env:SESSION_DRIVER = 'cookie'; $env:F08_ARTIFACT_DIR = $out
    $deps = Join-Path $root 'storage\app\f08-test-deps'
    if (Test-Path -LiteralPath $deps -PathType Container) { $env:PYTHONPATH = $deps + [IO.Path]::PathSeparator + $env:PYTHONPATH }
    $mysql = [Net.Sockets.TcpClient]::new()
    try { $mysql.Connect('127.0.0.1', 3306) } finally { $mysql.Dispose() }
    Push-Location $root
    $diagnostic = @(
        "Time: $(Get-Date -Format o)", "Project root: $root", "Database: $database", 'MySQL endpoint: 127.0.0.1:3306',
        "Edge: $edge", 'Edge CDP port: 19486', 'WebDriver: not needed (Edge DevTools protocol)',
        'PHPUnit: phpunit.xml forces SQLite :memory:', 'Browser fixture: isolated MySQL; creates records; no cleanup',
        'PDF source: existing rendered HTML fixtures; manual content/print review required.'
    )
    $diagnostic | Out-File -LiteralPath (Join-Path $out 'environment.txt') -Encoding utf8
    if (-not (NativeStep 'dependencies' 'python' @('tests/Integration/F08OwnerDependencies.py'))) { throw 'Python packages websocket-client, pypdfium2 and Pillow must be installed by the owner.' }
    if (-not (NativeStep 'mysql_account_scope' 'php' @('tests/Integration/F08OwnerMysqlGrantCheck.php'))) { throw 'MySQL account is not restricted to the isolated test schema or scope cannot be verified.' }
    if ((Get-Content -Raw -LiteralPath (Join-Path $out 'mysql_account_scope.log')) -notmatch 'ISOLATED_ACCOUNT_SCOPE_PASS') { throw 'MySQL account scope was not verified.' }
    if (-not (NativeStep 'mysql_schema_readonly' 'php' @('tests/Integration/F08MysqlSchemaAudit.php'))) { throw 'Cannot verify isolated MySQL schema/account; no fixture will be created.' }
    if ((Get-Content -Raw -LiteralPath (Join-Path $out 'mysql_schema_readonly.log')) -notmatch 'Unique transaction_code index: PASS; orphan transactions: 0') {
        Record 'mysql_schema_readonly' 'FAIL' 'Expected success marker missing; connection or schema check failed.'
        throw 'MySQL schema check did not produce its success marker; no fixture will be created.'
    }
    Record 'preflight' 'PASS' 'Root, tools, isolated database name, localhost and fixed ports checked; owner confirmed isolation.'
    Write-Host 'Steps: PHPUnit SQLite; Vite build; Blade cache; isolated MySQL concurrency and HTTP; browser; print-handler check; fixture PDF export.'
    if (-not (NativeStep 'phpunit' 'php' @('artisan', 'test'))) { throw 'PHPUnit failed; remaining steps not executed.' }
    if (-not (NativeStep 'vite' 'npm.cmd' @('run', 'build'))) { throw 'Vite build failed; browser not started.' }
    if (-not (NativeStep 'blade' 'php' @('artisan', 'view:cache'))) { throw 'Blade cache failed; browser not started.' }
    if (-not (NativeStep 'mysql_concurrency' 'php' @('tests/Integration/PaymentDateMysqlConcurrency.php'))) { throw 'MySQL concurrency test failed.' }
    if (-not (NativeStep 'mysql_http_admin_wins' 'python' @('tests/Integration/F08HttpConcurrency.py', '--csrf'))) { throw 'MySQL HTTP concurrency/CSRF failed.' }
    if (-not (NativeStep 'mysql_http_cashier_wins' 'python' @('tests/Integration/F08HttpConcurrency.py', '--cashier-wins'))) { throw 'MySQL HTTP concurrency failed.' }
    if (-not (NativeStep 'mysql_http_retry_exhaustion' 'python' @('tests/Integration/F08HttpRetryExhaustion.py'))) { throw 'MySQL HTTP retry exhaustion failed.' }
    if (-not (NativeStep 'mysql_http_date_guard' 'python' @('tests/Integration/F08HttpDateGuard.py'))) { throw 'MySQL HTTP DateGuard failed.' }
    if (-not (NativeStep 'mysql_ulid' 'python' @('tests/Integration/F08UlidConcurrency.py'))) { throw 'MySQL ULID concurrency failed.' }
    if (-not (NativeStep 'browser' 'python' @('tests/Integration/F08BrowserAcceptance.py'))) { throw 'Browser test failed; inspect screenshot and log. Subsequent steps not executed.' }
    Record 'visual_review' 'UNVERIFIED' 'Screenshots are captured; owner must inspect every viewport and state.'
    # Existing script uses its own CDP port and an existing HTML fixture; it checks the print handler, not a physical print.
    if (-not (NativeStep 'print_handler' 'python' @('storage/app/f08-print-extracted-check.py'))) { throw 'Print-handler check failed.' }
    $printLog = Get-Content -Raw -LiteralPath (Join-Path $out 'print_handler.log')
    if ($printLog -notmatch 'print_handler_calls\s+1') { Record 'print_handler' 'FAIL' 'Expected exactly one window.print call.'; $failed = $true; throw 'Print handler result incorrect.' }
    foreach ($kind in @('exam', 'service', 'medicine')) {
        $html = Join-Path $root "storage\app\f08-acceptance-final-$kind.html"
        $pdf = Join-Path $out "receipt-$kind.pdf"
        $profile = Join-Path $out "edge-$kind"
        $uri = ([uri]$html).AbsoluteUri
        $proc = Start-Process -FilePath $edge -WindowStyle Hidden -PassThru -Wait -ArgumentList @('--headless=new','--disable-gpu','--no-pdf-header-footer',"--user-data-dir=$profile", "--print-to-pdf=$pdf", $uri)
        if ($proc.ExitCode -ne 0 -or -not (Test-Path -LiteralPath $pdf -PathType Leaf) -or (Get-Item -LiteralPath $pdf).Length -eq 0) { throw "PDF export failed for $kind" }
    }
    $render = @'
import os, pathlib, pypdfium2
p=pathlib.Path(os.environ['F08_ARTIFACT_DIR'])
for name in ('exam','service','medicine'):
    doc=pypdfium2.PdfDocument(str(p / ('receipt-'+name+'.pdf')))
    if not len(doc): raise RuntimeError('Empty PDF: '+name)
    for i in range(len(doc)):
        page=doc[i]
        print(name, 'page', i+1, 'size', page.get_size())
        page.render(scale=1.3).to_pil().save(p / ('receipt-'+name+'-'+str(i+1)+'.png'))
'@
    $renderPath = Join-Path $out 'render_pdf.py'
    $render | Out-File -LiteralPath $renderPath -Encoding ascii
    if (-not (NativeStep 'pdf_render' 'python' @($renderPath))) { throw 'PDFium render failed.' }
    Record 'pdf_visual_and_live_data' 'UNVERIFIED' 'PDFs use existing HTML fixtures; inspect every rendered page, data, CSS and A4 manually.'
    Record 'long_receipt_pdf' 'UNVERIFIED' 'Long receipt is not generated by this runner.'
    Record 'overall_acceptance' 'UNVERIFIED' 'Manual visual review and outstanding business decisions are required.'
} catch {
    $failed = $true
    Record 'run' 'BLOCKED' $_.Exception.Message
} finally {
    if ((Get-Location).Path -eq $root) { Pop-Location -ErrorAction SilentlyContinue }
    if (Test-Path -LiteralPath $out -PathType Container) {
        $summary | ConvertTo-Json -Depth 6 | Out-File -LiteralPath (Join-Path $out 'summary.json') -Encoding utf8
        Write-Host "Results: $out"
    }
}
if ($failed) { exit 1 }
exit 0
