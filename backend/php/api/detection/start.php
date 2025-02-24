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
    $logger->info("Iniciando script start_detection.php");

    // Verify required files
    $requiredFiles = [
        'Python Script' => MAIN_PYTHON_SCRIPT,
        'Model File' => MODELS_PATH . '/bestC.pt',
        'Config File' => DETECTION_CONFIG_FILE
    ];

    foreach ($requiredFiles as $name => $path) {
        $logger->debug("Verificando $name", ['path' => $path]);
        if (!file_exists($path)) {
            throw new Exception("$name no encontrado: $path");
        }
    }

    // Verify Python installation
    $pythonCommand = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'python' : 'python3';
    exec($pythonCommand . ' --version 2>&1', $pythonOutput, $pythonReturnVar);
    
    $logger->debug("Verificación de Python", [
        'output' => $pythonOutput, 
        'returnVar' => $pythonReturnVar
    ]);

    if ($pythonReturnVar !== 0) {
        throw new Exception('Python no está instalado correctamente');
    }

    // Clean previous control files
    $filesToClean = [PID_FILE, INIT_FILE, STOP_FILE];
    foreach ($filesToClean as $file) {
        if (file_exists($file)) {
            $logger->debug("Limpiando archivo", ['file' => $file]);
            @unlink($file);
        }
    }

    // Set environment variables
    putenv("PYTHONPATH=" . PYTHON_PATH);
    putenv("DETECTION_ROOT=" . ROOT_PATH);

    // Execute Python script
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $command = sprintf(
            'start /B "" %s "%s" > "%s/detection.log" 2>&1',
            $pythonCommand,
            MAIN_PYTHON_SCRIPT,
            LOGS_PATH
        );
        $logger->debug("Ejecutando comando Windows", ['command' => $command]);
        
        $process = popen($command, 'r');
        $success = ($process !== false);
        if ($process) pclose($process);
    } else {
        $command = sprintf(
            '%s "%s" > "%s/detection.log" 2>&1 & echo $!',
            $pythonCommand,
            MAIN_PYTHON_SCRIPT,
            LOGS_PATH
        );
        $logger->debug("Ejecutando comando Unix", ['command' => $command]);
        
        $pid = shell_exec($command);
        $success = !empty($pid);
        if ($success) {
            file_put_contents(PID_FILE, trim($pid));
        }
    }

    if (!$success) {
        throw new Exception('No se pudo iniciar el proceso de Python');
    }

    // Wait for initialization
    $logger->debug("Esperando inicialización");
    $startTime = time();
    $initialized = false;
    $maxWaitTime = 60;

    while (time() - $startTime < $maxWaitTime) {
        if (file_exists(INIT_FILE)) {
            $initialized = true;
            break;
        }

        // Check for errors in the log
        $logFile = LOGS_PATH . '/detection.log';
        if (file_exists($logFile)) {
            $logContent = file_get_contents($logFile);
            if (strpos($logContent, 'Error') !== false || 
                strpos($logContent, 'ERROR') !== false) {
                $logger->error("Error encontrado en log de Python", ['log' => $logContent]);
                throw new Exception('Error en la inicialización de Python');
            }
        }

        usleep(500000); // 500ms
    }

    if (!$initialized) {
        $logContent = file_exists($logFile) ? file_get_contents($logFile) : "No hay archivo de log";
        $logger->error("Timeout en inicialización", ['log' => $logContent]);
        throw new Exception('Timeout en la inicialización');
    }

    $logger->info("Proceso iniciado exitosamente");
    Response::success(null, 'Detección iniciada correctamente', [
        'log_file' => LOGS_PATH . '/detection.log'
    ]);

} catch (Exception $e) {
    $logger->error("Error en start_detection", [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    Response::error($e->getMessage(), [
        'error' => error_get_last(),
        'trace' => $e->getTraceAsString()
    ]);
}