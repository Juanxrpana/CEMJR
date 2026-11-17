# Vistas y plantillas

## Overview

`src/vistas` contiene las plantillas PHP que renderizan la interfaz. No son páginas autónomas: los controladores las incluyen con `require_once` después de cargar datos, sesión y permisos. La mayoría empieza con:

```php
require_once './src/vistas/head/head.php';
```

y termina con:

```php
require_once './src/vistas/head/footer.php';
```

`head.php` verifica la sesión, calcula `$urlBase` y construye el menú según los módulos autorizados. `footer.php` carga Bootstrap, DataTables, SweetAlert2, los módulos genéricos, `webSocket.js` y `chatModal.js`.

Este contrato es consolidado: cubre `src/vistas` y sus subcarpetas de primer nivel, sin crear un `EGM.md` por subcarpeta. Quedan fuera `vistaReportes`, `vistaEstadisticas`, `vistaRecuperarContr` y `vistaHospitalizacion/prueba` por alcance solicitado.

## Interface

### Archivos compartidos y raíz

| Archivo | Rol |
|---|---|
| `head/head.php` | Verifica `$_SESSION['usuario']`, calcula `$urlBase` desde `$parametro`, carga permisos del menú con `ModeloPermisos::modulosConPermiso('consultar')`, imprime `<head>`, estilos, logo, menú lateral y barra superior. Expone `csrf-token` y `iva-tasa` como metaetiquetas. |
| `head/footer.php` | Cierra el layout y carga Bootstrap, DataTables, extensión responsive, `app.js`, `expresionesModulares.js`, SweetAlert2, `webSocket.js` y `chatModal.js`; define `openNav`/`closeNav`. |
| `dashboard.php` | Vista de inicio; espera `$puedeVerEstadisticas`, `$puedeConsultarCitas`, `$validarCargo`, `$puedeMostrarCalendario`, `$idPersonalCalendario`, `$horarioSemanal` y `$agendaPersonal` desde `ControllerInicio::inicio`. |
| `btnOpciones.php` | Fragmento de botones de acción reutilizable. |
| `tasaBCV.php` | Fragmento/indicador de tasa de cambio. |
| `modalCierreS/modalCierreS.php` | Modal de cierre de sesión. |

### Subcarpetas de módulos

| Subcarpeta | Archivos principales y función |
|---|---|
| `vistaIniciarSesion` | `iniciarSesion.php` (formulario de login), `vistaError.php` (error de acceso). |
| `vistaInicio` | `vistaInicio.php` (contenido de inicio/dashboard). |
| `vistaPacientes` | `pacientes.php`, `pacientesPapelera.php`, `tablaPacientes.php`, `tablaHistorialSalud.php`, `modalAgregarPaciente.php`, `modalEditarPaciente.php`. |
| `vistaCliente` | `vistaCliente.php`, `modalAgregarCliente.php`, `modalEditarCliente.php`. |
| `vistasCitas` | `vistaCitas.php`, `modalesCitas-Control.php`. |
| `vistaControl` | `vistaControl.php`, `modalAgregarControl.php`, `modalesSintomas.php`. |
| `vistaHospitalizacion` | `hospitalizacion.php`, `hospitalizacionesRealizadas.php`, `modalHopitalizacion.php`, `modal/modalAgregarHospitalizacion.php`, `modal/modalAgregarInsumos.php`, `modal/modalEditarHospitalizacion.php`, `modal/modalEditarInsumos.php`, `modal/modalEliminarHospitalizacion.php`, `modal/modalPrecioHora.php`. |
| `vistaInsumos` | `vistaInsumos.php`, `vistaInsumosVencidos.php`, `insumosPapelera.php`, `modalInsumos.php`, `paginationInsumos.php`. |
| `vistaEntrada` | `vistaEntrada.php`, `vistaEntradaDesactiva.php`, `modalEntrada.php`. |
| `vistaProveedores` | `vistaProveedores.php`, `vistaProveedoresPapelera.php`, `modalProveedores.php`. |
| `vistaFactura` | `factura.php`, `facturaCita.php`, `facturaHospitalizacion.php`, `modalAgregarFactura.php`, `comprobante.php`, `comprobanteHospitalizacion.php`, `vistaFacturaPdf.php`, `vistaFacturaPdf2.php`, `vistaFacturaPdf3.php`. |
| `vistaDoctores` | `vistaDoctores.php`, `modal/modalAgregarDoctores.php`, `modal/modalEditarDoctores.php`, `modal/modalesEspecialidades.php`, `modal/infoModalDoctores.php`. |
| `vistaServicios` | `vistaServiciosMedicos.php`, `modalesCategoria.php`. |
| `vistaPatologia` | `patologia.php`, `modalesPatologia.php`. |
| `vistaUsuarios` | `vistaUsuarios.php`, `vistaUsuariosAdmin.php`, `vistaListaNegra.php`, `modal/modalAgregarAdmin.php`, `modal/modalEditarAdmin.php`, `modal/modalEditarPasswordAdmin.php`, `modal/modalEditarUsuario.php`, `modal/modalMostrarAdmin.php`, `modal/modalMostrarUsuario.php`, `modal/modalAddUserBlackList.php`. |
| `vistaRoles` | `vistaRoles.php`, `listaDePermisos.php`, `modalesRol.php`, `modal/modalMostrarPermisos.php`, `modal/listaModulos.php`. |
| `vistaPerfil` | `vistaPerfil.php`, `modalPerfil.php`. |
| `vistaBitacora` | `bitacora.php`. |
| `vistaChat` | `chat.php`, `modalChat.php`. |
| `vistaMantenimiento` | `mantenimiento.php`. |

