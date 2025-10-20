<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin', 'manajer']);

$user = getUserData();
$db = db();

// Get apotik list
$query = "SELECT a.*,
          (SELECT COUNT(*) FROM users WHERE id_apotik = a.id_apotik) as total_user,
          (SELECT COUNT(*) FROM obat WHERE id_apotik = a.id_apotik) as total_obat,
          (SELECT COALESCE(SUM(total_bayar), 0) FROM penjualan WHERE id_apotik = a.id_apotik AND DATE(tanggal_penjualan) = CURDATE()) as penjualan_hari_ini
          FROM apotik a
          ORDER BY a.nama_apotik";
$apotikList = $db->query($query);

// Calculate totals
$total_apotik = $apotikList->num_rows;
$total_aktif = 0;
$total_nonaktif = 0;
$apotikList->data_seek(0);
while ($row = $apotikList->fetch_assoc()) {
    if ($row['status'] === 'aktif') $total_aktif++;
    else $total_nonaktif++;
}
$apotikList->data_seek(0);

$pageTitle = 'Data Apotik';
include '../includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Data Apotik</h2>
            <p class="text-gray-600 mt-1">Kelola data cabang apotik</p>
        </div>
        <?php if ($user['role'] === 'admin'): ?>
        <a href="create.php" class="px-6 py-3 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg transition-all">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Tambah Apotik
        </a>
        <?php endif; ?>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Total Apotik</h3>
                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($total_apotik) ?></p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Apotik Aktif</h3>
                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($total_aktif) ?></p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Non-Aktif</h3>
                <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($total_nonaktif) ?></p>
        </div>
    </div>

    <!-- Apotik Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php 
        $apotikList->data_seek(0);
        while ($apotik = $apotikList->fetch_assoc()): 
        ?>
        <div class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden">
            <!-- Header Card -->
            <div class="bg-gradient-to-r from-purple-600 to-indigo-600 p-6 text-white">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="text-xl font-bold mb-1"><?= htmlspecialchars($apotik['nama_apotik']) ?></h3>
                        <p class="text-purple-100 text-sm font-mono"><?= htmlspecialchars($apotik['kode_apotik']) ?></p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?= $apotik['status'] === 'aktif' ? 'bg-green-500 text-white' : 'bg-gray-500 text-white' ?>">
                        <?= ucfirst($apotik['status']) ?>
                    </span>
                </div>
                
                <!-- Stats Mini -->
                <div class="grid grid-cols-3 gap-4 pt-4 border-t border-purple-400">
                    <div class="text-center">
                        <p class="text-2xl font-bold"><?= number_format($apotik['total_user']) ?></p>
                        <p class="text-xs text-purple-100">User</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold"><?= number_format($apotik['total_obat']) ?></p>
                        <p class="text-xs text-purple-100">Obat</p>
                    </div>
                    <div class="text-center">
                        <p class="text-lg font-bold"><?= formatRupiah($apotik['penjualan_hari_ini']) ?></p>
                        <p class="text-xs text-purple-100">Hari Ini</p>
                    </div>
                </div>
            </div>

            <!-- Body Card -->
            <div class="p-6">
                <!-- Contact Info -->
                <div class="space-y-3 mb-6">
                    <div class="flex items-start space-x-3">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <p class="text-sm text-gray-600 flex-1"><?= htmlspecialchars($apotik['alamat']) ?></p>
                    </div>
                    
                    <?php if ($apotik['no_telp']): ?>
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        <p class="text-sm text-gray-600"><?= htmlspecialchars($apotik['no_telp']) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($apotik['email']): ?>
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-sm text-gray-600"><?= htmlspecialchars($apotik['email']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons -->
                <?php if ($user['role'] === 'admin'): ?>
                <div class="flex space-x-2">
                    <a href="edit.php?id=<?= $apotik['id_apotik'] ?>" 
                       class="flex-1 px-4 py-2 bg-blue-50 text-blue-600 rounded-xl text-center font-medium hover:bg-blue-100 transition-all">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </a>
                    <button onclick="if(confirmDelete()) window.location='delete.php?id=<?= $apotik['id_apotik'] ?>'" 
                            class="px-4 py-2 bg-red-50 text-red-600 rounded-xl font-medium hover:bg-red-100 transition-all">
                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <?php if ($apotikList->num_rows === 0): ?>
    <div class="bg-white rounded-2xl shadow-sm p-12 text-center">
        <svg class="w-24 h-24 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
        </svg>
        <p class="text-gray-500 font-medium text-lg">Belum ada data apotik</p>
        <p class="text-gray-400 mt-2">Tambahkan cabang apotik untuk memulai</p>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>