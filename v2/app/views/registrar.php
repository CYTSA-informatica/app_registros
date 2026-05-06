<div class="card shadow-sm">
  <div class="card-body">
    <h2 class="h5"><?= $registerForm['id'] ? 'Editar registro' : 'Nuevo registro' ?></h2>
    <form method="post" class="row g-2 g-md-3">
      <input type="hidden" name="action" value="<?= $registerForm['id'] ? 'update_register' : 'create_register' ?>">
      <?php if ($registerForm['id']): ?>
        <input type="hidden" name="id" value="<?= (int) $registerForm['id'] ?>">
        <input type="hidden" name="id_empleado" value="<?= (int) $registerForm['id_empleado'] ?>">
      <?php endif; ?>
      <div class="col-12 col-sm-6 col-lg-3">
        <label class="form-label">Duracion (h)</label>
        <input class="form-control" type="number" name="duracion" value="<?= htmlspecialchars((string) $registerForm['duracion'], ENT_QUOTES, 'UTF-8') ?>" required>
      </div>
      <div class="col-12 col-sm-6 col-lg-4">
        <label class="form-label">Cliente</label>
        <select class="form-select" name="id_cliente" required>
          <option value="">Selecciona...</option>
          <?php foreach ($clients as $client): ?>
            <option value="<?= (int) $client['id'] ?>" <?= (string) $registerForm['id_cliente'] === (string) $client['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $client['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <label class="form-label">Estado</label>
        <select class="form-select" name="estado">
          <option value="pendiente" <?= $registerForm['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
          <option value="en_progreso" <?= $registerForm['estado'] === 'en_progreso' ? 'selected' : '' ?>>En progreso</option>
          <option value="completada" <?= $registerForm['estado'] === 'completada' ? 'selected' : '' ?>>Completada</option>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label">Descripción</label>
        <textarea class="form-control" name="descripcion" rows="2" required><?= htmlspecialchars((string) $registerForm['descripcion'], ENT_QUOTES, 'UTF-8') ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Notas</label>
        <textarea class="form-control" name="notas" rows="2"><?= htmlspecialchars((string) $registerForm['notas'], ENT_QUOTES, 'UTF-8') ?></textarea>
      </div>
      <div class="col-12 d-flex gap-2 flex-column flex-sm-row">
        <button class="btn btn-primary flex-grow-1" type="submit"><?= $registerForm['id'] ? 'Actualizar' : 'Guardar' ?></button>
        <?php if ($registerForm['id']): ?>
          <a class="btn btn-outline-secondary flex-grow-1" href="/index.php?view=registrar">Cancelar</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>
