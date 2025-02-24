<?php
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Logger.php';

use Utils\Response;
use Utils\Logger;

try {
    $logger = Logger::getInstance();
    
    // Manejar las diferentes operaciones según el método HTTP
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGetAssignments();
            break;
        case 'POST':
            handleSaveAssignments();
            break;
        default:
            Response::error('Método no permitido', [], 405);
    }

} catch (Exception $e) {
    $logger->error("Error en manejo de asignaciones", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    Response::error($e->getMessage());
}

/**
 * Maneja la obtención de asignaciones existentes
 */
function handleGetAssignments() {
    $logger = Logger::getInstance();
    $logger->info("Obteniendo asignaciones actuales");

    $assignments = readCurrentAssignments();
    
    Response::success($assignments, 'Asignaciones obtenidas exitosamente');
}

/**
 * Maneja el guardado de nuevas asignaciones
 */
function handleSaveAssignments() {
    $logger = Logger::getInstance();
    $logger->info("Procesando guardado de asignaciones");

    // Validar entrada
    if (!isset($_POST['assignments'])) {
        throw new Exception('No se recibieron asignaciones');
    }

    $assignments = json_decode($_POST['assignments'], true);
    if ($assignments === null) {
        throw new Exception('Error al decodificar asignaciones: ' . json_last_error_msg());
    }

    // Validar asignaciones
    validateAssignments($assignments);

    // Guardar asignaciones
    if (!saveAssignments($assignments)) {
        throw new Exception('Error al guardar las asignaciones');
    }

    Response::success(null, 'Asignaciones guardadas exitosamente');
}

/**
 * Lee las asignaciones actuales del archivo
 * @return array Asignaciones actuales
 */
function readCurrentAssignments() {
    if (!file_exists(CLASS_NAMES_FILE)) {
        return [];
    }

    $assignments = [];
    $lines = file(CLASS_NAMES_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            list($class, $name) = explode('=', $line);
            $assignments[trim($class)] = trim($name);
        }
    }

    return $assignments;
}

/**
 * Valida las asignaciones recibidas
 * @param array $assignments Asignaciones a validar
 * @throws Exception si las asignaciones son inválidas
 */
function validateAssignments($assignments) {
    $logger = Logger::getInstance();

    if (!is_array($assignments)) {
        throw new Exception('Las asignaciones deben ser un array');
    }

    $usedNames = [];
    foreach ($assignments as $class => $name) {
        // Validar clase
        if (empty($class)) {
            throw new Exception('Se encontró una clase vacía');
        }

        // Validar nombre
        if (empty($name)) {
            continue; // Permitir desasignar (nombre vacío)
        }

        // Verificar nombres duplicados
        if (isset($usedNames[$name])) {
            throw new Exception('Nombre duplicado encontrado: ' . $name);
        }
        $usedNames[$name] = true;
    }

    $logger->debug("Asignaciones validadas correctamente", [
        'count' => count($assignments)
    ]);
}

/**
 * Guarda las asignaciones en el archivo
 * @param array $assignments Asignaciones a guardar
 * @return bool true si se guardó correctamente
 */
function saveAssignments($assignments) {
    $logger = Logger::getInstance();

    // Crear directorio si no existe
    $dir = dirname(CLASS_NAMES_FILE);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true)) {
            throw new Exception('No se pudo crear el directorio para las asignaciones');
        }
    }

    // Preparar contenido
    $content = '';
    foreach ($assignments as $class => $name) {
        if (!empty($name)) {
            $content .= sprintf("%s=%s\n", $class, $name);
        }
    }

    // Guardar archivo
    if (file_put_contents(CLASS_NAMES_FILE, $content) === false) {
        throw new Exception('Error al escribir el archivo de asignaciones');
    }

    // Verificar permisos
    chmod(CLASS_NAMES_FILE, 0666);

    $logger->info("Asignaciones guardadas exitosamente", [
        'file' => CLASS_NAMES_FILE,
        'count' => count($assignments)
    ]);

    return true;
}
