function Invoke-F08NativeLogged {
    param([string]$Command, [string[]]$Arguments, [string]$LogPath)
    # Windows PowerShell 5.1 represents native stderr as ErrorRecord. Continue
    # collecting every line rather than throwing on the first stderr line.
    $ErrorActionPreference = 'Continue'
    $code = 1
    try {
        $lines = @(& $Command @Arguments 2>&1)
        $code = $LASTEXITCODE
    } catch {
        $lines = @('Native process could not be started.')
    }
    $secrets = @('DB_PASSWORD', 'APP_KEY', 'DB_URL') | ForEach-Object { [Environment]::GetEnvironmentVariable($_, 'Process') } | Where-Object { -not [string]::IsNullOrEmpty($_) }
    $lines = @($lines | ForEach-Object {
        $line = $_.ToString()
        foreach ($secret in $secrets) { $line = $line.Replace($secret, '[REDACTED]') }
        $line
    })
    @($lines) + @("Exit code: $code") | Out-File -LiteralPath $LogPath -Encoding utf8
    return $code
}
