# JavaScript del navegador

## Overview

`src/assets/js` contiene el comportamiento del navegador: módulos CRUD por entidad, DataTables, formularios, el asistente de chat, el WebSocket de citas, la facturación en dos monedas y utilidades de moneda, fechas, modales y permisos.

No hay bundler ni `package.json`. El `footer.php` carga scripts globales y cada vista carga su módulo con `<script type="module">`. Los módulos usan imports relativos y funciones exportadas; algunos archivos antiguos son scripts clásicos.

Este contrato es consolidado: cubre la raíz de `src/assets/js` y las subcarpetas `ajax`, `generic` y `hospitalizacion`. Quedan fuera `ayudaInteractiva`, `reportes.js`, `estadisticas.js`, `recuperarContr.js` y `reporteCitaYEntradasDeInsumos.js` por alcance solicitado.

## Interface

### Módulos de raíz

| Archivo | Rol |
|---|---|
| `f.js` | Facturación activa: carga paciente/cliente, servicios, insumos, métodos de pago, referencias, cálculo en bolívares/divisas, IVA, validación de pagos y confirmación. Es el módulo que cargan `factura.php`, `facturaCita.php` y `facturaHospitalizacion.php`. |
| `factura.js` | Variante antigua de facturación; las vistas actuales cargan `f.js`. Contiene búsquedas de paciente/cliente y pintado con `innerHTML`. |
| `insumo.js` | Inventario: lista, papelera, creación/edición, vencimientos, imágenes y búsqueda. |
| `mantenimiento.js` | Respaldos: descarga desde la nube, listado de ZIP, verificación de usuario y restauración. |
| `webSocket.js` | Conecta a `ws://localhost:8080`, pide citas próximas, pinta toasts y lista desplegable; reintenta cada 5 s. |
| `chatModal.js` | Chat del asistente: modal, historial en localStorage, envío a `/Chat/preguntar`, borrado de memoria en `/Chat/borrarMemoria`, render Markdown seguro. |
| `chatInterfaz.js` | Interfaz de conversaciones; espera `/Chat/chatConversacionesAjax`, `/Chat/chatMensajesAjax` y `/Chat/chatEnviarAjax`, que no existen en `ControllerChat`. |
| `dashboard.js` | Dashboard: calendario, agenda, servicios, gráficos y llamadas a `/Inicio/...`. |
| `doctorSchedule.js` | Utilidades de horarios de doctores. |
| `doctores.js` | Utilidades de la vista de doctores. |
| `admin.js` | Administración de contraseñas de usuario; importa `../ajax/funtionExecutePetition.js`, ruta que no existe, y hace `fetch` directo a `/Usuarios/verificarPassw`. |
| `imgAdmin.js` | Vista previa de imagen de usuario y mostrar/ocultar contraseña. |
| `imgDoctores.js` | Vista previa de imagen de doctor. |
| `validacionesDoctorEditar.js` | Validación de horarios al editar doctor. |

### `ajax/`

Módulos CRUD por entidad: `pacientes.js`, `clientes.js`, `citas.js`, `control.js`, `doctor.js`, `entradas.js`, `entradasVencidas.js`, `insumos` (en raíz), `patologia.js`, `perfil.js`, `permisos.js`, `proveedor.js`, `roles.js`, `servicios.js`, `usuarios.js`, `bitacora.js`, `blackListUser.js`, `categoria.js`, `inicioSesion.js`. Cada uno define funciones como `readX`, `createX`, `updateX`, `deleteX`, `asignarEventos` y llama endpoints del controlador correspondiente.

### `generic/`

| Archivo | Rol |
|---|---|
| `funtionGeneric.js` | Núcleo: `executePetition`, `hasPermision`, `initDataTable`, `showDataModal`, `alertConfirm`, `alertError`, `alertSuccess`, `sanitizeText`, `cargarImg`, `setImgWithFallback`, paginación y responsive. |
| `expresionesModulares.js` | Validación de formularios: `validarFormulario`, `validarCampo`, `validarFecha`, `validarSelect`, `chulitoYX`. |
| `moneda.js` | Formato y parseo de montos en Bs/USD, `montoDualHTML`, `formatUSD`, `formatBS`. |
| `coversion.js` | Tasa de cambio: `valorDolar`, `guardarTasaManual`, `tasaGuardada`, persistencia en localStorage y llamada a `/Inicio/valorDolar/`. |
| `chatMarkdown.js` | Escapa HTML y renderiza Markdown básico de forma segura. |
| `Paginator.js` | Paginador reutilizable. |
| `imagePreview.js` | Vista previa y fallback de imágenes. |

