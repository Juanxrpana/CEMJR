<?php

use App\modelos\ModeloPacientes;
use App\modelos\ModeloCliente;
use App\modelos\ModeloBitacora;
use App\modelos\ModeloSanetizarJSON;
// use App\


function getPacientes($parametro)
{
	$ayuda = "btnayudaPaciente";
	$vistaActiva = 'pacientes';
	require_once './src/vistas/vistaPacientes/pacientes.php';
}

function getPacientesAjax()
{
	if (empty($_GET)) {
		http_response_code(409);
		echo json_encode(['ok' => false, 'error' => "Error al realizar la petición :("]);
		exit;
	}

	$draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;
	$inicio = isset($_GET['start']) ? (int)$_GET['start'] : 0;
	$limite = isset($_GET['length']) ? (int)$_GET['length'] : 10;
	$buscar = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';


	//mapeada en el mismo orden que la tabla (para ordenar datatable)
	$columnasMapeadas = ['id_paciente', 'cedula', 'nombre', 'apellido', 'telefono', 'direccion'];

	// capturo el índice de la columna cliqueada
	$colIndex = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 0;
	// direccion (asc o desc)
	$ordenDir = isset($_GET['order'][0]['dir']) && in_array(strtoupper($_GET['order'][0]['dir']), ['ASC', 'DESC']) ? strtoupper($_GET['order'][0]['dir']) : 'DESC';
	//si ordena por el id por defecto
	$ordenColumna = isset($columnasMapeadas[$colIndex]) ? $columnasMapeadas[$colIndex] : 'id_paciente';

	if (!preg_match('/^[a-zA-Z_]+$/', $ordenColumna)) {
		$ordenColumna = 'id_paciente';
	}

	$modelo = new ModeloPacientes();
	$sanitizador = new ModeloSanetizarJSON();

	$sanitizador->setHashKeys(['id_paciente']);

	$pacientes = $modelo->index($inicio, $limite, $buscar, $ordenColumna, $ordenDir);
	$pacientesSanitizados = $sanitizador->sanitizeRecursive($pacientes);

	$totalRegistros = $modelo->contarTotalPacientes('ACT');
	$totalFiltrados = !empty($buscar) ? $modelo->contarTotalPacientes('ACT', $buscar) : $totalRegistros;

	//datos que se le envia al js (esto es estandar de datatable)
	$response = [
		"draw" => $draw,
		"recordsTotal" => $totalRegistros,
		"recordsFiltered" => $totalFiltrados,
		"data" => is_array($pacientesSanitizados) ? $pacientesSanitizados : []
	];

	echo json_encode($response);
	exit;
}

/* hay q hacerlo con ajax, pero lo hice sencillo, no se si se vaya a pasar a ajax to esto, pa despues del sabado ;) */
function getHistorialSalud($parametro)
{
	$modelo = new ModeloPacientes();

	$vistaActiva = 'historial';
	require './src/vistas/vistaPacientes/pacientes.php';
}

