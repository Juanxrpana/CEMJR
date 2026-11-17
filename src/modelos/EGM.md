# Modelos y reglas de negocio

## Overview

`src/modelos` es la capa de datos y reglas de negocio del sistema. No recibe HTTP ni renderiza HTML: los controladores crean instancias, cargan setters y llaman a métodos que consultan o escriben en MySQL mediante PDO.

El sistema usa dos bases:

- `bd`: datos clínicos y operativos (`paciente`, `cita`, `control`, `hospitalizacion`, `factura`, `insumo`, `entrada`, `proveedor`, `personal`, etc.).
- `segurity`: identidad y control (`usuario`, `rol`, `permisos`, `modulos`, `permisos_de_rol`, `bitacora`, `control_rate_limit`).

El modelo de negocio que reflejan el esquema y los modelos es:

| Entidad | Rol en el negocio | Evidencia |
|---|---|---|
| `paciente` | Persona atendida: nacionalidad, cédula, nombre, teléfono, dirección, fecha de nacimiento, género, estado y estado de salud. | `database/bd.sql`, tabla `paciente`; `src/modelos/ModeloPacientes.php` |
| `cliente` | Pagador de la factura. No es necesariamente el paciente; se relaciona por cédula en la facturación. | `database/bd.sql`, tabla `cliente`; `ModeloCliente`, `ModeloFactura::coincidenciaPacienteCliente` |
| `personal` | Miembro del personal (médico o administrativo), vinculado a `usuario` y, si aplica, a `especialidad`. | tabla `personal`; `ModeloDoctores`, `ModeloInicio` |
| `cita` | Reserva de un servicio médico con un paciente, un doctor, fecha, hora y estado. | tabla `cita`; `ModeloCita::reservar`, `guardarCita`, `editarCita` |
| `control` | Encuentro clínico: diagnóstico, historia clínica, indicaciones, nota, severidad, fecha de regreso, síntomas y patologías. | tabla `control`; `ModeloControl::insertControl`, `editarControl` |
| `hospitalizacion` | Internamiento con costo por hora en bolívares y divisas, servicios, insumos y totales; termina en facturación. | tabla `hospitalizacion`; `ModeloHospitalizacion::insertarH`, `facturarH` |
| `insumo` / `entrada` / `entrada_insumo` | Inventario por lote: entrada de proveedor, vencimiento, cantidad disponible, precio, IVA y stock mínimo. | tablas `insumo`, `entrada`, `entrada_insumo`; `ModeloInsumo`, `ModeloEntrada` |
| `factura` / `detalle_factura` / `pagodefactura` | Factura en bolívares con tasa de cambio, líneas de servicios/insumos/hospitalización y pagos con método y referencia. | tablas `factura`, `detalle_factura`, `pagodefactura`, `pago`; `ModeloFactura` |
| `serviciomedico` / `categoria_servicio` / `especialidad` | Catálogo de servicios, categorías y especialidades; relación médico-servicio. | tablas `serviciomedico`, `categoria_servicio`, `especialidad`; `ModeloServicios`, `ModeloDoctores` |
| `usuario` / `rol` / `permisos` | Identidad de acceso, rol y permisos por módulo. | `segurity.sql`; `ModeloUsuarios`, `ModeloRoles`, `ModeloPermisos` |
| `bitacora` | Registro de actividad por usuario, tabla, texto y fecha. | `segurity.sql`; `ModeloBitacora` |

No se describen en este contrato los modelos de Reportes, Estadísticas y Recuperación de Contraseña porque quedaron fuera del alcance solicitado. Los archivos siguen existiendo en el directorio.

## Interface

### Núcleo de acceso a datos

- `Db`: `connectionSistema()` abre PDO contra `DB_NAME`; `connectionSegurity()` contra `DB_NAME_SEGURITY`. Ambos usan las constantes de `src/config/config.php`.
- `ModelBase`: base de todos los modelos. Expone `storedProcedure`, `create`, `read`, `update`, `delete`, `update_logic`, `search`, `query`, `beginTransaction`, `commit`, `rollBack`, `getSQL`, `setSQL` y `getPDO`.
- `ModelBase::__construct($dbSystem)`: con `true` usa la base principal `bd`; con `false` usa `segurity`. Varios modelos invierten o fijan ese valor según su dominio.

### Modelos por dominio

