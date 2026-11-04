# DAMI-OS en red local (Windows)

La instalación de revisión realizada en esta computadora y sus comprobaciones posteriores están en [ESTADO_LOCAL.md](ESTADO_LOCAL.md). Consultarlo antes de repetir pasos de instalación o elegir qué base utilizar.

Una sola computadora ejecuta Apache, PHP y MySQL. Los clientes solo necesitan navegador y conexión a la misma red. **La computadora principal debe estar encendida**, sin suspensión, para poder usar el sistema. No instalar bases de datos en clientes ni sincronizarlas. Los cambios se ven al consultar o recargar; no se añadió actualización en tiempo real.

## 1. Requisitos y alcance

Equipo principal Windows, IP reservada en DHCP, disco con espacio para aplicación, adjuntos y respaldos. Instalar Apache 2.4 x64, PHP **8.3 o superior compatible con composer.lock** (preferible 8.4), MySQL 8.4, Composer 2 y Node compatible con Vite 8 (22.12+ o 24). Para Apache con mod_php usar PHP Thread Safe x64 y módulo compatible con la compilación de Apache. Habilitar PDO MySQL, mbstring, fileinfo, curl, openssl, DOM/XML, intl, zip y gd; ejecutar `composer check-platform-reqs` para confirmar requisitos efectivos.

Proyecto inspeccionado: Laravel ^13.8, Filament ~5.0 y Dompdf ^3.1. Se conservan autenticación, roles, módulos y diseño. Archivos privados en `storage/app/private`, públicos en `storage/app/public`. Sesiones, caché y colas se guardan en la misma base local. No se encontraron tareas de negocio programadas en `routes/console.php`.

Google Drive es opcional y necesita internet/OAuth. Con sus variables vacías se conservan los archivos nuevos localmente y no se crean carpetas remotas al gestionar expedientes, incluso si conservan IDs antiguos. Los enlaces históricos a Drive y WhatsApp necesitan internet. Los trabajos de Drive conservan ahora la copia local. Los documentos que ya estaban solo en Drive deben descargarse y relacionarse antes de declarar una migración totalmente offline. El correo usa `log`: no envía mensajes reales. S3, Redis y proveedores de correo aparecen como opciones de configuración y no son necesarios con `.env.local.example`.

## 2. Preparar equipo principal

1. Respaldar la instalación y datos actuales antes de cambiar nada. Usar una copia de trabajo, por ejemplo `C:\DAMI-OS`, fuera de carpetas públicas o sincronizadas. No compartir el repositorio por SMB con los usuarios.
2. Instalar las dependencias desde los archivos lock:

   ```powershell
   composer install --no-dev --optimize-autoloader
   composer check-platform-reqs
   npm.cmd ci
   npm.cmd run build
   php artisan filament:assets
   ```

   Se necesita internet durante instalación/compilación para descargar dependencias y fuentes. `public/build` incluye las fuentes Plus Jakarta Sans; conservarlo completo al distribuir la instalación. El uso diario no necesita Node, Vite ni internet para recursos esenciales. Si existe `public/hot`, detener Vite y quitar únicamente ese archivo para usar el build.
