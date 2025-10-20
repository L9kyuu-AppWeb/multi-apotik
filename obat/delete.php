<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin']);

$db = db();

$id_obat = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if obat exists
$stmt = $db->prepare("SELECT nama_obat FROM obat WHERE id_obat = ?");
$stmt->bind_param("i", $id_obat);
$stmt->execute();
$obat = $stmt->get_result()->fetch_assoc();

if (!$obat) {
    alert('Obat tidak ditemukan', 'error');
    redirect('index.php');
}

// Check if obat has transactions
$checkStmt = $db->prepare("SELECT COUNT(*) as total FROM detail_penjualan WHERE id_obat = ?");
$checkStmt->bind_param("i", $id_obat);
$checkStmt->execute();
$check = $checkStmt->get_result()->fetch_assoc();

if ($check['total'] > 0) {
    alert('Obat tidak dapat dihapus karena sudah ada transaksi terkait. Ubah status menjadi Non-Aktif sebagai gantinya.', 'error');
    redirect('index.php');
}

try {
    // Delete obat (cascade will delete batch_obat)
    $stmtDelete = $db->prepare("DELETE FROM obat WHERE id_obat = ?");
    $stmtDelete->bind_param("i", $id_obat);
    
    if ($stmtDelete->execute()) {
        alert('Data obat "' . $obat['nama_obat'] . '" berhasil dihapus!', 'success');
    } else {
        throw new Exception('Gagal menghapus data');
    }
    
} catch (Exception $e) {
    alert('Error: ' . $e->getMessage(), 'error');
}

redirect('index.php');
?>