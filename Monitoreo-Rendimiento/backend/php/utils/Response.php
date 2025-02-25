<?php
namespace Utils;

class Response {
    /**
     * Envía una respuesta JSON exitosa
     * @param mixed $data Los datos a enviar
     * @param string $message Mensaje opcional
     * @param array $extra Datos adicionales
     */
    public static function success($data = null, $message = '', $extra = []) {
        self::clearOutput();
        
        $response = [
            'status' => 'success',
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if (!empty($extra)) {
            $response = array_merge($response, $extra);
        }

        self::send($response);
    }

    /**
     * Envía una respuesta JSON de error
     * @param string $message Mensaje de error
     * @param array $details Detalles adicionales del error
     * @param int $code Código HTTP opcional
     */
    public static function error($message, $details = [], $code = 400) {
        self::clearOutput();
        
        http_response_code($code);
        
        $response = [
            'status' => 'error',
            'message' => $message
        ];

        if (!empty($details)) {
            $response['details'] = $details;
        }

        self::send($response);
    }

    /**
     * Limpia cualquier salida anterior
     */
    private static function clearOutput() {
        // Limpiar cualquier salida previa
        if (ob_get_level()) {
            ob_end_clean();
        }
        // Iniciar nuevo buffer
        ob_start();
    }

    /**
     * Envía una respuesta JSON
     * @param array $data Los datos a enviar
     */
    private static function send($data) {
        // Asegurarse de que no haya salida previa
        if (headers_sent($file, $line)) {
            $logger = Logger::getInstance();
            $logger->error("Headers ya enviados", [
                'file' => $file,
                'line' => $line
            ]);
        }
        
        // Establecer headers
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
        // Enviar respuesta
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}