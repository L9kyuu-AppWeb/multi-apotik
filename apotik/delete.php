<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin']);

$db = db();

$id_apotik = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if apotik exists
$stmt = $db->prepare("SELECT nama_apotik FROM apotik WHERE id_apotik = ?");
$stmt->bind_param("i", $id_apotik);
$stmt->execute();
$apotik = $stmt->get_result()->fetch_assoc();

if (!$apotik) {
    alert('Apotik tidak ditemukan', 'error');
    redirect('index.php');
}

// Check if apotik has related data
$checks = [
    'users' => "SELECT COUNT(*) as total FROM users WHERE id_apotik = ?",
    'obat' => "SELECT COUNT(*) as total FROM obat WHERE id_apotik = ?",
    'penjualan' => "SELECT COUNT(*) as total FROM penjualan WHERE id_apotik = ?",
    'pembelian' => "SELECT COUNT(*) as total FROM pembelian WHERE id_apotik = ?",
    'resep' => "SELECT COUNT(*) as total FROM resep WHERE id_apotik = ?",
    'pengeluaran' => "SELECT COUNT(*) as total FROM pengeluaran WHERE id_apotik = ?"
];

$hasData = false;
$errors = [];

foreach ($checks as $table => $query) {
    $checkStmt = $db->prepare($query);
    $checkStmt->bind_param("i", $id_apotik);
    $checkStmt->execute();
    $result = $checkStmt->get_result()->fetch_assoc();
    
    if ($result['total'] > 0) {
        $hasData = true;
        $errors[] = ucfirst($table) . ": " . $result['total'] . " record";
    }
}

if ($hasData) {
    $errorMsg = "Apotik tidak dapat dihapus karena masih memiliki data terkait:<br>";
    $errorMsg .= "• " . implode("<br>• ", $errors);
    $errorMsg .= "<br><br>Silakan hapus semua data terkait terlebih dahulu atau ubah status menjadi Non-Aktif.";
    
    alert($errorMsg, 'error');
    redirect('index.php');
}

try {
    // Delete apotik (no related data)
    $stmtDelete = $db->prepare("DELETE FROM apotik WHERE id_apotik = ?");
    $stmtDelete->bind_param("i", $id_apotik);
    
    if ($stmtDelete->execute()) {
        alert('Apotik "' . $apotik['nama_apotik'] . '" berhasil dihapus!', 'success');
    } else {
        throw new Exception('Gagal menghapus data');
    }
    
} catch (Exception $e) {
    alert('Error: ' . $e->getMessage(), 'error');
}

redirect('index.php');
?>