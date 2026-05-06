<div class="mx-auto" style="max-width: 1240px;">
<div class="row g-3 g-lg-4">
  <div class="col-12 col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h5"><?= $clientForm['id'] ? 'Editar cliente' : 'Nuevo cliente' ?></h2>
        <form method="post" class="d-grid gap-2">
          <input type="hidden" name="action" value="<?= $clientForm['id'] ? 'update_client' : 'create_client' ?>">
          <?php if ($clientForm['id']): ?>
            <input type="hidden" name="id" value="<?= (int) $clientForm['id'] ?>">
          <?php endif; ?>
          <input class="form-control" name="nombre" placeholder="Nombre" value="<?= htmlspecialchars((string) $clientForm['nombre'], ENT_QUOTES, 'UTF-8') ?>" required>
          <input class="form-control" name="email" placeholder="Email" value="<?= htmlspecialchars((string) $clientForm['email'], ENT_QUOTES, 'UTF-8') ?>">
          <input class="form-control" name="phone" placeholder="Teléfono" value="<?= htmlspecialchars((string) $clientForm['phone'], ENT_QUOTES, 'UTF-8') ?>">
          <input class="form-control" name="address" placeholder="Dirección" value="<?= htmlspecialchars((string) $clientForm['address'], ENT_QUOTES, 'UTF-8') ?>">
          <input class="form-control" name="dni" placeholder="DNI" value="<?= htmlspecialchars((string) $clientForm['dni'], ENT_QUOTES, 'UTF-8') ?>">
          <input class="form-control" name="pais" placeholder="País" value="<?= htmlspecialchars((string) $clientForm['pais'], ENT_QUOTES, 'UTF-8') ?>">
          <input class="form-control" name="postal" placeholder="CP" value="<?= htmlspecialchars((string) $clientForm['postal'], ENT_QUOTES, 'UTF-8') ?>">
          <input class="form-control" name="poblacion" placeholder="Población" value="<?= htmlspecialchars((string) $clientForm['poblacion'], ENT_QUOTES, 'UTF-8') ?>">
          <input class="form-control" name="provincia" placeholder="Provincia" value="<?= htmlspecialchars((string) $clientForm['provincia'], ENT_QUOTES, 'UTF-8') ?>">
          <div class="d-flex gap-2 flex-column">
            <button class="btn btn-primary" type="submit"><?= $clientForm['id'] ? 'Actualizar' : 'Crear' ?></button>
            <?php if ($clientForm['id']): ?>
              <a class="btn btn-outline-secondary" href="/index.php?view=clientes">Cancelar</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h5">Clientes</h2>
        <?php
        $clientProvincias = [];
        foreach ($clients as $tmpClient) {
            $provincia = trim((string) ($tmpClient['provincia'] ?? ''));
            if ($provincia !== '') {
                $clientProvincias[$provincia] = true;
            }
        }
        $clientProvincias = array_keys($clientProvincias);
        sort($clientProvincias, SORT_NATURAL | SORT_FLAG_CASE);
        ?>
        <form class="row g-2 mb-3" id="clientFilters" onsubmit="return false;">
          <div class="col-12 col-md-7">
            <input type="text" class="form-control" id="clientFilterText" placeholder="Buscar por nombre, email, telefono, dni o direccion">
          </div>
          <div class="col-8 col-md-4">
            <select class="form-select" id="clientFilterProvincia">
              <option value="">Todas las provincias</option>
              <?php foreach ($clientProvincias as $provincia): ?>
                <option value="<?= htmlspecialchars($provincia, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($provincia, ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-4 col-md-1 d-grid">
            <button type="button" class="btn btn-outline-secondary" id="clientFilterReset">Limpiar</button>
          </div>
        </form>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr><th>Nombre</th><th class="d-none-mobile">Email</th><th class="d-none-mobile">Tel.</th><th class="action-cell">Acciones</th></tr>
            </thead>
            <tbody id="clientTableBody">
              <?php foreach ($clients as $c): ?>
                <tr
                  data-search="<?= htmlspecialchars(((string) ($c['nombre'] ?? '')) . ' ' . ((string) ($c['email'] ?? '')) . ' ' . ((string) ($c['phone'] ?? '')) . ' ' . ((string) ($c['dni'] ?? '')) . ' ' . ((string) ($c['address'] ?? '')) . ' ' . ((string) ($c['pais'] ?? '')) . ' ' . ((string) ($c['postal'] ?? '')) . ' ' . ((string) ($c['poblacion'] ?? '')) . ' ' . ((string) ($c['provincia'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                  data-provincia="<?= htmlspecialchars((string) ($c['provincia'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                >
                  <td><?= htmlspecialchars((string) $c['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="d-none-mobile"><small><?= htmlspecialchars((string) $c['email'], ENT_QUOTES, 'UTF-8') ?></small></td>
                  <td class="d-none-mobile"><small><?= htmlspecialchars((string) $c['phone'], ENT_QUOTES, 'UTF-8') ?></small></td>
                  <td class="action-cell">
                    <div class="d-flex gap-1 flex-column flex-sm-row">
                      <a class="btn btn-sm btn-outline-secondary flex-grow-1" href="/index.php?view=clientes&edit_client_id=<?= (int) $c['id'] ?>">✏️ Editar</a>
                      <form method="post" onsubmit="return confirm('¿Eliminar cliente?');" class="flex-grow-1">
                        <input type="hidden" name="action" value="delete_client">
                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
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
  </div>
</div>
</div>

<script>
  (() => {
    const tbody = document.getElementById('clientTableBody');
    if (!tbody) {
      return;
    }

    const textInput = document.getElementById('clientFilterText');
    const provinciaInput = document.getElementById('clientFilterProvincia');
    const resetButton = document.getElementById('clientFilterReset');

    const applyFilters = () => {
      const query = (textInput?.value || '').trim().toLowerCase();
      const provincia = (provinciaInput?.value || '').trim().toLowerCase();

      tbody.querySelectorAll('tr').forEach((row) => {
        const rowSearch = (row.getAttribute('data-search') || '').toLowerCase();
        const rowProvincia = (row.getAttribute('data-provincia') || '').toLowerCase();

        const matchesText = query === '' || rowSearch.includes(query);
        const matchesProvincia = provincia === '' || rowProvincia === provincia;

        row.style.display = matchesText && matchesProvincia ? '' : 'none';
      });
    };

    [textInput, provinciaInput].forEach((el) => {
      if (el) {
        el.addEventListener('input', applyFilters);
        el.addEventListener('change', applyFilters);
      }
    });

    if (resetButton) {
      resetButton.addEventListener('click', () => {
        if (textInput) textInput.value = '';
        if (provinciaInput) provinciaInput.value = '';
        applyFilters();
      });
    }
  })();
</script>
