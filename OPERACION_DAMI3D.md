# Uso y pruebas de DAMI 3D

Acceso en este equipo y en la misma red: **http://192.168.18.25/admin/workshop**. También aparece en **DAMI 3D → Operación diaria**. La computadora principal debe estar encendida. Se conserva Apache/PHP/MySQL local, una única base compartida y los otros módulos de DAMI OS.

## Antes de comenzar

La instalación sigue utilizando la **copia de revisión recuperada del respaldo local de julio de 2026**. Confirmar si existen registros posteriores en Railway antes de adoptarla como base definitiva. Estos cambios no accedieron ni retiraron Railway.

El pedido anterior se incorporó conservando su cliente, importe y adelanto. Su adelanto se considera **saldo cobrado inicial**, sin inventar un movimiento en una caja actual. No se inventó producción ni entrega a partir del estado antiguo. Las columnas, archivos y respaldos anteriores se conservan.

Los perfiles de DAMI 3D son **Administrador** (`gerencia`) y **Trabajador DAMI 3D** (`dami_3d`). Los perfiles de las otras líneas continúan disponibles. Usar una cuenta por persona; el administrador las configura en **Usuarios y credenciales**. Las restricciones individuales permiten impedir ventas, cobros, clientes, compras, producción, creación de tareas o entregas. Se comprueban en el servidor, además de ocultar controles.

## Cómo explorar sin registrar dinero ficticio

1. Entrar como administrador y abrir las pestañas. Abrir formularios y usar **Cancelar** para explorar sin guardar.
2. Para probar un registro no financiero, elegir **Nueva venta / pedido → Cotización**, describirlo claramente como prueba y guardar. Revisar total y líneas; luego usar **Acción de pedido → Anular cotización**. No convertirlo en pedido ni registrar un cobro si no es una operación real.
3. Los escenarios automatizados de ventas, consumos, fondos y cobros usan SQLite en memoria, separado de la base del local. No introducir gastos, saldos o tarifas de ejemplo en la base del negocio para ejecutar pruebas.
4. Si se necesita un ensayo operativo con dinero/stock ficticios, preparar una instalación y base de prueba independientes mediante el procedimiento de restauración. No ejecutar pruebas destructivas sobre la base compartida.

## Configuración inicial como administrador

Entrar a **Costos y configuración**:

- **Configurar → Caja / cuenta**: nombre y saldo inicial realmente verificado. Crear cada caja y cuenta receptora por separado.
- **Configurar → Medio de pago**: efectivo, Yape, transferencia u otros que realmente se utilicen. El medio y la cuenta se seleccionan por separado al cobrar/pagar.
- **Configurar → Máquina**: reutilizar las impresoras existentes o registrar otros equipos con nombre y ubicación. El costo individual se configura con una tarifa de máquina. La actividad identifica impresión, láser u otro proceso.
- **Configurar → Material o producto**: distinguir material de producto terminado, categoría, unidad de compra, unidad de consumo y conversión explícita. Por ejemplo, para kg a g el factor es 1000. Para una plancha a m² se introduce su superficie real. No se deducen conversiones de volumen a peso. Las compras registran precio y cantidad reales y actualizan el promedio ponderado; la ficha no inventa un costo de material sin compras o una entrada valorizada.
- **Configurar → Actividad de producción**: añadir actividades propias además de las habituales.
- Registrar existencias iniciales verificadas con **Materiales y productos → Ajuste de existencias**, indicando cantidad, valor y motivo. Solo el administrador hace ajustes.

No se precargaron tarifas, fondos, compras ni saldos de ejemplo.

### Tarifas y gastos generales

**Nueva tarifa / vigencia** crea una versión, sin modificar producciones anteriores. Desde una tarifa existente se puede crear una nueva vigencia conservando su configuración.

