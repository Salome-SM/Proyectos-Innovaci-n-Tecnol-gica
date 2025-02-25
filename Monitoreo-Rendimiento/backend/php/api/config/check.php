<?php
require_once __DIR__ . '/../../utils/Response.php';

try {
    // Verificar archivo de configuración
    $configFile = dirname(dirname(dirname(__DIR__))) . '/data/config/detection_config.json';
    
    if (!file_exists($configFile)) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'No existe configuración',
            'path' => $configFile
        ]);
        exit;
    }

    // Leer configuración
    $config = json_decode(file_get_contents($configFile), true);
    
    if (!$config) {
        throw new Exception('Error al leer configuración');
    }

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'data' => $config
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}