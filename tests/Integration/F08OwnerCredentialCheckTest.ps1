$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot 'F08OwnerCredentialCheck.ps1')
$cases = @(
    @{ Name='non-empty'; Env=@{DB_USERNAME='test';DB_PASSWORD='secret'}; Empty=$false; Expected='provided' },
    @{ Name='declared empty'; Env=@{DB_USERNAME='test';DB_PASSWORD=''}; Empty=$false; Expected='empty' },
    @{ Name='explicit empty on PS 5'; Env=@{DB_USERNAME='test'}; Empty=$true; Expected='empty' },
    @{ Name='missing password'; Env=@{DB_USERNAME='test'}; Empty=$false; Expected='error' },
    @{ Name='missing username'; Env=@{DB_PASSWORD=''}; Empty=$true; Expected='error' },
    @{ Name='empty username'; Env=@{DB_USERNAME='';DB_PASSWORD='secret'}; Empty=$false; Expected='error' },
    @{ Name='conflicting password'; Env=@{DB_USERNAME='test';DB_PASSWORD='secret'}; Empty=$true; Expected='error' }
)
foreach ($case in $cases) {
    try { $actual = Get-F08PasswordMode -Environment $case.Env -ConfirmEmpty $case.Empty }
    catch { $actual = 'error' }
    if ($actual -ne $case.Expected) { throw "FAIL: $($case.Name)" }
    Write-Host "PASS: $($case.Name)"
}
