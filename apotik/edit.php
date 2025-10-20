<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin']);

$db = db();

$id_apotik = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get apotik data
$stmt = $db->prepare("SELECT * FROM apotik WHERE id_apotik = ?");
$stmt->bind_param("i", $id_apotik);
$stmt->execute();
$apotik = $stmt->get_result()->fetch_assoc();

if (!$apotik) {
    alert('Apotik tidak ditemukan', 'error');
    redirect('index.php');
}

// Get statistics
$statsQuery = "SELECT 
    (SELECT COUNT(*) FROM users WHERE id_apotik = ?) as total_user,
    (SELECT COUNT(*) FROM obat WHERE id_apotik = ?) as total_obat,
    (SELECT COUNT(*) FROM penjualan WHERE id_apotik = ?) as total_transaksi";
$stmtStats = $db->prepare($statsQuery);
$stmtStats->bind_param("iii", $id_apotik, $id_apotik, $id_apotik);
$stmtStats->execute();
$stats = $stmtStats->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nama_apotik = sanitize($_POST['nama_apotik']);
        $alamat = sanitize($_POST['alamat']);
        $no_telp = sanitize($_POST['no_telp']);
        $email = sanitize($_POST['email']);
        $status = sanitize($_POST['status']);
        
        $stmt = $db->prepare("UPDATE apotik SET 
            nama_apotik = ?, alamat = ?, no_telp = ?, email = ?, status = ?
            WHERE id_apotik = ?");
        
        $stmt->bind_param("sssssi",
            $nama_apotik, $alamat, $no_telp, $email, $status, $id_apotik
        );
        
        if ($stmt->execute()) {
            alert('Data apotik berhasil diupdate!', 'success');
            redirect('index.php');
        } else {
            throw new Exception('Gagal update data: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        alert('Error: ' . $e->getMessage(), 'error');
    }
}

$pageTitle = 'Edit Apotik';
include '../includes/header.php';
?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Edit Apotik</h2>
            <p class="text-gray-600 mt-1">Ubah data cabang apotik</p>
        </div>
        <a href="index.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all">
            Kembali
        </a>
    </div>

    <!-- Stats Info -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-2xl p-6 text-white">
        <div class="grid grid-cols-3 gap-6">
            <div class="text-center">
                <p class="text-3xl font-bold"><?= number_format($stats['total_user']) ?></p>
                <p class="text-purple-100 text-sm mt-1">Total User</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold"><?= number_format($stats['total_obat']) ?></p>
                <p class="text-purple-100 text-sm mt-1">Total Obat</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold"><?= number_format($stats['total_transaksi']) ?></p>
                <p class="text-purple-100 text-sm mt-1">Total Transaksi</p>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" class="bg-white rounded-2xl shadow-sm p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Kode Apotik (Read Only) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Kode Apotik</label>
                <input type="text" value="<?= htmlspecialchars($apotik['kode_apotik']) ?>" readonly
                       class="w-full px-4 py-3 bg-gray-100 border border-gray-300 rounded-xl text-gray-600 cursor-not-allowed">
                <p class="text-xs text-gray-500 mt-1">Kode tidak dapat diubah</p>