<?php
// Definir la ruta base del proyecto de forma absoluta
define('PROJECT_ROOT', realpath($_SERVER['DOCUMENT_ROOT'] . '/L_Siembra'));

// Incluir los archivos necesarios usando rutas absolutas
require_once PROJECT_ROOT . '/backend/php/config/paths.php';
require_once PROJECT_ROOT . '/backend/php/utils/Response.php';
require_once PROJECT_ROOT . '/backend/php/utils/Logger.php';

use Utils\Response;
use Utils\Logger;

try {
    $logger = Logger::getInstance();
    $logger->debug("Verificando estado de detección");

    // Verificar si la detección está activa
    $pidFile = PROJECT_ROOT . '/tmp/detection_pid.txt';
    $isActive = file_exists($pidFile);

    $logger->debug("Estado de detección activa: " . ($isActive ? "Sí" : "No"));

    // Si no hay detección activa pero se solicita el estado, retornar datos vacíos
    if (!$isActive) {
        $logger->info("No hay detección activa");
        Response::success([], 'No hay detección activa');
        exit;
    }

    // Verificar archivo de estado
    $statusFile = PROJECT_ROOT . '/backend/data/config/detection_status.json';
    $logger->debug("Verificando archivo de estado: " . $statusFile);
    
    if (!file_exists($statusFile)) {
        // Si no existe el archivo de estado pero hay PID, crear estado inicial
        $initialStatus = [];
        
        // Leer configuración actual
        $configFile = PROJECT_ROOT . '/backend/data/config/detection_config.json';
        $logger->debug("Leyendo configuración: " . $configFile);
        
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            
            if ($config) {
                // Para configuración mixta
                if ($config['type'] === 'mixed') {
                    // Procesar personas de Aster
                    if (isset($config['aster']) && is_array($config['aster'])) {
                        foreach ($config['aster'] as $person) {
                            $initialStatus[$person['class']] = [
                                'current_count' => 0,
                                'target' => 25,
                                'deficit' => 0,
                                'type' => 'aster'
                            ];
                        }
                    }
                    // Procesar personas de Pompón
                    if (isset($config['pompon']) && is_array($config['pompon'])) {
                        foreach ($config['pompon'] as $person) {
                            $initialStatus[$person['class']] = [
                                'current_count' => 0,
                                'target' => 29,
                                'deficit' => 0,
                                'type' => 'pompon'
                            ];
                        }
                    }
                } 
                // Para configuración simple (aster o pompón)
                else {
                    $target = $config['type'] === 'aster' ? 25 : 29;
                    if (isset($config['selected_persons']) && is_array($config['selected_persons'])) {
                        foreach ($config['selected_persons'] as $person) {
                            $initialStatus[$person['class']] = [
                                'current_count' => 0,
                                'target' => $target,
                                'deficit' => 0,
                                'type' => $config['type']
                            ];
                        }
                    }
                }

                $logger->info("Estado inicial creado", ['status' => $initialStatus]);
            } else {
                $logger->error("Error al decodificar archivo de configuración");
                throw new Exception('Error en la configuración');
            }
        } else {
            $logger->error("Archivo de configuración no encontrado");
            throw new Exception('Configuración no encontrada');
        }
        
        // Guardar estado inicial
        if (!empty($initialStatus)) {
            if (file_put_contents($statusFile, json_encode($initialStatus, JSON_PRETTY_PRINT)) === false) {
                throw new Exception('Error al crear archivo de estado inicial');
            }
            $logger->info("Archivo de estado inicial creado con éxito");
        } else {
            throw new Exception('No se pudo crear estado inicial');
        }
        
        Response::success($initialStatus, 'Estado inicial creado');
        exit;
    }

    // Leer estado actual
    $statusContent = file_get_contents($statusFile);
    $logger->debug("Contenido del archivo de estado: " . $statusContent);
    
    $statusData = json_decode($statusContent, true);
    if ($statusData === null) {
        throw new Exception('Error al decodificar datos de estado: ' . json_last_error_msg());
    }

    $logger->debug("Datos de estado leídos", ['data' => $statusData]);

    // Leer nombres de clases
    $namesFile = PROJECT_ROOT . '/backend/data/config/nombres_clases.txt';
    $classNames = [];
    if (file_exists($namesFile)) {
        $lines = file($namesFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false) {
                list($class, $name) = explode('=', $line);
                $classNames[trim($class)] = trim($name);
            }
        }
        $logger->debug("Nombres de clases cargados", ['names' => $classNames]);
    }

    // Verificar si la detección está pausada
    $pauseFile = PROJECT_ROOT . '/tmp/detection_paused.txt';
    $isPaused = file_exists($pauseFile);

    // Preparar respuesta
    $responseData = [];
    foreach ($statusData as $class => $data) {
        $target = $data['target'] ?? ($data['type'] === 'aster' ? 25 : 29);
        $currentCount = $data['current_count'] ?? 0;
        $deficit = $data['deficit'] ?? 0;

        // Agregar los datos de cada persona
        $responseData[] = [
            'name' => $classNames[$class] ?? $class,
            'class' => $class,
            'current_count' => $currentCount,
            'target' => $target,
            'deficit' => $deficit,
            'type' => $data['type'] ?? 'unknown',
            'status' => $isPaused ? 'paused' : 'active'
        ];
    }

    // Ordenar por nombre
    usort($responseData, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });

    $logger->debug("Enviando respuesta", [
        'total_records' => count($responseData),
        'data' => $responseData,
        'status' => $isPaused ? 'paused' : 'active'
    ]);

    // Justo antes de Response::success
    $logger->debug("Preparando respuesta final", [
        'responseData' => $responseData,
        'count' => count($responseData),
        'isPaused' => $isPaused,
        'statusData' => $statusData
    ]);

    Response::success($responseData, '', [
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => $isPaused ? 'paused' : 'active',
        'debug' => [
            'totalRecords' => count($responseData),
            'hasData' => !empty($responseData)
        ]
    ]);

} catch (Exception $e) {
    $logger->error("Error al obtener estado", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    Response::error($e->getMessage(), [
        'status_file_exists' => file_exists($statusFile ?? ''),
        'pid_file_exists' => file_exists($pidFile ?? ''),
        'config_file_exists' => file_exists($configFile ?? ''),
        'details' => [
            'errorType' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}