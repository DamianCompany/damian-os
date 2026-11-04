param(
    [ValidateSet('Start','Stop','Status')][string]$Action = 'Status',
    [string]$ApacheService = 'DamiOSApache',
    [string]$MySqlService = 'MySQL84'
)
$ErrorActionPreference = 'Stop'
switch ($Action) {
    Start { Start-Service $MySqlService; Start-Service $ApacheService }
    Stop { Stop-Service $ApacheService; Stop-Service $MySqlService }
}
Get-Service $MySqlService, $ApacheService | Select-Object Name, Status, StartType
