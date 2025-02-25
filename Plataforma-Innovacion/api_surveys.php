<?php
header('Content-Type: application/json');

require_once 'config/database.php';
require_once 'models/Survey.php';

error_log("API Surveys: Started");

$survey = new Survey($pdo);

try {
    $surveys = $survey->getAllSurveys();
    error_log("API Surveys: Got " . count($surveys) . " surveys");
    
    $formattedData = [];
    foreach ($surveys as $s) {
        $initiativeType = $s['initiative_type'];
        
        $item = [
            'name' => $s['name'],
            'area' => $s['area'],
            'ideas' => ($initiativeType == 'idea') ? 1 : 0,
            'retos' => ($initiativeType == 'reto') ? 1 : 0,
            'problemas' => ($initiativeType == 'problema') ? 1 : 0
        ];
        
        $formattedData[] = $item;
    }
    
    error_log("API Surveys: Formatted data: " . json_encode($formattedData));
    echo json_encode($formattedData);
} catch (Exception $e) {
    error_log("API Surveys Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}