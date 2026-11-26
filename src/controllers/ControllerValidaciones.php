<?php

/**
 * Cola de validación clínica de MIDI.
 *
 * El router delega en este controlador sin consultar permisos de módulo para
 * no tocar la base de datos del sistema. Por eso cada entrada exige sesión y
 * rol 10 (Superadmin), y las acciones POST validan CSRF. Las vistas consumen
 * FastAPI con la identidad de la sesión (`id_rol`, no `rol`).
 */

function validacionesBaseIA(): string
{
	$base = $_ENV['IA_BASE_URL'] ?? '';
	if ($base === '') {
		$base = preg_replace('#/orientar/?$#', '', $_ENV['IA_API_URL'] ?? '');
	}
	return rtrim((string)$base, '/');
}

function validacionesJson(int $codigo, array $datos): void
{
	if (ob_get_length()) {
		ob_clean();
	}
	http_response_code($codigo);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($datos, JSON_UNESCAPED_UNICODE);
	exit;
}

function validacionesSesionYCsrf(): void
{
	if (empty($_SESSION['id_usuario'])) {
		validacionesJson(401, ['ok' => false, 'error' => 'Sesión no válida.']);
	}

	$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? null);
	if (empty($_SESSION['csrf_token']) || empty($csrf)
		|| !hash_equals($_SESSION['csrf_token'], $csrf)) {
		validacionesJson(403, ['ok' => false, 'error' => 'Token CSRF inválido.']);
	}
}

function validacionesExigirRol(bool $json = true): void
{
	if (empty($_SESSION['id_usuario'])) {
		if ($json) {
			validacionesJson(401, ['ok' => false, 'error' => 'Sesión no válida.']);
		}
		http_response_code(401);
		echo 'Sesión no válida.';
		exit;
	}

	if ((string)($_SESSION['id_rol'] ?? '') !== '10') {
		if ($json) {
			validacionesJson(403, [
				'ok' => false,
				'error' => 'Acceso reservado al super usuario.',
			]);
		}
		http_response_code(403);
		echo 'Acceso reservado al super usuario.';
		exit;
	}
}

function validacionesHeaders(): array{
	$nombre = trim(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? ''));
	return [
		'Content-Type: application/json',
		'X-API-Key: ' . ($_ENV['API_KEY_IA'] ?? ''),
		'X-Usuario-Id: ' . ($_SESSION['id_usuario'] ?? ''),
		'X-Usuario-Rol: ' . ($_SESSION['id_rol'] ?? ''),
		'X-Usuario-Nombre: ' . $nombre,
	];
}

/**
 * Llama a FastAPI y devuelve [código HTTP, cuerpo decodificado].
 */
function validacionesLlamarIA(string $metodo, string $ruta, ?array $cuerpo = null): array
{
	$base = validacionesBaseIA();
	if ($base === '' || empty($_ENV['API_KEY_IA'])) {
		return [503, ['ok' => false, 'error' => 'El componente IA no está configurado.']];
	}

	$url = $base . $ruta;
	$headers = validacionesHeaders();
	$payload = $cuerpo === null ? null : json_encode($cuerpo, JSON_UNESCAPED_UNICODE);

	if (function_exists('curl_init')) {
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST  => $metodo,
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_CONNECTTIMEOUT => 5,
		]);
		if ($payload !== null) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		}
		$texto = curl_exec($ch);
		$codigo = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		if ($texto === false) {
			error_log("Validaciones IA: fallo de red ($error) en $url");
			return [503, ['ok' => false, 'error' => 'No se pudo conectar con el componente IA.']];
		}
	} else {
		$contexto = stream_context_create([
			'http' => [
				'method'        => $metodo,
				'header'        => implode("\r\n", $headers),
				'content'       => $payload ?? '',
				'timeout'       => 30,
				'ignore_errors' => true,
			],
		]);
		$texto = @file_get_contents($url, false, $contexto);
		$codigo = 0;
		if (isset($http_response_header[0])
			&& preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
			$codigo = (int)$m[1];
		}
		if ($texto === false) {
			return [503, ['ok' => false, 'error' => 'No se pudo conectar con el componente IA.']];
		}
	}

	$datos = json_decode($texto, true);
	if ($codigo === 0) {
		$codigo = 502;
	}
	return [$codigo, is_array($datos) ? $datos : ['ok' => false, 'error' => 'Respuesta inválida']];
}

function cola()
{
	validacionesExigirRol(false);
	$vistaActiva = 'validaciones';
	$ayuda = '';
	[$codigo, $datos] = validacionesLlamarIA('GET', '/validaciones?estado=pendiente&limite=50');
	$validaciones = $codigo === 200 ? ($datos['items'] ?? []) : [];
	$error = $codigo === 200 ? null : ($datos['detail'] ?? $datos['error'] ?? 'No se pudo cargar la cola');
	require_once __DIR__ . '/../../src/vistas/vistaValidaciones/cola.php';
}

