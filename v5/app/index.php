<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/bootstrap.php';

$errors = [];
$success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

$pdo = app_pdo();
$authController = new AuthController(new UserDAO($pdo));
$dashboardController = new DashboardController(
    new RegisterDAO($pdo),
    new ClientDAO($pdo),
    new ProviderDAO($pdo),
    new UserDAO($pdo)
);
$loginCandidates = $_SESSION['pending_login_candidates'] ?? [];
$loginIdentifier = '';

if (!empty($_GET['clear_login_selection']) && !current_user()) {
    unset($_SESSION['pending_login_candidates']);
    unset($_SESSION['pending_login_identifier']);
    header('Location: index.php');
    exit;
}

// Endpoint para exportar Excel (ya no se usa - ahora se exporta desde el cliente con filtros applied)
// if (isset($_GET['export']) && $_GET['export'] === 'excel') { ... }
// Mantenido por compatibilidad pero ya no se llama

// Endpoint para exportar con IDs filtrados - devuelve todos los campos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export_by_ids') {
    require_once __DIR__ . '/bootstrap.php';
    $pdo = app_pdo();
    $user = current_user();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'No autenticado']);
        exit;
    }

    $tabla = $_POST['tabla'] ?? '';
    $idsJson = $_POST['ids'] ?? '[]';

    // Decodificar JSON enviado desde JS
    $ids = json_decode($idsJson, true);

    if (!is_array($ids) || $ids === []) {
        echo json_encode(['error' => 'Sin IDs', 'received' => $idsJson]);
        exit;
    }

    // Sanitizar IDs
    $ids = array_map('intval', $ids);
    $ids = array_filter($ids, static fn(int $id): bool => $id > 0);

    if ($ids === []) {
        echo json_encode(['error' => 'IDs inválidos']);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $tablaMap = [
        'registers' => 'registers',
        'clients' => 'clients',
        'providers' => 'providers',
        'users' => 'users',
    ];

    if (!isset($tablaMap[$tabla])) {
        echo json_encode(['error' => 'Tabla inválida: ' . $tabla]);
        exit;
    }

    $tablaNombre = $tablaMap[$tabla];

    try {
        if ($tablaNombre === 'registers') {
            $stmt = $pdo->prepare("
                SELECT 
                    r.id,
                    r.duracion,
                    r.descripcion,
                    r.estado,
                    r.notas,
                    r.fecha_inicio,
                    r.fecha_actualizacion,
                    u.username AS empleado,
                    u.dpto AS empleado_dpto
                FROM {$tablaNombre} r
                LEFT JOIN users u ON u.id = r.id_empleado
                WHERE r.id IN ({$placeholders})
            ");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM {$tablaNombre} WHERE id IN ({$placeholders})");
        }
        $stmt->execute($ids);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Para registros: limpiar columnas para Excel prolijo
        if ($tablaNombre === 'registers' && $rows !== []) {
            foreach ($rows as $index => $row) {
                // 1. Reemplazar id_empleado con el username y renombrar a "empleado"
                if (isset($row['empleado_nombre'])) {
                    $rows[$index]['empleado'] = $row['empleado_nombre'];
                    unset($rows[$index]['id_empleado']);
                    unset($rows[$index]['empleado_nombre']);
                }
                // 2. Quitar id_cliente (dejar solo clientes_nombres)
                if (isset($rows[$index]['id_cliente'])) {
                    unset($rows[$index]['id_cliente']);
                }
                // 3. Quitar columna "nombres" si existe
                if (isset($rows[$index]['nombres'])) {
                    unset($rows[$index]['nombres']);
                }
                // 4. Mantener empleado_dpto (departamento) - NO borrar
            }
        }

        // Para registros, incluir nombres de clientes y proveedores
        if ($tabla === 'registers' && $rows !== []) {
            $registerIds = array_column($rows, 'id');
            $regPlaceholders = implode(',', array_fill(0, count($registerIds), '?'));

            // Clientes por registro
            $stmtClients = $pdo->prepare("
                SELECT rr.register_id, GROUP_CONCAT(c.nombre SEPARATOR ', ') AS clientes
                FROM register_clients rr
                INNER JOIN clients c ON c.id = rr.client_id
                WHERE rr.register_id IN ({$regPlaceholders})
                GROUP BY rr.register_id
            ");
            $stmtClients->execute($registerIds);
            $clientesMap = [];
            while ($row = $stmtClients->fetch(PDO::FETCH_ASSOC)) {
                $clientesMap[$row['register_id']] = $row['clientes'];
            }

            // Proveedores por registro
            $stmtProviders = $pdo->prepare("
                SELECT rr.register_id, GROUP_CONCAT(p.proveedor SEPARATOR ', ') AS proveedores
                FROM register_providers rr
                INNER JOIN providers p ON p.id = rr.provider_id
                WHERE rr.register_id IN ({$regPlaceholders})
                GROUP BY rr.register_id
            ");
            $stmtProviders->execute($registerIds);
            $proveedoresMap = [];
            while ($row = $stmtProviders->fetch(PDO::FETCH_ASSOC)) {
                $proveedoresMap[$row['register_id']] = $row['proveedores'];
            }

            foreach ($rows as $index => $row) {
                $rows[$index]['clientes_nombres'] = $clientesMap[$row['id']] ?? '';
                $rows[$index]['proveedores_nombres'] = $proveedoresMap[$row['id']] ?? '';
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['rows' => $rows]);
        exit;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'login') {
            $loginIdentifier = trim((string) ($_POST['identifier'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $loginStep = (string) ($_POST['login_step'] ?? '');

            if ($loginStep === 'select_user') {
                $selectedUserIds = $_POST['selected_user_ids'] ?? [];
                if (!is_array($selectedUserIds)) {
                    $selectedUserIds = [];
                }

                if (count($selectedUserIds) !== 1) {
                    $errors[] = 'Selecciona solo un usuario para iniciar sesion.';
                } else {
                    $selectedUserId = (int) $selectedUserIds[0];
                    if (!$authController->completeSelectedLogin($selectedUserId, $password)) {
                        $errors[] = 'Contrasena invalida para el usuario seleccionado.';
                    } else {
                        header('Location: index.php?view=registrar');
                        exit;
                    }
                }

                $loginCandidates = $_SESSION['pending_login_candidates'] ?? [];
                if ($loginIdentifier === '') {
                    $loginIdentifier = (string) ($_SESSION['pending_login_identifier'] ?? '');
                }
            } else {
                $loginResult = $authController->login($loginIdentifier, $password);

                if (($loginResult['status'] ?? 'invalid') === 'success') {
                    header('Location: index.php?view=registrar');
                    exit;
                }

                if (($loginResult['status'] ?? 'invalid') === 'select') {
                    $_SESSION['pending_login_identifier'] = $loginIdentifier;
                    $loginCandidates = $loginResult['candidates'] ?? [];
                } else {
                    unset($_SESSION['pending_login_identifier']);
                    $errors[] = 'Credenciales invalidas.';
                }
            }
        }

        if (current_user()) {
            $success = $dashboardController->handleAction($action, $_POST, current_user());
            if ($success !== null) {
                $_SESSION['flash_success'] = $success;

                $redirectView = $_GET['view'] ?? 'registrar';
                $allowedViews = ['registrar', 'misregistros', 'clientes', 'proveedores', 'empleados'];
                if (!in_array($redirectView, $allowedViews, true)) {
                    $redirectView = 'registrar';
                }

                header('Location: index.php?view=' . rawurlencode((string) $redirectView));
                exit;
            }
        }
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

$user = current_user();

if (!$user) {
    $loginCandidates = $_SESSION['pending_login_candidates'] ?? [];
    if ($loginIdentifier === '') {
        $loginIdentifier = (string) ($_SESSION['pending_login_identifier'] ?? '');
    }
    require __DIR__ . '/views/login.php';
    exit;
}

$view = $_GET['view'] ?? 'registrar';
$editRegisterId = isset($_GET['edit_register_id']) ? (int) $_GET['edit_register_id'] : 0;
$editClientId = isset($_GET['edit_client_id']) ? (int) $_GET['edit_client_id'] : 0;
$editProviderId = isset($_GET['edit_provider_id']) ? (int) $_GET['edit_provider_id'] : 0;
$editUserId = isset($_GET['edit_user_id']) ? (int) $_GET['edit_user_id'] : 0;

try {
    $dashboardData = $dashboardController->getDashboardData($user, $editRegisterId, $editClientId, $editProviderId, $editUserId);
} catch (Throwable $e) {
    $errors[] = 'Error al cargar datos: ' . $e->getMessage();
    $dashboardData = [
        'clients' => [],
        'providers' => [],
        'users' => [],
        'registers' => [],
        'registerForm' => ['id' => null, 'duracion' => '', 'duracion_horas' => '', 'duracion_minutos' => '', 'descripcion' => '', 'estado' => 'pendiente', 'notas' => '', 'ids_clientes' => [], 'ids_proveedores' => [], 'id_empleado' => (int) $user['id']],
        'clientForm' => ['id' => null, 'nombre' => '', 'email' => '', 'phone' => '', 'address' => '', 'dni' => '', 'pais' => '', 'postal' => '', 'poblacion' => '', 'provincia' => ''],
        'providerForm' => ['id' => null, 'proveedor' => '', 'telefono' => '', 'contacto' => '', 'movil' => '', 'correo' => '', 'categoria' => ''],
        'userForm' => ['id' => null, 'username' => '', 'email' => '', 'dpto' => '', 'contra_hash' => '', 'isAdmin' => false],
    ];
}

$clients = $dashboardData['clients'];
$providers = $dashboardData['providers'];
$users = $dashboardData['users'];
$registers = $dashboardData['registers'];
$registerForm = $dashboardData['registerForm'];
$clientForm = $dashboardData['clientForm'];
$providerForm = $dashboardData['providerForm'];
$userForm = $dashboardData['userForm'];

$viewMap = [
    'registrar' => 'registrar.php',
    'misregistros' => 'misregistros.php',
    'clientes' => 'clientes.php',
    'proveedores' => 'proveedores.php',
    'empleados' => 'empleados.php',
];

if (!isset($viewMap[$view])) {
    $view = 'registrar';
}

if (in_array($view, ['clientes', 'proveedores', 'empleados'], true) && !(bool) $user['isAdmin']) {
    $view = 'registrar';
}

require __DIR__ . '/views/layout_header.php';
require __DIR__ . '/views/alerts.php';
require __DIR__ . '/views/' . $viewMap[$view];
require __DIR__ . '/views/layout_footer.php';
