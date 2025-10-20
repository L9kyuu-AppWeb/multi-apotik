<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin', 'kasir']);

$db = db();

$id_pelanggan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if pelanggan exists
$stmt = $db->prepare("SELECT nama_pelanggan FROM pelanggan WHERE id_pelanggan = ?");
$stmt->bind_param("i", $id_pelanggan);
$stmt->execute();
$pelanggan = $stmt->get_result()->fetch_assoc();

if (!$pelanggan) {
    alert('Pelanggan tidak ditemukan', 'error');
    redirect('index.php');
}

// Check if pelanggan has transactions
$checks = [
    'penjualan' => "SELECT COUNT(*) as total FROM penjualan WHERE id_pelanggan = ?",
    'resep' => "SELECT COUNT(*) as total FROM resep WHERE id_pelanggan = ?"
];

$hasData = false;
$errors = [];

foreach ($checks as $table => $query) {
    $checkStmt = $db->prepare($query);
    $checkStmt->bind_param("i", $id_pelanggan);
    $checkStmt->execute();
    $result = $checkStmt->get_result()->fetch_assoc();
    
    if ($result['total'] > 0) {
        $hasData = true;
        $errors[] = ucfirst($table) . ": " . $result['total'] . " record";
    }
}

if ($hasData) {
    $errorMsg = "Pelanggan tidak dapat dihapus karena masih memiliki data terkait:<br>";
    $errorMsg .= "• " . implode("<br>• ", $errors);
    $errorMsg .= "<br><br>Data pelanggan dengan riwayat transaksi tidak dapat dihapus untuk menjaga integritas data.";
    
    alert($errorMsg, 'error');
    redirect('index.php');
}

try {
    // Delete pelanggan (no related data)
    $stmtDelete = $db->prepare("DELETE FROM pelanggan WHERE id_pelanggan = ?");
    $stmtDelete->bind_param("i", $id_pelanggan);
    
    if ($stmtDelete->execute()) {
        alert('Pelanggan "' . $pelanggan['nama_pelanggan'] . '" berhasil dihapus!', 'success');
    } else {
        throw new Exception('Gagal menghapus data');
    }
    
} catch (Exception $e) {
    alert('Error: ' . $e->getMessage(), 'error');
}

redirect('index.php');
?>