# Configuración, enrutamiento y utilidades

## Overview

`src/config` agrupa el arranque de configuración, el router, el control de permisos, la sesión, el limitador de peticiones, los helpers de IDs y precios, el cifrado del canal móvil, el correo, la validación de imágenes, el PDF de factura y la tasa de cambio.

El archivo se carga desde `index.php` (helpers y tasa) y desde el autoload de Composer (`App\config\...`). `Rutas` inicia la sesión, aplica el límite de peticiones, resuelve el controlador y el método, valida JWT móvil, comprueba permisos y ejecuta la función. `config.php` convierte variables de `.env` en constantes globales.

Quedan fuera de este contrato `src/config/keys` y `src/config/backups` por alcance solicitado.

## Interface

| Archivo / símbolo | Rol |
|---|---|
| `config.php` | Define `host_cos`, `user_cos`, `pass_cos`, `dbsegname_cos`, `dbname_cos`, `passwordResp_cos` y `TASA_IMPUESTO` a partir de `$_ENV`. |
| `Rutas` | `noigual`, `__construct`, `gestionarRutas`. Configura cookies de sesión, arranca sesión, crea `RateLimiter`, carga `equivalencias.php`, resuelve controlador/método/parámetros, valida JWT de rutas `*Apk`, aplica permisos y llama al método. |
| `equivalencias.php` | Devuelve un arreglo método → permiso (`consultar`, `guardar`, `editar`, `eliminar`). Es la tabla que usa el router para autorizar por nombre de método. |
| `helpers.php` | `hashId`, `unhashId`, `esHashIdValido`, `validarIdReal`, `numeroFacturaLegible`, `parsearPrecio`. Usa `Hashids` con una sal fija. |
| `Cifrado` | `cifrarRespuesta`, `descifrarPeticion`. Cifra con AES-256-CBC y RSA; lee `encrypt_public.key`/`private.key`. |
| `RateLimiter` | `evaluarLimiteDB`, `evaluar_rate_limit_by_user`, `checkRateLimitByIP`, getters/setters. Límites por IP y por sesión; escribe en `segurity.control_rate_limit`. |
| `Mailer` | `crear()`: configura PHPMailer con `MAIL_USER`, `MAIL_PASS`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION`, `MAIL_FROM`; acepta las variables antiguas `NameCorreoClinica`/`PasswordCorreoClinica`. |
| `ImagenUpload` | `validar`, `validarContenido`: máximo 5 MB, máximo 12 millones de píxeles, solo JPG/PNG, verifica MIME, `getimagesizefromstring` y `imagecreatefromstring`. |
| `FacturaPdf` | Clase que extiende FPDF y convierte UTF-8 a Windows-1252 en `Cell` y `MultiCell`. |
| `tasaCambio.php` | `tasaCambioActual`, `tasaDesdeApi`, `tasaDesdeApiConStream`, `tasaDesdeRespuesta`, `fechaTasaActual`. Consulta `https://ve.dolarapi.com/v1/dolares/oficial`, cachea en sesión y usa el valor previo si la API falla. |

## Invariants

