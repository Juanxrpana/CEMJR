# CEMJR Blueprint

## System Overview

CEMJR es una aplicación PHP para la gestión de un centro de especialidades médicas. El flujo de entrada es `index.php`: carga Composer, helpers y `.env`, construye `App\config\Rutas` y despacha a un controlador. El controlador usa modelos para leer/escribir MySQL y renderiza una vista PHP o responde JSON/PDF.

Arquitectura observada:

- **Entrada:** `index.php` → `Rutas::gestionarRutas()` → `src/controllers/Controller*.php` → `src/modelos/*` → MySQL.
- **Vistas:** `src/vistas` son includes PHP; `head.php` y `footer.php` aportan layout, menú, CSRF y librerías JS.
- **Datos:** dos bases, `bd` (clínica/operación) y `segurity` (usuarios, roles, permisos, bitácora, rate limit).
- **Frontend:** JS sin bundler; módulos por vista, DataTables, Bootstrap, SweetAlert2, WebSocket y chat.
- **Servicios externos:** API de tasa de cambio (`ve.dolarapi.com`), SMTP (PHPMailer), FastAPI del chatbot y WebSocket Ratchet.

Flujo de negocio principal:

1. Se registra un **paciente** y, si aplica, un **cliente** pagador.
2. Se agenda una **cita** con servicio, doctor, fecha y hora; la cita pasa por reserva, pendiente y realizada.
3. La atención genera un **control** clínico con diagnóstico, historia, síntomas, patologías, indicaciones y nota.
4. Si hay internamiento, se registra una **hospitalización** con costos por hora, servicios e insumos.
5. El cierre genera una **factura** en bolívares con tasa de cambio, detalle de servicios/insumos/hospitalización y pagos.
6. El inventario se mueve por **entradas** de proveedor y lotes con vencimiento; la facturación descuenta lotes.
7. Usuarios, roles, permisos y **bitácora** controlan y registran el acceso.

Alcance de este bootstrap: `src/modelos`, `src/controllers` (sin Reportes, Estadísticas ni Recuperar Contraseña), `src/config` (sin `keys` ni `backups`), `src/vistas` (sin Reportes, Estadísticas, Recuperar Contraseña ni la carpeta de prueba de Hospitalización) y `src/assets/js` (sin `ayudaInteractiva` ni módulos de reportes/estadísticas/recuperación).

## Subdirectories & System Boundaries

- [Modelos](./src/modelos/EGM.md): capa de datos, reglas de negocio, transacciones, inventario, facturación, identidad y bitácora.
- [Controladores](./src/controllers/EGM.md): endpoints HTTP, validación, sesión, CSRF, IDs opacos, API móvil, chat y mantenimiento.
- [Configuración](./src/config/EGM.md): `.env`, router, permisos, sesión, rate limit, helpers, cifrado, correo, imágenes, PDF y tasa de cambio.
- [Vistas](./src/vistas/EGM.md): plantillas consolidadas, layout, menú, formularios, modales, DataTables y variables de controlador.
- [JavaScript](./src/assets/js/EGM.md): módulos de navegador, `executePetition`, permisos visuales, facturación, inventario, hospitalización, chat y WebSocket.

Fuera de alcance: `src/controllers/Reportes`, `ControllerEstadisticas.php`, `ControllerRecuperarContr.php`, `src/vistas/vistaReportes`, `vistaEstadisticas`, `vistaRecuperarContr`, `vistaHospitalizacion/prueba`, `src/config/keys`, `src/config/backups`, `src/assets/js/ayudaInteractiva`, `reportes.js`, `estadisticas.js`, `recuperarContr.js` y `reporteCitaYEntradasDeInsumos.js`.

## Global Invariants

- **Dos bases.** `bd` guarda la operación clínica/financiera; `segurity` guarda identidad, permisos y bitácora. `Db` y `ModelBase` eligen la conexión con `$dbSystem`.
- **SQL preparado.** Los modelos usan PDO con `prepare`/`bindValue`; las escrituras multi-tabla usan transacciones y, en varios flujos, `SELECT ... FOR UPDATE`.
- **IDs opacos.** Muchos controladores y respuestas JSON usan `hashId`/`unhashId`; los modelos reciben enteros. La API móvil usa IDs numéricos en la URL y no aplica Hashids. La sal está fija en `src/config/helpers.php`, así que el ID no es una autorización.
- **Sesión y permisos.** `Rutas` inicia la sesión, resuelve controlador/método y aplica el permiso de `equivalencias.php`. `ControllerInicio`, `ControllerPerfil`, `ControllerApi`, `ControllerBitacora` y `ControllerChat` están exceptuados y deben validar por su cuenta.
- **CSRF.** La mayoría de las acciones mutantes comparan `$_SESSION['csrf_token']` con `X-CSRF-Token` o `csrf_token`; la cobertura no es uniforme.
- **Estados.** Entidades y catálogos usan `estado` (`ACT`, `DES`, `BLOQUED`); las citas usan `Reservado`, `Pendiente`, `Realizadas` y `Expirado`; las facturas usan `ACT` y estados de anulación/reporte.
- **Facturación.** El servidor recalcula precios y total desde la base; `detalle_factura` y `pagodefactura` son la fuente del cobro. La tasa de cambio se guarda en `factura.tipo_cambio`.
- **Inventario.** `entrada` + `entrada_insumo` representan lotes; `DescontarLotes`, `insert_entrada` e `insert_insumo` son procedimientos usados por los modelos.
- **Vistas incluidas.** Las vistas no son endpoints autónomos; esperan variables del controlador y, muchas, una sesión activa.
- **Sin build JS.** Los módulos se cargan directamente; los imports relativos y los IDs del DOM son parte del contrato.

## Global Side Effects

- Conexiones y escrituras en `bd` y `segurity`; transacciones, bloqueos y procedimientos.
- Archivos: imágenes de pacientes/usuarios/doctores/insumos, PDFs, respaldos ZIP, checkpoints de binlog y logs.
- Correo SMTP para recuperación de contraseña.
- Llamadas HTTP a la API de tasa de cambio y al servicio FastAPI del chatbot.
- Procesos del sistema para WebSocket y respaldos/restauración.
- Cabeceras, cookies de sesión, redirecciones y respuestas JSON/HTML/PDF.
- `localStorage` del navegador para tema, tasa e historial del chat.

## System Verification

- Run: `vendor\bin\phpunit`
- Nota: la suite es de integración y usa MySQL; ejecutarla solo contra una base de pruebas aislada. La interfaz y los módulos JS se verifican manualmente en navegador.
