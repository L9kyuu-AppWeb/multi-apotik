<?php
define('APP_ACCESS', true);
require_once 'config.php';

if (isLoggedIn()) {
    $db = db();
    $user_id = $_SESSION['user_id'];
    
    // Log aktivitas logout
    $stmt = $db->prepare("INSERT INTO log_aktivitas (id_user, tipe_aktivitas, aksi, ip_address, keterangan) 
                          VALUES (?, 'Logout', 'create', ?, 'User logout dari sistem')");
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt->bind_param("is", $user_id, $ip);
    $stmt->execute();
}

// Destroy session
session_unset();
session_destroy();

// Redirect to login
redirect('login.php');
?>