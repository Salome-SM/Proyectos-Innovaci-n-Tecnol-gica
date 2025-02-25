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
    $logger->info("Deteniendo detección");

    $pidFile = ROOT_PATH . '/tmp/detection_pid.txt';
    
    if (!file_exists($pidFile)) {
        throw new Exception('No hay detección en curso');
    }

    $pid = trim(file_get_contents($pidFile));
    if (empty($pid)) {
        throw new Exception('No se pudo leer el PID del proceso');
    }

    // Crear archivo de señal de detención
    $stopFile = ROOT_PATH . '/tmp/stop_detection.txt';
    file_put_contents($stopFile, date('Y-m-d H:i:s'));
    $logger->debug("Archivo de detención creado", ['pid' => $pid]);

    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    
    if ($isWindows) {
        // Primero intentar terminar normalmente
        exec("taskkill /PID $pid 2>&1", $output, $returnVar);
        
        // Si falla, forzar la terminación
        if ($returnVar !== 0) {
            $logger->debug("Forzando terminación en Windows", ['pid' => $pid]);
            exec("taskkill /F /T /PID $pid 2>&1", $output, $returnVar);
        }
    } else {
        // En sistemas Unix, primero SIGTERM
        exec("kill -15 $pid 2>&1", $output, $returnVar);
        sleep(1);
        // Si sigue vivo, SIGKILL
        exec("kill -9 $pid 2>&1", $output, $returnVar);
    }

    // Esperar a que los archivos de señalización desaparezcan
    $timeout = 10;
    $startTime = time();
    
    while (time() - $startTime < $timeout) {
        if (!file_exists(ROOT_PATH . '/tmp/detection_initialized.txt')) {
            break;
        }
        usleep(100000); // 100ms
    }

    // Limpiar archivos
    $filesToClean = [
        $pidFile,
        $stopFile,
        ROOT_PATH . '/tmp/detection_initialized.txt',
        ROOT_PATH . '/tmp/detection_paused.txt'
    ];

    foreach ($filesToClean as $file) {
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    // En Windows, asegurarse de matar la ventana de detección
    if ($isWindows) {
        exec('taskkill /F /IM "python.exe" /FI "WINDOWTITLE eq Detección en tiempo real" 2>&1');
    }

    $logger->info("Detección detenida exitosamente");
    Response::success(null, 'Detección detenida correctamente');

} catch (Exception $e) {
    $logger->error("Error deteniendo detección", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    Response::error($e->getMessage());
}