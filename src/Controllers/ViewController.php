<?php

namespace App\Controllers;

use App\Services\ReservaService;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

/**
 * Controlador para las vistas renderizadas en servidor con Twig.
 * Gestiona el listado y el detalle de reservas con su historial.
 */
class ViewController
{
    private ReservaService $service;
    private Environment $twig;

    public function __construct(ReservaService $service)
    {
        $this->service = $service;

        // Configuramos la ruta a las plantillas Twig
        $loader = new FilesystemLoader(__DIR__ . '/../../templates');

        // Inicializamos el entorno Twig con autoescaping habilitado por seguridad (anti-XSS)
        $this->twig = new Environment($loader, [
            'cache'       => false, // Desactivado para desarrollo
            'autoescape'  => 'html',
            'debug'       => true,
        ]);
    }

    /**
     * Vista principal: listado de reservas con filtros
     */
    public function index(): void
    {
        $filtros = [
            'estado'      => $_GET['estado'] ?? '',
            'fecha_desde' => $_GET['fecha_desde'] ?? '',
            'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
            'search'      => $_GET['search'] ?? '',
        ];

        $reservas = $this->service->listarReservas($filtros);

        header('Content-Type: text/html; charset=utf-8');
        echo $this->twig->render('reservas/index.html.twig', [
            'titulo'   => 'Gestión de Reservas',
            'reservas' => $reservas,
            'filtros'  => $filtros,
        ]);
    }

    /**
     * Vista de detalle: datos de la reserva y su timeline de eventos
     */
    public function detalle(int $id): void
    {
        $data = $this->service->obtenerDetalleReserva($id);

        header('Content-Type: text/html; charset=utf-8');
        if (!$data) {
            http_response_code(404);
            echo $this->twig->render('base.html.twig', [
                'titulo' => 'Reserva no encontrada',
                'error'  => "La reserva con ID #{$id} no existe en el sistema.",
            ]);
            return;
        }

        echo $this->twig->render('reservas/detalle.html.twig', [
            'titulo'  => "Detalle Reserva #{$id}",
            'reserva' => $data['reserva'],
            'eventos' => $data['eventos'],
        ]);
    }
}