3. Copiar `.env.local.example` a un archivo de preparación y completar IP/contraseña localmente. **No sobrescribir el `.env` actual**. Al instalar en un directorio nuevo sin `.env`, copiar el ejemplo allí como `.env`. Si reutiliza una instalación, respaldar su `.env` y editar solo los valores necesarios. Conservar también `DAMIAN_COMPANY_*` y otras opciones propias. No usar `composer setup`: genera clave y ejecuta migraciones.
4. Configurar `APP_URL=http://192.168.1.50` con la IP realmente reservada; `APP_DEBUG=false`, `DB_HOST=127.0.0.1`, `DB_URL=` vacío, disco local y las opciones de sesión del ejemplo. `SESSION_DOMAIN=null` crea cookies para el host utilizado; todos deben usar la misma dirección. No arrastrar `ASSET_URL`, cookies seguras ni dominios Railway al entorno HTTP local. `TRUSTED_PROXIES=` vacío para conexión directa. Si se conserva otro despliegue tras proxy, configurar explícitamente sus IP/CIDR; `*` solo en un entorno de proxy controlado.
5. MySQL: incorporar `deploy/windows/mysql-local.ini.example` a su `my.ini` sin cambiar `datadir`. Reiniciar el servicio y verificar que 3306 y 33060 escuchen solo en loopback. No crear reglas de firewall que publiquen MySQL. En la consola administrativa de MySQL, crear una base vacía y un usuario exclusivo (reemplazar contraseña):

   ```sql
   CREATE DATABASE damios CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'damios'@'127.0.0.1' IDENTIFIED BY 'CONTRASENA_LOCAL_UNICA';
   GRANT ALL PRIVILEGES ON damios.* TO 'damios'@'127.0.0.1';
   ```

   No ejecutar estas sentencias sobre una base existente sin comprobarla. No usar root en `.env`.
6. Migrar los datos según la sección siguiente **antes** de ejecutar migraciones. Para instalación totalmente nueva, generar `APP_KEY` con `php artisan key:generate`. Para una migración, conservar la clave original; no generar otra. Después:

   ```powershell
   php artisan migrate:status
   php artisan migrate --pretend
   # Revisar SQL y respaldar antes de aplicar pendientes:
   php artisan migrate --force
   php artisan storage:link
   php artisan config:cache
   php artisan view:cache
   ```

   No ejecutar `migrate:fresh`, `refresh` ni seeders sobre datos importados: el seeder actual puede restablecer la cuenta gerencia. En una instalación vacía se puede ejecutar `php artisan db:seed --force` una vez y cambiar inmediatamente la contraseña inicial. En una migración, las cuentas existentes se conservan.

## 3. Apache, IP y acceso

Reservar en el router una IP por la dirección MAC del equipo principal, por ejemplo `192.168.1.50`. Confirmar subred mediante `ipconfig`; no copiar la subred de ejemplo si la red real es distinta. No abrir puertos del router, configurar NAT ni publicar el servidor en internet.

En `httpd.conf` configurar `Listen 80`, `ServerName 192.168.1.50:80`, cargar `mod_rewrite` y PHP. Ejemplo de PHP (ajustar instalación):

```apache
LoadModule php_module "C:/PHP/php8apache2_4.dll"
PHPIniDir "C:/PHP"
<FilesMatch "\.php$">
    SetHandler application/x-httpd-php
</FilesMatch>
Include "C:/DAMI-OS/deploy/windows/apache-damios.conf"
```

Copiar `apache-damios.conf.example` como `apache-damios.conf`, editar IP, subred y rutas. Desactivar sitios predeterminados/aliases que expongan otras carpetas. Publicar **únicamente `public`**, nunca el repositorio. Dar al usuario de servicio Apache lectura sobre la aplicación y escritura solo en `storage` y `bootstrap/cache`; proteger `.env`, configuración MySQL y respaldos con ACL para administradores y cuenta del servicio correspondiente.

Validar y registrar el servicio en PowerShell administrador:

```powershell
& C:\Apache24\bin\httpd.exe -t
& C:\Apache24\bin\httpd.exe -k install -n DamiOSApache
Set-Service DamiOSApache -StartupType Automatic
Set-Service MySQL84 -StartupType Automatic
.\scripts\local-services.ps1 -Action Start
New-NetFirewallRule -DisplayName 'DAMI-OS LAN' -Direction Inbound -Action Allow -Protocol TCP -LocalPort 80 -Program 'C:\Apache24\bin\httpd.exe' -Profile Private -RemoteAddress LocalSubnet
```

La regla se crea una vez; revisar y restringir reglas amplias creadas por instaladores. Usar perfil privado solo para la red confiable del local. Los nombres de servicio y rutas son configurables en `local-services.ps1`. No se instalaron servicios ni se modificó el firewall automáticamente.