| Dominio | Modelos y símbolos principales |
|---|---|
| Identidad y acceso | `ModeloUsuarios` (`buscar_por_id`, `select`, `selectAdmin`, `addUserBlackList`, `removeUserBlackList`, `agregarUsuario`, `editarUsuario`, `eliminarUsuario`, `actualizarTokenInicioSesion`), `ModeloRoles` (`roles`, `guardarRol`, `editarRol`, `eliminarRol`, `mostrarPermisos`), `ModeloPermisos` (`gestionarPermisos`, `returnIdModule`, `modulosConPermiso`, `registrarModulo`, `delete_modulo`), `ModeloInicioSesion` (`validarUsuarioExistente`, `validarIniciarSesion`, `verificacionUsuarioToken`), `ModeloPerfil` (`seleccionarUsuario`, `update_perfil`, `seleccionarUsuarioApk`, `update_perfil_apk`). |
| Pacientes y citas | `ModeloPacientes` (`index`, `indexHistorial`, `indexPapelera`, `guardarPaciente`, `editarPaciente`, `eliminarPaciente`), `ModeloCliente` (`index`, `indexPapelera`, `guardarCliente`, `editarCliente`, `eliminarCliente`), `ModeloCita` (`mostrarCita`, `mostrarCitaHoy`, `mostrarCitaR`, `reservar`, `guardarCita`, `editarCita`, `eliminarCitaPublic`, `mostrarHorarioDoctores`), `ModeloControl` (`insertControl`, `editarControl`, `mostrarControlPacienteA`, `mostrarControlPacienteU`, `mostrarSintomasPaId`, `mostrarPatologiaP`). |
| Catálogo clínico | `ModeloDoctores` (`select`, `selectEspecialidad`, `selectTodasEspecialidades`, `registrarAdmin`, `insertarDoctor`, `updateDoctor`, `eliminacionLogica`, `EspecialidadRegistrar`, `EspecialidadEliminar`), `ModeloPatologia` (`mostrarPatologias`, `guardarPatologia`, `deletePatologia`, `buscarPatologiaPaciente`), `ModeloSintomas` (`selects`, `selectSintomas`, `insertar`, `eliminarL`), `ModeloServicios` (`mostrarServicios`, `guardarServicio`, `editarServicio`, `eliminarServicio`, `asignarServicioDoctor`), `ModeloCategoria` (`seleccionarCategoria`, `seleccionarTodasLasCategoria`, `guardarCategoria`, `eliminarCategoria`). |
| Hospitalización e inventario | `ModeloHospitalizacion` (`insertarH`, `editarH`, `facturarH`, `selectsH`, `selectsHR`, `EInsumosM`, `buscarInsumos`, `eliminaLogico`), `ModeloFacturaHospitalizacion` (`registrarConsumo`), `ModeloInsumo` (`insumos`, `InsumosVencidos`, `guardarInsumo`, `editarInsumo`, `eliminarInsumo`, `vencerInsumos`, `validarImagen`), `ModeloEntrada` (`todasLasEntradas`, `guardarEntrada`, `updateEntrda`, `eliminarEntrada`, `restablecerEntrada`), `ModeloProveedores` (`consultar`, `guardarEntrada`, `deleteEntrada`, `editarEntrada`). |
| Facturación | `ModeloFactura` (`guardarFactura`, `buscar`, `buscarCliente`, `buscarPacientePorCita`, `consultarFactura`, `consultarPagoFactura`, `consultarFacturaInsumo`, `reservarLotes`, `calcularTotalDesdeDetalle`, `precioRealServicio`, `datosRealesInsumo`, `ajustarPagoPorRedondeo`). |
| Inicio y API | `ModeloInicio` (`contarCitasPendientes`, `contarCitasDeHoy`, `obtenerAgendaPersonal`, `obtenerHorarioSemanal`, `servicios`, `especialidades_solicitadas`, `sintomas_comunes`, `comprobarCargo`, `datos_personal`), `ModeloApi` (`esPersonalDelCentro`, `pacienteDeUsuario`, `historialDePaciente`, `citasDePaciente`, `hospitalizacionesDePaciente`, `buscarPacientes`, `controlesRecientes`, `patologiasDePaciente`). |
| Bitácora y saneamiento | `ModeloBitacora` (`consultarBitacora`, `contarTotalBitacora`, `insertarBitacora`), `ModeloSanetizarJSON` (`setHashKeys`, `sanitizeRecursive`). |
| Mantenimiento | `ModeloMantenimiento` (`generateBackup`, `traerBds`, `traerBdsNube`, `restaurarBackup`, `verifU`). |

## Invariants

