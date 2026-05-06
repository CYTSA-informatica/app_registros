<?php

declare(strict_types=1);

/**
 * Cargar variables de entorno desde múltiples fuentes
 * Orden: getenv() > $_ENV > .env file > valores por defecto
 */

function get_env(string $key, ?string $default = null): ?string
{
    // 1. Intentar .env file primero para evitar que variables del hosting lo pisen.
    static $envVariables = null;
    if ($envVariables === null) {
        $envVariables = load_env_file();
    }

    if (isset($envVariables[$key])) {
        return $envVariables[$key];
    }

    // 2. Intentar getenv()
    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }

    // 3. Intentar $_SERVER (Apache SetEnv)
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return (string) $_SERVER[$key];
    }

    // 4. Intentar $_ENV
    if (isset($_ENV[$key])) {
        return $_ENV[$key];
    }

    // 5. Valor por defecto
    return $default;
}

/**
 * Cargar variables desde archivo .env
 */
function load_env_file(): array
{
    $vars = [];

    $candidatePaths = get_env_file_candidates();

    $envPath = null;
    foreach (array_unique($candidatePaths) as $path) {
        if (is_file($path) && is_readable($path)) {
            $envPath = $path;
            break;
        }
    }

    if ($envPath === null) {
        return $vars;
    }

    $raw = file_get_contents($envPath);
    if ($raw === false) {
        return $vars;
    }

    $content = normalize_env_content($raw);
    $lines = preg_split('/\R/u', $content) ?: [];

    foreach ($lines as $line) {
        $line = trim($line);

        // Ignorar líneas vacías y comentarios
        if ($line === '' || $line[0] === '#' || $line[0] === ';') {
            continue;
        }

        // Aceptar formato: export KEY=VALUE
        if (strpos($line, 'export ') === 0) {
            $line = trim(substr($line, 7));
        }

        // Parsear KEY=VALUE tolerando espacios
        if (!preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/', $line, $matches)) {
            continue;
        }

        $key = trim($matches[1]);
        $value = trim($matches[2]);

        if (empty($key)) {
            continue;
        }

        // Remover comillas
        if (strlen($value) >= 2) {
            if (($value[0] === '"' && $value[-1] === '"') || 
                ($value[0] === "'" && $value[-1] === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        // Quitar comentario inline en valores no entrecomillados
        if ($value !== '' && $value[0] !== '"' && $value[0] !== "'") {
            $commentPos = strpos($value, ' #');
            if ($commentPos !== false) {
                $value = rtrim(substr($value, 0, $commentPos));
            }
        }

        $vars[$key] = $value;
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }

    return $vars;
}

/**
 * Normaliza contenido del .env para soportar BOM y UTF-16 en hostings compartidos.
 */
function normalize_env_content(string $raw): string
{
    if (str_starts_with($raw, "\xFF\xFE")) {
        $converted = @iconv('UTF-16LE', 'UTF-8//IGNORE', substr($raw, 2));
        if ($converted !== false) {
            return $converted;
        }
    }

    if (str_starts_with($raw, "\xFE\xFF")) {
        $converted = @iconv('UTF-16BE', 'UTF-8//IGNORE', substr($raw, 2));
        if ($converted !== false) {
            return $converted;
        }
    }

    if (str_starts_with($raw, "\xEF\xBB\xBF")) {
        return substr($raw, 3);
    }

    if (strpos($raw, "\0") !== false) {
        $converted = @iconv('UTF-16', 'UTF-8//IGNORE', $raw);
        if ($converted !== false) {
            return $converted;
        }
    }

    return $raw;
}

/**
 * Obtener rutas candidatas para el archivo .env segun el entorno.
 */
function get_env_file_candidates(): array
{
    $candidatePaths = [
        __DIR__ . '/.env',
        dirname(__DIR__) . '/.env',
    ];

    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $candidatePaths[] = rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/\\') . '/.env';
    }

    if (!empty($_SERVER['SCRIPT_FILENAME'])) {
        $candidatePaths[] = dirname((string) $_SERVER['SCRIPT_FILENAME']) . '/.env';
    }

    $cwd = getcwd();
    if (is_string($cwd) && $cwd !== '') {
        $candidatePaths[] = rtrim($cwd, '/\\') . '/.env';
    }

    return array_values(array_unique($candidatePaths));
}

/**
 * Informacion de diagnostico para saber por que no se carga .env en produccion.
 */
function get_env_debug_info(): string
{
    $parts = [];
    foreach (get_env_file_candidates() as $path) {
        $exists = is_file($path) ? 'si' : 'no';
        $readable = is_readable($path) ? 'si' : 'no';
        $parts[] = $path . ' [existe:' . $exists . ', legible:' . $readable . ']';
    }

    return 'Rutas .env probadas: ' . implode(' | ', $parts);
}
