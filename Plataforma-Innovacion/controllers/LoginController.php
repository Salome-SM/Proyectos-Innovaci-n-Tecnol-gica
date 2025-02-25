<?php
require_once 'models/User.php';

class LoginController {
    private $user;
    private $pdo;

    public function __construct($pdo) {
        error_log("LoginController constructor called");
        $this->pdo = $pdo;
        if ($this->pdo === null) {
            error_log("PDO is null in LoginController constructor");
        } else {
            error_log("PDO is not null in LoginController constructor");
        }
        
        try {
            $this->user = new User($this->pdo);
            if ($this->user === null) {
                error_log("User object is null after creation");
            } else {
                error_log("User object created successfully");
            }
        } catch (Exception $e) {
            error_log("Exception when creating User object: " . $e->getMessage());
        }
    }

    public function login() {
        error_log("Login method called");
        if ($this->user === null) {
            error_log("User object is null in login method");
            // Intenta crear el objeto User nuevamente
            $this->user = new User($this->pdo);
        }
        
        error_log("Método de solicitud: " . ($_SERVER['REQUEST_METHOD'] ?? 'No definido'));
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            error_log("Datos POST recibidos: " . print_r($_POST, true));
            
            $emailOrUser = $_POST['emailOrUser'] ?? '';
            $password = $_POST['password'] ?? '';

            error_log("Intento de inicio de sesión para: " . $emailOrUser);

            if ($this->user !== null) {
                $user = $this->user->login($emailOrUser, $password);
                error_log("Resultado de autenticación: " . ($user ? 'Éxito' : 'Fallo'));
                
                if ($user) {
                    session_start();
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Redirige según el tipo de usuario
                    $email = $user['email'];
                    $adminUsers = ['innovaciontr@gmail.com', 'gerencia@gmail.com', 'gh@gmail.com', 'poscosechaD@gmail.com', 'produccionD@gmail.com'];

                    if (in_array($email, $adminUsers)) {
                        header('Location: index.php?action=dashboard');
                    } else {
                        header('Location: index.php?action=survey');
                    }
                    exit;
                } else {
                    $error = "Credenciales inválidas";
                    error_log("Inicio de sesión fallido para: " . $emailOrUser);
                }
            } else {
                error_log("User object is still null, cannot proceed with login");
                $error = "Error interno del servidor";
            }
        }
        require 'views/login.php';
    }
}