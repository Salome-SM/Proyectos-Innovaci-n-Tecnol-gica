<?php
// Definir constantes de rutas base
define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));  // Apunta a la raíz de L_Siembra
define('BACKEND_PATH', ROOT_PATH . '/backend');
define('FRONTEND_PATH', ROOT_PATH . '/frontend');

// Rutas del backend
define('PHP_PATH', BACKEND_PATH . '/php');
define('PYTHON_PATH', BACKEND_PATH . '/python');
define('LOGS_PATH', BACKEND_PATH . '/logs');
define('DATA_PATH', BACKEND_PATH . '/data');

// Rutas específicas de Python
define('MODELS_PATH', PYTHON_PATH . '/models');
define('CORE_PATH', PYTHON_PATH . '/core');
define('UTILS_PATH', PYTHON_PATH . '/utils');

// Rutas de configuración y datos
define('CONFIG_PATH', DATA_PATH . '/config');

// Archivos de configuración
define('DETECTION_CONFIG_FILE', CONFIG_PATH . '/detection_config.json');
define('CLASS_NAMES_FILE', CONFIG_PATH . '/nombres_clases.txt');
define('DETECTION_STATUS_FILE', CONFIG_PATH . '/detection_status.json');

// Archivos Python
define('MAIN_PYTHON_SCRIPT', CORE_PATH . '/main.py');
define('MODEL_FILE', MODELS_PATH . '/bestC.pt');

// Archivos temporales y de control
define('TMP_PATH', ROOT_PATH . '/tmp');
define('PID_FILE', TMP_PATH . '/detection_pid.txt');
define('INIT_FILE', TMP_PATH . '/detection_initialized.txt');
define('STOP_FILE', TMP_PATH . '/stop_detection.txt');
define('PAUSE_FILE', TMP_PATH . '/detection_paused.txt');

// URLs base (útiles para redirecciones y assets)
define('BASE_URL', '/L_Siembra');
define('FRONTEND_URL', BASE_URL . '/frontend');
define('BACKEND_URL', BASE_URL . '/backend');

// Asegurarse de que los directorios necesarios existan
$required_directories = [
    LOGS_PATH,
    CONFIG_PATH,
    TMP_PATH,
    MODELS_PATH
];

// Crear directorios si no existen y establecer permisos
foreach ($required_directories as $dir) {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true)) {
            error_log("Error al crear directorio: " . $dir);
            throw new RuntimeException("No se pudo crear el directorio: " . $dir);
        }
        chmod($dir, 0777);
    } else if (!is_writable($dir)) {
        // Intentar establecer permisos si el directorio existe pero no es escribible
        chmod($dir, 0777);
        if (!is_writable($dir)) {
            error_log("Error de permisos en directorio: " . $dir);
            throw new RuntimeException("El directorio no tiene permisos de escritura: " . $dir);
        }
    }
}

// Función helper para verificar rutas
function verify_paths() {
    $critical_files = [
        'Model File' => MODEL_FILE,
        'Config File' => DETECTION_CONFIG_FILE,
        'Class Names File' => CLASS_NAMES_FILE
    ];

    $missing_files = [];
    foreach ($critical_files as $name => $path) {
        if (!file_exists($path)) {
            $missing_files[] = "$name ($path)";
        }
    }

    if (!empty($missing_files)) {
        error_log("Archivos críticos faltantes: " . implode(", ", $missing_files));
        return false;
    }

    return true;
}

// Función helper para obtener rutas relativas
function get_relative_path($absolute_path) {
    return str_replace(ROOT_PATH, '', $absolute_path);
}

// Función para obtener información del sistema
function get_system_paths() {
    return [
        'root' => ROOT_PATH,
        'backend' => BACKEND_PATH,
        'frontend' => FRONTEND_PATH,
        'python' => PYTHON_PATH,
        'data' => DATA_PATH,
        'logs' => LOGS_PATH,
        'tmp' => TMP_PATH,
        'base_url' => BASE_URL
    ];
}

// Verificar la existencia de archivos críticos al cargar el script
if (!verify_paths()) {
    error_log("Advertencia: Faltan archivos críticos del sistema.");
}