<?php

declare(strict_types=1);

function app_pdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = get_env('DB_HOST');
    $port = get_env('DB_PORT', '3306');
    $name = get_env('DB_NAME');
    $user = get_env('DB_USER');
    $pass = get_env('DB_PASSWORD');

    // Validar que las variables de entorno estén configuradas
    if (empty($host) || empty($name) || empty($user) || empty($pass)) {
        $missing = [];
        if (empty($host)) $missing[] = 'DB_HOST';
        if (empty($name)) $missing[] = 'DB_NAME';
        if (empty($user)) $missing[] = 'DB_USER';
        if (empty($pass)) $missing[] = 'DB_PASSWORD';

        throw new Exception(
            'Error: Variables de entorno de base de datos no configuradas. ' .
            'Faltan: ' . implode(', ', $missing) . '. ' .
            'Crea un archivo .env en la raíz del proyecto o configura las variables de entorno en tu servidor. ' .
            get_env_debug_info()
        );
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    $hostsToTry = [$host];
    if ($host === 'localhost') {
        // En algunos hostings `localhost` y `127.0.0.1` tienen grants distintos.
        $hostsToTry[] = '127.0.0.1';
    }

    $lastError = null;
    foreach (array_unique($hostsToTry) as $candidateHost) {
        $candidateDsn = "mysql:host={$candidateHost};port={$port};dbname={$name};charset=utf8mb4";

        try {
            $pdo = new PDO($candidateDsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            return $pdo;
        } catch (PDOException $e) {
            $lastError = $e;
        }
    }

    $details = [
        'No se pudo conectar a la base de datos.',
        'DB_HOST=' . $host,
        'DB_PORT=' . $port,
        'DB_NAME=' . $name,
        'DB_USER=' . $user,
        get_env_debug_info(),
    ];

    throw new PDOException(implode(' | ', $details), (int) ($lastError?->getCode() ?? 0), $lastError);

    return $pdo;
}
