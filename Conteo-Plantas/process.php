<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Aumentar límites de tiempo
ini_set('max_execution_time', '1800');    // 30 minutos
ini_set('max_input_time', '1800');        // 30 minutos
set_time_limit(1800);                     // 30 minutos

function debugLog($message) {
    $logFile = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

while (ob_get_level()) {
    ob_end_clean();
}

ob_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

@apache_setenv('no-gzip', 1);
ini_set('zlib.output_compression', 0);
ini_set('implicit_flush', 1);
set_time_limit(0);

function sendSSEMessage($data) {
    $encoded = is_string($data) ? $data : json_encode($data);
    echo "data: " . $encoded . "\n\n";
    
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

try {
    debugLog("Iniciando proceso con parámetros: " . json_encode($_GET));
    
    $controllerPath = __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'ProcessController.php';
    
    if (!file_exists($controllerPath)) {
        throw new Exception('Archivo del controlador no encontrado en: ' . $controllerPath);
    }

    $requiredParams = ['block_number', 'bed_number', 'year_week', 'count_date', 'video_file'];
    $missingParams = [];
    
    foreach ($requiredParams as $param) {
        if (!isset($_GET[$param])) {
            $missingParams[] = $param;
        }
    }
    
    if (!empty($missingParams)) {
        throw new Exception('Parámetros faltantes: ' . implode(', ', $missingParams));
    }

    $processedDir = __DIR__ . '/storage/processed';
    $uploadsDir = __DIR__ . '/storage/uploads';
    
    foreach ([$processedDir, $uploadsDir] as $dir) {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new Exception("No se pudo crear el directorio: $dir");
            }
        }
        if (!is_writable($dir)) {
            throw new Exception("El directorio no tiene permisos de escritura: $dir");
        }
    }

    $videoFile = $_GET['video_file'];
    $videoPath = $uploadsDir . DIRECTORY_SEPARATOR . $videoFile;
    
    debugLog("Buscando archivo en: $videoPath");
    
    if (!file_exists($videoPath)) {
        throw new Exception("Archivo no encontrado: $videoPath");
    }

    require_once $controllerPath;
    
    debugLog("Controlador cargado, creando instancia");
    
    sendSSEMessage(['status' => 'iniciando', 'message' => 'Iniciando procesamiento']);

    $controller = new ProcessController();
    
    debugLog("Controller creado, iniciando procesamiento");
    
    register_shutdown_function(function() {
        debugLog("Conexión cerrada");
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    });

    $controller->processVideo();

} catch (Exception $e) {
    debugLog("Error en process.php: " . $e->getMessage());
    sendSSEMessage(['error' => $e->getMessage()]);
} finally {
    debugLog("Proceso finalizado");
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
}