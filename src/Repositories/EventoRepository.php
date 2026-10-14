<?php

namespace App\Repositories;

use PDO;

/**
 * Repositorio para la tabla 'reserva_evento'.
 * Se encarga exclusivamente del acceso a datos del historial de eventos.
 */
class EventoRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtiene todos los eventos de una reserva ordenados cronológicamente
     */
    public function obtenerPorReservaId(int $reservaId): array
    {
        $sql = "SELECT id, reserva_id, tipo, descripcion, fecha_creacion 
                FROM reserva_evento 
                WHERE reserva_id = :reserva_id 
                ORDER BY fecha_creacion ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':reserva_id', $reservaId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Inserta un nuevo evento asociado a una reserva
     */
    public function crear(int $reservaId, string $tipo, string $descripcion): bool
    {
        $sql = "INSERT INTO reserva_evento (reserva_id, tipo, descripcion, fecha_creacion) 
                VALUES (:reserva_id, :tipo, :descripcion, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':reserva_id', $reservaId, PDO::PARAM_INT);
        $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
        $stmt->bindValue(':descripcion', $descripcion, PDO::PARAM_STR);

        return $stmt->execute();
    }
}
