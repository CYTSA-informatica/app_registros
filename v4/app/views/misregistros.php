<div class="card shadow-sm">
  <div class="card-body">
    <h2 class="h5">Registros</h2>
    <?php
    $splitNames = static function (string $value): array {
        $parts = preg_split('/\s*,\s*/', trim($value)) ?: [];
        return array_values(array_filter(array_map('trim', $parts), static fn (string $item): bool => $item !== ''));
    };

    $dptoOptions = [];
    $clientOptions = [];
    $providerOptions = [];

    foreach ($registers as $registerOption) {
        $dpto = trim((string) ($registerOption['empleado_dpto'] ?? ''));
        if ($dpto !== '') {
        $dptoOptions[strtolower($dpto)] = $dpto;
        }

        foreach ($splitNames((string) ($registerOption['clientes_nombres'] ?? '')) as $clientOption) {
        $clientOptions[strtolower($clientOption)] = $clientOption;
        }

        foreach ($splitNames((string) ($registerOption['proveedores_nombres'] ?? '')) as $providerOption) {
        $providerOptions[strtolower($providerOption)] = $providerOption;
        }
    }

    uasort($dptoOptions, static fn (string $left, string $right): int => strnatcasecmp($left, $right));
    uasort($clientOptions, static fn (string $left, string $right): int => strnatcasecmp($left, $right));
    uasort($providerOptions, static fn (string $left, string $right): int => strnatcasecmp($left, $right));
    ?>
    <form class="row g-2 mb-3" id="registerFilters" onsubmit="return false;">
      <div class="col-12 col-md-5">
        <input type="text" class="form-control" id="registerFilterText" placeholder="Buscar por empleado o descripcion">
      </div>
      <?php if ((bool) $user['isAdmin']): ?>
      <div class="col-6 col-md-2">
        <button type="button" class="btn btn-outline-secondary w-100" id="registerFilterDptoButton" data-bs-toggle="modal" data-bs-target="#registerFilterDptoModal">Dpto (0)</button>
      </div>
      <?php endif; ?>
      <div class="col-6 col-md-2">
        <button type="button" class="btn btn-outline-secondary w-100" id="registerFilterClientButton" data-bs-toggle="modal" data-bs-target="#registerFilterClientModal">Clientes (0)</button>
      </div>
      <div class="col-6 col-md-2">
        <button type="button" class="btn btn-outline-secondary w-100" id="registerFilterProviderButton" data-bs-toggle="modal" data-bs-target="#registerFilterProviderModal">Proveedores (0)</button>
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
      <div class="col-12 d-flex gap-2 align-items-end">
        <div class="flex-grow-1">
          <label for="registerFilterFechaDesde" class="form-label small mb-1 d-block">Fecha inicio</label>
          <input type="date" class="form-control" id="registerFilterFechaDesde" aria-label="Fecha inicio">
        </div>
        <div class="flex-grow-1">
          <label for="registerFilterFechaHasta" class="form-label small mb-1 d-block">Fecha fin</label>
          <input type="date" class="form-control" id="registerFilterFechaHasta" aria-label="Fecha fin">
        </div>
      </div>
      <div class="col-12 d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="registerFilterReset">Limpiar filtros</button>
        <button type="button" class="btn btn-outline-primary btn-sm" id="registerExport">📥 Exportar</button>
      </div>
    </form>

    <?php if ((bool) $user['isAdmin']): ?>
      <div class="modal fade" id="registerFilterDptoModal" tabindex="-1" aria-labelledby="registerFilterDptoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="registerFilterDptoModalLabel">Filtrar por dpto</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
              <?php if ($dptoOptions === []): ?>
                <p class="text-muted mb-0">No hay departamentos para mostrar.</p>
              <?php else: ?>
                <div class="d-grid gap-2" id="registerFilterDptoOptions">
                  <?php foreach ($dptoOptions as $index => $dptoOption): ?>
                    <label class="form-check border rounded p-2 mb-0">
                      <input class="form-check-input register-filter-option" type="checkbox" value="<?= htmlspecialchars($dptoOption, ENT_QUOTES, 'UTF-8') ?>" data-filter-group="dpto">
                      <span class="form-check-label ms-1"><?= htmlspecialchars($dptoOption, ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary btn-sm" data-filter-clear="dpto">Limpiar</button>
              <button type="button" class="btn btn-primary btn-sm" data-filter-apply="dpto">Aplicar</button>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="modal fade" id="registerFilterClientModal" tabindex="-1" aria-labelledby="registerFilterClientModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="registerFilterClientModalLabel">Filtrar por clientes</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <?php if ($clientOptions === []): ?>
              <p class="text-muted mb-0">No hay clientes para mostrar.</p>
            <?php else: ?>
              <div class="d-grid gap-2" id="registerFilterClientOptions">
                <?php foreach ($clientOptions as $index => $clientOption): ?>
                  <label class="form-check border rounded p-2 mb-0">
                    <input class="form-check-input register-filter-option" type="checkbox" value="<?= htmlspecialchars($clientOption, ENT_QUOTES, 'UTF-8') ?>" data-filter-group="client">
                    <span class="form-check-label ms-1"><?= htmlspecialchars($clientOption, ENT_QUOTES, 'UTF-8') ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-filter-clear="client">Limpiar</button>
            <button type="button" class="btn btn-primary btn-sm" data-filter-apply="client">Aplicar</button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="registerFilterProviderModal" tabindex="-1" aria-labelledby="registerFilterProviderModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="registerFilterProviderModalLabel">Filtrar por proveedores</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <?php if ($providerOptions === []): ?>
              <p class="text-muted mb-0">No hay proveedores para mostrar.</p>
            <?php else: ?>
              <div class="d-grid gap-2" id="registerFilterProviderOptions">
                <?php foreach ($providerOptions as $index => $providerOption): ?>
                  <label class="form-check border rounded p-2 mb-0">
                    <input class="form-check-input register-filter-option" type="checkbox" value="<?= htmlspecialchars($providerOption, ENT_QUOTES, 'UTF-8') ?>" data-filter-group="provider">
                    <span class="form-check-label ms-1"><?= htmlspecialchars($providerOption, ENT_QUOTES, 'UTF-8') ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-filter-clear="provider">Limpiar</button>
            <button type="button" class="btn btn-primary btn-sm" data-filter-apply="provider">Aplicar</button>
          </div>
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-striped table-sm align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th class="d-none-mobile">Empleado</th>
            <th style="width: 16%;">Clientes</th>
            <th class="d-none-mobile" style="width: 14%;">Proveedores</th>
            <th>Dur.</th>
            <th>Estado</th>
            <th class="d-none-mobile">Desc.</th>
            <th class="d-none-mobile">Fecha</th>
            <th class="action-cell">Acciones</th>
          </tr>
        </thead>
        <tbody id="registerTableBody">
          <?php
          $formatDuration = static function (int $totalMinutes): string {
              $hours = intdiv($totalMinutes, 60);
              $minutes = $totalMinutes % 60;

              if ($hours <= 0) {
                  return $minutes . ' min';
              }

              if ($minutes <= 0) {
                  return $hours . ' h';
              }

              return $hours . ' h ' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) . ' min';
          };
          ?>
          <?php foreach ($registers as $r): ?>
            <?php
              $clientesTexto = trim((string) ($r['clientes_nombres'] ?? ''));
              $proveedoresTexto = trim((string) ($r['proveedores_nombres'] ?? ''));
              $clientesLista = implode('|', $splitNames($clientesTexto));
              $proveedoresLista = implode('|', $splitNames($proveedoresTexto));
            ?>
            <tr
              data-id="<?= (int) $r['id'] ?>"
              data-search="<?= htmlspecialchars(((string) ($r['empleado_nombre'] ?? '')) . ' ' . ((string) ($r['descripcion'] ?? '')) . ' ' . ((string) ($r['notas'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
              data-estado="<?= htmlspecialchars((string) ($r['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
              data-duracion="<?= (int) ($r['duracion'] ?? 0) ?>"
              data-fecha="<?= htmlspecialchars(substr((string) ($r['fecha_creacion'] ?? ''), 0, 10), ENT_QUOTES, 'UTF-8') ?>"
              data-dpto="<?= htmlspecialchars((string) ($r['empleado_dpto'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
              data-clientes="<?= htmlspecialchars($clientesLista, ENT_QUOTES, 'UTF-8') ?>"
              data-proveedores="<?= htmlspecialchars($proveedoresLista, ENT_QUOTES, 'UTF-8') ?>"
            >
              <td><?= (int) $r['id'] ?></td>
              <td class="d-none-mobile"><?= htmlspecialchars((string) ($r['empleado_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <?php if ($clientesTexto === ''): ?>
                  <small class="text-muted">-</small>
                <?php else: ?>
                  <small><?= htmlspecialchars($clientesTexto, ENT_QUOTES, 'UTF-8') ?></small>
                <?php endif; ?>
              </td>
              <td class="d-none-mobile">
                <?php if ($proveedoresTexto === ''): ?>
                  <small class="text-muted">-</small>
                <?php else: ?>
                  <small><?= htmlspecialchars($proveedoresTexto, ENT_QUOTES, 'UTF-8') ?></small>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($formatDuration((int) ($r['duracion'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></td>
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
                <td class="d-none-mobile" title="<?= htmlspecialchars((string) $r['descripcion'], ENT_QUOTES, 'UTF-8') ?>"><small><?= substr(htmlspecialchars((string) $r['descripcion'], ENT_QUOTES, 'UTF-8'), 0, 55) ?></small></td>
                <td class="d-none-mobile"><small><?= htmlspecialchars(substr((string) ($r['fecha_creacion'] ?? ''), 0, 10), ENT_QUOTES, 'UTF-8') ?></small></td>
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
    const dptoButton = document.getElementById('registerFilterDptoButton');
    const clientButton = document.getElementById('registerFilterClientButton');
    const providerButton = document.getElementById('registerFilterProviderButton');
    const estadoInput = document.getElementById('registerFilterEstado');
    const durMinInput = document.getElementById('registerFilterDuracionMin');
    const durMaxInput = document.getElementById('registerFilterDuracionMax');
    const fechaDesdeInput = document.getElementById('registerFilterFechaDesde');
    const fechaHastaInput = document.getElementById('registerFilterFechaHasta');
    const resetButton = document.getElementById('registerFilterReset');

    const filterState = {
      dpto: new Set(),
      client: new Set(),
      provider: new Set(),
    };

    const normalize = (value) => (value || '').trim().toLowerCase();
    const splitAttr = (value) => (value || '').split('|').map((item) => normalize(item)).filter(Boolean);

    const setButtonLabel = (button, label, values) => {
      if (!button) {
        return;
      }

      button.textContent = `${label} (${values.size})`;
    };

    const syncButtons = () => {
      setButtonLabel(dptoButton, 'Dpto', filterState.dpto);
      setButtonLabel(clientButton, 'Clientes', filterState.client);
      setButtonLabel(providerButton, 'Proveedores', filterState.provider);
    };

    const syncModalCheckboxes = (group) => {
      const modal = document.getElementById(`registerFilter${group.charAt(0).toUpperCase() + group.slice(1)}Modal`);
      if (!modal) {
        return;
      }

      modal.querySelectorAll('input.register-filter-option[data-filter-group="' + group + '"]').forEach((checkbox) => {
        checkbox.checked = filterState[group].has(normalize(checkbox.value));
      });
    };

    const collectModalValues = (group) => {
      const modal = document.getElementById(`registerFilter${group.charAt(0).toUpperCase() + group.slice(1)}Modal`);
      if (!modal) {
        return [];
      }

      return Array.from(modal.querySelectorAll('input.register-filter-option[data-filter-group="' + group + '"]:checked'))
        .map((checkbox) => normalize(checkbox.value))
        .filter(Boolean);
    };

    const clearFilterGroup = (group) => {
      filterState[group].clear();
      syncButtons();
      syncModalCheckboxes(group);
      applyFilters();
    };

    const applyFilters = () => {
      const query = (textInput?.value || '').trim().toLowerCase();
      const selectedDptos = Array.from(filterState.dpto);
      const selectedClients = Array.from(filterState.client);
      const selectedProviders = Array.from(filterState.provider);
      const estado = (estadoInput?.value || '').trim().toLowerCase();
      const durMin = durMinInput?.value !== '' ? Number(durMinInput.value) : null;
      const durMax = durMaxInput?.value !== '' ? Number(durMaxInput.value) : null;
      const fechaDesde = (fechaDesdeInput?.value || '').trim();
      const fechaHasta = (fechaHastaInput?.value || '').trim();

      tbody.querySelectorAll('tr').forEach((row) => {
        const rowSearch = (row.getAttribute('data-search') || '').toLowerCase();
        const rowDpto = (row.getAttribute('data-dpto') || '').toLowerCase();
        const rowClients = splitAttr(row.getAttribute('data-clientes') || '');
        const rowProviders = splitAttr(row.getAttribute('data-proveedores') || '');
        const rowEstado = (row.getAttribute('data-estado') || '').toLowerCase();
        const rowDuracion = Number(row.getAttribute('data-duracion') || '0');
        const rowFecha = (row.getAttribute('data-fecha') || '').trim();

        const matchesText = query === '' || rowSearch.includes(query);
        const matchesDpto = selectedDptos.length === 0 || selectedDptos.includes(rowDpto);
        const matchesClient = selectedClients.length === 0 || selectedClients.some((client) => rowClients.includes(client));
        const matchesProvider = selectedProviders.length === 0 || selectedProviders.some((provider) => rowProviders.includes(provider));
        const matchesEstado = estado === '' || rowEstado === estado;
        const matchesDurMin = durMin === null || rowDuracion >= durMin;
        const matchesDurMax = durMax === null || rowDuracion <= durMax;
        const matchesFechaDesde = fechaDesde === '' || rowFecha >= fechaDesde;
        const matchesFechaHasta = fechaHasta === '' || rowFecha <= fechaHasta;

        row.style.display = matchesText && matchesDpto && matchesClient && matchesProvider && matchesEstado && matchesDurMin && matchesDurMax && matchesFechaDesde && matchesFechaHasta ? '' : 'none';
      });
    };

    [textInput, estadoInput, durMinInput, durMaxInput, fechaDesdeInput, fechaHastaInput].forEach((el) => {
      if (el) {
        el.addEventListener('input', applyFilters);
        el.addEventListener('change', applyFilters);
      }
    });

    [dptoButton, clientButton, providerButton].forEach((button) => {
      if (button) {
        button.addEventListener('click', () => {
          const group = button.id === 'registerFilterDptoButton' ? 'dpto' : button.id === 'registerFilterClientButton' ? 'client' : 'provider';
          syncModalCheckboxes(group);
        });
      }
    });

    document.querySelectorAll('[data-filter-apply]').forEach((button) => {
      button.addEventListener('click', () => {
        const group = button.getAttribute('data-filter-apply');
        if (!group) {
          return;
        }

        filterState[group] = new Set(collectModalValues(group));
        syncButtons();
        applyFilters();

        const modalElement = document.getElementById(`registerFilter${group.charAt(0).toUpperCase() + group.slice(1)}Modal`);
        if (modalElement) {
          const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
          modal.hide();
        }
      });
    });

    document.querySelectorAll('[data-filter-clear]').forEach((button) => {
      button.addEventListener('click', () => {
        const group = button.getAttribute('data-filter-clear');
        if (!group) {
          return;
        }

        clearFilterGroup(group);

        const modalElement = document.getElementById(`registerFilter${group.charAt(0).toUpperCase() + group.slice(1)}Modal`);
        if (modalElement) {
          modalElement.querySelectorAll('input.register-filter-option[data-filter-group="' + group + '"]').forEach((checkbox) => {
            checkbox.checked = false;
          });
        }
      });
    });

    ['dpto', 'client', 'provider'].forEach((group) => {
      const modalElement = document.getElementById(`registerFilter${group.charAt(0).toUpperCase() + group.slice(1)}Modal`);
      if (modalElement) {
        modalElement.addEventListener('show.bs.modal', () => syncModalCheckboxes(group));
      }
    });

    if (resetButton) {
      resetButton.addEventListener('click', () => {
        if (textInput) textInput.value = '';
        filterState.dpto.clear();
        filterState.client.clear();
        filterState.provider.clear();
        if (estadoInput) estadoInput.value = '';
        if (durMinInput) durMinInput.value = '';
        if (durMaxInput) durMaxInput.value = '';
        if (fechaDesdeInput) fechaDesdeInput.value = '';
        if (fechaHastaInput) fechaHastaInput.value = '';
        syncButtons();
        syncModalCheckboxes('dpto');
        syncModalCheckboxes('client');
        syncModalCheckboxes('provider');
        applyFilters();
      });
    }

    syncButtons();
    applyFilters();

    // Export functionality - all columns from backend
    XlsxExport.attachExportVisibleButton('#registerExport', '#registerTableBody', 'registros', { tabla: 'registers' });
  })();
</script>