- **Máquina, modo manual**: introducir una única tarifa total por hora. Los componentes no se suman en este modo.
- **Máquina, modo desglosado**: electricidad (`kW promedio × S/ por kWh`) + depreciación (`costo depreciable / horas de vida útil`) + mantenimiento por hora + consumibles por hora. Introducir el consumo de todo el equipo y auxiliares; la potencia del tubo láser no equivale al consumo eléctrico del equipo.
- **Mano de obra**: tarifa manual o costo mensual / horas productivas normales. Precedencia: trabajador y actividad, trabajador, actividad, general. Se utiliza solo una tarifa. El administrador puede incluirse como trabajador para valorar su tiempo.
- **Gastos generales**: nombre del concepto, presupuesto mensual, parte atribuible al negocio, capacidad normal en horas manuales y vigencia. Crear conceptos distintos para gastos distintos. Para actualizar un concepto, usar **Nueva vigencia de esta tarifa**, manteniendo su nombre. Se suman las versiones vigentes de los distintos conceptos, sin sumar dos versiones del mismo concepto.
- No volver a incluir en gastos generales electricidad/mantenimiento ya valorizados en máquinas, materiales directos o mano de obra ya valorizada. No recalcular la capacidad normal por meses con pocas ventas.

Las producciones guardan IDs y valores de tarifas, consumos valorizados y componentes. **Simular recálculo histórico** registra una comparación en **Actividad**, sin reescribir costos ni inventarios cerrados. Una revalorización contable retroactiva que afecte entregas y existencias requiere conciliación administrativa; no se aplica automáticamente.

### Recetas

**Guardar receta**: elegir producto, tamaño del lote (1 para receta por unidad), materiales, preparación fija por tanda, tiempo variable por lote, tiempo manual, máquina y servicios externos. Aros, accesorios y embalajes pueden ser materiales de consumo por unidad. También se puede iniciar una receta desde un registro de producción.

La preparación se repite por tanda, no por pieza. Al elegir el producto en producción se sugieren cantidades y tiempos; cambiar la cantidad recalcula las sugerencias. El trabajador confirma o ajusta consumos y tiempos. Las sugerencias de receta no cambian tarifas.

Las líneas de venta guardan receta y estimación originales cuando están disponibles. Los consumos confirmados se muestran por separado y sustituyen la estimación para el resultado; no se suman ambos costos.

## Cómo registrar como trabajador

### Venta / pedido / cotización

1. **Nueva venta / pedido**: elegir pedido personalizado, venta de stock o cotización.
2. Elegir un cliente, crearlo con nombre desde el selector, o dejarlo vacío para cliente ocasional. Buscar por nombre, teléfono o documento.
3. Elegir producto de catálogo o descripción libre; cantidad predeterminada 1, precio unitario y total automático. Añadir más líneas si hace falta.
4. Si se recibió dinero, introducir importe, medio y caja/cuenta receptora. Para crédito dejar pago recibido en cero.
5. Indicar entrega. En **Más opciones** están aprobación de diseño, vencimiento del pago, OC, dirección, notas y archivos.
6. Guardar. Para stock existente se puede elegir entrega inmediata; caja, venta y descuento de stock se confirman juntos o se revierten juntos si la operación falla.

Una cotización se puede revisar conservando las líneas de versiones anteriores. **Acción de pedido → Convertir cotización en pedido** evita repetir los datos. Una cotización ya confirmada no se modifica por ese formulario. Se puede duplicar un pedido para iniciar otro con pago recibido en cero.

Cobros posteriores: **Registrar cobro**, elegir pedido, importe, medio y cuenta. El servidor impide cobrar más que el saldo. El trabajador solicita correcciones mediante **Solicitar corrección**; no edita importes confirmados.

En **Clientes**, **Completar cliente** permite añadir documento/RUC, contacto, dirección y condiciones de pago después del alta rápida. Los posibles duplicados generan aviso sin impedir el alta. **Consultar pedidos y saldos** filtra el historial.

### Producción y trabajos

