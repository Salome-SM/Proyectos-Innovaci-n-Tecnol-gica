<?php
namespace Utils;

require_once __DIR__ . '/Logger.php';

use PDO;
use PDOException;
use Exception;

/**
 * Clase singleton para gestionar la conexión a base de datos
 */
class Database {
    private $conn;
    private static $instance = null;
    private $logger;

    private function __construct() {
        $this->logger = Logger::getInstance();
        $this->connect();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect() {
        try {
            $host = 'localhost';
            $dbname = 'l_siembra';
            $user = 'postgres';  // Ajusta según tu configuración
            $password = 'password';  // Ajusta según tu configuración

            $dsn = "pgsql:host=$host;dbname=$dbname";
            $this->conn = new PDO($dsn, $user, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $this->logger->info("Conexión a base de datos establecida");
        } catch (PDOException $e) {
            $this->logger->error("Error de conexión a base de datos: " . $e->getMessage());
            throw new Exception("Error de conexión a base de datos");
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function saveDetection($personaClass, $count, $hour, $tipo) {
        try {
            // Iniciar transacción
            $this->conn->beginTransaction();

            // Obtener ID de la persona
            $stmt = $this->conn->prepare("
                SELECT id FROM personas WHERE clase = :clase
            ");
            $stmt->execute(['clase' => $personaClass]);
            $personaId = $stmt->fetchColumn();

            if (!$personaId) {
                throw new Exception("Persona no encontrada");
            }

            // Obtener ID del tipo de producción
            $stmt = $this->conn->prepare("
                SELECT id FROM tipos_produccion WHERE nombre = :tipo
            ");
            $stmt->execute(['tipo' => $tipo]);
            $tipoId = $stmt->fetchColumn();

            // Preparar datos
            $fecha = date('Y-m-d');
            $semana = date('W');
            $dia = date('l');
            $meta = ($tipo === 'aster') ? 25 : 29;
            $deficit = max(0, $meta - $count);

            // Insertar o actualizar detección
            $stmt = $this->conn->prepare("
                INSERT INTO detecciones 
                (persona_id, fecha, hora, semana, dia, conteo, meta, deficit, tipo_produccion_id)
                VALUES (:persona_id, :fecha, :hora, :semana, :dia, :conteo, :meta, :deficit, :tipo_id)
                ON CONFLICT (persona_id, fecha, hora) 
                DO UPDATE SET 
                    conteo = :conteo,
                    deficit = :deficit,
                    fecha_actualizacion = CURRENT_TIMESTAMP
            ");

            $stmt->execute([
                'persona_id' => $personaId,
                'fecha' => $fecha,
                'hora' => $hour,
                'semana' => $semana,
                'dia' => $dia,
                'conteo' => $count,
                'meta' => $meta,
                'deficit' => $deficit,
                'tipo_id' => $tipoId
            ]);

            // Actualizar resumen diario
            $this->updateDailySummary($personaId, $fecha, $semana, $dia, $tipoId);

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            $this->logger->error("Error guardando detección: " . $e->getMessage());
            throw $e;
        }
    }

    private function updateDailySummary($personaId, $fecha, $semana, $dia, $tipoId) {
        // Calcular totales del día
        $stmt = $this->conn->prepare("
            SELECT 
                SUM(conteo) as total_conteo,
                SUM(meta) as total_meta,
                SUM(deficit) as total_deficit
            FROM detecciones
            WHERE persona_id = :persona_id 
            AND fecha = :fecha
        ");

        $stmt->execute([
            'persona_id' => $personaId,
            'fecha' => $fecha
        ]);

        $totales = $stmt->fetch(PDO::FETCH_ASSOC);

        // Insertar o actualizar resumen
        $stmt = $this->conn->prepare("
            INSERT INTO resumen_diario 
            (persona_id, fecha, semana, dia, conteo_total, meta_total, deficit_total, tipo_produccion_id)
            VALUES (:persona_id, :fecha, :semana, :dia, :conteo_total, :meta_total, :deficit_total, :tipo_id)
            ON CONFLICT (persona_id, fecha) 
            DO UPDATE SET 
                conteo_total = :conteo_total,
                meta_total = :meta_total,
                deficit_total = :deficit_total,
                fecha_actualizacion = CURRENT_TIMESTAMP
        ");

        $stmt->execute([
            'persona_id' => $personaId,
            'fecha' => $fecha,
            'semana' => $semana,
            'dia' => $dia,
            'conteo_total' => $totales['total_conteo'],
            'meta_total' => $totales['total_meta'],
            'deficit_total' => $totales['total_deficit'],
            'tipo_id' => $tipoId
        ]);
    }
}
