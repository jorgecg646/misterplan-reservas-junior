<?php

namespace App;

use PDO;
use PDOException;

/**
 * Clase para gestionar la conexión a la base de datos con PDO.
 * Utilizamos el patrón Singleton simple para reutilizar la misma conexión en toda la petición.
 */
class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['dbname'],
                $config['charset']
            );

            $options = [
                // Lanzar excepciones si ocurre algún error SQL
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                // Devolver los resultados como arrays asociativos
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Usar sentencias preparadas reales del motor de base de datos (mayor seguridad)
                PDO::ATTR_EMULATE_PREPARES   => false,
                // Forzar codificación UTF-8 en la conexión MySQL
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'",
            ];

            try {
                self::$instance = new PDO($dsn, $config['user'], $config['pass'], $options);
            } catch (PDOException $e) {
                // Mensaje limpio si no se puede conectar
                throw new PDOException('Error al conectar con la base de datos: ' . $e->getMessage(), (int)$e->getCode());
            }
        }

        return self::$instance;
    }
}