En los clientes abrir `http://192.168.1.50/admin`. Crear acceso directo de Windows: clic derecho en escritorio → Nuevo → Acceso directo → esa URL → nombre `DAMI-OS`. Usar la IP reservada también desde el servidor. No usar `localhost` en clientes. Para cambiar IP, editar `APP_URL`, Apache y los accesos directos, ejecutar `php artisan config:cache` y volver a iniciar sesión.

## 4. Migrar desde Railway sin perder datos

No se dispone de una sesión Railway ni de sus volúmenes en este trabajo. Faltan: acceso al servicio MySQL y su versión/motor, host/puerto de exportación, copia de `APP_KEY`, volúmenes con adjuntos y acceso a documentos históricos de Drive. No detener ni eliminar servicios Railway hasta validar y coordinar el corte.

1. Hacer un respaldo inicial antes del corte. Comparar versión/motor de origen (`SELECT VERSION()`) con MySQL local; si no es MySQL, no importar un dump incompatible: preparar conversión y validar en una base de prueba.
2. En el corte acordado, impedir nuevas escrituras en Railway con mantenimiento y detener sus workers de negocio. Esperar a que finalicen tareas en ejecución. Mantenerlo así durante la copia final y evitar que usuarios escriban simultáneamente en ambos sistemas.
3. Desde un equipo autorizado con acceso al endpoint MySQL de Railway, crear un archivo privado `railway-client.cnf` con host, puerto, usuario, contraseña y las opciones TLS requeridas por Railway. No guardarlo en Git. Exportar con el cliente compatible:

   ```powershell
   mysqldump --defaults-extra-file=C:\Privado\railway-client.cnf --single-transaction --quick --no-tablespaces --set-gtid-purged=OFF --hex-blob --result-file=C:\Privado\railway-final.sql NOMBRE_BASE_ORIGEN
   ```

   Comprobar código de salida, tamaño y SHA256. `--single-transaction` requiere tablas transaccionales; con otros motores mantener bloqueadas todas las escrituras. Inventariar vistas, triggers y rutinas si existen y exportarlos con permisos y opciones adecuados.
4. Copiar íntegramente `storage/app` del volumen real del servidor (incluye `private` y `public`), preservando rutas y nombres, hacia `C:\DAMI-OS\storage\app`. El repositorio Git no contiene estos archivos. Exportar a lugar privado el `.env` original; copiar su `APP_KEY` y parámetros de empresa al `.env` local preparado, **sin copiar endpoints Railway**. Guardar inventario y hashes antes/después. Un campo URL de Drive no constituye copia de su contenido: descargar los originales por ID y restaurar las rutas asociadas si faltan. No sustituir URLs masivamente sin verificar cada archivo.
5. Importar en una **base local nueva y vacía** desde la consola interactiva de MySQL (evita corrupción de redirecciones en PowerShell):

   ```powershell
   mysql --defaults-extra-file=C:\Privado\local-admin.cnf damios
   ```

   ```sql
   SOURCE C:/Privado/railway-final.sql;
   ```

6. Comparar conteos por tabla, últimos códigos/fechas, cuentas, roles, montos y archivos con Railway. Aplicar solo migraciones pendientes revisadas. Comprobar descifrado de credenciales existentes usando la misma `APP_KEY`. Las sesiones previas pueden requerir nuevo inicio de sesión por cambio de host.
7. Validar con la lista final y habilitar escrituras locales. Si hay que volver a Railway, bloquear primero escrituras locales y conciliar los registros nuevos antes de reabrir el origen. No borrar el origen ni sus respaldos. Su retirada requiere coordinación posterior.

## 5. Arranque, colas y respaldos

Apache y MySQL como servicios automáticos permiten funcionar sin sesión interactiva. Revisar desde `services.msc` sus opciones de recuperación. Desactivar suspensión del equipo principal. Comandos de operación:

```powershell
.\scripts\local-services.ps1 -Action Status
.\scripts\local-services.ps1 -Action Stop
.\scripts\local-services.ps1 -Action Start
Get-Content C:\Apache24\logs\damios-error.log -Tail 50
Get-Content C:\DAMI-OS\storage\logs\laravel.log -Tail 50
```

En modo local sin Drive no hace falta worker ni scheduler de negocio. Si se activa Drive, configurar un worker supervisado (por ejemplo servicio WinSW con directorio `C:\DAMI-OS` y ejecutable `C:\PHP\php.exe`, argumentos `artisan queue:work database --sleep=3 --tries=3 --timeout=180`, reinicio automático y logs privados). Revisar `php artisan queue:failed`. Existen tareas `dispatchAfterResponse` que se ejecutan tras responder y también requieren internet cuando Drive está configurado. No iniciar workers con trabajos históricos sin revisar sus efectos; los que sincronizan Drive pueden necesitar credenciales antiguas. No configurar `schedule:run` por defecto: no hay tareas de negocio registradas.

### Respaldo automático

Copiar `mysql-backup.cnf.example` a `C:\Privado\damios-backup.cnf`, completar credenciales y restringir sus ACL. Crear usuario de respaldo con SELECT, SHOW VIEW, TRIGGER sobre `damios.*` (añadir privilegios solo si el dump los necesita). Usar una cuenta de Windows dedicada con acceso a ese archivo, PHP, aplicación y destino. Los respaldos contienen datos privados y `.env` con `APP_KEY`: protegerlos igual que la base.

```powershell
C:\DAMI-OS\scripts\backup-local.ps1 -Destination E:\RespaldosDamiOS -DefaultsFile C:\Privado\damios-backup.cnf -Php C:\PHP\php.exe -RetentionDays 30
```

El script entra brevemente en mantenimiento, exporta MySQL, copia todo `storage/app` y `.env`, guarda hashes y marca `COMPLETE` solo al terminar. Ante errores vuelve a habilitar la app y no aplica retención. Si hay workers activos, detenerlos y esperar su salida antes de ejecutar el respaldo; reanudarlos después. No ejecutar dos respaldos a la vez. Los conjuntos incompletos requieren revisión manual y no sirven para restaurar.

En Programador de tareas crear tarea diaria 23:00, ejecutar aunque no haya sesión iniciada, con cuenta dedicada, acción `powershell.exe`, argumentos:

```text
-NoProfile -File "C:\DAMI-OS\scripts\backup-local.ps1" -Destination "E:\RespaldosDamiOS" -DefaultsFile "C:\Privado\damios-backup.cnf" -Php "C:\PHP\php.exe" -RetentionDays 30
```

Directorio inicial `C:\DAMI-OS`; no iniciar nueva instancia si ya se está ejecutando; ejecutar cuando vuelva a estar disponible si se perdió el horario. Habilitar historial y comprobar resultado 0 y marcador `COMPLETE` diariamente. Con `RetentionDays 30` se autoriza eliminar únicamente carpetas completas creadas por este script con más de 30 días. Sin ese parámetro no se elimina ningún respaldo. Probar la tarea manualmente antes de confiar en ella.

Guardar además una copia completa en otro disco físico, idealmente desconectado entre copias. Copiar solo conjuntos `COMPLETE`, incluyendo hashes y `.env`; verificar hashes en destino. Una segunda carpeta del mismo disco no protege frente a fallo físico. Los destinos de red requieren cuenta con acceso y ruta UNC; las letras mapeadas no suelen estar disponibles para tareas sin sesión.

### Restaurar