- **Variables de entorno.** `config.php` asume que `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, `DB_NAME_SEGURITY` y `PASSWORD_RESP` existen en `$_ENV`. `index.php` carga `.env` con `Dotenv::createImmutable` antes de construir `Rutas`. Si falta una variable, las constantes quedan indefinidas y el fallo aparece al conectar o al definir la tasa.
- **Sesión.** `Rutas::__construct` llama a `session_set_cookie_params` con `secure` calculado desde HTTPS o `X-Forwarded-Proto`, `httponly=true`, `samesite=Strict`, `domain=$_SERVER['HTTP_HOST']` y `lifetime=0`; luego `session_start()` si no hay sesión activa. La cookie se emite antes de resolver el controlador.
- **Enrutamiento.** El router divide `$_GET['url']` por `/`; el primer segmento se convierte en `Controller<Nombre>` y el segundo en el método. Carga `src/controllers/Controller<Nombre>.php`, exige que la función exista y llama `$metodo($parametro)`. Si la URL contiene `.php`, redirige a login. `ControllerIniciarSesion` y `ControllerRecuperarContr` se ejecutan antes del bloque de permisos; `ControllerInicio`, `ControllerPerfil`, `ControllerApi`, `ControllerBitacora` y `ControllerChat` están exceptuados.
- **Límite de peticiones.** `RateLimiter` usa 60 peticiones por 10 segundos por IP y 20 por segundo por sesión. La IP se toma de `X-Forwarded-For` si existe; la tabla `control_rate_limit` es `MEMORY` y guarda `ip` y `creado_en`. Las URLs con extensión `css`, `js`, `png`, `jpg`, `jpeg`, `gif`, `svg` o `ico` omiten el límite por IP.
- **Permisos.** `Rutas` deriva el módulo del controlador (`Permisos` se trata como `Roles`), resuelve el ID de módulo con `ModeloPermisos::returnIdModule`, exige `id_rol` en sesión y llama `gestionarPermisos`. Si el método no está en `equivalencias.php`, el permiso es el nombre del método. Hay claves duplicadas y diferencias de mayúsculas; por ejemplo, `eliminarEspecialidad` está mapeado a `consultar`.
- **JWT móvil.** Si la petición trae `Authorization`, el router intenta decodificar un JWT HS256 con `JWT_SECRET` y solo permite rutas cuyo método termina en `Apk`. Copia `id_usuario` e `id_rol` del token a la sesión y llama al método. No valida permisos del módulo en esa rama. Si la ruta termina en `Apk` y no hay token, responde 401 JSON.
- **IDs y precios.** `hashId`/`unhashId` usan `Hashids` con sal fija y longitud mínima 10; no son cifrado ni autorización. `parsearPrecio` acepta formatos con punto/coma y devuelve `float`.
- **Cifrado móvil.** `Cifrado::cifrarRespuesta` genera una clave AES y un IV aleatorios, cifra el JSON con AES-256-CBC, cifra la clave AES con RSA (`openssl_public_encrypt`) y devuelve `data`, `key` e `iv` en base64. `descifrarPeticion` lee `private.key`, descifra la clave y el payload y devuelve el JSON decodificado. No hay MAC ni autenticación del ciphertext.
- **Correo.** `Mailer::crear` exige usuario y contraseña; sin ellos lanza excepción. Usa STARTTLS por defecto y `MAIL_PORT` 587; acepta `ssl`/`smtps`.
- **Imágenes.** `ImagenUpload::validar` exige `UPLOAD_ERR_OK`, `is_uploaded_file`, tamaño >0 y ≤5 MB; `validarContenido` exige extensión `jpg`, `jpeg` o `png`, MIME coincidente, `getimagesize` válido, ≤12 Mpx y decodificación con `imagecreatefromstring`.
- **PDF.** `FacturaPdf` no genera el PDF por sí mismo; solo adapta la codificación de texto para FPDF.
- **Tasa de cambio.** `tasaCambioActual` usa `$_SESSION['dolar']` si `dolar_fecha` es hoy; si no, consulta la API, guarda el valor en sesión y, si falla, usa el valor previo. `ControllerInicio::valorDolar` permite fijar la tasa en sesión desde una ruta pública.
- **Mapa de permisos.** `equivalencias.php` usa claves por nombre de método, no por controlador+método. Las claves repetidas se sobrescriben en silencio y las mayúsculas importan para el arreglo aunque PHP no distinga mayúsculas al llamar funciones.

## Side Effects

- Arranque de sesión y emisión de cookie.
- Conexión a MySQL y escritura/lectura en `control_rate_limit`.
- Cabeceras HTTP (`Cache-Control`, `Location`, `Content-Type`) y respuestas JSON de error.
- Lectura de `.env`, claves RSA y archivos de configuración.
- Llamada HTTP a la API de tasa de cambio y envío SMTP con PHPMailer.
- Lectura de claves RSA y archivos de imagen; la generación de PDF ocurre en los controladores.
- Cifrado/descifrado de payloads y generación de IDs.

## Verification

- Run: `vendor\bin\phpunit`
- Nota: la suite es de integración y requiere MySQL; usar una base de pruebas aislada. La verificación de las utilidades de imagen está cubierta por `tests/ImagenUploadTest.php`.
