<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin']);

$db = db();
$currentUser = getUserData();

$id_user = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Prevent self-deletion
if ($id_user == $currentUser['id']) {
    alert('Anda tidak dapat menghapus akun Anda sendiri!', 'error');
    redirect('index.php');
}

// Check if user exists
$stmt = $db->prepare("SELECT username, nama_lengkap FROM users WHERE id_user = ?");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    alert('User tidak ditemukan', 'error');
    redirect('index.php');
}

// Check if user has transactions
$checkStmt = $db->prepare("SELECT COUNT(*) as total FROM penjualan WHERE id_user = ?");
$checkStmt->bind_param("i", $id_user);
$checkStmt->execute();
$check = $checkStmt->get_result()->fetch_assoc();

if ($check['total'] > 0) {
    alert('User tidak dapat dihapus karena sudah ada ' . $check['total'] . ' transaksi terkait. Ubah status menjadi Non-Aktif sebagai gantinya.', 'error');
    redirect('index.php');
}

try {
    // Delete user (no related transactions)
    $stmtDelete = $db->prepare("DELETE FROM users WHERE id_user = ?");
    $stmtDelete->bind_param("i", $id_user);
    
    if ($stmtDelete->execute()) {
        alert('User "' . $user['nama_lengkap'] . '" berhasil dihapus!', 'success');
    } else {
        throw new Exception('Gagal menghapus data');
    }
    
} catch (Exception $e) {
    alert('Error: ' . $e->getMessage(), 'error');
}

redirect('index.php');
?>