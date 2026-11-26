<?php require_once './src/vistas/head/head.php'; ?>

<div class="col-12 m-auto pt-3 contenedor-fondo" style="height: 100vh">
	<input type="hidden" id="vistaActiva" value="validaciones">

	<div class="d-flex justify-content-between align-items-center m-auto mb-3" style="width: 95%;">
		<h5 class="mb-0">Validaciones clínicas</h5>
		<a class="btn btn-outline-secondary btn-sm" href="/Validaciones/propuestas">Propuestas de conocimiento</a>
	</div>

	<?php if (!empty($error)): ?>
		<div class="alert alert-warning m-auto mb-3" style="width: 95%;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
	<?php endif; ?>

	<div class="caja-contenedor-tabla fondo-tabla p-3 mb-3 m-auto" style="width: 95%;">
		<div class="table table-responsive">
			<table class="table table-striped align-middle">
				<thead>
					<tr>
						<th class="text-dark text-center">Caso</th>
						<th class="text-dark text-center">Confianza</th>
						<th class="text-dark text-center">Especialidad</th>
						<th class="text-dark text-center">Dominio</th>
						<th class="text-dark text-center">Estado</th>
						<th class="text-dark text-center">Creado</th>
						<th class="text-dark text-center">Acciones</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($validaciones)): ?>
						<tr>
							<td colspan="7" class="text-center text-muted py-4">No hay casos pendientes.</td>
						</tr>
					<?php else: ?>
						<?php foreach ($validaciones as $v): ?>
							<?php
								$confianza = (float)($v['confianza_total'] ?? 0);
								$bandaTexto = $v['banda'] ?? ($confianza >= 0.75 ? 'alta' : ($confianza >= 0.5 ? 'media' : 'baja'));
								$coloresBanda = ['alta' => 'success', 'media' => 'warning', 'baja' => 'danger'];
								$banda = $coloresBanda[$bandaTexto] ?? 'secondary';
								$estado = (string)($v['estado'] ?? '');
							?>
							<tr>
								<td class="text-center">#<?= (int)$v['id'] ?><br><small class="text-muted"><?= htmlspecialchars((string)($v['motivo_revision'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small></td>
								<td class="text-center"><span class="badge bg-<?= $banda ?>"><?= htmlspecialchars($bandaTexto, ENT_QUOTES, 'UTF-8') ?> · <?= number_format($confianza, 2) ?></span></td>
								<td class="text-center"><?= htmlspecialchars((string)($v['especialidad'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
								<td class="text-center"><?= htmlspecialchars((string)($v['dominio'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
								<td class="text-center"><?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?></td>
								<td class="text-center"><small><?= htmlspecialchars((string)($v['creada_en'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small></td>
								<td class="text-center">
									<a class="btn btn-sm btn-outline-primary" href="/Validaciones/detalle/<?= (int)$v['id'] ?>">Ver</a>
									<?php if ($estado === 'pendiente'): ?>
										<button type="button" class="btn btn-sm btn-primary" onclick="tomarCaso(<?= (int)$v['id'] ?>)">Tomar</button>
									<?php endif; ?>
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

	async function tomarCaso(id) {
		try {
			const respuesta = await fetch(`/Validaciones/tomar/${id}`, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken() },
				body: '{}'
			});
			const datos = await respuesta.json();
			if (!respuesta.ok) {
				const detalle = datos.detail || datos.error || datos;
				alert(`No se pudo tomar el caso: ${JSON.stringify(detalle)}`);
				location.reload();
				return;
			}
			location.href = `/Validaciones/detalle/${id}`;
		} catch (error) {
			alert('Error de red al tomar el caso.');
		}
	}
</script>
