<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xampp/htdocs/web/custom_errors.log');

require_once 'config/database.php';
require_once 'controllers/LoginController.php';
require_once 'controllers/SurveyController.php';
require_once 'controllers/DashboardController.php';
require_once 'controllers/AuthController.php';

$action = $_GET['action'] ?? 'login';

$loginController = new LoginController($pdo);
$surveyController = new SurveyController($pdo);
$dashboardController = new DashboardController($pdo);
$authController = new AuthController();

$restrictedUsers = ['poscosecha@gmail.com', 'produccion@gmail.com'];

// Función para verificar si el usuario actual es un usuario restringido
function isRestrictedUser() {
    global $restrictedUsers;
    return isset($_SESSION['email']) && in_array($_SESSION['email'], $restrictedUsers);
}

// Redireccionar usuarios restringidos a la encuesta si intentan acceder a otras páginas
if (isRestrictedUser() && $action !== 'survey' && $action !== 'saveSurvey' && $action !== 'logout') {
    header('Location: index.php?action=survey');
    exit;
}

switch ($action) {
    case 'login':
        $loginController->login();
        break;
    case 'logout':
        $authController->logout();
        break;
    case 'survey':
        $surveyController->showSurvey();
        break;
    case 'saveSurvey':
        $surveyController->saveSurvey();
        break;
    case 'dashboard':
        if (!isRestrictedUser()) {
            $dashboardController->showDashboard();
        } else {
            header('Location: index.php?action=survey');
        }
        break;
    case 'database':
        if (!isRestrictedUser()) {
            require_once 'views/database.php'; // Asumiendo que está en la carpeta views
        } else {
            header('Location: index.php?action=survey');
        }
        break;
    case 'updatePoints':
        if (!isRestrictedUser()) {
            $surveyController->updatePoints();
        } else {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
        }
        break;
    case 'listSurveys':
        if (!isRestrictedUser()) {
            $surveyController->listSurveys();
        } else {
            header('Location: index.php?action=survey');
        }
        break;
    case 'rateSurveys':
        if (!isRestrictedUser()) {
            $surveyController->showRateSurveys();
        } else {
            header('Location: index.php?action=survey');
        }
        break;;
    case 'surveyStats':
        if (!isRestrictedUser()) {
            $surveyController->showStats();
        } else {
            header('Location: index.php?action=survey');
        }
        break;
    case 'prioritizeSurveys':
        if (!isRestrictedUser()) {
            $surveyController->showPrioritizeSurveys();
        } else {
            header('Location: index.php?action=survey');
        }
        break;
    case 'saveRating':
        $surveyController->saveRating();
        break;
    case 'updateCosto':
        if ($_SESSION['email'] === 'innovaciontr@gmail.com') {
            $surveyController->updateCosto();
        } else {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
        }
        break;
    case 'getRatingForm':
         $surveyController->getRatingForm();
        break;
    case 'downloadPDF':
        $surveyController->downloadPDF();
        break;
    case 'getRatings':
    case 'viewRanking':
        $surveyController->viewRanking();
        break;
    case 'getRatingDetails':
        if (!isRestrictedUser()) {
            $surveyController->$action();
        } else {
            http_response_code(403);
            echo "Acceso denegado";
        }
        break;
    default:
        if (isRestrictedUser()) {
            header('Location: index.php?action=survey');
        } else {
            $loginController->login();
        }
        break;
}


