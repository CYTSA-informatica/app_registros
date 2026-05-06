<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registros - Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script>
    localStorage.removeItem('registros_session');
  </script>
</head>
<body class="bg-light">
  <div class="container py-5" style="max-width: 440px;">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h1 class="h4 mb-3">Registros de tareas</h1>
        <?php foreach ($errors as $error): ?>
          <div class="alert alert-danger py-2"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>
        <?php if (!empty($loginCandidates)): ?>
          <div class="alert alert-warning py-2">
            Ese correo coincide con varios usuarios. Marca uno y valida su contrasena.
          </div>
          <form method="post" class="d-grid gap-2">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="login_step" value="select_user">
            <input type="hidden" name="identifier" value="<?= htmlspecialchars((string) ($loginIdentifier ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <?php foreach ($loginCandidates as $candidate): ?>
              <label class="border rounded p-2 d-flex align-items-start gap-2">
                <input class="form-check-input mt-1 user-selection-checkbox" type="checkbox" name="selected_user_ids[]" value="<?= (int) $candidate['id'] ?>">
                <span>
                  <strong><?= htmlspecialchars((string) ($candidate['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                  <div class="text-muted small"><?= htmlspecialchars((string) ($candidate['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                  <?php if (!empty($candidate['dpto'])): ?>
                    <div class="text-muted small">Dpto: <?= htmlspecialchars((string) $candidate['dpto'], ENT_QUOTES, 'UTF-8') ?></div>
                  <?php endif; ?>
                </span>
              </label>
            <?php endforeach; ?>
            <div class="mb-1">
              <label class="form-label">Contrasena</label>
              <input class="form-control" type="password" name="password" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">Entrar con el usuario seleccionado</button>
            <a class="btn btn-outline-secondary w-100" href="/index.php?clear_login_selection=1">Cambiar usuario o correo</a>
          </form>
          <script>
            (() => {
              const checkboxes = document.querySelectorAll('.user-selection-checkbox');
              checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                  if (!checkbox.checked) {
                    return;
                  }

                  checkboxes.forEach((other) => {
                    if (other !== checkbox) {
                      other.checked = false;
                    }
                  });
                });
              });
            })();
          </script>
        <?php else: ?>
          <form method="post">
            <input type="hidden" name="action" value="login">
            <div class="mb-3">
              <label class="form-label">Usuario o correo</label>
              <input class="form-control" type="text" name="identifier" value="<?= htmlspecialchars((string) ($loginIdentifier ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Contrasena</label>
              <input class="form-control" type="password" name="password" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">Entrar</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>
