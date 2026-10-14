<?php

namespace App\Services;

use App\Repositories\ReservaRepository;
use App\Repositories\EventoRepository;
use App\Exceptions\ValidationException;
use PDO;
use Exception;

/**
 * Capa de servicio de Reservas.
 * Contiene la lógica de negocio, reglas de validación y control de transacciones
 * para garantizar que la reserva y sus eventos en el historial siempre se guarden juntos.
 */
class ReservaService
{
    private PDO $db;
    private ReservaRepository $reservaRepo;
    private EventoRepository $eventoRepo;

    public function __construct(PDO $db, ReservaRepository $reservaRepo, EventoRepository $eventoRepo)
    {
        $this->db = $db;
        $this->reservaRepo = $reservaRepo;
        $this->eventoRepo = $eventoRepo;
    }

    /**
     * Devuelve el listado de reservas aplicando los filtros opcionales
     */
    public function listarReservas(array $filtros = []): array
    {
        return $this->reservaRepo->obtenerTodas($filtros);
    }

    /**
     * Obtiene una reserva junto a su historial cronológico de eventos
     */
    public function obtenerDetalleReserva(int $id): ?array
    {
        $reserva = $this->reservaRepo->obtenerPorId($id);
        if (!$reserva) {
            return null;
        }

        $eventos = $this->eventoRepo->obtenerPorReservaId($id);

        return [
            'reserva' => $reserva,
            'eventos' => $eventos
        ];
    }

    /**
     * Valida los datos y crea una nueva reserva junto a su evento inicial de auditoría.
     * Utiliza una transacción para asegurar la atomicidad de la operación.
     */
    public function crearReserva(array $datos): array
    {
        // 1. Validar los datos de entrada
        $this->validarDatosReserva($datos);

        // Estado inicial por defecto
        $datos['estado'] = 'pendiente';

        // 2. Iniciar transacción en la base de datos
        $this->db->beginTransaction();

        try {
            // Guardar la reserva
            $reservaId = $this->reservaRepo->crear($datos);

            // Registrar el evento de creación en el historial
            $this->eventoRepo->crear(
                $reservaId, 
                'creacion', 
                'Reserva creada inicialmente con estado pendiente.'
            );

            // Confirmar transacción
            $this->db->commit();

            return $this->obtenerDetalleReserva($reservaId);
        } catch (Exception $e) {
            // Si algo falla, revertimos cualquier cambio para no dejar datos corruptos
            $this->db->rollBack();
            throw new Exception('Error al guardar la reserva: ' . $e->getMessage());
        }
    }

    /**
     * Cambia el estado de una reserva (confirmar o cancelar) y añade un evento al historial.
     */
    public function cambiarEstado(int $id, string $nuevoEstado, ?string $motivo = null): ?array
    {
        $estadosPermitidos = ['confirmada', 'cancelada'];
        if (!in_array($nuevoEstado, $estadosPermitidos, true)) {
            throw new ValidationException(['estado' => 'El estado debe ser "confirmada" o "cancelada".']);
        }

        $reserva = $this->reservaRepo->obtenerPorId($id);
        if (!$reserva) {
            return null;
        }

        if ($reserva['estado'] === $nuevoEstado) {
            throw new ValidationException(['estado' => "La reserva ya se encuentra en estado '{$nuevoEstado}'."]);
        }

        $this->db->beginTransaction();

        try {
            // Actualizar estado de la reserva
            $this->reservaRepo->actualizarEstado($id, $nuevoEstado);

            // Descripción para el historial de eventos
            $descripcion = ($nuevoEstado === 'confirmada') 
                ? 'Reserva confirmada con éxito.' 
                : 'Reserva cancelada.';

            if (!empty($motivo)) {
                $descripcion .= ' Motivo: ' . trim($motivo);
            }

            // Registrar el evento
            $this->eventoRepo->crear($id, $nuevoEstado, $descripcion);

            $this->db->commit();

            return $this->obtenerDetalleReserva($id);
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Error al cambiar el estado: ' . $e->getMessage());
        }
    }

    /**
     * Valida los campos obligatorios, formato de email y coherencia de fechas.
     */
    private function validarDatosReserva(array $datos): void
    {
        $errores = [];

        // Nombre del huésped
        if (empty($datos['nombre_huesped']) || strlen(trim($datos['nombre_huesped'])) < 3) {
            $errores['nombre_huesped'] = 'El nombre del huésped es obligatorio y debe tener al menos 3 caracteres.';
        }

        // Email del huésped
        if (empty($datos['email_huesped'])) {
            $errores['email_huesped'] = 'El correo electrónico es obligatorio.';
        } elseif (!filter_var($datos['email_huesped'], FILTER_VALIDATE_EMAIL)) {
            $errores['email_huesped'] = 'El formato del correo electrónico no es válido.';
        }

        // Nombre del alojamiento
        if (empty($datos['nombre_alojamiento']) || strlen(trim($datos['nombre_alojamiento'])) < 3) {
            $errores['nombre_alojamiento'] = 'El nombre del alojamiento es obligatorio.';
        }

        // Validación de fechas
        if (empty($datos['fecha_entrada'])) {
            $errores['fecha_entrada'] = 'La fecha de entrada es obligatoria.';
        }
        if (empty($datos['fecha_salida'])) {
            $errores['fecha_salida'] = 'La fecha de salida es obligatoria.';
        }

        if (!empty($datos['fecha_entrada']) && !empty($datos['fecha_salida'])) {
            $fechaEntrada = strtotime($datos['fecha_entrada']);
            $fechaSalida  = strtotime($datos['fecha_salida']);

            if (!$fechaEntrada) {
                $errores['fecha_entrada'] = 'La fecha de entrada no tiene un formato de fecha válido (AAAA-MM-DD).';
            }
            if (!$fechaSalida) {
                $errores['fecha_salida'] = 'La fecha de salida no tiene un formato de fecha válido (AAAA-MM-DD).';
            }

            if ($fechaEntrada && $fechaSalida && $fechaSalida <= $fechaEntrada) {
                $errores['fecha_salida'] = 'La fecha de salida debe ser posterior a la fecha de entrada.';
            }
        }

        // Importe
        if (!isset($datos['importe']) || !is_numeric($datos['importe']) || (float)$datos['importe'] <= 0) {
            $errores['importe'] = 'El importe debe ser un número positivo mayor que cero.';
        }

        if (!empty($errores)) {
            throw new ValidationException($errores);
        }
    }
}
