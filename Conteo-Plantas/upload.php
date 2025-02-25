<?php
// Archivo: upload.php (en la raíz del proyecto)

// Configurar cabeceras
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Incluir el controlador
require_once __DIR__ . '/controllers/UploadController.php';

try {
    $controller = new UploadController();
    $result = $controller->handleUpload();
    
    // Asegurar que la respuesta sea JSON
    if (!is_string($result)) {
        $result = json_encode([
            'success' => true,
            'message' => 'Archivo cargado correctamente'
        ]);
    }
    
    echo $result;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}