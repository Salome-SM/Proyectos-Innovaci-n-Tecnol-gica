<?php
// api/history.php
header('Content-Type: application/json');

require_once __DIR__ . '/../models/PlantCount.php';

try {
   $model = new PlantCount();
   
   $filters = [
       'block_number' => $_GET['block'] ?? null,
       'bed_number' => $_GET['bed'] ?? null,
       'variety' => $_GET['variety'] ?? null,
       'count_date' => $_GET['date'] ?? null
   ];

   $records = $model->getResults($filters);
   
   echo json_encode([
       'success' => true,
       'records' => $records
   ]);

} catch (Exception $e) {
   http_response_code(500);
   echo json_encode([
       'success' => false,
       'message' => $e->getMessage()
   ]);
}