1. Verificar `COMPLETE` y recalcular hashes del dump, `.env` y archivos. Conservar intacto el conjunto original.
2. Bloquear escrituras, detener workers y respaldar primero el estado actual. Restaurar a una nueva base vacía, importar `database.sql` con `SOURCE` como arriba y cambiar `DB_DATABASE` para apuntar a ella tras verificarla. No importar encima de tablas con datos.
3. Restaurar `files` como `storage/app` en una instalación de recuperación separada, sin mezclar archivos viejos. Tomar del `.env` respaldado la clave y configuración necesaria, conservando host/credenciales locales correctos. No publicar ese archivo.
4. Revisar permisos, `php artisan storage:link`, `php artisan config:cache` y `php artisan view:cache`. Comparar conteos y adjuntos, probar inicio de sesión/reportes y ejecutar `php artisan up`. No regenerar clave. Conservar base y archivos previos hasta aceptar la recuperación.

## 6. Validación y errores frecuentes

Comprobación disponible en este entorno: `npm.cmd run build` genera CSS/JS y fuentes locales. No había PHP, MySQL ni Apache disponibles en PATH para ejecutar pruebas de Laravel o levantar servicios. No se migraron datos ni se probó una segunda computadora real. Ejecutar después de instalar PHP `php artisan test` (SQLite de pruebas en memoria, nunca apuntar las pruebas a producción).

Lista de aceptación en el local:

- Desde dos navegadores/equipos iniciar sesión con cuentas de roles distintos. Verificar rechazo a usuarios inactivos/vencidos y URLs de módulos sin permiso.
- Crear un registro identificable por cada módulo utilizado, recargar en el segundo equipo y comprobar los mismos datos. Probar altas simultáneas de pedidos/solicitudes y comprobar códigos únicos, relaciones y totales; no asumir resueltos conflictos de edición simultánea del mismo registro.
- Subir/abrir adjuntos de DAMI 3D, Investiga, Automation, servicio técnico y proveedores. Desconectar internet y repetir con archivos locales; comprobar cotización PDF y reportes con importes conocidos.
- Comprobar desde clientes que `/.env`, `/composer.json` y `/storage/app/private/…` no son accesibles, y que MySQL no acepta conexiones desde la LAN. Los enlaces privados firmados caducan a los 15 minutos: recargar para generar otro.
- Reiniciar Windows: Apache/MySQL deben arrancar automáticamente; comprobar persistencia de registros, archivos, roles e inicio de sesión desde otro equipo.
- Ejecutar respaldo y restaurar en una instalación/base de prueba. Comparar datos y adjuntos antes de aceptar el cambio definitivo.

Errores habituales:

- **No conecta otro equipo:** revisar IP reservada, perfil privado, firewall, Apache iniciado, aislamiento Wi-Fi/VLAN y puerto 80 ocupado por IIS. No abrir el router.
- **419 / sesión perdida:** usar misma IP, revisar `APP_KEY`, tabla `sessions`, `SESSION_DOMAIN=null`, cookies no seguras para HTTP, hora de los equipos; ejecutar `config:cache` tras editar y borrar cookies antiguas.
- **CSS/JS faltantes:** recompilar, copiar todo `public/build`, publicar assets de Filament y quitar `public/hot` residual; revisar APP_URL y ASSET_URL.
- **500 / acceso denegado:** consultar logs; revisar PDO MySQL, credenciales, permisos de carpetas, clave y migraciones. No habilitar debug para toda la red.
- **Adjunto ausente:** verificar ruta en DB y archivo en `storage/app/private`, o si se eliminó históricamente tras subirlo a Drive. Recuperar su original; no crear enlaces públicos para eludir permisos.
- **Drive sin conexión:** dejar raíces/credenciales vacías para operación local. Para reactivarlo revisar OAuth y URI autorizada; Google puede exigir HTTPS o un callback permitido, por lo que no asumir que acepta una IP HTTP privada.

Referencias: [despliegue de Laravel](https://laravel.com/framework/docs/deployment), [Apache como servicio Windows](https://httpd.apache.org/docs/2.4/en/platform/windows.html). La instalación del software, reserva DHCP, credenciales y corte de Railway se realizan en el equipo/red del local.
