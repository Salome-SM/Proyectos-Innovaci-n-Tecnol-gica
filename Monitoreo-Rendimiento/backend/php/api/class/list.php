<?php
// Asegurarnos de que no haya salida antes de los headers
ob_start();

require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Logger.php';

use Utils\Response;
use Utils\Logger;

try {
    $logger = Logger::getInstance();
    $logger->info("Obteniendo lista de personas y clases");

    // Solo permitir método GET
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Método no permitido');
    }

    // Verificar que existe el archivo de nombres usando la constante correcta
    if (!file_exists(CLASS_NAMES_FILE)) {
        throw new Exception('Archivo de nombres no encontrado: ' . CLASS_NAMES_FILE);
    }

    // Leer y validar el archivo
    $lines = file(CLASS_NAMES_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        throw new Exception('Error al leer el archivo de nombres');
    }

    $data = [];
    $seenClasses = [];
    $seenNames = [];
    $lineNumber = 0;

    foreach ($lines as $line) {
        $lineNumber++;
        
        // Saltar líneas vacías
        if (empty(trim($line))) {
            continue;
        }

        // Validar formato de la línea
        if (strpos($line, '=') === false) {
            $logger->warning("Línea mal formateada", [
                'line' => $line,
                'line_number' => $lineNumber
            ]);
            continue;
        }

        list($class, $name) = explode('=', $line, 2);
        $class = trim($class);
        $name = trim($name);

        // Validaciones
        if (empty($class) || empty($name)) {
            $logger->warning("Clase o nombre vacío", [
                'line' => $line,
                'line_number' => $lineNumber
            ]);
            continue;
        }

        // Verificar duplicados
        if (isset($seenClasses[$class])) {
            $logger->warning("Clase duplicada encontrada", [
                'class' => $class,
                'line_number' => $lineNumber
            ]);
            continue;
        }

        if (isset($seenNames[$name])) {
            $logger->warning("Nombre duplicado encontrado", [
                'name' => $name,
                'line_number' => $lineNumber
            ]);
            continue;
        }

        // Registrar para verificación de duplicados
        $seenClasses[$class] = true;
        $seenNames[$name] = true;

        // Agregar a la lista de resultados
        $data[] = [
            'class' => $class,
            'name' => $name
        ];
    }

    // Validar que hay datos
    if (empty($data)) {
        throw new Exception('No se encontraron asignaciones válidas');
    }

    // Ordenar por nombre
    usort($data, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });

    $logger->info("Lista de personas obtenida exitosamente", [
        'count' => count($data)
    ]);

    // Limpiar cualquier salida anterior
    ob_clean();
    
    Response::success($data, 'Lista obtenida exitosamente', [
        'total' => count($data),
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    $logger->error("Error obteniendo lista de personas", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    // Limpiar cualquier salida anterior
    ob_clean();

    Response::error($e->getMessage(), [
        'file_exists' => file_exists(CLASS_NAMES_FILE),
        'file_size' => file_exists(CLASS_NAMES_FILE) ? filesize(CLASS_NAMES_FILE) : 0
    ]);
}

function handleGetAssignments() {
    $logger = Logger::getInstance();
    $logger->info("Obteniendo asignaciones actuales");

    // Leer asignaciones existentes
    $assignments = readCurrentAssignments();
    Response::success($assignments, 'Asignaciones obtenidas exitosamente');
}

function handleSaveAssignments() {
    $logger = Logger::getInstance();
    $logger->info("Procesando guardado de asignaciones");

    // Validar entrada
    if (!isset($_POST['assignments'])) {
        throw new Exception('No se recibieron asignaciones');
    }

    // Decodificar asignaciones
    $assignments = json_decode($_POST['assignments'], true);
    if ($assignments === null) {
        throw new Exception('Error al decodificar asignaciones: ' . json_last_error_msg());
    }

    $logger->debug("Asignaciones recibidas", ['assignments' => $assignments]);

    // Leer asignaciones existentes antes de modificar
    $currentAssignments = readCurrentAssignments();
    $logger->debug("Asignaciones actuales", ['current' => $currentAssignments]);

    // Validar asignaciones
    validateAssignments($assignments);

    // Hacer backup del archivo actual
    if (file_exists(CLASS_NAMES_FILE)) {
        $backupFile = CLASS_NAMES_FILE . '.bak';
        copy(CLASS_NAMES_FILE, $backupFile);
        $logger->info("Backup creado en: " . $backupFile);
    }

    // Guardar asignaciones
    if (!saveAssignments($assignments)) {
        throw new Exception('Error al guardar las asignaciones');
    }

    Response::success(null, 'Asignaciones guardadas exitosamente');
}

function readCurrentAssignments() {
    if (!file_exists(CLASS_NAMES_FILE)) {
        return [];
    }

    $assignments = [];
    $lines = file(CLASS_NAMES_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            list($class, $name) = explode('=', $line, 2);
            $assignments[trim($class)] = trim($name);
        }
    }

    return $assignments;
}

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

function saveAssignments($assignments) {
    $logger = Logger::getInstance();

    // Crear directorio si no existe
    $dir = dirname(CLASS_NAMES_FILE);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true)) {
            throw new Exception('No se pudo crear el directorio para las asignaciones');
        }
    }

    // Obtener asignaciones existentes
    $currentAssignments = readCurrentAssignments();

    // Combinar asignaciones existentes con las nuevas
    $finalAssignments = array_merge($currentAssignments, $assignments);

    // Preparar contenido
    $content = '';
    foreach ($finalAssignments as $class => $name) {
        if (!empty($name)) {
            $content .= sprintf("%s=%s\n", trim($class), trim($name));
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
        'count' => count($finalAssignments)
    ]);

    return true;
}