<?php

// Configuración de la base de datos
// Soporta variables de entorno del servidor, archivo .env y valores por defecto locales.

// 1. Cargar archivo .env si existe en la raíz
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$nombre, $valor] = explode('=', $line, 2);
            $nombre = trim($nombre);
            $valor = trim($valor, " \t\n\r\0\x0B\"'");
            $_ENV[$nombre] = $valor;
            $_SERVER[$nombre] = $valor;
            putenv("{$nombre}={$valor}");
        }
    }
}

// 2. Función auxiliar para leer variables con fallback
if (!function_exists('app_env')) {
    function app_env(string $key, ?string $default = null): ?string {
        if (!empty($_ENV[$key])) {
            return (string)$_ENV[$key];
        }
        if (!empty($_SERVER[$key])) {
            return (string)$_SERVER[$key];
        }
        $val = getenv($key);
        return ($val !== false && $val !== '') ? (string)$val : $default;
    }
}

return [
    'host'    => app_env('DB_HOST', '127.0.0.1'),
    'port'    => app_env('DB_PORT', '3306'),
    'dbname'  => app_env('DB_NAME', 'reservas_db'),
    'user'    => app_env('DB_USER', 'root'),
    'pass'    => app_env('DB_PASS', ''),
    'charset' => 'utf8mb4'
];
