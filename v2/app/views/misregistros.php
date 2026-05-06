<div class="card shadow-sm">
  <div class="card-body">
    <h2 class="h5">Registros</h2>
    <form class="row g-2 mb-3" id="registerFilters" onsubmit="return false;">
      <div class="col-12 col-md-5">
        <input type="text" class="form-control" id="registerFilterText" placeholder="Buscar por empleado, cliente, descripcion o notas">
      </div>
      <div class="col-6 col-md-3">
        <select class="form-select" id="registerFilterEstado">
          <option value="">Todos los estados</option>
          <option value="pendiente">Pendiente</option>
          <option value="en_progreso">En progreso</option>
          <option value="completada">Completada</option>
        </select>
      </div>
      <div class="col-3 col-md-2">
        <input type="number" min="0" class="form-control" id="registerFilterDuracionMin" placeholder="Dur. min">
      </div>
      <div class="col-3 col-md-2">
        <input type="number" min="0" class="form-control" id="registerFilterDuracionMax" placeholder="Dur. max">
      </div>
      <div class="col-12">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="registerFilterReset">Limpiar filtros</button>
      </div>
    </form>
    <div class="table-responsive">
      <table class="table table-striped table-sm align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th class="d-none-mobile">Empleado</th>
            <th>Cliente</th>
            <th>Dur.</th>
            <th>Estado</th>
            <th class="d-none-mobile">Desc.</th>
            <th class="d-none-mobile">Notas</th>
            <th class="d-none-mobile">Fecha</th>
            <th class="action-cell">Acciones</th>
          </tr>
        </thead>
        <tbody id="registerTableBody">
          <?php foreach ($registers as $r): ?>
            <tr
              data-search="<?= htmlspecialchars(((string) ($r['empleado_nombre'] ?? '')) . ' ' . ((string) ($r['cliente_nombre'] ?? '')) . ' ' . ((string) ($r['descripcion'] ?? '')) . ' ' . ((string) ($r['notas'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
              data-estado="<?= htmlspecialchars((string) ($r['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
              data-duracion="<?= (int) ($r['duracion'] ?? 0) ?>"
            >
              <td><?= (int) $r['id'] ?></td>
              <td class="d-none-mobile"><?= htmlspecialchars((string) ($r['empleado_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) ($r['cliente_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= (int) $r['duracion'] ?></td>
                <?php
                $estado = (string) ($r['estado'] ?? 'pendiente');
                $estadoBadgeClass = match ($estado) {
                  'pendiente' => 'bg-warning text-dark',
                  'en_progreso' => 'bg-primary',
                  'completada' => 'bg-success',
                  default => 'bg-secondary',
                };
                ?>
                <td><span class="badge <?= $estadoBadgeClass ?>"><?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?></span></td>
              <td class="d-none-mobile" title="<?= htmlspecialchars((string) $r['descripcion'], ENT_QUOTES, 'UTF-8') ?>"><small><?= substr(htmlspecialchars((string) $r['descripcion'], ENT_QUOTES, 'UTF-8'), 0, 20) ?></small></td>
              <td class="d-none-mobile"><small><?= substr(htmlspecialchars((string) $r['notas'], ENT_QUOTES, 'UTF-8'), 0, 15) ?></small></td>
              <td class="d-none-mobile"><?= htmlspecialchars((string) $r['fecha_creacion'], ENT_QUOTES, 'UTF-8') ?></td>
              <td class="action-cell">
                <div class="d-flex gap-1 flex-column flex-sm-row">
                  <a class="btn btn-sm btn-outline-secondary flex-grow-1" href="/index.php?view=registrar&edit_register_id=<?= (int) $r['id'] ?>">✏️ Editar</a>
                  <form method="post" onsubmit="return confirm('¿Eliminar registro?');" class="flex-grow-1">
                    <input type="hidden" name="action" value="delete_register">
                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                    <input type="hidden" name="id_empleado" value="<?= (int) $r['id_empleado'] ?>">
                    <button class="btn btn-sm btn-outline-danger w-100" type="submit">🗑️ Eliminar</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  (() => {
    const tbody = document.getElementById('registerTableBody');
    if (!tbody) {
      return;
    }

    const textInput = document.getElementById('registerFilterText');
    const estadoInput = document.getElementById('registerFilterEstado');
    const durMinInput = document.getElementById('registerFilterDuracionMin');
    const durMaxInput = document.getElementById('registerFilterDuracionMax');
    const resetButton = document.getElementById('registerFilterReset');

    const applyFilters = () => {
      const query = (textInput?.value || '').trim().toLowerCase();
      const estado = (estadoInput?.value || '').trim().toLowerCase();
      const durMin = durMinInput?.value !== '' ? Number(durMinInput.value) : null;
      const durMax = durMaxInput?.value !== '' ? Number(durMaxInput.value) : null;

      tbody.querySelectorAll('tr').forEach((row) => {
        const rowSearch = (row.getAttribute('data-search') || '').toLowerCase();
        const rowEstado = (row.getAttribute('data-estado') || '').toLowerCase();
        const rowDuracion = Number(row.getAttribute('data-duracion') || '0');

        const matchesText = query === '' || rowSearch.includes(query);
        const matchesEstado = estado === '' || rowEstado === estado;
        const matchesDurMin = durMin === null || rowDuracion >= durMin;
        const matchesDurMax = durMax === null || rowDuracion <= durMax;

        row.style.display = matchesText && matchesEstado && matchesDurMin && matchesDurMax ? '' : 'none';
      });
    };

    [textInput, estadoInput, durMinInput, durMaxInput].forEach((el) => {
      if (el) {
        el.addEventListener('input', applyFilters);
        el.addEventListener('change', applyFilters);
      }
    });

    if (resetButton) {
      resetButton.addEventListener('click', () => {
        if (textInput) textInput.value = '';
        if (estadoInput) estadoInput.value = '';
        if (durMinInput) durMinInput.value = '';
        if (durMaxInput) durMaxInput.value = '';
        applyFilters();
      });
    }
  })();
</script>
