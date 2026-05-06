<?php
$normalizeIds = static function (mixed $rawIds): array {
  if (is_string($rawIds)) {
    $rawIds = explode(',', $rawIds);
  }

  if (!is_array($rawIds)) {
    return [];
  }

  return array_values(
    array_filter(
      array_map('intval', $rawIds),
      static fn(int $id): bool => $id > 0
    )
  );
};

$collectSelectedNames = static function (array $items, string $nameKey, array $selectedIds): array {
  $selectedMap = array_fill_keys($selectedIds, true);
  $names = [];

  foreach ($items as $item) {
    $itemId = (int) ($item['id'] ?? 0);
    if (isset($selectedMap[$itemId])) {
      $names[] = (string) ($item[$nameKey] ?? '');
    }
  }

  return $names;
};

$selectedClientIds = $normalizeIds($registerForm['ids_clientes'] ?? []);
$selectedProviderIds = $normalizeIds($registerForm['ids_proveedores'] ?? []);

$selectedClientNames = $collectSelectedNames($clients, 'nombre', $selectedClientIds);
$selectedProviderNames = $collectSelectedNames($providers, 'proveedor', $selectedProviderIds);

$durationTotal = (int) ($registerForm['duracion'] ?? 0);
$durationHours = (string) ($registerForm['duracion_horas'] ?? (string) intdiv($durationTotal, 60));
$durationMinutes = (string) ($registerForm['duracion_minutos'] ?? (string) ($durationTotal % 60));

$taskDepartments = [];
$taskDefaultsPath = dirname(__DIR__) . '/assets/task_default.json';
if (is_file($taskDefaultsPath) && is_readable($taskDefaultsPath)) {
  $jsonContent = file_get_contents($taskDefaultsPath);
  if ($jsonContent !== false) {
    $decoded = json_decode($jsonContent, true);
    if (is_array($decoded)) {
      $taskDepartments = $decoded;
    }
  }
}
?>

