<?php
require_once __DIR__ . '/../config/database.php';

class PlantCount {
    private $db;

    public function __construct() {
        $config = require __DIR__ . '/../config/database.php';
        $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}";
        $this->db = new PDO($dsn, $config['username'], $config['password']);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    public function getResults($filters = []) {
        try {
            $sql = "SELECT pc.*, v.name as variety 
                    FROM plant_counts pc 
                    JOIN varieties v ON pc.variety_id = v.id 
                    WHERE 1=1";
            $params = [];
    
            if (!empty($filters['block_number'])) {
                $sql .= " AND block_number = ?";
                $params[] = $filters['block_number'];
            }
            if (!empty($filters['bed_number'])) {
                $sql .= " AND bed_number = ?";
                $params[] = $filters['bed_number'];
            }
            if (!empty($filters['variety'])) {
                $sql .= " AND v.name = ?";
                $params[] = $filters['variety'];
            }
            if (!empty($filters['count_date'])) {
                $sql .= " AND count_date = ?";
                $params[] = $filters['count_date'];
            }
    
            $sql .= " ORDER BY count_date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching results: " . $e->getMessage());
        }
    }

    public function saveResults($data) {
        try {
            $sql = "INSERT INTO plant_counts (
                block_number, bed_number, year_week, count_date, 
                variety_id, total_plants, total_frames,
                video_path, processed_video_path
            ) VALUES (
                :block_number, :bed_number, :year_week, :count_date,
                (SELECT id FROM varieties WHERE name = :variety),
                :total_plants, :total_frames, :video_path, :processed_video_path
            ) RETURNING id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':block_number' => $data['block_number'],
                ':bed_number' => $data['bed_number'],
                ':year_week' => $data['year_week'],
                ':count_date' => $data['count_date'],
                ':variety' => $data['variety'],
                ':total_plants' => $data['total_unique_plants'],
                ':total_frames' => $data['total_frames'],
                ':video_path' => $data['video_path'],
                ':processed_video_path' => $data['processed_video_path']
            ]);

            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new Exception("Error saving results: " . $e->getMessage());
        }
    }

    public function getVarieties() {
        $stmt = $this->db->query("SELECT name FROM varieties ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}