- **Mis trabajos / programación → Nuevo trabajo**: el trabajador se asigna a sí mismo; el administrador puede asignar a otros. Elegir pedido y línea, destino de stock, muestra o trabajo interno. Registrar actividad, cantidad, máquina, fechas previstas y archivo opcional. Los solapamientos de máquina o responsable generan aviso.
- **Iniciar / Pausar / Reanudar / Terminar** guardan tiempos. Activar el cronómetro manual solo cuando se dedica trabajo manual real; una máquina autónoma no implica el mismo tiempo de mano de obra. Solo puede haber un cronómetro manual por trabajador y una tarea en curso por máquina.
- Al terminar, confirmar materiales, cantidades, fallidas, desperdicio y servicios externos si existen. Se crea automáticamente el registro diario; no volver a registrarlo manualmente.
- **Registrar producción** sirve para trabajos sin cronómetro, cantidades parciales, muestras o stock. Los campos de tiempo representan minutos reales; el registro directo no puede deducir intervalos manuales que no se cronometraron.
- Marcar **producto terminado** solo en la etapa final. Cortar, armar y pintar diez piezas produce diez unidades terminadas, no treinta. Los pedidos aceptan terminaciones parciales hasta su cantidad pendiente.
- Para stock con varias etapas, crear **Nuevo lote para stock** y asociar sus tareas/producciones al mismo lote. Primero completar las etapas intermedias, después registrar terminaciones completas o parciales. El costo de las etapas anteriores se reparte sin volver a sumar unidades. No añadir etapas previas después de empezar a terminar ese lote; un reproceso debe identificarse aparte.
- El desperdicio del detalle está **incluido** en el consumo total y no se vuelve a descontar. Las fallidas no se cuentan como unidades buenas terminadas.
- Si faltan tarifas o no se confirmaron consumos, el costo se indica **Incompleto**, no cero ni utilidad definitiva.

El administrador puede corregir tiempos de una tarea pausada indicando motivo. La producción confirmada es inmutable; las correcciones posteriores a entregas/stock requieren conciliación, y el recálculo histórico disponible es una simulación auditada.

### Compra / gasto

1. **Registrar compra / gasto**: descripción e importe real.
2. Elegir compra de materiales/productos, costo directo de pedido o gasto general.
3. Para materiales detallar cantidades en **unidad de compra** e importes por línea. La suma debe coincidir con el total. Comprar material aumenta stock, no consumo de un pedido.
4. Elegir origen: caja/cuenta, fondo propio asignado, dinero personal por reembolsar o crédito. Indicar proveedor (se puede dar de alta rápidamente), pedido si corresponde y comprobante opcional.
5. Guardar. Las compras propias pendientes de reembolso no mueven caja empresarial hasta que el administrador las reembolsa; el reembolso no crea otra compra.

No repetir un servicio externo como importe manual de producción y como compra directa del mismo pedido. Si ambos existen, el sistema conserva los registros y marca la valoración como incompleta para que el administrador concilie la posible duplicación.

### Fondos y rendiciones

El administrador asigna importe desde una cuenta, con finalidad, trabajador y pedido opcional. El trabajador consulta **Fondos y rendiciones**, registra sus gastos contra el fondo y devuelve el remanente a una caja/cuenta.

Se muestran asignado, gastos, devoluciones y saldo por justificar/devolver. El administrador aprueba u observa compras y cierra la rendición solo cuando los gastos estén aprobados y no quede saldo. La entrega de fondos es una transferencia, no un gasto adicional.

### Entregar

**Entregar pedido**: elegir línea, cantidad parcial o total, origen (stock o producción del pedido), modalidad y referencia opcional. No se entrega más de lo pendiente ni más stock del disponible. No se descuenta stock al reservar. Entrega y pago son estados independientes.

## Caja, correcciones y resultados

**Caja y cobros** muestra cuentas y movimientos con usuario, fecha y referencias. El administrador autoriza reversos de cobros, pagos de compras, reembolsos y transferencias/devoluciones de fondos cuando las condiciones de saldo lo permiten. Se conserva el movimiento original y se crea el inverso con motivo. Un reverso representa dinero realmente devuelto o corregido, no una forma de borrar un error sin conciliarlo.

Una compra aún no consumida puede anularse administrativamente con devolución de dinero/deuda confirmada: se conservan sus registros, se revierten pagos y se devuelve su stock. Si ya hubo salidas o entregas relacionadas, la anulación automática se bloquea para no romper inventario/costos. Los pedidos sin producción/entrega se pueden anular después de revertir sus cobros; los ya ejecutados requieren conciliación.

**Reportes**: ventas registradas, ventas entregadas, cobros, vencidos, costos conocidos de producción/entrega, gastos generales reales/asignados y diferencia, capacidad, fondos, reembolsos y caja. Cada indicador lleva al detalle y se puede exportar el resumen CSV. Usar filtros de periodo, trabajador, máquina, cliente, pedido y categoría según corresponda; el saldo actual global de cajas se identifica como tal.