function getHistorialSaludAjax()
{
	if (empty($_GET)) {
		http_response_code(409);
		echo json_encode(['ok' => false, 'error' => "Error al realizar la petición :("]);
		exit;
	}

	$draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;
	$inicio = isset($_GET['start']) ? (int)$_GET['start'] : 0;
	$limite = isset($_GET['length']) ? (int)$_GET['length'] : 10;
	$buscar = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';


	//mapeada en el mismo orden que la tabla (para ordenar datatable)
	$columnasMapeadas = ['id_paciente', 'cedula', 'nombre', 'apellido', 'telefono', 'direccion'];

	// capturo el índice de la columna cliqueada
	$colIndex = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 0;
	// direccion (asc o desc)
	$ordenDir = isset($_GET['order'][0]['dir']) && in_array(strtoupper($_GET['order'][0]['dir']), ['ASC', 'DESC']) ? strtoupper($_GET['order'][0]['dir']) : 'DESC';
	//si ordena por el id por defecto
	$ordenColumna = isset($columnasMapeadas[$colIndex]) ? $columnasMapeadas[$colIndex] : 'id_paciente';

	if (!preg_match('/^[a-zA-Z_]+$/', $ordenColumna)) {
		$ordenColumna = 'id_paciente';
	}

	$modelo = new ModeloPacientes();
	$sanitizador = new ModeloSanetizarJSON();


	// traigo los datos de todos los registros
	$pacientes = $modelo->indexHistorial($inicio, $limite, $buscar, $ordenColumna, $ordenDir);
	$pacientesSanitizados = $sanitizador->sanitizeRecursive($pacientes);

	// Aqui solamente el total de registros
	$totalRegistros = $modelo->contarTotalHistorial();
	$totalFiltrados = !empty($buscar) ? $modelo->contarTotalHistorial($buscar) : $totalRegistros;

	// devuelvo esta estructuta (la cual es estandar para datatable)
	$respuesta = [
		"draw"            => $draw,
		"recordsTotal"    => (int)$totalRegistros,
		"recordsFiltered" => (int)$totalFiltrados,
		"data"            => is_array($pacientesSanitizados) ? $pacientesSanitizados : []
	];

	echo json_encode($respuesta);
	exit;
}

function papeleraPaciente($parametro)
{

	$modelo  = new ModeloPacientes();
	$vistaActiva = 'papelera';
	$modelo  = new ModeloPacientes();

	$pacientes = $modelo->indexPapelera();
	require_once './src/vistas/vistaPacientes/pacientes.php';
}

function papeleraPacienteAjax()
{
	if (empty($_GET)) {
		http_response_code(409);
		echo json_encode(['ok' => false, 'error' => "Error al realizar la petición :("]);
		exit;
	}

	$draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;
	$inicio = isset($_GET['start']) ? (int)$_GET['start'] : 0;
	$limite = isset($_GET['length']) ? (int)$_GET['length'] : 10;
	$buscar = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';


	//mapeada en el mismo orden que la tabla (para ordenar datatable)
	$columnasMapeadas = ['id_paciente', 'cedula', 'nombre', 'apellido', 'telefono', 'direccion'];

	// capturo el índice de la columna cliqueada
	$colIndex = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 0;
	// direccion (asc o desc)
	$ordenDir = isset($_GET['order'][0]['dir']) && in_array(strtoupper($_GET['order'][0]['dir']), ['ASC', 'DESC']) ? strtoupper($_GET['order'][0]['dir']) : 'DESC';
	//si ordena por el id por defecto
	$ordenColumna = isset($columnasMapeadas[$colIndex]) ? $columnasMapeadas[$colIndex] : 'id_paciente';
	if (!preg_match('/^[a-zA-Z_]+$/', $ordenColumna)) {
		$ordenColumna = 'id_paciente';
	}

	$modelo = new ModeloPacientes();
	$sanitizador = new ModeloSanetizarJSON();
	
	$sanitizador->setHashKeys(['id_paciente']);
	$pacientes = $modelo->indexPapelera($inicio, $limite, $buscar, $ordenColumna, $ordenDir);
	$pacientesSanitizados = $sanitizador->sanitizeRecursive($pacientes);



	$totalRegistros = $modelo->contarTotalPacientes('DES');
	$totalFiltrados = !empty($buscar) ? $modelo->contarTotalPacientes('DES', $buscar) : $totalRegistros;

	//datos que se le envia al js (esto es estandar de datatable)
	$response = [
		"draw" => $draw,
		"recordsTotal" => $totalRegistros,
		"recordsFiltered" => $totalFiltrados,
		"data" => is_array($pacientesSanitizados) ? $pacientesSanitizados : []
	];

	echo json_encode($response);
	exit;
}



