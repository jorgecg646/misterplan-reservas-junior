<?php

// Front Controller / Enrutador principal de la aplicación
// MisterPlan - Prueba Técnica Junior PHP

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database;
use App\Repositories\ReservaRepository;
use App\Repositories\EventoRepository;
use App\Services\ReservaService;
use App\Controllers\ReservaApiController;
use App\Controllers\ViewController;

// Configuración de visualización de errores (para desarrollo)
ini_set('display_errors', '1');
error_reporting(E_ALL);

// Inicialización de la base de datos y dependencias (Inyección manual y limpia)
try {
    $db = Database::getConnection();
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'  => 'error',
        'message' => 'No se pudo conectar a la base de datos: ' . $e->getMessage()
    ]);
    exit;
}

$reservaRepo   = new ReservaRepository($db);
$eventoRepo    = new EventoRepository($db);
$reservaService = new ReservaService($db, $reservaRepo, $eventoRepo);
$apiController  = new ReservaApiController($reservaService);

// Obtener la URL solicitada y el método HTTP
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Soporte para override de método HTTP (_method en formularios o header X-HTTP-Method-Override)
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
} elseif (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
    $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
}

// -------------------------------------------------------------
// ENRUTAMIENTO DE LA API REST (JSON)
// -------------------------------------------------------------

// 1. Listar reservas (con filtros por query string)
if ($uri === '/api/reservas' && $method === 'GET') {
    $apiController->listar();
    exit;
}

// 2. Crear una nueva reserva
if ($uri === '/api/reservas' && $method === 'POST') {
    $apiController->crear();
    exit;
}

// 3. Cambiar estado de una reserva (/api/reservas/{id}/estado)
if (preg_match('#^/api/reservas/(\d+)/estado$#', $uri, $matches) && in_array($method, ['POST', 'PATCH', 'PUT'])) {
    $id = (int)$matches[1];
    $apiController->cambiarEstado($id);
    exit;
}

// 4. Ver detalle de una reserva (/api/reservas/{id})
if (preg_match('#^/api/reservas/(\d+)$#', $uri, $matches) && $method === 'GET') {
    $id = (int)$matches[1];
    $apiController->detalle($id);
    exit;
}

// -------------------------------------------------------------
// ENRUTAMIENTO DE VISTAS (TWIG)
// -------------------------------------------------------------
if (class_exists(ViewController::class)) {
    $viewController = new ViewController($reservaService);

    // Listado de reservas
    if ($uri === '/' || $uri === '/reservas') {
        $viewController->index();
        exit;
    }

    // Detalle de una reserva
    if (preg_match('#^/reservas/(\d+)$#', $uri, $matches)) {
        $id = (int)$matches[1];
        $viewController->detalle($id);
        exit;
    }
} else {
    // Si aún no hemos creado las vistas Twig, mostramos un mensaje informativo
    if ($uri === '/' || $uri === '/reservas') {
        header('Content-Type: text/html; charset=utf-8');
        echo "<h1>API de Reservas MisterPlan (Activa)</h1>";
        echo "<p>Endpoints disponibles:</p>";
        echo "<ul>";
        echo "<li><a href='/api/reservas'>GET /api/reservas</a> (Listado JSON)</li>";
        echo "<li><a href='/api/reservas/1'>GET /api/reservas/1</a> (Detalle Reserva #1)</li>";
        echo "</ul>";
        exit;
    }
}

// Si ninguna ruta coincide, devolvemos 404
http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'status'  => 'error',
    'message' => 'Ruta no encontrada: ' . $uri
]);
