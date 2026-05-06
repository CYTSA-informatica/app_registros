<div class="mx-auto" style="max-width: 1240px;">
<div class="row g-3 g-lg-4">
  <div class="col-12 col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h5"><?= $providerForm['id'] ? 'Editar proveedor' : 'Nuevo proveedor' ?></h2>
        <form method="post" class="d-grid gap-2">
          <input type="hidden" name="action" value="<?= $providerForm['id'] ? 'update_provider' : 'create_provider' ?>">
          <?php if ($providerForm['id']): ?>
            <input type="hidden" name="id" value="<?= (int) $providerForm['id'] ?>">
          <?php endif; ?>
          <input class="form-control" name="proveedor" placeholder="Proveedor" value="<?= htmlspecialchars((string) $providerForm['proveedor'], ENT_QUOTES, 'UTF-8') ?>" required>
          <input class="form-control" name="telefono" placeholder="Telefono" value="<?= htmlspecialchars((string) $providerForm['telefono'], ENT_QUOTES, 'UTF-8') ?>">
          <input class="form-control" name="contacto" placeholder="Contacto" value="<?= htmlspecialchars((string) $providerForm['contacto'], ENT_QUOTES, 'UTF-8') ?>">
          <input class="form-control" name="movil" placeholder="Movil" value="<?= htmlspecialchars((string) $providerForm['movil'], ENT_QUOTES, 'UTF-8') ?>">
          <input class="form-control" name="correo" placeholder="Correo" value="<?= htmlspecialchars((string) $providerForm['correo'], ENT_QUOTES, 'UTF-8') ?>">
          <input class="form-control" name="categoria" placeholder="Categoria" value="<?= htmlspecialchars((string) $providerForm['categoria'], ENT_QUOTES, 'UTF-8') ?>">
          <div class="d-flex gap-2 flex-column">
            <button class="btn btn-primary" type="submit"><?= $providerForm['id'] ? 'Actualizar' : 'Crear' ?></button>
            <?php if ($providerForm['id']): ?>
              <a class="btn btn-outline-secondary" href="/index.php?view=proveedores">Cancelar</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h5">Proveedores</h2>
        <?php
        $providerCategorias = [];
        foreach ($providers as $tmpProvider) {
            $categoria = trim((string) ($tmpProvider['categoria'] ?? ''));
            if ($categoria !== '') {
                $providerCategorias[$categoria] = true;
            }
        }
        $providerCategorias = array_keys($providerCategorias);
        sort($providerCategorias, SORT_NATURAL | SORT_FLAG_CASE);
        ?>
        <form class="row g-2 mb-3" id="providerFilters" onsubmit="return false;">
          <div class="col-12 col-md-7">
            <input type="text" class="form-control" id="providerFilterText" placeholder="Buscar por proveedor, contacto, correo, telefono o movil">
          </div>
          <div class="col-8 col-md-4">
            <select class="form-select" id="providerFilterCategoria">
              <option value="">Todas las categorias</option>
              <?php foreach ($providerCategorias as $categoria): ?>
                <option value="<?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-4 col-md-1 d-grid">
            <button type="button" class="btn btn-outline-secondary" id="providerFilterReset">Limpiar</button>
          </div>
        </form>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>Proveedor</th>
                <th class="d-none-mobile">Contacto</th>
                <th class="d-none-mobile">Correo</th>
                <th class="d-none-mobile">Categoria</th>
                <th class="action-cell">Acciones</th>
              </tr>
            </thead>
            <tbody id="providerTableBody">
              <?php foreach ($providers as $p): ?>
                <tr
                  data-search="<?= htmlspecialchars(((string) ($p['proveedor'] ?? '')) . ' ' . ((string) ($p['telefono'] ?? '')) . ' ' . ((string) ($p['contacto'] ?? '')) . ' ' . ((string) ($p['movil'] ?? '')) . ' ' . ((string) ($p['correo'] ?? '')) . ' ' . ((string) ($p['categoria'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                  data-categoria="<?= htmlspecialchars((string) ($p['categoria'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                >
                  <td><?= htmlspecialchars((string) ($p['proveedor'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="d-none-mobile"><small><?= htmlspecialchars((string) ($p['contacto'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small></td>
                  <td class="d-none-mobile"><small><?= htmlspecialchars((string) ($p['correo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small></td>
                  <td class="d-none-mobile"><small><?= htmlspecialchars((string) ($p['categoria'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small></td>
                  <td class="action-cell">
                    <div class="d-flex gap-1 flex-column flex-sm-row">
                      <a class="btn btn-sm btn-outline-secondary flex-grow-1" href="/index.php?view=proveedores&edit_provider_id=<?= (int) $p['id'] ?>">✏️ Editar</a>
                      <form method="post" onsubmit="return confirm('¿Eliminar proveedor?');" class="flex-grow-1">
                        <input type="hidden" name="action" value="delete_provider">
                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
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
    const tbody = document.getElementById('providerTableBody');
    if (!tbody) {
      return;
    }

    const textInput = document.getElementById('providerFilterText');
    const categoriaInput = document.getElementById('providerFilterCategoria');
    const resetButton = document.getElementById('providerFilterReset');

    const applyFilters = () => {
      const query = (textInput?.value || '').trim().toLowerCase();
      const categoria = (categoriaInput?.value || '').trim().toLowerCase();

      tbody.querySelectorAll('tr').forEach((row) => {
        const rowSearch = (row.getAttribute('data-search') || '').toLowerCase();
        const rowCategoria = (row.getAttribute('data-categoria') || '').toLowerCase();

        const matchesText = query === '' || rowSearch.includes(query);
        const matchesCategoria = categoria === '' || rowCategoria === categoria;

        row.style.display = matchesText && matchesCategoria ? '' : 'none';
      });
    };

    [textInput, categoriaInput].forEach((el) => {
      if (el) {
        el.addEventListener('input', applyFilters);
        el.addEventListener('change', applyFilters);
      }
    });

    if (resetButton) {
      resetButton.addEventListener('click', () => {
        if (textInput) textInput.value = '';
        if (categoriaInput) categoriaInput.value = '';
        applyFilters();
      });
    }
  })();
</script>