## Invariants

- **Vistas incluidas, no autónomas.** La vista espera variables preparadas por el controlador. Si se accede directo por URL, puede fallar por variables indefinidas, por `$_SESSION` vacía o por `$this->modelo` en las vistas PDF. No se debe asumir que una vista es un endpoint.
- **Sesión.** `head.php` redirige a `/IniciarSesion/mostrarIniciarSesion` si no existe `$_SESSION['usuario']`. También lee `$_SESSION['id_usuario']`, `id_rol`, `nombre`, `apellido` y `csrf_token`.
- **Variables de layout.** Los controladores pasan `$vistaActiva`, `$ayuda`, `$parametro` y, según el módulo, `$datosU`, `$datosRoles`, `$datosEspecialidades`, `$puedeVerEstadisticas`, etc. `head.php` calcula `$urlBase` repitiendo `../` por cada parámetro de ruta.
- **Menú por permisos.** El menú se arma con `ModeloPermisos::modulosConPermiso('consultar')` y `$modulosAutorizados`. Si el controlador ya pasó `$modulosAutorizados`, no se vuelve a consultar. El menú oculta módulos, pero la autorización real está en el router.
- **CSRF.** `head.php` publica `csrf-token` en una metaetiqueta. Muchos modales incluyen un `input hidden` con `csrf_token`; otros formularios envían el token por `X-CSRF-Token` desde `executePetition`.
- **Formularios.** Los `name` de los campos se corresponden con las claves que leen los controladores (`$_POST['nombre']`, `$_POST['cedula']`, `$_POST['id_usuario']`, etc.). Cambiar un `name` sin cambiar el controlador rompe el flujo.
- **DataTables.** Las tablas suelen usar DataTables con `serverSide`; los endpoints devuelven `draw`, `recordsTotal`, `recordsFiltered` y `data`. Los botones de acción se generan en la columna final y se controlan con `hasPermision`.
- **Módulos JS.** Cada vista carga su módulo (`pacientes.js`, `citas.js`, `control.js`, `f.js`, `hospitalizacion.js`, etc.) y, a veces, un archivo de ayuda interactiva. `footer.php` carga los módulos globales; las vistas cargan los específicos.
- **Codificación de salida.** Algunas vistas usan `htmlspecialchars` y otras imprimen `<?= $variable ?>` directamente. No se debe asumir que toda salida está escapada; al añadir datos nuevos hay que escapar en la vista.
- **PDFs.** `vistaFactura/vistaFacturaPdf*.php` usan `$this->modelo` y se incluyen desde el controlador; no son accesibles directamente. `FacturaPdf` adapta la codificación a Windows-1252.
- **Chat.** `vistaChat/chat.php` y `modalChat.php` cargan la interfaz del asistente; `chatModal.js` usa `/Chat/preguntar` y `/Chat/borrarMemoria`. `chatInterfaz.js` espera endpoints de conversaciones que no están definidos en `ControllerChat`.

## Side Effects

- Salida HTML y carga de CSS/JS.
- Consulta a `ModeloPermisos` para el menú.
- Lectura de sesión y redirección a login.
- Formularios que envían POST a los controladores; apertura de modales y DataTables.
- Carga de imágenes y PDFs.
- Ejecución de JS que hace fetch, WebSocket, localStorage (tema, tasa, historial del chat) y manipulación del DOM.

## Verification

- Run: `N/A` (no hay runner de vistas; la verificación es render manual en navegador y `php -l` sobre los archivos).
- Nota: las pruebas de integración de controladores cubren algunos flujos, pero no sustituyen la revisión visual.