<div class="card shadow-sm">
  <div class="card-body">
    <h2 class="h5"><?= $registerForm['id'] ? 'Editar registro' : 'Nuevo registro' ?></h2>
    <form method="post" class="row g-2 g-md-3" id="registerFormMain">
      <input type="hidden" name="action" value="<?= $registerForm['id'] ? 'update_register' : 'create_register' ?>">
      <?php if ($registerForm['id']): ?>
        <input type="hidden" name="id" value="<?= (int) $registerForm['id'] ?>">
        <input type="hidden" name="id_empleado" value="<?= (int) $registerForm['id_empleado'] ?>">
      <?php endif; ?>
      <input type="hidden" name="duracion" id="registerDurationTotal" value="<?= htmlspecialchars((string) $registerForm['duracion'], ENT_QUOTES, 'UTF-8') ?>">

      <div class="col-12">
        <h3 class="h6 mb-1">Planificacion</h3>
        <small class="text-muted">Tiempo, estado y tarea pregenerada.</small>
      </div>

      <div class="col-12 col-sm-6 col-lg-2">
        <label class="form-label">Horas</label>
        <input class="form-control" type="number" min="0" name="duracion_horas" id="registerDurationHours" value="<?= htmlspecialchars($durationHours, ENT_QUOTES, 'UTF-8') ?>" required>
      </div>
      <div class="col-12 col-sm-6 col-lg-2">
        <label class="form-label">Minutos</label>
        <input class="form-control" type="number" min="0" max="59" name="duracion_minutos" id="registerDurationMinutes" value="<?= htmlspecialchars($durationMinutes, ENT_QUOTES, 'UTF-8') ?>" required>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <label class="form-label d-block">Clientes</label>
        <input type="hidden" id="registerClientIds" name="ids_clientes" value="<?= htmlspecialchars(implode(',', $selectedClientIds), ENT_QUOTES, 'UTF-8') ?>">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#clientsModal">Clientes</button>
          <span class="badge text-bg-secondary" id="selectedClientsCount"><?= count($selectedClientNames) ?> seleccionados</span>
        </div>
        <div class="mt-2" id="selectedClientsList">
          <?php if ($selectedClientNames === []): ?>
            <small class="text-muted">No hay clientes seleccionados.</small>
          <?php else: ?>
            <ul class="list-group list-group-flush">
              <?php foreach ($selectedClientNames as $clientName): ?>
                <li class="list-group-item py-1 px-0 border-0"><?= htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8') ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <label class="form-label d-block">Proveedores</label>
        <input type="hidden" id="registerProviderIds" name="ids_proveedores" value="<?= htmlspecialchars(implode(',', $selectedProviderIds), ENT_QUOTES, 'UTF-8') ?>">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#providersModal">Proveedores</button>
          <span class="badge text-bg-secondary" id="selectedProvidersCount"><?= count($selectedProviderNames) ?> seleccionados</span>
        </div>
        <div class="mt-2" id="selectedProvidersList">
          <?php if ($selectedProviderNames === []): ?>
            <small class="text-muted">No hay proveedores seleccionados.</small>
          <?php else: ?>
            <ul class="list-group list-group-flush">
              <?php foreach ($selectedProviderNames as $providerName): ?>
                <li class="list-group-item py-1 px-0 border-0"><?= htmlspecialchars($providerName, ENT_QUOTES, 'UTF-8') ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-12 col-lg-4">
        <div class="row g-2">
          <div class="col-12 col-sm-6">
            <label class="form-label">Departamento</label>
            <select class="form-select" id="registerTaskDepartment">
              <option value="" selected></option>
              <?php foreach ($taskDepartments as $department): ?>
                <option value="<?= htmlspecialchars((string) ($department['depart'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($department['depart'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-sm-6">
            <label class="form-label">Tarea pregenerada</label>
            <select class="form-select" id="registerTaskPreset" disabled>
              <option value="" selected></option>
            </select>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-2">
        <label class="form-label">Estado</label>
        <select class="form-select" name="estado">
          <option value="pendiente" <?= $registerForm['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
          <option value="en_progreso" <?= $registerForm['estado'] === 'en_progreso' ? 'selected' : '' ?>>En progreso</option>
          <option value="completada" <?= $registerForm['estado'] === 'completada' ? 'selected' : '' ?>>Completada</option>
        </select>
      </div>

      <div class="col-12 mt-2">
        <h3 class="h6 mb-1">Detalle</h3>
        <small class="text-muted">Descripcion principal y notas opcionales.</small>
      </div>

      <div class="col-12">
        <label class="form-label">Descripcion</label>
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

<div class="modal fade" id="clientsModal" tabindex="-1" aria-labelledby="clientsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="clientsModalLabel">Seleccionar clientes</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="text" id="clientSearchInput" class="form-control mb-3" placeholder="Buscar por nombre">
        <div id="clientCheckboxList">
          <?php foreach ($clients as $client): ?>
            <div class="form-check client-option" data-search="<?= htmlspecialchars((string) ($client['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
              <input
                class="form-check-input client-checkbox"
                type="checkbox"
                value="<?= (int) $client['id'] ?>"
                id="client-check-<?= (int) $client['id'] ?>"
                <?= in_array((int) $client['id'], $selectedClientIds, true) ? 'checked' : '' ?>
              >
              <label class="form-check-label" for="client-check-<?= (int) $client['id'] ?>"><?= htmlspecialchars((string) $client['nombre'], ENT_QUOTES, 'UTF-8') ?></label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="saveClientsSelection">Guardar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="providersModal" tabindex="-1" aria-labelledby="providersModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="providersModalLabel">Seleccionar proveedores</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="text" id="providerSearchInput" class="form-control mb-3" placeholder="Buscar por nombre">
        <div id="providerCheckboxList">
          <?php foreach ($providers as $provider): ?>
            <div class="form-check provider-option" data-search="<?= htmlspecialchars((string) ($provider['proveedor'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
              <input
                class="form-check-input provider-checkbox"
                type="checkbox"
                value="<?= (int) $provider['id'] ?>"
                id="provider-check-<?= (int) $provider['id'] ?>"
                <?= in_array((int) $provider['id'], $selectedProviderIds, true) ? 'checked' : '' ?>
              >
              <label class="form-check-label" for="provider-check-<?= (int) $provider['id'] ?>"><?= htmlspecialchars((string) ($provider['proveedor'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="saveProvidersSelection">Guardar</button>
      </div>
    </div>
  </div>
</div>

<script>
  (() => {
    // Main form elements.
    const form = document.getElementById('registerFormMain');
    const clientHiddenInput = document.getElementById('registerClientIds');
    const clientCountBadge = document.getElementById('selectedClientsCount');
    const clientSelectedList = document.getElementById('selectedClientsList');
    const clientSaveButton = document.getElementById('saveClientsSelection');
    const clientSearchInput = document.getElementById('clientSearchInput');
    const clientCheckboxList = document.getElementById('clientCheckboxList');
    const clientsModalEl = document.getElementById('clientsModal');
    const providerHiddenInput = document.getElementById('registerProviderIds');
    const providerCountBadge = document.getElementById('selectedProvidersCount');
    const providerSelectedList = document.getElementById('selectedProvidersList');
    const providerSaveButton = document.getElementById('saveProvidersSelection');
    const providerSearchInput = document.getElementById('providerSearchInput');
    const providerCheckboxList = document.getElementById('providerCheckboxList');
    const providersModalEl = document.getElementById('providersModal');
    const taskDepartmentSelect = document.getElementById('registerTaskDepartment');
    const taskPresetSelect = document.getElementById('registerTaskPreset');
    const descriptionInput = form.querySelector('textarea[name="descripcion"]');
    const durationTotalInput = document.getElementById('registerDurationTotal');
    const durationHoursInput = document.getElementById('registerDurationHours');
    const durationMinutesInput = document.getElementById('registerDurationMinutes');

    if (!form || !clientHiddenInput || !clientCountBadge || !clientSelectedList || !clientSaveButton || !clientSearchInput || !clientCheckboxList || !clientsModalEl || !providerHiddenInput || !providerCountBadge || !providerSelectedList || !providerSaveButton || !providerSearchInput || !providerCheckboxList || !providersModalEl || !taskDepartmentSelect || !taskPresetSelect || !descriptionInput || !durationTotalInput || !durationHoursInput || !durationMinutesInput) {
      return;
    }

    const taskCatalog = <?= json_encode($taskDepartments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const getModal = (modalEl) => {
      if (typeof window.bootstrap === 'undefined' || !window.bootstrap.Modal) {
        return null;
      }
      return window.bootstrap.Modal.getOrCreateInstance(modalEl);
    };

    const escapeHtml = (value) => {
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    };

    const getCheckedItems = (container, itemClass) => {
      return Array.from(container.querySelectorAll(itemClass)).map((input) => {
        const label = container.querySelector(`label[for="${input.id}"]`);
        return {
          id: Number(input.value),
          name: label ? label.textContent.trim() : '',
        };
      }).filter((item) => Number.isFinite(item.id) && item.id > 0);
    };

    const renderSelectedItems = (selectedList, countBadge, items, emptyText) => {
      countBadge.textContent = `${items.length} seleccionados`;
      if (items.length === 0) {
        selectedList.innerHTML = `<small class="text-muted">${emptyText}</small>`;
        return;
      }

      selectedList.innerHTML = `<ul class="list-group list-group-flush">${items
        .map((item) => `<li class="list-group-item py-1 px-0 border-0">${escapeHtml(item.name)}</li>`)
        .join('')}</ul>`;
    };

    const applySearch = (searchInput, optionsContainer, optionClass) => {
      const query = (searchInput.value || '').trim().toLowerCase();
      optionsContainer.querySelectorAll(optionClass).forEach((item) => {
        const rowSearch = (item.getAttribute('data-search') || '').toLowerCase();
        const matchesText = query === '' || rowSearch.includes(query);
        item.style.display = matchesText ? '' : 'none';
      });
    };

    let selectedTaskPrefix = '';
    let baseDescription = descriptionInput.value;

    const renderDescription = () => {
      descriptionInput.value = selectedTaskPrefix === '' ? baseDescription : `${selectedTaskPrefix}: ${baseDescription}`;
    };

    const syncBaseDescription = () => {
      const currentValue = descriptionInput.value;
      if (selectedTaskPrefix !== '') {
        const prefix = `${selectedTaskPrefix}: `;
        baseDescription = currentValue.startsWith(prefix) ? currentValue.slice(prefix.length) : currentValue;
        return;
      }

      baseDescription = currentValue;
    };

    const populateTaskOptions = () => {
      const selectedDepartment = taskDepartmentSelect.value;
      const departmentData = taskCatalog.find((department) => department.depart === selectedDepartment);

      taskPresetSelect.innerHTML = '<option value="" selected></option>';
      taskPresetSelect.disabled = selectedDepartment === '';

      if (!departmentData || !Array.isArray(departmentData.values)) {
        taskPresetSelect.value = '';
        return;
      }

      departmentData.values.forEach((item, index) => {
        const option = document.createElement('option');
        option.value = String(index);
        option.dataset.task = String(item.task || '');
        option.textContent = `${String(item.task || '')}: ${String(item.details || '')}`;
        taskPresetSelect.appendChild(option);
      });

      taskPresetSelect.value = '';
    };

    taskDepartmentSelect.addEventListener('change', () => {
      syncBaseDescription();
      selectedTaskPrefix = '';
      populateTaskOptions();
      renderDescription();
    });

    taskPresetSelect.addEventListener('change', () => {
      syncBaseDescription();
      const selectedOption = taskPresetSelect.options[taskPresetSelect.selectedIndex];
      selectedTaskPrefix = taskPresetSelect.value === '' || !selectedOption ? '' : String(selectedOption.dataset.task || '');
      renderDescription();
    });

    descriptionInput.addEventListener('input', () => {
      syncBaseDescription();
    });

    populateTaskOptions();
    renderDescription();

    clientSearchInput.addEventListener('input', () => applySearch(clientSearchInput, clientCheckboxList, '.client-option'));
    providerSearchInput.addEventListener('input', () => applySearch(providerSearchInput, providerCheckboxList, '.provider-option'));

    clientSaveButton.addEventListener('click', () => {
      const selected = getCheckedItems(clientCheckboxList, '.client-checkbox:checked');
      clientHiddenInput.value = selected.map((item) => item.id).join(',');
      renderSelectedItems(clientSelectedList, clientCountBadge, selected, 'No hay clientes seleccionados.');
      const modal = getModal(clientsModalEl);
      if (modal) {
        modal.hide();
      }
    });

    providerSaveButton.addEventListener('click', () => {
      const selected = getCheckedItems(providerCheckboxList, '.provider-checkbox:checked');
      providerHiddenInput.value = selected.map((item) => item.id).join(',');
      renderSelectedItems(providerSelectedList, providerCountBadge, selected, 'No hay proveedores seleccionados.');
      const modal = getModal(providersModalEl);
      if (modal) {
        modal.hide();
      }
    });

    const syncDuration = () => {
      const hours = Number(durationHoursInput.value || '0');
      const minutes = Number(durationMinutesInput.value || '0');
      const safeHours = Number.isFinite(hours) && hours > 0 ? Math.floor(hours) : 0;
      const safeMinutes = Number.isFinite(minutes) && minutes > 0 ? Math.floor(minutes) : 0;
      durationTotalInput.value = String((safeHours * 60) + safeMinutes);
    };

    durationHoursInput.addEventListener('input', syncDuration);
    durationHoursInput.addEventListener('change', syncDuration);
    durationMinutesInput.addEventListener('input', syncDuration);
    durationMinutesInput.addEventListener('change', syncDuration);
    syncDuration();

    form.addEventListener('submit', () => {
      syncDuration();
    });

    clientsModalEl.addEventListener('shown.bs.modal', () => {
      clientSearchInput.value = '';
      applySearch(clientSearchInput, clientCheckboxList, '.client-option');
      clientSearchInput.focus();
    });

    providersModalEl.addEventListener('shown.bs.modal', () => {
      providerSearchInput.value = '';
      applySearch(providerSearchInput, providerCheckboxList, '.provider-option');
      providerSearchInput.focus();
    });
  })();
</script>
