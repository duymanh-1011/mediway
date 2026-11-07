function Get-F08PasswordMode {
    param([System.Collections.IDictionary]$Environment, [bool]$ConfirmEmpty)
    if (-not $Environment.Contains('DB_USERNAME') -or [string]::IsNullOrWhiteSpace([string]$Environment['DB_USERNAME'])) {
        throw 'Set DB_USERNAME for the isolated MySQL account before running.'
    }
    $present = $Environment.Contains('DB_PASSWORD')
    $password = if ($present) { [string]$Environment['DB_PASSWORD'] } else { $null }
    if ($ConfirmEmpty) {
        if ($present -and $password.Length -gt 0) { throw 'Empty-password confirmation conflicts with a non-empty DB_PASSWORD.' }
        return 'empty'
    }
    if (-not $present) { throw 'Declare DB_PASSWORD or explicitly confirm an empty test password.' }
    if ($password.Length -eq 0) { return 'empty' }
    return 'provided'
}
