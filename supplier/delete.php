<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin']);

$db = db();

$id_supplier = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if supplier exists
$stmt = $db->prepare("SELECT nama_supplier FROM supplier WHERE id_supplier = ?");
$stmt->bind_param("i", $id_supplier);
$stmt->execute();
$supplier = $stmt->get_result()->fetch_assoc();

if (!$supplier) {
    alert('Supplier tidak ditemukan', 'error');
    redirect('index.php');
}

// Check if supplier has transactions
$checkStmt = $db->prepare("SELECT COUNT(*) as total FROM pembelian WHERE id_supplier = ?");
$checkStmt->bind_param("i", $id_supplier);
$checkStmt->execute();
$check = $checkStmt->get_result()->fetch_assoc();

if ($check['total'] > 0) {
    alert('Supplier tidak dapat dihapus karena sudah ada ' . $check['total'] . ' transaksi pembelian terkait. Ubah status menjadi Non-Aktif sebagai gantinya.', 'error');
    redirect('index.php');
}

try {
    // Delete supplier (no related transactions)
    $stmtDelete = $db->prepare("DELETE FROM supplier WHERE id_supplier = ?");
    $stmtDelete->bind_param("i", $id_supplier);
    
    if ($stmtDelete->execute()) {
        alert('Supplier "' . $supplier['nama_supplier'] . '" berhasil dihapus!', 'success');
    } else {
        throw new Exception('Gagal menghapus data');
    }
    
} catch (Exception $e) {
    alert('Error: ' . $e->getMessage(), 'error');
}

redirect('index.php');
?>