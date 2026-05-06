<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

const REMEMBER_COOKIE_NAME = 'registros_remember';

if (empty(session_id())) {
	session_start();
}

try {
	if (!empty($_COOKIE[REMEMBER_COOKIE_NAME])) {
		$cookieValue = (string) $_COOKIE[REMEMBER_COOKIE_NAME];
		$parts = explode(':', $cookieValue, 2);

		if (count($parts) === 2 && $parts[0] !== '') {
			$pdo = app_pdo();
			$stmt = $pdo->prepare('DELETE FROM remember_tokens WHERE selector = :selector');
			$stmt->execute(['selector' => $parts[0]]);
		}
	}
} catch (Throwable $e) {
	error_log('Logout cleanup failed: ' . $e->getMessage());
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
}
setcookie(REMEMBER_COOKIE_NAME, '', [
	'expires' => time() - 3600,
	'path' => '/',
	'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
	'httponly' => true,
	'samesite' => 'Lax',
]);
session_destroy();

header('Location: /index.php');
exit;
