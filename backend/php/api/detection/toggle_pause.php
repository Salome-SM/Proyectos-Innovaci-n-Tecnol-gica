<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Logger.php';

use Utils\Response;
use Utils\Logger;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', [], 405);
    exit;
}

try {
    $logger = Logger::getInstance();
    
    // Verificar que la acción fue proporcionada
    if (!isset($_POST['action'])) {
        throw new Exception('Acción no especificada');
    }

    $action = $_POST['action'];
    
    // Verificar que la detección esté activa
    if (!file_exists(ROOT_PATH . '/tmp/detection_pid.txt')) {
        throw new Exception('No hay detección activa');
    }

    switch ($action) {
        case 'pause':
            $logger->info("Pausando detección");
            
            // Crear archivo de pausa
            if (file_put_contents(ROOT_PATH . '/tmp/detection_paused.txt', date('Y-m-d H:i:s')) === false) {
                throw new Exception('No se pudo crear el archivo de pausa');
            }

            Response::success(null, 'Detección pausada');
            break;

        case 'resume':
            $logger->info("Reanudando detección");
            
            // Verificar si está pausada
            if (!file_exists(ROOT_PATH . '/tmp/detection_paused.txt')) {
                throw new Exception('La detección no está pausada');
            }

            // Eliminar archivo de pausa
            if (!unlink(ROOT_PATH . '/tmp/detection_paused.txt')) {
                throw new Exception('No se pudo eliminar el archivo de pausa');
            }

            Response::success(null, 'Detección reanudada');
            break;

        default:
            throw new Exception('Acción no válida');
    }

} catch (Exception $e) {
    $logger->error("Error en toggle-pause", [
        'action' => $action ?? 'undefined',
        'error' => $e->getMessage()
    ]);

    Response::error($e->getMessage());
}
