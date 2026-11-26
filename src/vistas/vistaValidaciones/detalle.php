<?php require_once './src/vistas/head/head.php'; ?>

<div class="col-12 m-auto pt-3 contenedor-fondo" style="min-height: 100vh">
	<input type="hidden" id="vistaActiva" value="validaciones">
	<div class="d-flex justify-content-between align-items-center m-auto mb-3" style="width: 95%;">
		<h5 class="mb-0">Detalle del caso <?= isset($validacion['id']) ? '#' . (int)$validacion['id'] : '' ?></h5>
		<a class="btn btn-outline-secondary btn-sm" href="/Validaciones/cola">Volver a la cola</a>
	</div>

	<?php if (empty($validacion)): ?>
		<div class="alert alert-warning m-auto" style="width: 95%;"><?= htmlspecialchars($error ?? 'Caso no encontrado', ENT_QUOTES, 'UTF-8') ?></div>
	<?php else: ?>
		<?php
			$id = (int)$validacion['id'];
			$estado = (string)($validacion['estado'] ?? '');
			$terminal = in_array($estado, ['validada', 'corregida', 'descartada', 'expirada'], true);
		?>
		<div class="m-auto" style="width: 95%;">
			<div class="row g-3 mb-3">
				<div class="col-md-4">
					<div class="card h-100"><div class="card-body">
						<h6 class="card-title">Caso</h6>
						<p class="mb-1">Estado: <strong><?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?></strong></p>
						<p class="mb-1">Asignado a: <?= htmlspecialchars((string)($validacion['asignado_a'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></p>
						<p class="mb-1">Confianza: <?= number_format((float)($validacion['confianza_total'] ?? 0), 2) ?></p>
						<p class="mb-1">Especialidad: <?= htmlspecialchars((string)($validacion['especialidad'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></p>
						<p class="mb-1">Dominio: <?= htmlspecialchars((string)($validacion['dominio'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></p>
						<p class="mb-0">Motivo: <?= htmlspecialchars((string)($validacion['motivo_revision'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></p>
					</div></div>
				</div>
				<div class="col-md-8">
					<div class="card h-100"><div class="card-body">
						<h6 class="card-title">Respuesta final</h6>
						<p class="mb-2"><?= nl2br(htmlspecialchars((string)($evidencia['respuesta_final'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
						<p class="mb-1 text-muted small">Modelo: <?= htmlspecialchars((string)($evidencia['modelo'] ?? '—'), ENT_QUOTES, 'UTF-8') ?> · KB: <?= htmlspecialchars((string)($evidencia['kb_version_id'] ?? '—'), ENT_QUOTES, 'UTF-8') ?> · Hash: <?= htmlspecialchars(substr((string)($evidencia['respuesta_hash'] ?? ''), 0, 12), ENT_QUOTES, 'UTF-8') ?></p>
					</div></div>
				</div>
			</div>

			<div class="row g-3 mb-3">
				<div class="col-md-6">
					<div class="card h-100"><div class="card-body">
						<h6 class="card-title">Ficha resumida</h6>
						<pre class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars(json_encode($resumen, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?></pre>
					</div></div>
				</div>
				<div class="col-md-6">
					<div class="card h-100"><div class="card-body">
						<h6 class="card-title">Candidatos y componentes</h6>
						<p class="mb-1"><strong>Candidatos:</strong></p>
						<ul class="mb-2">
							<?php foreach ($candidatos as $c): ?>
								<li><?= htmlspecialchars((string)$c['dominio_clave'], ENT_QUOTES, 'UTF-8') ?> — <?= number_format((float)$c['score'], 2) ?></li>
							<?php endforeach; ?>
						</ul>
						<p class="mb-1"><strong>Componentes:</strong></p>
						<ul class="mb-0">
							<?php foreach ($componentes as $c): ?>
								<li><?= htmlspecialchars((string)$c['nombre'], ENT_QUOTES, 'UTF-8') ?>: <?= number_format((float)$c['valor'], 2) ?> (peso <?= number_format((float)$c['peso'], 2) ?>)</li>
							<?php endforeach; ?>
						</ul>
					</div></div>
				</div>
			</div>

			<div class="card mb-3"><div class="card-body">
				<h6 class="card-title">Decisión</h6>
				<?php if ($terminal): ?>
					<p class="mb-0">El caso ya fue cerrado como <strong><?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?></strong>.</p>
				<?php elseif (empty($validacion['asignado_a'])): ?>
					<p class="mb-2">Toma el caso para poder decidir.</p>
					<button class="btn btn-primary" type="button" onclick="tomarCaso(<?= $id ?>)">Tomar caso</button>
				<?php elseif ((string)$validacion['asignado_a'] !== (string)($_SESSION['id_usuario'] ?? '')): ?>
					<p class="mb-0">El caso está asignado a <strong><?= htmlspecialchars((string)$validacion['asignado_a'], ENT_QUOTES, 'UTF-8') ?></strong>.</p>
				<?php else: ?>
					<form id="formDecision" class="row g-2" onsubmit="decidir(event, <?= $id ?>, <?= (int)($validacion['version'] ?? 1) ?>)">
						<div class="col-md-3">
							<select class="form-select" id="decision" required>
								<option value="validar">Validar</option>
								<option value="corregir">Corregir</option>
								<option value="descartar">Descartar</option>
							</select>
						</div>
						<div class="col-md-3">
							<input class="form-control" id="motivo_codigo" placeholder="Motivo (obligatorio al corregir/descartar)">
						</div>
						<div class="col-md-4">
							<input class="form-control" id="comentario" placeholder="Comentario (opcional)">
						</div>
						<div class="col-md-2">
							<button class="btn btn-primary w-100" type="submit">Guardar</button>
						</div>
					</form>
				<?php endif; ?>
			</div></div>

			<div class="card mb-3"><div class="card-body">
				<h6 class="card-title">Historial</h6>
				<ul class="mb-0">
					<?php foreach ($eventos as $e): ?>
						<li><?= htmlspecialchars((string)$e['creado_en'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars((string)$e['evento'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)($e['actor_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
					<?php endforeach; ?>
					<?php foreach ($accesos as $a): ?>
						<li><?= htmlspecialchars((string)$a['creado_en'], ENT_QUOTES, 'UTF-8') ?> — visto por <?= htmlspecialchars((string)$a['actor_id'], ENT_QUOTES, 'UTF-8') ?></li>
					<?php endforeach; ?>
				</ul>
			</div></div>
		</div>
	<?php endif; ?>
</div>

<?php require_once './src/vistas/head/footer.php'; ?>
<?php if (!empty($validacion)): ?>
<script>
	const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
	const CASO_ID = <?= (int)$validacion['id'] ?>;

	async function marcarVisto() {
		try {
			await fetch(`/Validaciones/visto/${CASO_ID}`, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
				body: '{}'
			});
		} catch (error) {
			console.log('No se pudo registrar el acceso', error);
		}
	}
	marcarVisto();

	async function tomarCaso(id) {
		const respuesta = await fetch(`/Validaciones/tomar/${id}`, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
			body: '{}'
		});
		const datos = await respuesta.json();
		if (!respuesta.ok) {
			alert(`No se pudo tomar el caso: ${JSON.stringify(datos.detail || datos.error || datos)}`);
		}
		location.reload();
	}

	async function decidir(evento, id, version) {
		evento.preventDefault();
		const decision = document.getElementById('decision').value;
		const motivo = document.getElementById('motivo_codigo').value.trim();
		if ((decision === 'corregir' || decision === 'descartar') && !motivo) {
			alert('El motivo es obligatorio para corregir o descartar.');
			return;
		}
		const respuesta = await fetch(`/Validaciones/decidir/${id}`, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
			body: JSON.stringify({
				decision: decision,
				motivo_codigo: motivo || null,
				comentario: document.getElementById('comentario').value.trim(),
				version: version
			})
		});
		const datos = await respuesta.json();
		if (!respuesta.ok) {
			alert(`No se pudo guardar: ${JSON.stringify(datos.detail || datos.error || datos)}`);
			location.reload();
			return;
		}
		location.href = '/Validaciones/cola';
	}
</script>
<?php endif; ?>
