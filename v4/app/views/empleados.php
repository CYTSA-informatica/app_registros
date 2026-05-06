<div class="mx-auto" style="max-width: 1240px;">
<div class="row g-3 g-lg-4">
  <div class="col-12 col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h5"><?= $userForm['id'] ? 'Editar empleado' : 'Nuevo empleado' ?></h2>
        <form method="post" class="d-grid gap-2">
          <input type="hidden" name="action" value="<?= $userForm['id'] ? 'update_user' : 'create_user' ?>">
          <?php if ($userForm['id']): ?>
            <input type="hidden" name="id" value="<?= (int) $userForm['id'] ?>">
          <?php endif; ?>
          <input class="form-control" name="username" placeholder="Usuario" value="<?= htmlspecialchars((string) $userForm['username'], ENT_QUOTES, 'UTF-8') ?>" required>
          <input class="form-control" type="email" name="email" placeholder="Email" value="<?= htmlspecialchars((string) $userForm['email'], ENT_QUOTES, 'UTF-8') ?>" required>
          <input class="form-control" name="dpto" placeholder="Dpto" value="<?= htmlspecialchars((string) $userForm['dpto'], ENT_QUOTES, 'UTF-8') ?>" required>
          <input class="form-control" type="password" name="contra_hash" placeholder="Contraseña<?= $userForm['id'] ? ' nueva (opcional)' : '' ?>" <?= $userForm['id'] ? '' : 'required' ?>>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="isAdminCreate" name="isAdmin" <?= $userForm['isAdmin'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="isAdminCreate">Administrador</label>
          </div>
          <div class="d-flex gap-2 flex-column">
            <button class="btn btn-primary" type="submit"><?= $userForm['id'] ? 'Actualizar' : 'Crear' ?></button>
            <?php if ($userForm['id']): ?>
              <a class="btn btn-outline-secondary" href="/index.php?view=empleados">Cancelar</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h5">Empleados</h2>
        <form class="row g-2 mb-3" id="employeeFilters" onsubmit="return false;">
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="employeeFilterText" placeholder="Buscar por usuario, email o dpto">
          </div>
          <div class="col-8 col-md-3">
            <select class="form-select" id="employeeFilterRole">
              <option value="">Todos los roles</option>
              <option value="admin">Admin</option>
              <option value="user">User</option>
            </select>
          </div>
          <div class="col-4 col-md-1 d-grid">
            <button type="button" class="btn btn-outline-secondary" id="employeeFilterReset">Limpiar</button>
          </div>
          <div class="col-4 col-md-1 d-grid">
            <button type="button" class="btn btn-outline-primary" id="employeeExport">📥 Exportar</button>
          </div>
        </form>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr><th>Usuario</th><th class="d-none-mobile">Email</th><th>Dpto</th><th>Rol</th><th class="action-cell">Acciones</th></tr>
            </thead>
            <tbody id="employeeTableBody">
              <?php foreach ($users as $u): ?>
                <tr
                  data-id="<?= (int) $u['id'] ?>"
                  data-search="<?= htmlspecialchars(((string) ($u['username'] ?? '')) . ' ' . ((string) ($u['email'] ?? '')) . ' ' . ((string) ($u['dpto'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                  data-role="<?= (int) ($u['isAdmin'] ?? 0) === 1 ? 'admin' : 'user' ?>"
                >
                  <td><?= htmlspecialchars((string) ($u['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="d-none-mobile"><small><?= htmlspecialchars((string) $u['email'], ENT_QUOTES, 'UTF-8') ?></small></td>
                  <td><small><?= htmlspecialchars((string) ($u['dpto'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small></td>
                  <td><?= (int) $u['isAdmin'] === 1 ? '<span class="badge bg-warning">Admin</span>' : '<span class="badge bg-secondary">User</span>' ?></td>
                  <td class="action-cell">
                    <div class="d-flex gap-1 flex-column flex-sm-row">
                      <a class="btn btn-sm btn-outline-secondary flex-grow-1" href="/index.php?view=empleados&edit_user_id=<?= (int) $u['id'] ?>">✏️ Editar</a>
                      <form method="post" onsubmit="return confirm('¿Eliminar empleado?');" class="flex-grow-1">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger w-100" type="submit" <?= (int) $u['id'] === (int) $user['id'] ? 'disabled' : '' ?>>🗑️ Eliminar</button>
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
    const tbody = document.getElementById('employeeTableBody');
    if (!tbody) {
      return;
    }

    const textInput = document.getElementById('employeeFilterText');
    const roleInput = document.getElementById('employeeFilterRole');
    const resetButton = document.getElementById('employeeFilterReset');

    const applyFilters = () => {
      const query = (textInput?.value || '').trim().toLowerCase();
      const role = (roleInput?.value || '').trim().toLowerCase();

      tbody.querySelectorAll('tr').forEach((row) => {
        const rowSearch = (row.getAttribute('data-search') || '').toLowerCase();
        const rowRole = (row.getAttribute('data-role') || '').toLowerCase();

        const matchesText = query === '' || rowSearch.includes(query);
        const matchesRole = role === '' || rowRole === role;

        row.style.display = matchesText && matchesRole ? '' : 'none';
      });
    };

    [textInput, roleInput].forEach((el) => {
      if (el) {
        el.addEventListener('input', applyFilters);
        el.addEventListener('change', applyFilters);
      }
    });

    if (resetButton) {
      resetButton.addEventListener('click', () => {
        if (textInput) textInput.value = '';
        if (roleInput) roleInput.value = '';
        applyFilters();
      });
    }

    // Export functionality - all columns from backend
    XlsxExport.attachExportVisibleButton('#employeeExport', '#employeeTableBody', 'empleados', { tabla: 'users' });
  })();
</script>
