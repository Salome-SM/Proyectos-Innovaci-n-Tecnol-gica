<?php
class ProcessController {
    private $config;
    private $debugLog;
    
    public function __construct() {
        $this->config = [
            'upload_dir' => dirname(__DIR__) . '/storage/uploads',
            'processed_dir' => dirname(__DIR__) . '/storage/processed',
            'python_path' => 'python',
            'python_script' => dirname(__DIR__) . '/python/process_video.py',
            'model_path' => dirname(__DIR__) . '/python/models/best.pt'
        ];
        
        $this->debugLog = dirname(__DIR__) . '/debug.log';
        $this->log("ProcessController inicializado");
        
        foreach ([$this->config['upload_dir'], $this->config['processed_dir']] as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
        }
    }

    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->debugLog, "[$timestamp] $message\n", FILE_APPEND);
    }

    private function sendEvent($data) {
        if (is_string($data)) {
            if ($this->isJson($data)) {
                echo "data: " . $data . "\n\n";
            } else {
                echo "data: " . json_encode(['message' => $data]) . "\n\n";
            }
        } else {
            echo "data: " . json_encode($data) . "\n\n";
        }
        
        ob_flush();
        flush();
    }

    private function isJson($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function processVideo() {
        try {
            // Validar parámetros
            if (empty($_GET['video_file']) || empty($_GET['block_number']) || 
                empty($_GET['bed_number']) || empty($_GET['year_week']) || 
                empty($_GET['count_date'])) {
                throw new Exception('Faltan parámetros requeridos');
            }

            $videoPath = $this->config['upload_dir'] . DIRECTORY_SEPARATOR . $_GET['video_file'];
            $this->log("Ruta completa del video: " . $videoPath);
            
            // Verificar archivo
            if (!file_exists($videoPath)) {
                throw new Exception("El archivo de video no existe: " . $videoPath);
            }

            if (!is_readable($videoPath)) {
                throw new Exception("El archivo existe pero no se puede leer: " . $videoPath);
            }

            // Verificar Python script y modelo
            if (!file_exists($this->config['python_script'])) {
                throw new Exception("Script Python no encontrado en: " . $this->config['python_script']);
            }

            if (!file_exists($this->config['model_path'])) {
                throw new Exception("Modelo no encontrado en: " . $this->config['model_path']);
            }

            // Construir comando
            $command = sprintf(
                '%s "%s" "%s" "%s" "%s" "%s" "%s" "%s" "%s" 2>&1',
                escapeshellcmd($this->config['python_path']),
                escapeshellarg($this->config['python_script']),
                escapeshellarg($videoPath),
                escapeshellarg($this->config['model_path']),
                escapeshellarg($_GET['block_number']),
                escapeshellarg($_GET['bed_number']),
                escapeshellarg($_GET['year_week']),
                escapeshellarg($_GET['count_date']),
                escapeshellarg($_GET['variety'])
            );

            $this->log("Comando a ejecutar: " . $command);

            $descriptorSpec = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ];

            $process = proc_open($command, $descriptorSpec, $pipes);

            if (is_resource($process)) {
                // Configurar pipes para lectura no bloqueante
                stream_set_blocking($pipes[1], false);
                stream_set_blocking($pipes[2], false);

                $this->sendEvent(['status' => 'iniciando', 'message' => 'Iniciando procesamiento']);

                while (true) {
                    $status = proc_get_status($process);
                    
                    // Leer stdout
                    while ($output = fgets($pipes[1])) {
                        $trimmedOutput = trim($output);
                        if (!empty($trimmedOutput)) {
                            $this->log("Salida: " . $trimmedOutput);
                            $this->sendEvent($trimmedOutput);
                        }
                    }

                    // Leer stderr
                    while ($error = fgets($pipes[2])) {
                        $trimmedError = trim($error);
                        if (!empty($trimmedError)) {
                            $this->log("Error: " . $trimmedError);
                            $this->sendEvent(['error' => $trimmedError]);
                        }
                    }

                    if (!$status['running']) {
                        $this->log("Proceso terminado");
                        break;
                    }

                    usleep(100000); // 100ms delay
                }

                // Cerrar pipes
                foreach ($pipes as $pipe) {
                    fclose($pipe);
                }
                
                // Obtener código de salida
                $exitCode = proc_close($process);
                $this->log("Proceso cerrado con código de salida: $exitCode");

                if ($exitCode !== 0) {
                    throw new Exception("El proceso terminó con errores (código $exitCode)");
                }

            } else {
                throw new Exception("No se pudo iniciar el proceso de Python");
            }

        } catch (Exception $e) {
            $this->log("Error en processVideo: " . $e->getMessage());
            $this->sendEvent(['error' => $e->getMessage()]);
        }
    }
}