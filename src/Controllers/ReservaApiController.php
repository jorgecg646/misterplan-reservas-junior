<?php

namespace App\Controllers;

use App\Services\ReservaService;
use App\Exceptions\ValidationException;
use Exception;

/**
 * Controlador de la API REST para la gestión de reservas.
 * Procesa las peticiones entrantes, interactúa con el servicio y devuelve respuestas JSON
 * con los códigos de estado HTTP correctos (200, 201, 400, 404, 422, 500).
 */
class ReservaApiController
{
    private ReservaService $service;

    public function __construct(ReservaService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/reservas
     * Lista reservas con filtros opcionales (estado, fecha_desde, fecha_hasta, search)
     */
    public function listar(): void
    {
        $filtros = [
            'estado'      => $_GET['estado'] ?? '',
            'fecha_desde' => $_GET['fecha_desde'] ?? '',
            'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
            'search'      => $_GET['search'] ?? '',
        ];

        try {
            $reservas = $this->service->listarReservas($filtros);
            $this->responderJson([
                'status' => 'success',
                'total'  => count($reservas),
                'data'   => $reservas
            ], 200);
        } catch (Exception $e) {
            $this->responderJson([
                'status'  => 'error',
                'message' => 'Error al obtener las reservas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reservas/{id}
     * Devuelve el detalle de una reserva y su historial de eventos
     */
    public function detalle(int $id): void
    {
        try {
            $resultado = $this->service->obtenerDetalleReserva($id);

            if (!$resultado) {
                $this->responderJson([
                    'status'  => 'error',
                    'message' => "La reserva con ID {$id} no fue encontrada."
                ], 404);
                return;
            }

            $this->responderJson([
                'status' => 'success',
                'data'   => $resultado
            ], 200);
        } catch (Exception $e) {
            $this->responderJson([
                'status'  => 'error',
                'message' => 'Error al obtener la reserva: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/reservas
     * Crea una nueva reserva
     */
    public function crear(): void
    {
        // Leemos el payload ya sea en formato JSON o Form-Data
        $datos = $this->obtenerPayload();

        try {
            $nuevaReserva = $this->service->crearReserva($datos);

            $this->responderJson([
                'status'  => 'success',
                'message' => 'Reserva creada correctamente.',
                'data'    => $nuevaReserva
            ], 201);
        } catch (ValidationException $e) {
            $this->responderJson([
                'status'  => 'fail',
                'message' => 'Los datos proporcionados no son válidos.',
                'errors'  => $e->getErrores()
            ], 422);
        } catch (Exception $e) {
            $this->responderJson([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PATCH o POST /api/reservas/{id}/estado
     * Cambia el estado de la reserva (confirmar o cancelar)
     */
    public function cambiarEstado(int $id): void
    {
        $datos = $this->obtenerPayload();
        $nuevoEstado = $datos['estado'] ?? '';
        $motivo = $datos['motivo'] ?? null;

        if (empty($nuevoEstado)) {
            $this->responderJson([
                'status'  => 'fail',
                'message' => 'Debe indicar el nuevo estado ("confirmada" o "cancelada").'
            ], 400);
            return;
        }

        try {
            $actualizada = $this->service->cambiarEstado($id, $nuevoEstado, $motivo);

            if (!$actualizada) {
                $this->responderJson([
                    'status'  => 'error',
                    'message' => "La reserva con ID {$id} no existe."
                ], 404);
                return;
            }

            $this->responderJson([
                'status'  => 'success',
                'message' => "Estado de la reserva actualizado a '{$nuevoEstado}'.",
                'data'    => $actualizada
            ], 200);
        } catch (ValidationException $e) {
            $this->responderJson([
                'status'  => 'fail',
                'message' => 'No se pudo cambiar el estado.',
                'errors'  => $e->getErrores()
            ], 422);
        } catch (Exception $e) {
            $this->responderJson([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extrae los datos recibidos (JSON o POST tradicional)
     */
    private function obtenerPayload(): array
    {
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                return $json;
            }
        }

        return $_POST;
    }

    /**
     * Envía una respuesta HTTP en formato JSON con su código de estado
     */
    private function responderJson(array $data, int $codigoHttp): void
    {
        http_response_code($codigoHttp);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
