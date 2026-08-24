<?php
// Authentication system
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    private $user = null;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->checkSession();
    }
    
    public function login($email, $password, $remember = false) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? AND status != 'suspended'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            
            // Update last login
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Remember me
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $stmt = $this->db->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $stmt->execute([$token, $user['id']]);
                setcookie('remember_token', $token, time() + 86400 * 30, '/', '', true, true);
            }
            
            // Log activity
            $this->logActivity($user['id'], 'login', 'User logged in');
            
            return ['success' => true, 'user' => $user];
        }
        
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    public function register($data) {
        // Validate
        if (empty($data['email']) || empty($data['password']) || empty($data['first_name'])) {
            return ['success' => false, 'message' => 'All fields are required'];
        }
        
        if ($data['password'] !== $data['confirm_password']) {
            return ['success' => false, 'message' => 'Passwords do not match'];
        }
        
        if (strlen($data['password']) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters'];
        }
        
        // Check if email exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $this->db->prepare("
            INSERT INTO users (email, password, first_name, last_name, phone, role, status) 
            VALUES (?, ?, ?, ?, ?, 'customer', 'active')
        ");
        
        try {
            $stmt->execute([
                $data['email'],
                $hashedPassword,
                $data['first_name'],
                $data['last_name'] ?? '',
                $data['phone'] ?? ''
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // Create customer record
            $stmt = $this->db->prepare("
                INSERT INTO customers (user_id, full_name, email, phone, address) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $data['first_name'] . ' ' . ($data['last_name'] ?? ''),
                $data['email'],
                $data['phone'] ?? '',
                $data['address'] ?? ''
            ]);
            
            return ['success' => true, 'message' => 'Registration successful! Please login.'];
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }
    
    public function logout() {
        // Clear session
        $_SESSION = [];
        session_destroy();
        
        // Clear cookies
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if ($this->user === null && $this->isLoggedIn()) {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $this->user = $stmt->fetch();
        }
        return $this->user;
    }
    
    public function hasRole($role) {
        $user = $this->getCurrentUser();
        if (!$user) return false;
        
        if (is_array($role)) {
            return in_array($user['role'], $role);
        }
        return $user['role'] === $role;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }
    }
    
    public function requireRole($role) {
        $this->requireLogin();
        if (!$this->hasRole($role)) {
            header('HTTP/1.0 403 Forbidden');
            include BASE_PATH . '/403.php';
            exit;
        }
    }
    
    private function checkSession() {
        // Check remember me token
        if (!$this->isLoggedIn() && isset($_COOKIE['remember_token'])) {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE remember_token = ? AND status != 'suspended'");
            $stmt->execute([$_COOKIE['remember_token']]);
            $user = $stmt->fetch();
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $stmt = $this->db->prepare("SELECT role, first_name, last_name FROM users WHERE id = ?");
                $stmt->execute([$user['id']]);
                $userData = $stmt->fetch();
                $_SESSION['user_role'] = $userData['role'];
                $_SESSION['user_name'] = $userData['first_name'] . ' ' . $userData['last_name'];
            }
        }
        
        // Session timeout
        if ($this->isLoggedIn() && isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
                $this->logout();
                header('Location: ' . BASE_URL . 'login.php?timeout=1');
                exit;
            }
        }
        $_SESSION['last_activity'] = time();
    }
    
    private function logActivity($userId, $action, $description = '') {
        $stmt = $this->db->prepare("
            INSERT INTO activity_logs (user_id, username, action, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $_SESSION['user_name'] ?? 'Unknown',
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
}

// Initialize Auth
$auth = new Auth();
?>
