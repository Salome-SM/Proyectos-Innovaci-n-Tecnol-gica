<?php
require_once 'models/Survey.php';

class DashboardController {
    private $pdo;
    private $survey;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->survey = new Survey($this->pdo);
    }

    public function showDashboard() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php');
            exit;
        }

        $totalSurveys = $this->getTotalSurveys();
        $totalPrioritized = $this->getTotalPrioritizedIdeas();

        require 'views/dashboard.php';
    }

    private function getTotalSurveys() {
        return $this->survey->getTotalSurveys();
    }

    private function getTotalPrioritizedIdeas() {
        return $this->survey->getTotalPrioritizedIdeas();
    }
}