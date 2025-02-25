<?php
// Definir la ruta base del proyecto de forma absoluta
define('PROJECT_ROOT', realpath($_SERVER['DOCUMENT_ROOT'] . '/L_Siembra'));

// Incluir los archivos necesarios usando rutas absolutas
require_once PROJECT_ROOT . '/backend/php/config/paths.php';
require_once PROJECT_ROOT . '/backend/php/utils/Response.php';
require_once PROJECT_ROOT . '/backend/php/utils/Logger.php';

use Utils\Response;
use Utils\Logger;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', [], 405);
}

try {
    $logger = Logger::getInstance();
    $logger->info("Iniciando guardado de configuración");

    // Verificar y decodificar configuración
    if (!isset($_POST['configuration'])) {
        throw new Exception('No se recibió configuración');
    }

    $configuration = json_decode($_POST['configuration'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar la configuración: ' . json_last_error_msg());
    }

    $logger->debug("Configuración recibida", ['config' => $configuration]);

    // Validar configuración
    validateConfiguration($configuration);

    // Crear directorios necesarios
    createRequiredDirectories();

    // Limpiar archivos antiguos
    cleanOldFiles();

    // Preparar configuración final
    $finalConfig = prepareFinalConfiguration($configuration);

    // Guardar configuración
    if (!saveConfigurationFile($finalConfig)) {
        throw new Exception('Error al escribir el archivo de configuración');
    }

    // Verificar integridad
    verifyConfigurationIntegrity();

    // Crear archivo de señal de nueva configuración
    $signalFile = CONFIG_PATH . '/new_config.txt';
    file_put_contents($signalFile, date('Y-m-d H:i:s'));

    $logger->info("Configuración guardada exitosamente");
    
    Response::success(null, 'Configuración guardada correctamente', [
        'type' => $configuration['type'],
        'persons_count' => getPersonsCount($configuration),
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    $logger->error("Error guardando configuración", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    Response::error($e->getMessage(), [
        'details' => [
            'error_type' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

/**
 * Valida la configuración recibida
 * @param array $config Configuración a validar
 * @throws Exception si la configuración es inválida
 */
function validateConfiguration($config) {
    if (!is_array($config)) {
        throw new Exception('La configuración debe ser un array');
    }

    if (!isset($config['type'])) {
        throw new Exception('El tipo de configuración es requerido');
    }

    $validTypes = ['aster', 'pompon', 'mixed'];
    if (!in_array($config['type'], $validTypes)) {
        throw new Exception('Tipo de configuración inválido');
    }

    switch ($config['type']) {
        case 'mixed':
            validateMixedConfig($config);
            break;
        default:
            validateSimpleConfig($config);
            break;
    }
}

/**
 * Valida configuración de tipo mixto
 * @param array $config Configuración a validar
 * @throws Exception si la configuración es inválida
 */
function validateMixedConfig($config) {
    if (!isset($config['aster']) || !isset($config['pompon'])) {
        throw new Exception('Configuración mixta debe incluir secciones aster y pompon');
    }

    if (!is_array($config['aster']) || !is_array($config['pompon'])) {
        throw new Exception('Las secciones aster y pompon deben ser arrays');
    }

    validatePersons($config['aster'], 'aster');
    validatePersons($config['pompon'], 'pompon');

    // Verificar que al menos una sección tenga personas
    if (empty($config['aster']) && empty($config['pompon'])) {
        throw new Exception('Debe seleccionar al menos una persona en modo mixto');
    }
}

/**
 * Valida configuración simple (aster o pompon)
 * @param array $config Configuración a validar
 * @throws Exception si la configuración es inválida
 */
function validateSimpleConfig($config) {
    if (!isset($config['selected_persons'])) {
        throw new Exception('No se encontraron personas seleccionadas');
    }

    if (!is_array($config['selected_persons'])) {
        throw new Exception('La lista de personas seleccionadas debe ser un array');
    }

    if (empty($config['selected_persons'])) {
        throw new Exception('Debe seleccionar al menos una persona');
    }

    validatePersons($config['selected_persons']);
}

/**
 * Valida lista de personas
 * @param array $persons Lista de personas a validar
 * @param string $section Nombre de la sección (opcional)
 * @throws Exception si la lista es inválida
 */
function validatePersons($persons, $section = '') {
    $sectionText = $section ? " en sección $section" : "";
    
    foreach ($persons as $person) {
        if (!is_array($person)) {
            throw new Exception("Formato inválido de persona$sectionText");
        }

        if (!isset($person['class']) || !isset($person['name'])) {
            throw new Exception("Datos incompletos de persona$sectionText");
        }

        if (empty($person['class']) || empty($person['name'])) {
            throw new Exception("Datos vacíos de persona$sectionText");
        }
    }
}

/**
 * Crea los directorios necesarios
 */
function createRequiredDirectories() {
    $directories = [
        CONFIG_PATH,
        LOGS_PATH,
    ];

    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new Exception("No se pudo crear el directorio: $dir");
            }
            chmod($dir, 0777);
        }
    }
}

/**
 * Limpia archivos antiguos
 */
function cleanOldFiles() {
    $files = [
        DATA_PATH . '/detection_status.json',
        ROOT_PATH . '/detection_initialized.txt',
        ROOT_PATH . '/detection_paused.txt',
        ROOT_PATH . '/stop_detection.txt'
    ];

    foreach ($files as $file) {
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}

/**
 * Prepara la configuración final
 * @param array $config Configuración recibida
 * @return array Configuración final
 */
function prepareFinalConfiguration($config) {
    if ($config['type'] === 'mixed') {
        return [
            'type' => 'mixed',
            'aster' => $config['aster'],
            'pompon' => $config['pompon'],
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0'
        ];
    } else {
        return [
            'type' => $config['type'],
            'selected_persons' => $config['selected_persons'],
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0'
        ];
    }
}

/**
 * Guarda el archivo de configuración
 * @param array $config Configuración a guardar
 * @return bool true si se guardó correctamente
 */
function saveConfigurationFile($config) {
    return file_put_contents(
        DETECTION_CONFIG_FILE,
        json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

/**
 * Verifica la integridad de la configuración guardada
 * @throws Exception si hay problemas con la configuración
 */
function verifyConfigurationIntegrity() {
    if (!file_exists(DETECTION_CONFIG_FILE)) {
        throw new Exception('No se pudo verificar el archivo de configuración');
    }

    $savedConfig = json_decode(file_get_contents(DETECTION_CONFIG_FILE), true);
    if ($savedConfig === null) {
        throw new Exception('Error al verificar la integridad del archivo de configuración');
    }
}

/**
 * Obtiene el conteo total de personas en la configuración
 * @param array $config Configuración
 * @return int Número total de personas
 */
function getPersonsCount($config) {
    if ($config['type'] === 'mixed') {
        return count($config['aster'] ?? []) + count($config['pompon'] ?? []);
    }
    return count($config['selected_persons'] ?? []);
}