param([string]$ApplicationRoot = (Split-Path $PSScriptRoot -Parent))
$ErrorActionPreference = 'Stop'
$application = (Resolve-Path -LiteralPath $ApplicationRoot).Path.Replace('\','/')
$runtime = 'C:/wamp64/damios'
New-Item -ItemType Directory -Path $runtime -Force | Out-Null
$phpIni = [IO.File]::ReadAllText('C:/wamp64/bin/php/php8.4.15/php.ini')
$phpIni = $phpIni -replace '(?m)^zend_extension=.*xdebug.*$', '; Xdebug disabled for DAMI-OS'
$phpIni = $phpIni -replace '(?m)^display_errors\s*=.*$', 'display_errors = Off'
[IO.File]::WriteAllText("$runtime/php.ini", $phpIni)
$config = @"
ServerRoot "C:/wamp64/bin/apache/apache2.4.65"
Listen 80
ServerName 192.168.18.25:80
ServerTokens Prod
ServerSignature Off
LoadModule authz_core_module modules/mod_authz_core.so
LoadModule authz_host_module modules/mod_authz_host.so
LoadModule dir_module modules/mod_dir.so
LoadModule env_module modules/mod_env.so
LoadModule log_config_module modules/mod_log_config.so
LoadModule mime_module modules/mod_mime.so
LoadModule rewrite_module modules/mod_rewrite.so
LoadFile "C:/wamp64/bin/php/php8.4.15/icudt75.dll"
LoadFile "C:/wamp64/bin/php/php8.4.15/icuuc75.dll"
LoadFile "C:/wamp64/bin/php/php8.4.15/icuin75.dll"
LoadFile "C:/wamp64/bin/php/php8.4.15/icuio75.dll"
LoadFile "C:/wamp64/bin/php/php8.4.15/nghttp2.dll"
LoadFile "C:/wamp64/bin/php/php8.4.15/libssh2.dll"
LoadFile "C:/wamp64/bin/php/php8.4.15/libsasl.dll"
LoadFile "C:/wamp64/bin/php/php8.4.15/libsqlite3.dll"
LoadFile "C:/wamp64/bin/php/php8.4.15/libsodium.dll"
LoadModule php_module "C:/wamp64/bin/php/php8.4.15/php8apache2_4.dll"
PHPIniDir "$runtime"
TypesConfig conf/mime.types
DirectoryIndex index.php
ErrorLog "C:/wamp64/logs/damios-error.log"
LogFormat "%h %l %u %t %r %>s %b" common
CustomLog "C:/wamp64/logs/damios-access.log" common
DocumentRoot "$application/public"
<Directory />
    AllowOverride None
    Require all denied
</Directory>
<Directory "$application/public">
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require ip 127.0.0.1 ::1 192.168.18.0/24
</Directory>
<FilesMatch "\.php$">
    SetHandler application/x-httpd-php
</FilesMatch>
SetEnv APP_ENV production
"@
[IO.File]::WriteAllText("$runtime/httpd.conf", $config)
& C:/wamp64/bin/apache/apache2.4.65/bin/httpd.exe -t -f "$runtime/httpd.conf"
if ($LASTEXITCODE -ne 0) { throw 'Invalid Apache configuration.' }
Write-Output "Prepared Apache config: $runtime/httpd.conf"
