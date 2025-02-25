<?php
// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Definir el directorio de videos procesados
$processedDir = __DIR__ . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'processed';

// Verificar si se proporcionó un nombre de archivo
if (!isset($_GET['file'])) {
    header("HTTP/1.0 400 Bad Request");
    die('Nombre de archivo no proporcionado');
}

// Obtener y sanitizar el nombre del archivo
$fileName = basename($_GET['file']);
$filePath = $processedDir . DIRECTORY_SEPARATOR . $fileName;

// Verificar si el archivo existe
if (!file_exists($filePath) || !is_file($filePath)) {
    error_log("Archivo no encontrado: $filePath");
    header("HTTP/1.0 404 Not Found");
    die('Archivo no encontrado');
}

// Obtener la extensión del archivo
$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Configurar el tipo MIME correcto según la extensión
switch ($extension) {
    case 'mov':
        $contentType = 'video/quicktime';
        break;
    case 'mp4':
        $contentType = 'video/mp4';
        break;
    default:
        $contentType = 'application/octet-stream';
}

// Configurar headers para la descarga
header('Content-Type: ' . $contentType);
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: inline; filename="' . $fileName . '"');
header('Accept-Ranges: bytes');
header('Cache-Control: no-cache');

// Leer y enviar el archivo en bloques
$handle = fopen($filePath, 'rb');
while (!feof($handle)) {
    echo fread($handle, 8192);
    flush();
}
fclose($handle);
exit();