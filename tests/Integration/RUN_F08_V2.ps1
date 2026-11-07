param([switch]$ConfirmApprovedSchema, [string]$ApprovalFile, [string]$ApprovalSha256)
# Fail closed: a valid schema name alone cannot authorize any database operation.
if (-not $ConfirmApprovedSchema) { throw 'BLOCKED: owner schema approval is required.' }
if (-not $ApprovalFile -or -not $ApprovalSha256) { throw 'BLOCKED: supply the reviewed approval manifest and its independently verified SHA256.' }
& php (Join-Path $PSScriptRoot 'F08V2Preflight.php') $ApprovalFile $ApprovalSha256
if ($LASTEXITCODE -ne 0) { throw 'BLOCKED: V2 environment is not ready; no database operations were performed.' }
throw 'BLOCKED: V2 runner is intentionally disabled pending seed and ownership integration.'
