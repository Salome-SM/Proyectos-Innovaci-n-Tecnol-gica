<?php
// Archivo: controllers/UploadController.php
class UploadController {
    private $config;
    private $allowedExtensions = ['mov', 'mp4'];
    
    public function __construct() {
        $this->config = require __DIR__ . '/../config/app.php';
    }

    public function handleUpload() {
        try {
            if (!isset($_FILES['file'])) {
                throw new Exception('No se recibió ningún archivo');
            }

            $chunk = isset($_POST["chunk"]) ? intval($_POST["chunk"]) : 0;
            $chunks = isset($_POST["chunks"]) ? intval($_POST["chunks"]) : 0;
            
            // Obtener el nombre original del archivo
            $originalName = isset($_POST['name']) ? $_POST['name'] : $_FILES['file']['name'];
            
            // Validar y procesar el chunk
            $this->validateUpload($_FILES['file'], $originalName);
            $this->processChunk($_FILES['file'], $originalName, $chunk, $chunks);
            
            return json_encode([
                'success' => true,
                'message' => 'Chunk procesado correctamente',
                'chunk' => $chunk,
                'chunks' => $chunks,
                'filename' => $originalName
            ]);

        } catch (Exception $e) {
            return json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function validateUpload($file, $fileName) {
        // Verificar errores de subida
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error en la subida del archivo: ' . $this->getUploadErrorMessage($file['error']));
        }

        // Validar extensión
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            throw new Exception('Extensión de archivo no permitida: ' . $extension);
        }

        // Verificar tamaño
        if ($file['size'] > $this->config['max_file_size']) {
            throw new Exception('El archivo excede el tamaño máximo permitido');
        }
    }

    private function processChunk($file, $fileName, $chunk, $chunks) {
        $uploadDir = $this->config['upload_dir'];
        $filePath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
        
        if ($chunk === 0) {
            // Para el primer chunk, abrir en modo binario write
            $out = fopen($filePath, 'wb');
        } else {
            // Para chunks subsecuentes, abrir en modo binario append
            $out = fopen($filePath, 'ab');
        }
        
        if (!$out) {
            throw new Exception('No se pudo abrir el archivo para escritura');
        }
        
        // Leer el chunk en modo binario
        $in = fopen($file['tmp_name'], 'rb');
        if (!$in) {
            fclose($out);
            throw new Exception('Error al leer el chunk');
        }
        
        // Copiar sin modificar los datos binarios
        while (!feof($in)) {
            fwrite($out, fread($in, 8192));
        }
        
        fclose($in);
        fclose($out);
        
        // Verificar integridad del archivo después de escribir el último chunk
        if ($chunk == $chunks - 1) {
            if (!$this->verifyFileIntegrity($filePath)) {
                throw new Exception('Error en la integridad del archivo');
            }
        }
    }

    private function verifyFileIntegrity($filePath) {
        // Verifica que el archivo se pueda abrir y leer
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }
        fclose($handle);
        
        // Verifica el tamaño mínimo
        if (filesize($filePath) < 1024) { // 1KB mínimo
            return false;
        }
        
        return true;
    }


    private function getUploadErrorMessage($error) {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                return 'El archivo excede el tamaño máximo permitido por PHP';
            case UPLOAD_ERR_FORM_SIZE:
                return 'El archivo excede el tamaño máximo permitido por el formulario';
            case UPLOAD_ERR_PARTIAL:
                return 'El archivo solo fue subido parcialmente';
            case UPLOAD_ERR_NO_FILE:
                return 'No se seleccionó ningún archivo';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Falta la carpeta temporal';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Error al escribir el archivo en el disco';
            case UPLOAD_ERR_EXTENSION:
                return 'Una extensión de PHP detuvo la subida del archivo';
            default:
                return 'Error desconocido en la subida del archivo';
        }
    }
}