function guardar()
{
	if (empty($_POST)) {
		http_response_code(409);
		echo json_encode(['ok' => false, 'error' => "Error  al realizar la peticion :("]);
		exit;
	}

	try {
		// Validar CSRF token
		// $headers = getallheaders();
		$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;

		if (empty($_SESSION['csrf_token']) || empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
			http_response_code(403);
			echo json_encode(['ok' => false, 'error' => 'Token CSRF inválido']);
			exit;
		}
		$idUsuario = $_SESSION['id_usuario'];

		$modelo  = new ModeloPacientes();
		$bitacora = new ModeloBitacora();

		$modelo->setNacionalidad(isset($_POST['nacionalidad']) ? $_POST['nacionalidad'] : 'V');
		$modelo->setCedula($_POST['cedula']);
		$modelo->setNombre($_POST['nombre']);
		$modelo->setApellido($_POST['apellido']);
		$modelo->setTelefono($_POST['telefono']);
		$modelo->setDireccion($_POST['direccion']);
		$modelo->setFn($_POST['fn']);
		$modelo->setGenero($_POST['genero']);

		$bitacora->setId_usuario($idUsuario);
		$bitacora->setActividad("Ha Insertado un nuevo paciente");
		$bitacora->setTabla("paciente");

		$insercion = $modelo->guardarPaciente($idUsuario);

		// Verifica si es un array con clave "exito"
		if (is_array($insercion) && $insercion[0] === "exito") {
			$bitacora->insertarBitacora($idUsuario);
			echo json_encode(['ok' => true, 'message' => 'La operación se realizó con éxito', 'data' => $insercion[1]]);
		} else {
			// error especifico
			if (is_string($insercion)) {
				http_response_code(409);
				echo json_encode(['ok' => false, 'error' => $insercion]);
			} else {
				//error generico
				http_response_code(409);
				error_log("Error en guardarPaciente: " . print_r($insercion, true));
				echo json_encode(['ok' => false, 'error' => 'Error al guardar el paciente.']);
				exit;
			}
			exit;
		}
	} catch (InvalidArgumentException $e) {
		http_response_code(409);
		echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
		exit;
	}
}


function setPaciente()
{

	if (empty($_POST)) {
		http_response_code(409);
		echo json_encode(['ok' => false, 'error' => "Error  al realizar la peticion :("]);
		exit;
	}


	try {

		// $headers = getallheaders();
		$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;

		if (empty($_SESSION['csrf_token']) || empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
			http_response_code(403);
			echo json_encode(['ok' => false, 'error' => 'Token CSRF inválido']);
			exit;
		}

		$id = unhashId($_POST['id']);

		$idUsuario = $_SESSION['id_usuario'];

		$modelo  = new ModeloPacientes();
		$bitacora = new ModeloBitacora();


		$modelo->setIdPaciente(intval($id));
		$modelo->setNacionalidad($_POST['nacionalidad']);
		$modelo->setCedulaRegistrada($_POST['cedulaRegistrada']);
		$modelo->setCedula($_POST['cedula']);
		$modelo->setNombre($_POST['nombre']);
		$modelo->setApellido($_POST['apellido']);
		$modelo->setTelefono($_POST['telefono']);
		$modelo->setDireccion($_POST['direccion']);
		$modelo->setFn($_POST['fn']);
		$modelo->setGenero($_POST['genero']);

		$bitacora->setId_usuario($idUsuario);
		$bitacora->setActividad("Ha modificado un paciente");
		$bitacora->setTabla("paciente");

		$edicion = $modelo->editarPaciente($idUsuario);


		//Verifica si es un array con clave "exito"
		if (is_array($edicion) && $edicion[0] === "exito") {
			$bitacora->insertarBitacora($idUsuario);
			echo json_encode(['ok' => true, 'message' => 'La operación se realizó con éxito']);
		} else {
			if (is_string($edicion)) {
				http_response_code(409);
				echo json_encode(['ok' => false, 'error' => $edicion]);
			} else {
				http_response_code(409);
				error_log("Error en setPaciente: " . print_r($edicion, true));
				echo json_encode(['ok' => false, 'error' => 'Error al editar el paciente.']);
				exit;
			}
			exit;
		}
	} catch (InvalidArgumentException $e) {
		http_response_code(409);
		echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
		exit;
	}
}

