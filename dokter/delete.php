<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin']);

$db = db();

$id_dokter = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if dokter exists
$stmt = $db->prepare("SELECT nama_dokter FROM dokter WHERE id_dokter = ?");
$stmt->bind_param("i", $id_dokter);
$stmt->execute();
$dokter = $stmt->get_result()->fetch_assoc();

if (!$dokter) {
    alert('Dokter tidak ditemukan', 'error');
    redirect('index.php');
}

// Check if dokter has resep
$checkStmt = $db->prepare("SELECT COUNT(*) as total FROM resep WHERE id_dokter = ?");
$checkStmt->bind_param("i", $id_dokter);
$checkStmt->execute();
$check = $checkStmt->get_result()->fetch_assoc();

if ($check['total'] > 0) {
    alert('Dokter tidak dapat dihapus karena sudah ada ' . $check['total'] . ' resep terkait. Ubah status menjadi Non-Aktif sebagai gantinya.', 'error');
    redirect('index.php');
}

try {
    // Delete dokter (no related resep)
    $stmtDelete = $db->prepare("DELETE FROM dokter WHERE id_dokter = ?");
    $stmtDelete->bind_param("i", $id_dokter);
    
    if ($stmtDelete->execute()) {
        alert('Dokter "' . $dokter['nama_dokter'] . '" berhasil dihapus!', 'success');
    } else {
        throw new Exception('Gagal menghapus data');
    }
    
} catch (Exception $e) {
    alert('Error: ' . $e->getMessage(), 'error');
}

redirect('index.php');
?>