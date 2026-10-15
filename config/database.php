<?php

// Configuración de la base de datos
// Lee variables de entorno con soporte para getenv(), $_ENV y $_SERVER
// (Compatible con Apache, Nginx, FastCGI, Docker y hostings como Alwaysdata).

if (!function_exists('app_env')) {
    function app_env(string $key, ?string $default = null): ?string {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return (string)$_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
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
