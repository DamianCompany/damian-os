$ErrorActionPreference = 'Stop'
& C:/wamp64/bin/apache/apache2.4.65/bin/httpd.exe -k config -n wampapache64 -f C:/wamp64/damios/httpd.conf
if ($LASTEXITCODE -ne 0) { throw 'Cannot configure Apache service.' }
Set-Service wampapache64 -StartupType Automatic
Set-Service wampmysqld64 -StartupType Automatic
Set-Service wampmariadb64 -StartupType Disabled
Start-Service wampmysqld64
Start-Service wampapache64
if (-not (Get-NetFirewallRule -DisplayName 'DAMI-OS LAN' -ErrorAction SilentlyContinue)) {
    New-NetFirewallRule -DisplayName 'DAMI-OS LAN' -Direction Inbound -Action Allow -Protocol TCP -LocalPort 80 -Program 'C:\wamp64\bin\apache\apache2.4.65\bin\httpd.exe' -Profile Private -RemoteAddress LocalSubnet | Out-Null
}
Get-Service wampapache64,wampmysqld64 | Select-Object Name,Status
