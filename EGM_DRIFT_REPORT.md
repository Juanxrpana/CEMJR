# Reporte de deriva de contratos EGM

**Fecha:** 2026-11-17  
**Alcance:** `EGM.md`, `src/modelos/EGM.md`, `src/controllers/EGM.md`, `src/config/EGM.md`, `src/vistas/EGM.md`, `src/assets/js/EGM.md`.  
**Método:** comparación estática contra el código, el esquema SQL y los archivos existentes. No se ejecutaron pruebas de integración, navegador ni base de datos.

## Resultado general

- **6 contratos revisados.**
- **3 afirmaciones incorrectas o demasiado amplias detectadas y corregidas.**
- **0 enlaces relativos rotos** en `EGM.md`; los cinco contratos enlazados existen.
- **0 archivos inventados** en las tablas de interfaces: todos los archivos y símbolos listados existen, salvo las rutas que el propio contrato marca como ausentes (los endpoints de `chatInterfaz.js` y el import de `admin.js`).
- **0 contradicciones de esquema** en las tablas y columnas citadas (`paciente`, `cliente`, `cita`, `control`, `hospitalizacion`, `factura`, `detalle_factura`, `pagodefactura`, `entrada`, `entrada_insumo`, `usuario`, `rol`, `permisos_de_rol`, `bitacora`, `control_rate_limit`).

## Correcciones aplicadas

### 1. `ModeloSanetizarJSON` y los ceros iniciales

**Antes:** el contrato decía que `sanitizeRecursive` convertía strings numéricos a número y que eso afectaba teléfonos/cédulas con ceros iniciales.

**Código real:** `src/modelos/ModeloSanetizarJSON.php:sanitizeRecursive` evalúa primero `is_string` y devuelve `htmlspecialchars`; solo los valores que ya son `int`/`float` pasan por `is_numeric($data)`. Un teléfono o cédula enviado como string **no se convierte** a número.

**Corrección:** se ajustó el invariante en `src/modelos/EGM.md` para decir que los strings conservan sus ceros y que el riesgo real es el doble escape si el valor ya viene escapado.

### 2. “Todos los controladores y el JSON usan Hashids”

**Antes:** el blueprint raíz afirmaba que los controladores y el JSON usan `hashId`/`unhashId`.

**Código real:** muchos lo hacen, pero `ControllerApi` recibe IDs numéricos en la URL (`apiDatosUsuario`, `apiControlesPaciente`, `apiPatologiasPaciente`) y no aplica Hashids. También hay respuestas internas que devuelven IDs numéricos.

**Corrección:** se cambió a “muchos controladores y respuestas JSON” y se aclaró que la API móvil usa IDs numéricos.

### 3. “Config escribe archivos de imagen y PDF”

**Antes:** la sección de efectos secundarios de `src/config/EGM.md` decía “lectura/escritura de archivos de imagen y PDF”.

**Código real:** `ImagenUpload` lee y valida archivos; `Cifrado` lee claves RSA; `FacturaPdf` solo adapta la codificación y no escribe PDFs. La generación de PDF ocurre en controladores/vistas.

**Corrección:** se separó “lectura de claves y archivos de imagen” de “generación de PDF en controladores”.

## Hechos de código confirmados (no son errores del contrato)

Estos puntos se verificaron y se dejaron documentados porque son comportamiento real, aunque parezcan anomalías:

1. **`chatInterfaz.js` llama endpoints inexistentes.** `src/assets/js/chatInterfaz.js` usa `/Chat/chatConversacionesAjax`, `/Chat/chatMensajesAjax` y `/Chat/chatEnviarAjax`; `src/controllers/ControllerChat.php` solo define `chat`, `validarSesionYCsrf`, `preguntar`, `enviarConsultaIA` y `borrarMemoria`. El chat activo es `chatModal.js` con `/Chat/preguntar`.
2. **`admin.js` importa un archivo inexistente.** `src/assets/js/admin.js` importa `../ajax/funtionExecutePetition.js`; ese archivo no existe. El módulo canónico es `generic/funtionGeneric.js`.
3. **`factura.js` es legado.** Las vistas `factura.php`, `facturaCita.php` y `facturaHospitalizacion.php` cargan `f.js`; `factura.js` no se carga en esas vistas.
4. **Restauración por GET.** `mantenimiento.js` llama `bajarBdsNube` y `restaurarRespaldo` por GET; el controlador recibe el nombre del ZIP por la URL.
5. **`ControllerApi` usa IDs numéricos y `X-Usuario-Id`.** No usa Hashids ni sesión web; la identidad se declara por header en algunos endpoints.
6. **`eliminarEspecialidad` está mapeado a `consultar`.** `src/config/equivalencias.php` tiene `"eliminarEspecialidad" => "consultar"`, aunque la función `ControllerDoctores::eliminarEspecialidad` elimina.
7. **`head.php` calcula el menú con permisos de consulta.** `src/vistas/head/head.php` llama `ModeloPermisos::modulosConPermiso('consultar')`; el router sigue siendo la autorización real.
8. **`webSocket.js` apunta a localhost.** `src/assets/js/webSocket.js` usa `ws://localhost:8080` y `http://localhost:8080`, sin token de sesión.
9. **`ModeloFactura` recalcula precios y total.** Ignora precios del navegador, reserva lotes y calcula el total desde `detalle_factura`.
10. **`ModeloControl` bloquea la fila del paciente.** Usa `SELECT ... FOR UPDATE` en `insertControl`; las citas, facturación, hospitalización e inventario usan transacciones.

## Verificaciones específicas

| Verificación | Resultado |
|---|---|
| Enlaces de `EGM.md` a los cinco contratos | Correctos; los seis archivos existen. |
| Nombres de archivos y funciones de controladores | Coinciden con `src/controllers`. |
| Clases y métodos públicos de modelos | Coinciden con `src/modelos`. |
| Tablas y columnas citadas | Coinciden con `database/bd.sql` y `database/segurity.sql`. |
| Funciones/archivos de configuración | Coinciden con `src/config`; se excluyeron `keys` y `backups`. |
| Archivos de vistas listados | Coinciden con `src/vistas`; se excluyeron Reportes, Estadísticas, Recuperar Contraseña y `prueba`. |
| Módulos JS listados | Coinciden con `src/assets/js`; se excluyó `ayudaInteractiva` y los módulos de reportes/estadísticas/recuperación. |
| CSRF no uniforme | Confirmado: `enviarAFacturar`, `verificacionU`, `bajarBdsNube`, `generarRespaldo` y `restaurarRespaldo` no usan el patrón estándar. |
| Dos bases | Confirmado: `Db::connectionSistema` y `Db::connectionSegurity`. |
| Rate limit por IP/sesión | Confirmado: 60/10 s y 20/1 s; tabla `MEMORY`; extensión de archivo omite el límite. |
| JWT móvil sin permiso de módulo | Confirmado: `Rutas` copia `id_usuario`/`id_rol` y llama al método antes del bloque de permisos. |
| `DEFINER=root` y procedimientos | Confirmado en `database/bd.sql`; el contrato de modelos lo menciona como parte de mantenimiento, no como invariante de negocio. |

## Pendiente de verificación dinámica

No se ejecutó:

- `vendor\bin\phpunit` (requiere MySQL y una base aislada).
- Navegador real para las vistas y módulos JS.
- WebSocket/Ratchet.
- Flujo FastAPI del chatbot.
- Restauración real de un ZIP.

Por tanto, este reporte valida **contratos contra código y esquema**, no el comportamiento en ejecución. Las afirmaciones sobre transacciones, CSRF, sesión y rutas se basan en lectura estática y en las líneas citadas.

## Conclusión

Los contratos describen el sistema real y no el modelo ideal. Las tres desviaciones encontradas eran de redacción/alcance, no de arquitectura. Las anomalías restantes son hallazgos reales del código que conviene tratar por separado, no corregir dentro de este bootstrap documental.
