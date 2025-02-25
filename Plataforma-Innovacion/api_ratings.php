<?php
require_once 'config/database.php';
require_once 'models/Survey.php';

header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $survey = new Survey($pdo);
    $ratingsData = $survey->getAllSurveyRatings();

    echo json_encode($ratingsData);
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}