<?php
namespace Utils;

class Logger {
    private static $instance = null;
    private $logPath;
    private $debugEnabled;

    private function __construct() {
        $this->logPath = dirname(dirname(__DIR__)) . '/logs';
        $this->debugEnabled = true; // Configurable en producción
        
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0777, true);
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Registra un mensaje informativo
     * @param string $message Mensaje a registrar
     * @param array $context Contexto adicional
     */
    public function info($message, array $context = []) {
        $this->log('INFO', $message, $context);
    }

    /**
     * Registra un mensaje de advertencia
     * @param string $message Mensaje de advertencia
     * @param array $context Contexto adicional
     */
    public function warning($message, array $context = []) {
        $this->log('WARNING', $message, $context);
    }

    /**
     * Registra un mensaje de error
     * @param string $message Mensaje de error
     * @param array $context Contexto adicional
     */
    public function error($message, array $context = []) {
        $this->log('ERROR', $message, $context);
    }

    /**
     * Registra un mensaje de depuración
     * @param string $message Mensaje de depuración
     * @param array $context Contexto adicional
     */
    public function debug($message, array $context = []) {
        if ($this->debugEnabled) {
            $this->log('DEBUG', $message, $context);
        }
    }

    /**
     * Registra un mensaje crítico
     * @param string $message Mensaje crítico
     * @param array $context Contexto adicional
     */
    public function critical($message, array $context = []) {
        $this->log('CRITICAL', $message, $context);
    }

    /**
     * Registra un mensaje
     * @param string $level Nivel del mensaje
     * @param string $message Mensaje a registrar
     * @param array $context Contexto adicional
     */
    private function log($level, $message, array $context = []) {
        $date = date('Y-m-d H:i:s');
        $logMessage = "[$date] [$level] $message";
        
        if (!empty($context)) {
            $logMessage .= " - Context: " . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        $logMessage .= PHP_EOL;
        
        $filename = $this->logPath . '/' . date('Y-m-d') . '.log';
        file_put_contents($filename, $logMessage, FILE_APPEND);

        // Si es un error o crítico, también lo guardamos en un archivo separado
        if (in_array($level, ['ERROR', 'CRITICAL'])) {
            $errorLog = $this->logPath . '/error.log';
            file_put_contents($errorLog, $logMessage, FILE_APPEND);
        }
    }

    /**
     * Limpia logs antiguos
     * @param int $days Número de días de antigüedad
     */
    public function cleanOldLogs($days = 7) {
        $files = glob($this->logPath . '/*.log');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= (60 * 60 * 24 * $days)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Habilita o deshabilita el modo debug
     * @param bool $enabled Estado del modo debug
     */
    public function setDebugEnabled($enabled) {
        $this->debugEnabled = $enabled;
    }

    /**
     * Obtiene el estado del modo debug
     * @return bool Estado actual del modo debug
     */
    public function isDebugEnabled() {
        return $this->debugEnabled;
    }

    /**
     * Obtiene la ruta de los logs
     * @return string Ruta de los logs
     */
    public function getLogPath() {
        return $this->logPath;
    }
}