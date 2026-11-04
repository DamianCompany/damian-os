# Instalación de revisión en este equipo

Preparada el 4 de noviembre de 2026. Abrir **http://192.168.18.25/admin**.

La ampliación operativa posterior está disponible en **http://192.168.18.25/admin/workshop**. Ver [OPERACION_DAMI3D.md](OPERACION_DAMI3D.md) para ventas, producción, costos, compras, fondos y pruebas. Las migraciones de noviembre ya se aplicaron con respaldo previo; la base continúa siendo la copia de revisión de julio.

Esta instalación permite revisar el respaldo local del 26 de julio de 2026. **No se ha confirmado que sea la información más reciente del negocio.** No se accedió a Railway, ni se detuvo o cambió su servicio. Antes de trabajar definitivamente aquí, comparar con el sistema usado hasta ahora y recuperar los registros posteriores si existen.

## Datos y configuración

- Respaldo original: `storage/app/backups/damian-os-local-before-normalization-20260726-211209.sql`, conservado intacto. Se detectó UTF-16; se generó una copia UTF-8 para importación.
- Base recuperada para revisión: `damios_revision_20261104_150852`. Contenía 2 usuarios, 4 impresoras y 1 pedido (`D3D-202607-0001`, del 23 de julio de 2026).
- Se conservan el `.env` original y `database/database.sqlite`. Apache usa `.env.production`, que contiene una cuenta MySQL exclusiva y conserva la clave original. Este archivo está ignorado por Git y protegido con ACL.
- Las migraciones antiguas eliminaban columnas. Para esta copia se prepararon variantes en `storage/app/backups/safe-migrations` que conservan las columnas antiguas; también se preservaron las horas y estado originales. Los archivos de migración del repositorio no se modificaron. No se borraron tablas ni se ejecutaron seeders.
- Tras un intento inicial de migración, se conservó una tabla vacía parcial como `review_partial_order_files`; no contiene registros de negocio.
- Las nuevas tablas MySQL usan InnoDB explícitamente para permitir claves foráneas, transacciones e índices apropiados.
- No se cambiaron las contraseñas de los usuarios de DAMI-OS. Se comprobó la cuenta inicial de gerencia. La contraseña administrativa de MySQL recién instalado se generó aleatoriamente y está en el archivo privado `storage/app/backups/mysql-admin.cnf`; no usarla en clientes ni compartirla.

## Servicios instalados

WampServer 3.4.0 en `C:\wamp64`, Apache 2.4.65, PHP 8.4.15 para esta aplicación y MySQL 8.4.7. Servicios `wampapache64` y `wampmysqld64` automáticos; MariaDB quedó deshabilitado para no levantar otra base.

Apache usa `C:\wamp64\damios\httpd.conf` y publica exclusivamente la carpeta `public` de esta copia. No incluye phpMyAdmin ni aliases de WampServer. MySQL escucha solo en loopback. La regla `DAMI-OS LAN` permite HTTP únicamente para perfil privado y subred local.

Para revisar/iniciar/detener, PowerShell como administrador:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\local-services.ps1 -Action Status -ApacheService wampapache64 -MySqlService wampmysqld64
```

Cambiar `Status` por `Start` o `Stop`. El bypass es solo para ese proceso; no se cambió la política global de PowerShell. No abrir WampManager para cambiar versiones o regenerar configuraciones sin revisar la configuración específica de DAMI-OS.

Para ejecutar Artisan sobre **esta copia recuperada**:

```powershell
& C:\wamp64\bin\php\php8.4.15\php.exe artisan migrate:status --env=production
```

La terminal debe tener acceso a `.env.production` (la configuración privada no es legible desde el aislamiento de algunas herramientas). No usar Artisan sin `--env=production` para operar esta copia. No ejecutar migraciones ni seeders adicionales sin respaldo.

## Comprobaciones realizadas

- 24 pruebas Laravel, 177 aserciones, todas correctas después de ajustar las pruebas de proxies explícitos.
- Inicio de sesión real mediante HTTP/Livewire con la cuenta de gerencia y carga del panel autenticado: 200.
- Pantalla de acceso por loopback y por IP LAN: 200. Diez URLs de recursos del panel: sin errores.
- `/.env` y `/composer.json`: 404.
- Entorno producción, debug desactivado y base MySQL de revisión comprobados.
- Los adjuntos de pedido referenciados en esta copia están presentes.
- Respaldo completo verificado por el script en `C:\DAMI-OS-Respaldos\damios-20261104-101451-9dab6486`, con dump, archivos, configuración y hashes. Destino protegido con ACL; no se eliminaron respaldos anteriores. Está en el mismo disco: falta una copia en otro disco físico.
- Puerto MySQL 3306 escuchando únicamente en `127.0.0.1`; Apache en el puerto 80.

Pendiente: confirmar datos definitivos, revisar en segunda computadora real, reservar DHCP para `192.168.18.25`, validar reinicio físico y programar/resguardar copias automáticas según `INSTALACION_LOCAL.md`. La IP actual todavía puede cambiar si no se reserva en el router.

Para respaldar específicamente esta copia, usar `scripts/backup-local.ps1` con `-Environment production`, `-Database damios_revision_20261104_150852`, ruta completa de `mysqldump.exe` de WampServer y un archivo de credenciales privado. La configuración general y restauración están en `INSTALACION_LOCAL.md`.