function eliminar()
{
	try {

		// $headers = getallheaders();
		$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;


		if (empty($_SESSION['csrf_token']) || empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
			http_response_code(403);
			echo json_encode(['ok' => false, 'error' => 'Token CSRF inválido']);
			exit;
		}

		$idUsuario = $_SESSION['id_usuario'];

		$modelo  = new ModeloPacientes();
		$bitacora = new ModeloBitacora();

		$input = json_decode(file_get_contents("php://input"), true);
		$id = unhashId($input["id"] ?? null);

		$estado = empty($input["estado"]) ? 'DES' : 'ACT';
		$text = empty($input["estado"]) ? 'eliminado' : 'restablecido';
		$text_error = empty($input["estado"]) ? 'eliminar' : 'restablecer';


		$modelo->setIdPaciente($id);

		$eliminacion = $modelo->eliminarPaciente($idUsuario, $estado);

		//Verifica si es un array con clave "exito"
		if (is_array($eliminacion) && $eliminacion[0] === "exito") {
			$bitacora->setId_usuario($idUsuario);
			$bitacora->setActividad("Ha {$text} un  paciente");
			$bitacora->setTabla("paciente");
			$bitacora->insertarBitacora($idUsuario);
			echo json_encode(['ok' => true, 'message' => 'La operación se realizó con éxito']);
		} else {
			if (is_string($eliminacion)) {
				http_response_code(409);
				echo json_encode(['ok' => false, 'error' => $eliminacion]);
			} else {
				http_response_code(409);
				error_log("Error en eliminar: " . print_r($eliminacion, true));
				echo json_encode(['ok' => false, 'error' => "Error al {$text_error} el paciente."]);
				exit;
			}
			exit;
		}
	} catch (InvalidArgumentException $e) {
		http_response_code(409);
		echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
		exit;
	}
}

// Genera la cédula de referencia de un menor a partir de la del representante.
function generarCedulaMenor()
{
	if (empty($_POST)) {
		http_response_code(409);
		echo json_encode(['ok' => false, 'error' => 'Error al realizar la petición :(']);
		exit;
	}

	try {
		$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;
		if (empty($_SESSION['csrf_token']) || empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
			http_response_code(403);
			echo json_encode(['ok' => false, 'error' => 'Token CSRF inválido']);
			exit;
		}

		$modelo = new ModeloPacientes();
		$cedula = $modelo->generarCedulaMenor($_POST['cedulaRepresentante'] ?? '');

		echo json_encode(['ok' => true, 'cedula' => $cedula]);
	} catch (InvalidArgumentException $e) {
		http_response_code(409);
		echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
		exit;
	}
}