### `hospitalizacion/`

`hospitalizacion.js`, `hospitalizacionEditar.js`, `hospitalizacionesRealizadas.js`, `reutilizableHospitalizacion.js`. Cubren alta, edición, servicios, insumos, costos por hora y cierre/facturación de hospitalización.

## Invariants

- **Peticiones HTTP.** `generic/funtionGeneric.js:executePetition(url, method, data)` es el cliente canónico. Lee `meta[name="csrf-token"]`, añade `X-CSRF-Token` en `POST/PUT/PATCH/DELETE`, envía JSON o `FormData`, y devuelve `{ok, ...}` o `{ok:false, error}`. Si la respuesta no es JSON, intenta leer el error y no lanza.
- **Permisos visuales.** `hasPermision(id_rol, module, permision, selector)` llama `/Permisos/hasPermision/{id_rol}/{module}/{permision}` y oculta los botones si la respuesta no es verdadera. Es una ayuda visual; la autorización real está en el router.
- **DataTables.** `initDataTable(selector, urlControlador, columnas, ...)` configura `serverSide`, responsive y la columna de botones; espera el JSON de DataTables (`draw`, `recordsTotal`, `recordsFiltered`, `data`).
- **URLs.** La mayoría de los módulos usan rutas absolutas desde la raíz (`/Pacientes/...`, `/Factura/...`, `/Citas/...`). Las vistas usan `$urlBase` para recursos estáticos. `chatModal.js` fija `BASE=""`.
- **Facturación.** `f.js` trabaja con montos en centavos para evitar decimales, muestra equivalencia Bs/USD, aplica IVA, valida métodos de pago y referencias, y envía `tipo_cambio`, servicios, insumos, doctores, cantidades, precios, pagos y total. `factura.js` es una variante antigua; no se debe asumir que ambas están activas.
- **Chat.** `chatModal.js` guarda `cem_chat_session_id` y `cem_chat_historial` en localStorage, envía `{consulta, session_id}` a `/Chat/preguntar` y usa `mdRender`/`textoPlano` para escapar la salida. `chatInterfaz.js` espera endpoints de conversaciones no implementados en `ControllerChat`; es código legado o incompleto.
- **WebSocket.** `webSocket.js` usa `ws://localhost:8080` y `http://localhost:8080`; no envía token de sesión; reintenta con `setTimeout` cada 5 s. La CSP debe permitir ese origen.
- **Mantenimiento.** `mantenimiento.js` llama `bajarBdsNube` y `restaurarRespaldo` por GET y muestra los nombres de los ZIP; no distingue completo/diferencial/incremental más allá de quitar prefijos.
- **DOM.** Los módulos dependen de IDs y clases concretas de las vistas (`#formUC`, `#datosTable`, `#chatModal`, etc.). Cambiar un ID o un `name` sin actualizar el JS rompe el flujo.
- **Sin build.** Los imports son rutas relativas y los archivos se cargan directamente. Un import roto falla en el navegador; `admin.js` es un ejemplo actual.
- **Estado local.** `coversion.js` y `chatModal.js` usan localStorage para tasa, tema e historial; no hay caducidad ni cifrado.

## Side Effects

- `fetch` a endpoints PHP; descarga de PDFs y respaldos.
- Mutación del DOM con `innerHTML`, `insertAdjacentHTML`, `textContent` y creación de elementos.
- WebSocket y reconexión.
- `localStorage` para tema, tasa e historial del chat.
- SweetAlert2 para confirmaciones/errores.
- DataTables con paginación y ordenamiento en servidor.
- Descarga de archivos de respaldo y PDFs.

## Verification

- Run: `N/A` (no hay `package.json` ni runner de pruebas JS).
- Nota: la verificación es manual en navegador; `node --check` solo valida sintaxis de archivos individuales, no imports ni comportamiento.