- Valoración de existencias: promedio ponderado móvil, en centavos y milésimas de unidad. El último consumo absorbe el saldo remanente de valoración. Los costos de entregas parciales se asignan desde el costo aún no entregado, sin volver a cargar costos ya reconocidos.
- Margen por pedido: valor de venta menos materiales, máquina, mano de obra, servicios/directos y generales asignados, siempre identificado como estimado y solo completo si hay datos suficientes.
- Resultado estándar del periodo: ingresos de lo entregado menos su costo congelado. Lo producido y aún en stock no se descuenta otra vez como vendido.
- La variante con generales reales reemplaza los generales asignados a lo vendido por los gastos generales registrados del periodo; no resta ambos. Es una aproximación de gestión, no una liquidación contable/tributaria ni una medición definitiva si faltan costos o gastos.
- Los importes se comparan tal como se registraron; no se añadió facturación electrónica ni una integración tributaria. Mantener un criterio consistente sobre importes con impuestos al ingresar precios y costos.
- Una compra directa registrada después de entregar no reescribe el costo histórico de esa entrega. El detalle del pedido advierte la diferencia; revisar periodos y conciliación antes de interpretar un cierre como definitivo.

**Actividad** conserva usuario, hora, acción y cambios. No hay edición habitual del historial. Los cambios de permisos de usuarios también se auditan sin exponer contraseñas.

## Dictado

Los campos de descripción y notas incluyen **Dictar**, texto editable y botón para detener. Nunca se guarda automáticamente. Se usa la API del navegador sin servicio contratado; puede requerir internet y permiso de micrófono.

En el acceso actual por IP **HTTP** el navegador puede impedirlo por contexto inseguro. Se comprobó el aviso y la escritura manual; el resto del sistema funciona. Para habilitar dictado en dispositivos compatibles haría falta HTTPS confiable en la red local y comprobar ese navegador. No se promete dictado offline ni se cambió la infraestructura para forzarlo.

## Migraciones, respaldo y comprobaciones

Las migraciones `2026_11_04_160000` a `160400` ya se aplicaron a la copia local con respaldo previo. Agregan tablas/campos, incorporan clientes y líneas del pedido anterior, y conservan versiones de cotizaciones; no borran tablas ni datos. Sus retrocesos se bloquean deliberadamente para evitar borrar historial: usar restauración verificada si hay que volver atrás.

En otra instalación, respaldar base, `storage/app` y clave antes de actualizar. Con PHP y el entorno correcto:

```powershell
& C:\wamp64\bin\php\php8.4.15\php.exe artisan migrate:status --env=production
& C:\wamp64\bin\php\php8.4.15\php.exe artisan migrate --env=production --force
npm.cmd ci
npm.cmd run build
```

La configuración privada de esta computadora es `.env.production`; el `.env` original se conserva. No regenerar `APP_KEY`, ejecutar seeders ni migraciones de borrado. Ver [INSTALACION_LOCAL.md](INSTALACION_LOCAL.md) y [ESTADO_LOCAL.md](ESTADO_LOCAL.md) para servicios y restauración.

Pruebas automatizadas:

```powershell
& C:\wamp64\bin\php\php8.4.15\php.exe vendor/phpunit/phpunit/phpunit
```

La suite usa la base SQLite **en memoria** definida en `phpunit.xml`. No apuntarla a la base operativa. Las pruebas de Chrome están en `tests/browser/workshop.spec.js`; las credenciales se suministran por entorno y no se guardan en el código. Sus recorridos sobre la copia local abren/cancelan formularios sin crear operaciones reales. El plugin Browser no estaba disponible; se utilizó Playwright.

Validación realizada: **53 pruebas Laravel y 319 aserciones correctas**, incluidos envíos de los formularios de venta, producción y compra con la misma cuenta de trabajador; **2 recorridos Chrome correctos** para administrador/trabajador, total automático, escritura manual, permisos y ancho móvil. Compilación de producción correcta. Dependencias JavaScript revisadas y corregidas con actualizaciones compatibles: `npm audit` informó cero vulnerabilidades. Se corrigió además la carga de bibliotecas PHP de Apache para los formatos numéricos de tablas y reportes.

Pendientes de validación en el local: segunda computadora real con dos usuarios, reserva DHCP, reinicio físico del equipo y comparación de datos con Railway. No se afirma haber validado esos puntos ni los objetivos de 30–60 segundos por venta. No se probó reconocimiento de voz real sobre HTTPS ni se implementó revalorización contable automática de periodos cerrados.
