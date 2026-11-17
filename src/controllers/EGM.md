# Controladores HTTP

## Overview

`src/controllers` es la capa HTTP. Cada archivo define funciones globales (no clases, salvo `r_factura.php`) que el router `App\config\Rutas` carga y ejecuta según la URL. El controlador valida la petición, usa uno o más modelos y responde con HTML, JSON o un archivo.

El router construye el nombre `Controller<Segmento>` a partir de la URL, carga `src/controllers/Controller<Segmento>.php`, comprueba que la función exista, aplica sesión/permiso y llama a la función con los segmentos restantes como parámetros. Los controladores `ControllerIniciarSesion` y `ControllerRecuperarContr` se ejecutan antes del control de permisos; `ControllerInicio`, `ControllerPerfil`, `ControllerApi`, `ControllerBitacora` y `ControllerChat` están exceptuados del bloque de permisos y deben validar por su cuenta.

Quedan fuera de este contrato `ControllerReportes`, `ControllerEstadisticas` y `ControllerRecuperarContr` por alcance solicitado. Los archivos siguen existiendo.

## Interface

| Área | Controlador y funciones principales |
|---|---|
| Sesión y perfil | `ControllerIniciarSesion` (`mostrarIniciarSesion`, `iniciarSesion`, `iniciarSesionMovilApk`, `error`, utilidades WebSocket), `ControllerPerfil` (`perfil`, `perfilAjax`, `guardar`, `perfilApk`, `editarPerfilApk`). |
| Pacientes y clientes | `ControllerPacientes` (`getPacientes`, `getPacientesAjax`, `getHistorialSalud`, `getHistorialSaludAjax`, `papeleraPaciente`, `papeleraPacienteAjax`, `guardar`, `setPaciente`, `eliminar`), `ControllerClientes` (`Clientes`, `clientesAjax`, `papelera`, `papeleraAjax`, `guardar`, `setCliente`, `eliminar`). |
| Citas y control | `ControllerCitas` (`citas`, `citasAjax`, `citasHoy`, `citasHoyAjax`, `citasP`, `apartarCupo`, `guardarCita`, `eliminarCita`, `citasRealizadas`, `citasRealizadasAjax`, `mostrarDoctoresCita`, `mostrarHorario`, `editarCita`, `citasHoyCompletasApk`), `ControllerControl` (`control`, `returnSistomasPaciente`, `returnPatologiasPaciente`, `returnPatologiasPacienteId`, `returnDoctores`, `listPacientesJS`, `mostrarBusquedaPacientesJS`, `mostrarControlPacientesJS`, `mostrarPacienteJS`, `insertarControl`, `editarControl`, `mostrarSP`, `mostrarPP`, `mostrarSPAll`, `mostrarPPAll`, `mostrarPIdP`, `eliminarSintoma`, `agregarSintoma`). |
| Catálogo médico | `ControllerDoctores` (`doctores`, `mostrarDiasSemana`, `selectEspcAjax`, `DoctoresAjax`, `papelera`, `papeleraDoctoresAjax`, `serviciosDoctor`, `asignarServicioDoctor`, `agregarDoctor`, `editarDoctor`, `borrarDoctor`, `registrarEspecialidad`, `eliminarEspecialidad`, `buscarHorario`), `ControllerServicios` (`servicios`, `papeleraServicio`, `datosServiciosPapelera`, `datosServicios`, `categoriasAjax`, `serviciosAjax`, `papeleraAjax`, `guardar`, `eliminar`, `editar`, `mostrarEspecialidad`, `registrarCategoria`, `eliminarCategoria`), `ControllerPatologias` (`patologias`, `patologiasAjax`, `papeleraPatologias`, `papeleraAjax`, `registrarPatologia`, `eliminarPatologia`). |
| Hospitalización | `ControllerHospitalizacion` (`hospitalizacion`, `hospitalizacionesRealizadas`, `traerHospP`, `traerHospR`, `selectServiciosD`, `serviciosDH`, `selectInsumos`, `validarPaciente`, `mostrarInformacionPCD`, `mostrarInsumos`, `mostrarUnInsumo`, `agregarH`, `traerInsuDHEd`, `modificarH`, `eliminaL`, `buscarIExH`, `enviarAFacturar`, `hospitalizacionApk`). |
| Inventario | `ControllerInsumos` (`insumos`, `insumosAjax`, `InsumosVencidos`, `vencidos`, `retornarLasEntradas`, `info`, `mostrarBusquedaInsumo`, `guardarInsumo`, `eliminar`, `editar`, `papelera`, `papeleraInsumosAjax`), `ControllerEntrada` (`entrada`, `entradasAjax`, `papelera`, `entradasPapeleraAjax`, `proveedoresEditar`, `guardar`, `eliminar`, `editar`, `entradaInsumo`), `ControllerProveedores` (`proveedores`, `proveedoresAjax`, `papelera`, `proveedoresPapeleraAjax`, `insertar`, `update`, `editar`). |
| Facturación | `ControllerFactura` (`factura`, `mostrarServicios`, `mostrarInsumos`, `mostrarMetodosDePago`, `facturaCita`, `facturarHospitalizacion`, `datosHospitalizacion`, `comprobante`, `mostrarPaciente`, `mostrarCliente`, `mostrarPacienteConCita`, `registrarInsumosHospitalizacion`, `guardarFactura`, `mostrarPDF`, `mostrarPDF2`, `mostrarPDF3`) y utilidades `facturaTokenCsrf`, `facturaValidarCsrf`, `facturaIdDesdeUrl`, `facturaPrepararIdempotencia`, `facturaReservarIdempotencia`, `facturaLiberarIdempotencia`, `facturaConsumirIdempotencia`. |
| Usuarios y permisos | `ControllerUsuarios` (`usuarios`, `usuariosAjax`, `administradores`, `administradoresAjax`, `listaNegra`, `listaNegraAjax`, `listaUserAjax`, `addUserBlackList`, `removeBlackList`, `editarUsuario`, `borrarUsuario`, `registrarAdmin`, `editarAdministrador`, `verificarPassw`), `ControllerRoles` (`mostrar`, `mostrarAjax`, `mostrarPermisos`, `cargarPermisosGuardados`, `returnPermisos`, `guardarRol`, `modificarRol`, `eliminarRol`), `ControllerPermisos` (`validarCsrfPermisos`, `returnModules`, `returnPermisionModule`, `hasPermision`, `registrarModulo`, `eliminar_modulo`). |
| Bitácora | `ControllerBitacora` (`bitacoraUsuario`, `bitacora`, `bitacoraAjaxUser`, `bitacoraAjaxAdmin`). |
| Dashboard | `ControllerInicio` (`inicio`, `valorDolar`, `manualUsuario`, `servicios`, `citasDeHoy`, `cerrarSession`, `citas`, `pacientes_hospitalizados`, `especialidades_solicitadas`, `especialidades_solicitadas_filtradas`, `todas_las_especialidades`, `sintomas_comunes`, `sintomas_comunes_filtrados`, `todos_los_sintomas`, `mostrarHorario`, `retornarDoctores`, `exportar_pdf`, `diasConMasCitas`, `especialidadesApk`, `sintomasApk`). |
| API y chat | `ControllerApi` (`validarApiKeyCompartida`, `apiEspecialidades`, `apiDoctoresPorEspecialidad`, `apiDatosUsuario`, `validarPersonalDelCentroApi`, `apiBuscarPaciente`, `apiControlesPaciente`, `apiPatologiasPaciente`), `ControllerChat` (`chat`, `validarSesionYCsrf`, `preguntar`, `enviarConsultaIA`, `borrarMemoria`). |
| Mantenimiento | `ControllerMantenimiento` (`mantenimiento`, `bajarBdsNube`, `consultarBd`, `generarRespaldo`, `restaurarRespaldo`, `verificacionU`). |
| Legado PDF | `r_factura.php`: clase `PDF` con `Header`; genera una factura de ejemplo con FPDF. No es un controlador enrutado. |

