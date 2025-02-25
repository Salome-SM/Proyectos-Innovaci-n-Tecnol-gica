<?php
class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getUserById($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :userId");
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function login($emailOrUser, $password) {
        error_log("Intentando autenticar: " . $emailOrUser);
        
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :emailOrUser OR user = :emailOrUser");
        $stmt->execute(['emailOrUser' => $emailOrUser]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
        error_log("Usuario encontrado en la base de datos: " . ($user ? 'Sí' : 'No'));
    
        if ($user) {
            error_log("Verificando contraseña para: " . $emailOrUser);
            
            // Comparación directa si la contraseña está en texto plano
            if ($password === $user['password']) {
                error_log("Contraseña correcta para: " . $emailOrUser);
                return $user;
            } else {
                error_log("Contraseña incorrecta para: " . $emailOrUser);
            }
        } else {
            error_log("Usuario no encontrado: " . $emailOrUser);
        }
        return false;
    }
}