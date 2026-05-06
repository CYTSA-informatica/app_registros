<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registros - Dashboard</title>
  <link rel="icon" href="../assets/registros.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @media (max-width: 576px) {
      .navbar-text { display: none; }
      .container { padding-left: 8px; padding-right: 8px; }
      .btn-sm { padding: 0.35rem 0.5rem; font-size: 0.75rem; }
      .table { font-size: 0.875rem; }
      .table td, .table th { padding: 0.4rem; }
      .d-flex.gap-2 { gap: 0.35rem !important; flex-wrap: wrap; }
      .form-control, .form-select { font-size: 0.9rem; padding: 0.5rem; }
      .card-body { padding: 1rem; }
    }
    @media (max-width: 768px) {
      .col-lg-4, .col-lg-8 { margin-bottom: 1rem; }
    }
    .d-none-mobile { display: none; }
    @media (min-width: 768px) {
      .d-none-mobile { display: table-cell; }
    }
    .action-cell { white-space: normal; }
  </style>
  <script>
    try {
      localStorage.setItem('registros_session', JSON.stringify({
        id: <?= (int) $user['id'] ?>,
        username: <?= json_encode((string) ($user['username'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        email: <?= json_encode((string) $user['email'] ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        isAdmin: <?= (bool) ($user['isAdmin'] ?? false) ? 'true' : 'false' ?>,
        savedAt: new Date().toISOString()
      }));
    } catch (err) {
      console.error('Error saving session to localStorage', err);
    }
   </script>
</head>
<body class="bg-body-tertiary">
<nav class="navbar navbar-expand-md bg-dark navbar-dark">
  <div class="container-fluid">
    <span class="navbar-brand">Registros</span>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <div class="navbar-nav me-auto">
        <a class="nav-link <?= $view === 'registrar' ? 'active' : '' ?>" href="/index.php?view=registrar">Registrar</a>
        <a class="nav-link <?= $view === 'misregistros' ? 'active' : '' ?>" href="/index.php?view=misregistros">Mis registros</a>
        <?php if ((bool) $user['isAdmin']): ?>
          <a class="nav-link <?= $view === 'clientes' ? 'active' : '' ?>" href="/index.php?view=clientes">Clientes</a>
          <a class="nav-link <?= $view === 'proveedores' ? 'active' : '' ?>" href="/index.php?view=proveedores">Proveedores</a>
          <a class="nav-link <?= $view === 'empleados' ? 'active' : '' ?>" href="/index.php?view=empleados">Empleados</a>
        <?php endif; ?>
      </div>
      <span class="navbar-text me-2"><?= htmlspecialchars((string) ($user['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
      <form class="d-inline" action="/logout.php" method="post">
        <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
      </form>
    </div>
  </div>
</nav>

  </script>