// Crea un paciente a partir del borrador confirmado del chatbot.
// Lo ejecuta el navegador de recepción con sesión y CSRF; FastAPI no escribe.
function guardarDesdeChatbot()
{
	if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
		http_response_code(405);
		echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
		exit;
	}

	if (empty($_SESSION['id_usuario'])) {
		http_response_code(401);
		echo json_encode(['ok' => false, 'error' => 'Debe iniciar sesión para registrar un paciente.']);
		exit;
	}

	$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;
	if (empty($_SESSION['csrf_token']) || empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
		http_response_code(403);
		echo json_encode(['ok' => false, 'error' => 'Token CSRF inválido.']);
		exit;
	}

	$cuerpo = json_decode(file_get_contents('php://input'), true);
	$datos = is_array($cuerpo) ? $cuerpo : $_POST;

	$modelo = new ModeloPacientes();
	$modeloCliente = new ModeloCliente();

	try {
		$nacionalidad = trim((string) ($datos['nacionalidad'] ?? 'V'));
		$cedula = trim((string) ($datos['cedula'] ?? ''));
		$nombre = trim((string) ($datos['nombre'] ?? ''));
		$apellido = trim((string) ($datos['apellido'] ?? ''));
		$telefono = trim((string) ($datos['telefono'] ?? ''));
		$direccion = trim((string) ($datos['direccion'] ?? ''));
		$fn = trim((string) ($datos['fn'] ?? ''));
		$genero = trim((string) ($datos['genero'] ?? ''));
		$cedulaRepresentante = trim((string) ($datos['cedulaRepresentante'] ?? ''));

		$modelo->setNacionalidad($nacionalidad);
		$modelo->setNombre($nombre);
		$modelo->setApellido($apellido);
		$modelo->setTelefono($telefono);
		$modelo->setDireccion($direccion);
		$modelo->setFn($fn);
		$modelo->setGenero($genero);

		// Regla de menores: cédula -R a partir del representante.
		$esMenor = false;
		$fechaNacimiento = \DateTime::createFromFormat('Y-m-d', $fn);
		if ($fechaNacimiento && $fechaNacimiento->format('Y-m-d') === $fn) {
			$esMenor = (int) $fechaNacimiento->diff(new \DateTime('today'))->y < 18;
		}

		if ($esMenor) {
			if (!preg_match('/^[1-9][0-9]{6,7}$/', $cedulaRepresentante)) {
				http_response_code(409);
				echo json_encode(['ok' => false, 'campo' => 'cedulaRepresentante', 'error' => 'La cédula del representante debe tener entre 7 y 8 dígitos.']);
				exit;
			}

			$representante = $modeloCliente->buscarPorCedula($cedulaRepresentante);
			if (!$representante) {
				$representante = $modelo->buscarPorCedula($cedulaRepresentante);
			}

			if (!$representante) {
				http_response_code(409);
				echo json_encode(['ok' => false, 'representante_no_registrado' => true, 'error' => 'El representante no está registrado. Regístrelo primero en el módulo de pacientes o clientes.']);
				exit;
			}

			$cedula = $modelo->generarCedulaMenor($cedulaRepresentante);
		}

		$modelo->setCedula($cedula);

		$existente = $modelo->buscarPorCedula($cedula);
		if ($existente) {
			http_response_code(409);
			echo json_encode(['ok' => false, 'existe' => true, 'paciente' => [
				'id' => hashId((int) $existente['id_paciente']),
				'nombre' => $existente['nombre'],
				'apellido' => $existente['apellido'],
				'cedula' => $existente['cedula'],
			]]);
			exit;
		}

		$insercion = $modelo->guardarPaciente($_SESSION['id_usuario']);

		if (!is_array($insercion) || ($insercion[0] ?? null) !== 'exito') {
			http_response_code(409);
			echo json_encode(['ok' => false, 'error' => is_string($insercion) ? $insercion : 'No se pudo guardar el paciente.']);
			exit;
		}

		$creado = $modelo->buscarPorCedula($cedula);

		$bitacora = new ModeloBitacora();
		$bitacora->setId_usuario($_SESSION['id_usuario']);
		$bitacora->setTabla('paciente');
		$bitacora->setActividad('Ha insertado un paciente desde el chatbot');
		$bitacora->insertarBitacora($_SESSION['id_usuario']);

		echo json_encode(['ok' => true, 'paciente' => [
			'id' => $creado ? hashId((int) $creado['id_paciente']) : null,
			'nombre' => $nombre,
			'apellido' => $apellido,
			'cedula' => $cedula,
		]]);
		exit;
	} catch (\InvalidArgumentException $e) {
		http_response_code(409);
		echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
		exit;
	} catch (\Throwable $e) {
		error_log('Error en guardarDesdeChatbot: ' . $e->getMessage());
		http_response_code(500);
		echo json_encode(['ok' => false, 'error' => 'No se pudo guardar el paciente.']);
		exit;
	}
}