- **Separación de bases.** `Db::connectionSistema()` y `Db::connectionSegurity()` son conexiones distintas; `ModelBase` decide con `$dbSystem`. No se deben mezclar escrituras de identidad con datos clínicos sin pasar por el modelo correspondiente.
- **SQL preparado.** `ModelBase::create`, `read`, `update`, `delete`, `update_logic`, `search` y `storedProcedure` usan `prepare`/`bindValue`; `search` enlaza enteros como `PARAM_INT` y el resto como `PARAM_STR`. `query()` ejecuta SQL ya construido y solo se usa para consultas internas fijas.
- **Transacciones y bloqueos.** Los flujos de cita (`ModeloCita::reservar`, `guardarCita`), control (`ModeloControl::insertControl`), hospitalización (`ModeloHospitalizacion::insertarH`, `facturarH`), inventario (`ModeloInsumo::guardarInsumo`, `ModeloEntrada::guardarEntrada`), facturación (`ModeloFactura::guardarFactura`, `ModeloFacturaHospitalizacion::registrarConsumo`) y perfil usan `beginTransaction`/`commit`/`rollBack`. Varios usan `SELECT ... FOR UPDATE` para bloquear filas.
- **Citas.** `cita` relaciona paciente, servicio, doctor, fecha, hora, `hora_salida`, estado y `creado_en`. `ModeloCita::reservar` limpia reservas vencidas, valida servicio, paciente y turno, y mantiene estados como `Reservado`, `Pendiente`, `Realizadas` y `Expirado`. La reserva tiene una ventana de 5 minutos.
- **Control clínico.** `ModeloControl::insertControl` bloquea la fila del paciente con `FOR UPDATE` y escribe el control, sus síntomas (`sintomas_control`) y patologías (`patologiadepaciente`). `editarControl` actualiza el mismo registro clínico.
- **Hospitalización.** `hospitalizacion` guarda paciente, doctor, inicio/fin, precio por hora en bolívares y divisas, y totales. `ModeloHospitalizacion` maneja servicios (`servicios_hospitalizacion`) e insumos (`insumodehospitalizacion`), y `facturarH` actualiza los totales y el control clínico asociado.
- **Inventario por lote.** `entrada` representa la entrada del proveedor; `entrada_insumo` el lote con vencimiento, precio, cantidad entrante y disponible. `ModeloEntrada` y `ModeloInsumo` llaman a los procedimientos `insert_entrada`, `insert_insumo`, `DescontarLotes` y `devolver_insumo_hospitalizacion`.
- **Facturación.** `factura` guarda fecha, total en bolívares, `tipo_cambio`, estado y cliente. `detalle_factura` guarda servicios, insumos y hospitalización; `pagodefactura` guarda método, referencia y monto. `ModeloFactura::guardarFactura` calcula precios desde la base, ignora precios del navegador, reserva lotes y recalcula el total desde el detalle. También valida el estado de la cita y de la hospitalización con `FOR UPDATE`.
- **Borrado lógico.** Pacientes, clientes, usuarios, doctores, insumos, proveedores y otros catálogos usan `estado` (`ACT`/`DES`/`BLOQUED`) y métodos de restablecimiento; no siempre se elimina la fila física.
- **IDs.** Los modelos reciben IDs enteros; los controladores los convierten con `hashId()`/`unhashId()`. Los modelos no deben asumir que el ID viene hasheado.
- **Validación de entrada.** Los setters validan cédula, teléfono, nombres, fechas, precios, cantidades, correo, usuario y campos obligatorios. Los fallos lanzan `InvalidArgumentException` o `Exception`; varios `catch` devuelven el texto del error en lugar de una excepción tipada.
- **Permisos.** `ModeloPermisos::setPermiso` solo acepta `consultar`, `guardar`, `editar` y `eliminar`; `gestionarPermisos` cruza rol, módulo y permiso. `returnIdModule` resuelve el módulo por nombre y estado `ACT`.
- **Bitácora.** `ModeloBitacora` vive en `segurity`; `insertarBitacora` exige un `id_usuario` de sesión o parámetro y escribe `tabla`, `actividad` y `fecha_hora`.
- **Saneamiento de JSON.** `ModeloSanetizarJSON::sanitizeRecursive` aplica `htmlspecialchars` a los strings, hashea las claves indicadas en `setHashKeys` y deja los valores numéricos como número. No convierte strings numéricos a número, así que un teléfono o cédula enviado como string conserva sus ceros iniciales; el riesgo es el doble escape si el valor ya viene escapado y se vuelve a insertar en HTML.
- **API de lectura.** `ModeloApi` solo consulta: personal del centro, paciente asociado a un usuario, historial, citas, hospitalizaciones, búsqueda de pacientes, controles y patologías.
- **Mantenimiento.** `ModeloMantenimiento` genera respaldos con `mysqldump`/`mysqlbinlog`, usa `ZipArchive`, checkpoints JSON y ejecuta comandos del sistema. No es un modelo de negocio clínico.

## Side Effects

- Escrituras en `bd` y `segurity`; transacciones y bloqueos de fila.
- Llamadas a procedimientos almacenados (`insert_entrada`, `insert_insumo`, `DescontarLotes`, `devolver_insumo_hospitalizacion`, `devolver_cantidad_insumos`) y lecturas de vistas.
- Subida, movimiento y borrado de imágenes en `ModeloDoctores`, `ModeloInsumo`, `ModeloPerfil` y `ModeloUsuarios`.
- `error_log` en varios modelos y controladores.
- Lectura/escritura de archivos, ZIP, checkpoints y binlogs en `ModeloMantenimiento`.
- Inserción de filas de bitácora en `segurity`.
- Sin llamadas HTTP directas: la capa de modelos no conoce la sesión HTTP salvo los modelos que la consultan para validar identidad.

## Verification

- Run: `vendor\bin\phpunit`
- Nota: la suite existente es de integración y se conecta a MySQL; ejecutarla solo contra una base de pruebas aislada, nunca contra datos reales.
