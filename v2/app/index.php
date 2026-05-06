<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/bootstrap.php';

$errors = [];
$success = null;

$pdo = app_pdo();
$authController = new AuthController(new UserDAO($pdo));
$dashboardController = new DashboardController(
    new RegisterDAO($pdo),
    new ClientDAO($pdo),
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
$editUserId = isset($_GET['edit_user_id']) ? (int) $_GET['edit_user_id'] : 0;

try {
    $dashboardData = $dashboardController->getDashboardData($user, $editRegisterId, $editClientId, $editUserId);
} catch (Throwable $e) {
    $errors[] = 'Error al cargar datos: ' . $e->getMessage();
    $dashboardData = [
        'clients' => [],
        'users' => [],
        'registers' => [],
        'registerForm' => ['id' => null, 'duracion' => '', 'descripcion' => '', 'estado' => 'pendiente', 'notas' => '', 'id_cliente' => '', 'id_empleado' => (int) $user['id']],
        'clientForm' => ['id' => null, 'nombre' => '', 'email' => '', 'phone' => '', 'address' => '', 'dni' => '', 'pais' => '', 'postal' => '', 'poblacion' => '', 'provincia' => ''],
        'userForm' => ['id' => null, 'username' => '', 'email' => '', 'contra_hash' => '', 'isAdmin' => false],
    ];
}

$clients = $dashboardData['clients'];
$users = $dashboardData['users'];
$registers = $dashboardData['registers'];
$registerForm = $dashboardData['registerForm'];
$clientForm = $dashboardData['clientForm'];
$userForm = $dashboardData['userForm'];

$viewMap = [
    'registrar' => 'registrar.php',
    'misregistros' => 'misregistros.php',
    'clientes' => 'clientes.php',
    'empleados' => 'empleados.php',
];

if (!isset($viewMap[$view])) {
    $view = 'registrar';
}

if (in_array($view, ['clientes', 'empleados'], true) && !(bool) $user['isAdmin']) {
    $view = 'registrar';
}

require __DIR__ . '/views/layout_header.php';
require __DIR__ . '/views/alerts.php';
require __DIR__ . '/views/' . $viewMap[$view];
require __DIR__ . '/views/layout_footer.php';