function detalle($parametro)
{
	validacionesExigirRol(false);
	$vistaActiva = 'validaciones';
	$ayuda = '';
	$id = (int)($parametro[0] ?? 0);
	[$codigo, $datos] = validacionesLlamarIA('GET', '/validaciones/' . $id);

	if ($codigo !== 200 || empty($datos['validacion'])) {
		http_response_code(404);
		$error = 'Caso no encontrado';
		require_once __DIR__ . '/../../src/vistas/vistaValidaciones/detalle.php';
		return;
	}

	$validacion = $datos['validacion'];
	$evidencia = $datos['evidencia'] ?? [];
	$componentes = $datos['componentes'] ?? [];
	$candidatos = $datos['candidatos'] ?? [];
	$eventos = $datos['eventos'] ?? [];
	$accesos = $datos['accesos'] ?? [];

	$resumen = [];
	if (!empty($evidencia['resumen_ficha'])) {
		$resumen = json_decode($evidencia['resumen_ficha'], true) ?: [];
	}

	require_once __DIR__ . '/../../src/vistas/vistaValidaciones/detalle.php';
}

function propuestas()
{
	validacionesExigirRol(false);
	$vistaActiva = 'validaciones';
	$ayuda = '';
	[$codigo, $datos] = validacionesLlamarIA('GET', '/propuestas?estado=pendiente&limite=100');
	$propuestas = $codigo === 200 ? $datos : [];
	$error = $codigo === 200 ? null : ($datos['detail'] ?? $datos['error'] ?? 'No se pudieron cargar las propuestas');
	require_once __DIR__ . '/../../src/vistas/vistaValidaciones/propuestas.php';
}

function tomar($parametro)
{
	validacionesSesionYCsrf();
	validacionesExigirRol();
	$id = (int)($parametro[0] ?? 0);
	[$codigo, $datos] = validacionesLlamarIA('POST', '/validaciones/' . $id . '/tomar');
	validacionesJson($codigo, $datos);
}

function decidir($parametro)
{
	validacionesSesionYCsrf();
	validacionesExigirRol();
	$id = (int)($parametro[0] ?? 0);
	$cuerpo = json_decode(file_get_contents('php://input'), true);
	if (!is_array($cuerpo)) {
		validacionesJson(400, ['ok' => false, 'error' => 'Petición malformada.']);
	}

	$decision = (string)($cuerpo['decision'] ?? '');
	if (!in_array($decision, ['validar', 'corregir', 'descartar'], true)) {
		validacionesJson(422, ['ok' => false, 'error' => 'Decisión inválida.']);
	}

	$motivo = trim((string)($cuerpo['motivo_codigo'] ?? ''));
	if (in_array($decision, ['corregir', 'descartar'], true) && $motivo === '') {
		validacionesJson(422, [
			'ok' => false,
			'error' => 'El motivo es obligatorio para corregir o descartar.',
		]);
	}

	[$codigo, $datos] = validacionesLlamarIA('POST', '/validaciones/' . $id . '/decidir', [
		'decision'      => $decision,
		'motivo_codigo' => $motivo !== '' ? $motivo : null,
		'comentario'    => trim((string)($cuerpo['comentario'] ?? '')),
		'version'       => isset($cuerpo['version']) ? (int)$cuerpo['version'] : null,
	]);
	validacionesJson($codigo, $datos);
}

function visto($parametro)
{
	validacionesSesionYCsrf();
	validacionesExigirRol();
	$id = (int)($parametro[0] ?? 0);
	[$codigo, $datos] = validacionesLlamarIA('POST', '/validaciones/' . $id . '/visto');
	validacionesJson($codigo, $datos);
}

function aprobarPropuesta($parametro)
{
	validacionesSesionYCsrf();
	validacionesExigirRol();
	$id = (int)($parametro[0] ?? 0);
	[$codigo, $datos] = validacionesLlamarIA('POST', '/propuestas/' . $id . '/aprobar');
	validacionesJson($codigo, $datos);
}

function rechazarPropuesta($parametro)
{
	validacionesSesionYCsrf();
	validacionesExigirRol();
	$id = (int)($parametro[0] ?? 0);
	$cuerpo = json_decode(file_get_contents('php://input'), true);
	$motivo = trim((string)($cuerpo['motivo'] ?? ''));
	if ($motivo === '') {
		validacionesJson(422, ['ok' => false, 'error' => 'El motivo es obligatorio.']);
	}
	[$codigo, $datos] = validacionesLlamarIA('POST', '/propuestas/' . $id . '/rechazar', [
		'motivo' => $motivo,
	]);
	validacionesJson($codigo, $datos);
}
