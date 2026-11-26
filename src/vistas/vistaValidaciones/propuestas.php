<?php require_once './src/vistas/head/head.php'; ?>

<div class="col-12 m-auto pt-3 contenedor-fondo" style="min-height: 100vh">
	<input type="hidden" id="vistaActiva" value="validaciones">
	<div class="d-flex justify-content-between align-items-center m-auto mb-3" style="width: 95%;">
		<h5 class="mb-0">Propuestas de conocimiento</h5>
		<a class="btn btn-outline-secondary btn-sm" href="/Validaciones/cola">Volver a la cola</a>
	</div>

	<?php if (!empty($error)): ?>
		<div class="alert alert-warning m-auto mb-3" style="width: 95%;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
	<?php endif; ?>

	<div class="caja-contenedor-tabla fondo-tabla p-3 mb-3 m-auto" style="width: 95%;">
		<div class="table table-responsive">
			<table class="table table-striped align-middle">
				<thead>
					<tr>
						<th class="text-dark text-center">#</th>
						<th class="text-dark text-center">Dominio</th>
						<th class="text-dark text-center">Tipo</th>
						<th class="text-dark text-center">Valor</th>
						<th class="text-dark text-center">Generada por</th>
						<th class="text-dark text-center">Creada</th>
						<th class="text-dark text-center">Acciones</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($propuestas)): ?>
						<tr><td colspan="7" class="text-center text-muted py-4">No hay propuestas pendientes.</td></tr>
					<?php else: ?>
						<?php foreach ($propuestas as $p): ?>
							<tr>
								<td class="text-center"><?= (int)$p['id'] ?></td>
								<td class="text-center"><?= htmlspecialchars((string)$p['dominio_clave'], ENT_QUOTES, 'UTF-8') ?></td>
								<td class="text-center"><?= htmlspecialchars((string)$p['tipo'], ENT_QUOTES, 'UTF-8') ?></td>
								<td><?= htmlspecialchars((string)$p['valor'], ENT_QUOTES, 'UTF-8') ?></td>
								<td class="text-center"><?= htmlspecialchars((string)($p['generado_por'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
								<td class="text-center"><small><?= htmlspecialchars((string)$p['creada_en'], ENT_QUOTES, 'UTF-8') ?></small></td>
								<td class="text-center">
									<button class="btn btn-sm btn-primary" onclick="aprobar(<?= (int)$p['id'] ?>)">Aprobar</button>
									<button class="btn btn-sm btn-outline-danger" onclick="rechazar(<?= (int)$p['id'] ?>)">Rechazar</button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<?php require_once './src/vistas/head/footer.php'; ?>
<script>
	function csrfToken() {
		return document.querySelector('meta[name="csrf-token"]')?.content || '';
	}

	async function aprobar(id) {
		const respuesta = await fetch(`/Validaciones/aprobarPropuesta/${id}`, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken() },
			body: '{}'
		});
		const datos = await respuesta.json();
		if (!respuesta.ok) {
			alert(`No se pudo aprobar: ${JSON.stringify(datos.detail || datos.error || datos)}`);
			return;
		}
		location.reload();
	}

	async function rechazar(id) {
		const motivo = prompt('Motivo del rechazo:');
		if (!motivo || !motivo.trim()) {
			return;
		}
		const respuesta = await fetch(`/Validaciones/rechazarPropuesta/${id}`, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken() },
			body: JSON.stringify({ motivo: motivo.trim() })
		});
		const datos = await respuesta.json();
		if (!respuesta.ok) {
			alert(`No se pudo rechazar: ${JSON.stringify(datos.detail || datos.error || datos)}`);
			return;
		}
		location.reload();
	}
</script>
