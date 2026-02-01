<?php
class Auth {
    private $db;
    
    public function __construct($db_connection = null) {
        if ($db_connection) {
            $this->db = $db_connection;
        } else {
            $database = new Database();
            $this->db = $database->getConnection();
        }
    }
    
    public function login($username, $password) {
        $query = "SELECT * FROM users WHERE username = :username AND is_active = 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                // Установка куки на 30 дней
                setcookie('user_auth', $user['username'], time() + (30 * 24 * 3600), "/");
                
                return true;
            }
        }
        return false;
    }
    
    public function register($userData) {
        $query = "INSERT INTO users (username, email, password, first_name, last_name, phone) 
                  VALUES (:username, :email, :password, :first_name, :last_name, :phone)";
        
        $stmt = $this->db->prepare($query);
        $hashed_password = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        $stmt->bindParam(':username', $userData['username']);
        $stmt->bindParam(':email', $userData['email']);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':first_name', $userData['first_name']);
        $stmt->bindParam(':last_name', $userData['last_name']);
        $stmt->bindParam(':phone', $userData['phone']);
        
        return $stmt->execute();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public function logout() {
        session_destroy();
        setcookie('user_auth', '', time() - 3600, "/");
        header('Location: ../pages/login.php');
        exit;
    }
    
    public function getUserRole() {
        return $_SESSION['role'] ?? 'guest';
    }

public function getStaffData() {
    if (isset($_SESSION['user_id']) && isset($_SESSION['email'])) {
        // Ищем сотрудника по email (как в вашей таблице)
        $query = "SELECT * FROM staff WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $_SESSION['email']);
        $stmt->execute();
        $staff_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Если сотрудник не найден, возвращаем базовые данные
        if (!$staff_data) {
            return [
                'name' => $_SESSION['username'] ?? 'Сотрудник',
                'position' => 'Сотрудник',
                'email' => $_SESSION['email'] ?? '',
                'phone' => 'Не указан',
                'department' => 'Не указан',
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        
        return $staff_data;
    }
    return null;
}
}
?>