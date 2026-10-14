<?php

// Configuración de la base de datos
// Lee variables de entorno si existen (útil para el despliegue en la nube)
// o utiliza los valores por defecto de XAMPP local.

return [
    'host'    => getenv('DB_HOST') ?: '127.0.0.1',
    'port'    => getenv('DB_PORT') ?: '3306',
    'dbname'  => getenv('DB_NAME') ?: 'reservas_db',
    'user'    => getenv('DB_USER') ?: 'root',
    'pass'    => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
    'charset' => 'utf8mb4'
];
