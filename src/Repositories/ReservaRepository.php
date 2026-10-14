<?php

namespace App\Repositories;

use PDO;

/**
 * Repositorio para la tabla 'reserva'.
 * Centraliza todas las consultas SQL hacia las reservas.
 * Todas las consultas usan sentencias preparadas (Prepared Statements) para prevenir inyecciones SQL.
 */
class ReservaRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtiene el listado de reservas con filtros combinables opcionales
     */
    public function obtenerTodas(array $filtros = []): array
    {
        $sql = "SELECT id, nombre_huesped, email_huesped, nombre_alojamiento, 
                       fecha_entrada, fecha_salida, estado, importe, notas, fecha_creacion 
                FROM reserva 
                WHERE 1=1";

        $params = [];

        // Filtro por estado (pendiente, confirmada, cancelada)
        if (!empty($filtros['estado'])) {
            $sql .= " AND estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        // Filtro por rango de fechas (fecha_desde y fecha_hasta)
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND fecha_entrada >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND fecha_salida <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }

        // Búsqueda por nombre de huésped (coincidencia parcial)
        if (!empty($filtros['search'])) {
            $sql .= " AND nombre_huesped LIKE :search";
            $params[':search'] = '%' . trim($filtros['search']) . '%';
        }

        $sql .= " ORDER BY fecha_creacion DESC";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $clave => $valor) {
            $stmt->bindValue($clave, $valor);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Busca una reserva por su ID
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql = "SELECT id, nombre_huesped, email_huesped, nombre_alojamiento, 
                       fecha_entrada, fecha_salida, estado, importe, notas, fecha_creacion 
                FROM reserva 
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    /**
     * Inserta una nueva reserva en la base de datos y devuelve el ID generado
     */
    public function crear(array $datos): int
    {
        $sql = "INSERT INTO reserva (
                    nombre_huesped, email_huesped, nombre_alojamiento, 
                    fecha_entrada, fecha_salida, estado, importe, notas, fecha_creacion
                ) VALUES (
                    :nombre_huesped, :email_huesped, :nombre_alojamiento, 
                    :fecha_entrada, :fecha_salida, :estado, :importe, :notas, NOW()
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':nombre_huesped', trim($datos['nombre_huesped']));
        $stmt->bindValue(':email_huesped', trim($datos['email_huesped']));
        $stmt->bindValue(':nombre_alojamiento', trim($datos['nombre_alojamiento']));
        $stmt->bindValue(':fecha_entrada', $datos['fecha_entrada']);
        $stmt->bindValue(':fecha_salida', $datos['fecha_salida']);
        $stmt->bindValue(':estado', $datos['estado'] ?? 'pendiente');
        $stmt->bindValue(':importe', (float)$datos['importe']);
        $stmt->bindValue(':notas', !empty($datos['notas']) ? trim($datos['notas']) : null);

        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualiza el estado de una reserva (confirmada o cancelada)
     */
    public function actualizarEstado(int $id, string $nuevoEstado): bool
    {
        $sql = "UPDATE reserva SET estado = :estado WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':estado', $nuevoEstado);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
