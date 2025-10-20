<?php
/**
 * Database Configuration
 * Sistem Informasi Multi Apotik
 */

// Prevent direct access
defined('APP_ACCESS') or define('APP_ACCESS', true);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'multi_apotik');

// Application Configuration
define('APP_NAME', 'Multi Apotik');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/multi-apotik/');

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'MULTIAPOTIK_SESSION');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Database Connection Class
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            $this->conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database Error: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function query($sql) {
        return $this->conn->query($sql);
    }
    
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
    
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
    
    public function lastInsertId() {
        return $this->conn->insert_id;
    }
    
    public function affectedRows() {
        return $this->conn->affected_rows;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserializing
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Helper Functions
function db() {
    return Database::getInstance();
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit;
}

function alert($message, $type = 'success') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

function showAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        $colors = [
            'success' => 'bg-green-100 border-green-500 text-green-700',
            'error' => 'bg-red-100 border-red-500 text-red-700',
            'warning' => 'bg-yellow-100 border-yellow-500 text-yellow-700',
            'info' => 'bg-blue-100 border-blue-500 text-blue-700'
        ];
        
        $color = $colors[$alert['type']] ?? $colors['info'];
        
        echo '<div class="' . $color . ' border-l-4 p-4 rounded-lg mb-4" role="alert">
                <p class="font-medium">' . htmlspecialchars($alert['message']) . '</p>
              </div>';
        
        unset($_SESSION['alert']);
    }
}

function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function formatTanggal($tanggal, $format = 'd-m-Y') {
    return date($format, strtotime($tanggal));
}

function formatTanggalWaktu($tanggal, $format = 'd-m-Y H:i:s') {
    return date($format, strtotime($tanggal));
}

function generateKode($prefix, $table, $field, $length = 4) {
    $db = db();
    $query = "SELECT MAX(CAST(SUBSTRING($field, " . (strlen($prefix) + 1) . ") AS UNSIGNED)) as max_no FROM $table WHERE $field LIKE '$prefix%'";
    $result = $db->query($query);
    $row = $result->fetch_assoc();
    
    $next = ($row['max_no'] ?? 0) + 1;
    return $prefix . str_pad($next, $length, '0', STR_PAD_LEFT);
}

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function checkRole($allowedRoles = []) {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
    
    if (!empty($allowedRoles) && !in_array($_SESSION['user_role'], $allowedRoles)) {
        alert('Anda tidak memiliki akses ke halaman ini', 'error');
        redirect('dashboard.php');
    }
}

function getUserData() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'nama' => $_SESSION['nama_lengkap'],
        'role' => $_SESSION['user_role'],
        'id_apotik' => $_SESSION['id_apotik'] ?? null,
        'nama_apotik' => $_SESSION['nama_apotik'] ?? null
    ];
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
    
    // Set session timeout
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_LIFETIME)) {
        session_unset();
        session_destroy();
        redirect('login.php?timeout=1');
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}
?>