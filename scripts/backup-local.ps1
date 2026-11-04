param(
    [Parameter(Mandatory)][string]$Destination,
    [Parameter(Mandatory)][string]$DefaultsFile,
    [string]$Database = 'damios',
    [string]$MySqlDump = 'C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqldump.exe',
    [string]$Php = 'php',
    [ValidatePattern('^[a-zA-Z0-9_-]*$')][string]$Environment = '',
    [ValidateRange(0,3650)][int]$RetentionDays = 0
)
$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$environmentFile = if ($Environment) { ".env.$Environment" } else { '.env' }
$environmentOptions = @()
if ($Environment) { $environmentOptions += "--env=$Environment" }
if (-not (Test-Path -LiteralPath (Join-Path $root $environmentFile))) { throw 'Environment file not found.' }
$destinationPath = [IO.Path]::GetFullPath($Destination)
if ($destinationPath.StartsWith($root + '\', [StringComparison]::OrdinalIgnoreCase) -or $destinationPath -eq $root) {
    throw 'Use a backup destination outside the application directory.'
}
if (Test-Path (Join-Path $root 'storage/framework/down')) { throw 'Application already in maintenance. Resolve before backup.' }
$credentials = (Resolve-Path -LiteralPath $DefaultsFile).Path
New-Item -ItemType Directory -Path $destinationPath -Force | Out-Null
$folder = Join-Path $destinationPath ('damios-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '-' + [guid]::NewGuid().ToString('N').Substring(0,8))
New-Item -ItemType Directory -Path $folder | Out-Null
Push-Location $root
try {
    & $Php artisan down @environmentOptions --retry=60
    if ($LASTEXITCODE -ne 0) { throw 'Cannot enter maintenance.' }
    # Stop optional queue workers before invoking this script.
    & $MySqlDump "--defaults-extra-file=$credentials" --single-transaction --quick --no-tablespaces --set-gtid-purged=OFF --hex-blob "--result-file=$folder/database.sql" $Database
    if ($LASTEXITCODE -ne 0) { throw 'MySQL dump failed; backup is incomplete.' }
    Copy-Item -LiteralPath (Join-Path $root 'storage/app') -Destination (Join-Path $folder 'files') -Recurse
    Copy-Item -LiteralPath (Join-Path $root $environmentFile) -Destination (Join-Path $folder '.env')
    Get-ChildItem -LiteralPath $folder -File -Recurse | Get-FileHash -Algorithm SHA256 |
        Select-Object Path, Hash | ConvertTo-Json | Set-Content (Join-Path $folder 'SHA256.json')
    Set-Content (Join-Path $folder 'COMPLETE') (Get-Date -Format o)
} finally {
    if (Test-Path (Join-Path $root 'storage/framework/down')) {
        & $Php artisan up @environmentOptions
        if ($LASTEXITCODE -ne 0) { Write-Warning 'Failed to leave maintenance: run artisan up.' }
    }
    Pop-Location
}
# Explicit opt-in only. Never delete existing directories or incomplete backups.
if ($RetentionDays -gt 0) {
    Get-ChildItem -LiteralPath $destinationPath -Directory | Where-Object {
        $_.Name -match '^damios-\d{8}-\d{6}-[a-f0-9]{8}$' -and
        $_.LastWriteTime -lt (Get-Date).AddDays(-$RetentionDays) -and
        (Test-Path -LiteralPath (Join-Path $_.FullName 'COMPLETE'))
    } | ForEach-Object {
        $resolved = (Resolve-Path -LiteralPath $_.FullName).Path
        if ([IO.Path]::GetDirectoryName($resolved) -ne $destinationPath.TrimEnd('\')) { throw 'Unsafe retention path.' }
        Remove-Item -LiteralPath $resolved -Recurse -Force
    }
}
Write-Output "Backup complete: $folder"
