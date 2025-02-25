<?php
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Logger.php';

use Utils\Response;
use Utils\Logger;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', [], 405);
}

try {
    $logger = Logger::getInstance();
    $logger->warning("Iniciando detención forzada de la detección");

    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    
    // Matar todos los procesos de Python
    if ($isWindows) {
        $logger->debug("Ejecutando kill en Windows");
        // Matar todos los procesos de Python
        exec('taskkill /F /IM python.exe /T 2>&1', $output);
        // Matar específicamente la ventana de detección
        exec('taskkill /F /IM "python.exe" /FI "WINDOWTITLE eq Detección en tiempo real" 2>&1');
        
        $logger->debug("Resultado del kill", ['output' => $output]);
    } else {
        $logger->debug("Ejecutando kill en Unix");
        exec('pkill -9 -f python', $output);
        $logger->debug("Resultado del kill", ['output' => $output]);
    }

    // Limpiar todos los archivos de control posibles
    $filesToClean = [
        PID_FILE,
        STOP_FILE,
        INIT_FILE,
        DATA_PATH . '/video_active.txt',
        DATA_PATH . '/window_active.txt',
        PAUSE_FILE
    ];

    foreach ($filesToClean as $file) {
        if (file_exists($file)) {
            $logger->debug("Eliminando archivo", ['file' => $file]);
            @unlink($file);
        }
    }

    $logger->info("Detención forzada completada exitosamente");
    Response::success(null, 'Todos los procesos de detección han sido terminados forzadamente.');

} catch (Exception $e) {
    $logger->error("Error en detención forzada", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    Response::error('Error en detención forzada: ' . $e->getMessage());
}