## Invariants

- **Enrutamiento por función global.** El router carga el archivo y llama a `$metodo($parametro)`; las funciones deben ser globales y tener el nombre exacto que usa la URL. Los controladores no declaran clases, salvo el PDF legado.
- **Permisos por nombre de método.** `src/config/equivalencias.php` traduce el método a `consultar`, `guardar`, `editar` o `eliminar`; el router usa el módulo derivado del controlador. Si el método no está en el mapa, se usa el nombre del método como permiso. Hay claves duplicadas y diferencias de mayúsculas en ese archivo.
- **Controladores exceptuados.** `ControllerInicio`, `ControllerPerfil`, `ControllerApi`, `ControllerBitacora` y `ControllerChat` no pasan por el bloque de permisos del router. Cada uno debe aplicar sus propias comprobaciones; no todas las funciones lo hacen.
- **Sesión.** Los controladores leen `$_SESSION['id_usuario']`, `id_rol`, `id_personal`, `usuario`, `nombre`, `apellido`, `csrf_token` y `token_de_session`. `ControllerIniciarSesion` regenera el ID de sesión al autenticar; `ControllerInicio::cerrarSession` destruye la sesión.
- **CSRF.** La mayoría de las acciones que cambian datos comparan `$_SESSION['csrf_token']` con `X-CSRF-Token` o `csrf_token` usando `hash_equals`. No es uniforme: `ControllerHospitalizacion::enviarAFacturar`, `ControllerMantenimiento::verificacionU`, `bajarBdsNube`, `generarRespaldo` y `restaurarRespaldo` no usan el patrón estándar; `ControllerFactura` usa `facturaValidarCsrf`.
- **IDs opacos.** Los formularios y URLs envían IDs hasheados; los controladores llaman a `unhashId()` antes de pasarlos al modelo. `unhashId()` lanza `InvalidArgumentException` si el hash no es válido. `esHashIdValido()` se usa en algunos flujos para validar sin lanzar.
- **Respuestas.** Los endpoints AJAX responden JSON con formas variables: `['ok'=>bool, 'message'=>string]`, `['ok'=>bool, 'error'=>string, 'data'=>...]`, arreglos crudos o el formato de DataTables (`draw`, `recordsTotal`, `recordsFiltered`, `data`). Algunos endpoints devuelven HTML o un PDF.
- **Archivos.** `ControllerPerfil`, `ControllerInsumos`, `ControllerUsuarios` y `ControllerDoctores` validan imágenes con `ImagenUpload::validar` antes de moverlas. `ControllerInicio::exportar_pdf` escribe un base64 en un PNG temporal sin pasar por `ImagenUpload`.
- **Facturación.** `ControllerFactura::guardarFactura` exige POST, valida CSRF, usa un token de idempotencia y delega el cálculo y la transacción en `ModeloFactura`. `registrarInsumosHospitalizacion` también valida CSRF. `enviarAFacturar` de hospitalización no valida CSRF y envía totales del POST.
- **Citas.** `ControllerCitas::apartarCupo`, `guardarCita`, `editarCita` y `eliminarCita` validan CSRF y usan hashes para paciente, servicio, doctor y cita.
- **Usuarios.** `ControllerUsuarios::verificarPassw` valida usuario/contraseña contra `ModeloInicioSesion` y actualiza la contraseña de ese usuario; no exige que sea el usuario de la sesión. `registrarAdmin` y `agregarDoctor` aceptan `id_rol` del POST y lo deshashean.
- **API móvil.** `ControllerApi` exige la API key `API_KEY_IA` y, en algunos endpoints, `X-Usuario-Id`; no usa sesión web. `ControllerApi::apiDatosUsuario` recibe el id de usuario por la URL. `ControllerApi` es de solo lectura.
- **Chat.** `ControllerChat` exige sesión y CSRF; envía la consulta al FastAPI configurado en `IA_API_URL` con `X-API-Key` y datos de usuario. `chatInterfaz.js` espera endpoints `/Chat/chatConversacionesAjax`, `/Chat/chatMensajesAjax` y `/Chat/chatEnviarAjax` que no existen en `ControllerChat`; `chatModal.js` usa `/Chat/preguntar` y `/Chat/borrarMemoria`, que sí existen.
- **Mantenimiento.** `ControllerMantenimiento` ejecuta respaldos/restauración y comandos del sistema; `verificacionU` activa `$_SESSION['validarPBD']` con credenciales de Superadmin. `bajarBdsNube` descarga desde rclone; `restaurarRespaldo` recibe el nombre del ZIP por la URL.
- **Bitácora.** Varios controladores insertan en `ModeloBitacora` después de una acción; el registro no es transaccional con la acción de negocio y algunos flujos no lo escriben.

## Side Effects

- Consultas y escrituras en `bd` y `segurity` a través de modelos.
- Inserción de bitácora; envío de correo con PHPMailer en recuperación de contraseña (controlador fuera de alcance, pero referenciado por login).
- Subida, movimiento y borrado de archivos de imagen.
- Escritura de PDFs y archivos temporales (`exportar_pdf`, comprobantes).
- Llamadas HTTP al servicio FastAPI del chatbot.
- Ejecución de procesos para WebSocket (`ControllerIniciarSesion`) y respaldos/restauración (`ControllerMantenimiento`).
- Cabeceras HTTP, `session_start`/`session_destroy`, redirecciones y respuestas JSON/HTML/PDF.
- Lectura de `$_GET`, `$_POST`, `$_FILES`, `php://input` y variables de sesión.

## Verification

- Run: `vendor\bin\phpunit`
- Nota: la suite es de integración y requiere MySQL; usar una base de pruebas aislada. Para endpoints HTTP, la verificación es manual o con las pruebas de caja negra existentes.
