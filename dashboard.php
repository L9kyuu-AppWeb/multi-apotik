<?php
define('APP_ACCESS', true);
require_once 'config.php';
requireLogin();

$user = getUserData();
$db = db();

// Get statistics based on role
$stats = [];

if ($user['role'] === 'manajer') {
    // Manajer can see all branches
    $today = date('Y-m-d');
    
    // Total penjualan hari ini
    $query = "SELECT COALESCE(SUM(total_bayar), 0) as total FROM penjualan WHERE DATE(tanggal_penjualan) = '$today'";
    $result = $db->query($query);
    $stats['penjualan_hari_ini'] = $result->fetch_assoc()['total'];
    
    // Total transaksi hari ini
    $query = "SELECT COUNT(*) as total FROM penjualan WHERE DATE(tanggal_penjualan) = '$today'";
    $result = $db->query($query);
    $stats['transaksi_hari_ini'] = $result->fetch_assoc()['total'];
    
    // Total apotik
    $query = "SELECT COUNT(*) as total FROM apotik WHERE status = 'aktif'";
    $result = $db->query($query);
    $stats['total_apotik'] = $result->fetch_assoc()['total'];
    
    // Obat mendekati expired
    $query = "SELECT COUNT(*) as total FROM batch_obat WHERE tanggal_kadaluarsa BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status = 'tersedia'";
    $result = $db->query($query);
    $stats['obat_expired_warning'] = $result->fetch_assoc()['total'];
    
    // Penjualan per apotik
    $query = "SELECT a.nama_apotik, COALESCE(SUM(p.total_bayar), 0) as total, COUNT(p.id_penjualan) as transaksi
              FROM apotik a
              LEFT JOIN penjualan p ON a.id_apotik = p.id_apotik AND DATE(p.tanggal_penjualan) = '$today'
              WHERE a.status = 'aktif'
              GROUP BY a.id_apotik
              ORDER BY total DESC
              LIMIT 5";
    $stats['penjualan_per_apotik'] = $db->query($query);
    
} else {
    // Admin & Kasir only see their branch
    $apotik_id = $user['id_apotik'];
    $today = date('Y-m-d');
    
    // Total penjualan hari ini
    $query = "SELECT COALESCE(SUM(total_bayar), 0) as total FROM penjualan WHERE id_apotik = $apotik_id AND DATE(tanggal_penjualan) = '$today'";
    $result = $db->query($query);
    $stats['penjualan_hari_ini'] = $result->fetch_assoc()['total'];
    
    // Total transaksi hari ini
    $query = "SELECT COUNT(*) as total FROM penjualan WHERE id_apotik = $apotik_id AND DATE(tanggal_penjualan) = '$today'";
    $result = $db->query($query);
    $stats['transaksi_hari_ini'] = $result->fetch_assoc()['total'];
    
    // Total obat
    $query = "SELECT COUNT(*) as total FROM obat WHERE id_apotik = $apotik_id AND status = 'aktif'";
    $result = $db->query($query);
    $stats['total_obat'] = $result->fetch_assoc()['total'];
    
    // Stok menipis (< 10)
    $query = "SELECT COUNT(DISTINCT o.id_obat) as total 
              FROM obat o 
              LEFT JOIN batch_obat b ON o.id_obat = b.id_obat AND b.status = 'tersedia'
              WHERE o.id_apotik = $apotik_id 
              GROUP BY o.id_obat
              HAVING COALESCE(SUM(b.stok_sisa), 0) < 10";
    $result = $db->query($query);
    $stats['stok_menipis'] = $result->num_rows;
}

// Recent transactions (last 5)
if ($user['role'] === 'manajer') {
    $query = "SELECT p.*, a.nama_apotik, u.nama_lengkap 
              FROM penjualan p
              LEFT JOIN apotik a ON p.id_apotik = a.id_apotik
              LEFT JOIN users u ON p.id_user = u.id_user
              ORDER BY p.tanggal_penjualan DESC
              LIMIT 5";
} else {
    $apotik_id = $user['id_apotik'];
    $query = "SELECT p.*, u.nama_lengkap 
              FROM penjualan p
              LEFT JOIN users u ON p.id_user = u.id_user
              WHERE p.id_apotik = $apotik_id
              ORDER BY p.tanggal_penjualan DESC
              LIMIT 5";
}
$recentTransactions = $db->query($query);

$pageTitle = 'Dashboard';
include 'includes/header.php';
?>

<div class="space-y-6">
    <!-- Welcome Section -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-2xl p-8 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2">Selamat Datang, <?= htmlspecialchars($user['nama']) ?>!</h1>
                <p class="text-purple-100">
                    <?php if ($user['role'] === 'manajer'): ?>
                        Anda login sebagai Manajer - Akses ke semua cabang
                    <?php else: ?>
                        <?= htmlspecialchars($user['nama_apotik'] ?? 'Apotik') ?> - <?= ucfirst($user['role']) ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="hidden md:block">
                <svg class="w-24 h-24 text-white opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Card 1 -->
        <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-lg transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <span class="text-xs font-medium text-green-600 bg-green-100 px-3 py-1 rounded-full">Hari Ini</span>
            </div>
            <h3 class="text-gray-500 text-sm font-medium mb-1">Penjualan</h3>
            <p class="text-2xl font-bold text-gray-800"><?= formatRupiah($stats['penjualan_hari_ini']) ?></p>
        </div>

        <!-- Card 2 -->
        <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-lg transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
                <span class="text-xs font-medium text-blue-600 bg-blue-100 px-3 py-1 rounded-full">Hari Ini</span>
            </div>
            <h3 class="text-gray-500 text-sm font-medium mb-1">Total Transaksi</h3>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($stats['transaksi_hari_ini']) ?></p>
        </div>

        <!-- Card 3 -->
        <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-lg transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
            </div>
            <h3 class="text-gray-500 text-sm font-medium mb-1">
                <?= $user['role'] === 'manajer' ? 'Total Apotik' : 'Total Obat' ?>
            </h3>
            <p class="text-2xl font-bold text-gray-800">
                <?= $user['role'] === 'manajer' ? number_format($stats['total_apotik']) : number_format($stats['total_obat']) ?>
            </p>
        </div>

        <!-- Card 4 -->
        <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-lg transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <span class="text-xs font-medium text-red-600 bg-red-100 px-3 py-1 rounded-full">Alert</span>
            </div>
            <h3 class="text-gray-500 text-sm font-medium mb-1">
                <?= $user['role'] === 'manajer' ? 'Obat Expired (30hr)' : 'Stok Menipis' ?>
            </h3>
            <p class="text-2xl font-bold text-gray-800">
                <?= $user['role'] === 'manajer' ? number_format($stats['obat_expired_warning']) : number_format($stats['stok_menipis']) ?>
            </p>
        </div>
    </div>

    <?php if ($user['role'] === 'manajer'): ?>
    <!-- Penjualan Per Apotik (Manajer Only) -->
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Penjualan Per Apotik Hari Ini</h2>
        <div class="space-y-4">
            <?php while ($row = $stats['penjualan_per_apotik']->fetch_assoc()): ?>
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-all">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-500 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800"><?= htmlspecialchars($row['nama_apotik']) ?></p>
                        <p class="text-sm text-gray-500"><?= $row['transaksi'] ?> transaksi</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xl font-bold text-gray-800"><?= formatRupiah($row['total']) ?></p>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-800">Transaksi Terbaru</h2>
            <a href="penjualan/index.php" class="text-purple-600 hover:text-purple-700 font-medium text-sm">
                Lihat Semua →
            </a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">No. Transaksi</th>
                        <?php if ($user['role'] === 'manajer'): ?>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Apotik</th>
                        <?php endif; ?>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Tanggal</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Kasir</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Tipe</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600">Total</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentTransactions->num_rows > 0): ?>
                        <?php while ($trx = $recentTransactions->fetch_assoc()): ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-3 px-4">
                                <span class="font-medium text-gray-800"><?= htmlspecialchars($trx['no_transaksi']) ?></span>
                            </td>
                            <?php if ($user['role'] === 'manajer'): ?>
                            <td class="py-3 px-4">
                                <span class="text-sm text-gray-600"><?= htmlspecialchars($trx['nama_apotik']) ?></span>
                            </td>
                            <?php endif; ?>
                            <td class="py-3 px-4">
                                <span class="text-sm text-gray-600"><?= formatTanggalWaktu($trx['tanggal_penjualan'], 'd/m/Y H:i') ?></span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="text-sm text-gray-600"><?= htmlspecialchars($trx['nama_lengkap']) ?></span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $trx['tipe_penjualan'] === 'resep' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' ?>">
                                    <?= ucfirst($trx['tipe_penjualan']) ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <span class="font-semibold text-gray-800"><?= formatRupiah($trx['total_bayar']) ?></span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <a href="penjualan/detail.php?id=<?= $trx['id_penjualan'] ?>" class="text-purple-600 hover:text-purple-700">
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $user['role'] === 'manajer' ? '7' : '6' ?>" class="py-8 text-center text-gray-500">
                                Belum ada transaksi hari ini
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php if ($user['role'] !== 'manajer'): ?>
        <a href="penjualan/create.php" class="bg-gradient-to-r from-green-500 to-green-600 rounded-2xl p-6 text-white hover:shadow-lg transition-all group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold mb-2">Transaksi Baru</h3>
                    <p class="text-green-100 text-sm">Buat penjualan baru</p>
                </div>
                <svg class="w-12 h-12 opacity-50 group-hover:opacity-100 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
            </div>
        </a>
        <?php endif; ?>

        <a href="obat/index.php" class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl p-6 text-white hover:shadow-lg transition-all group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold mb-2">Data Obat</h3>
                    <p class="text-blue-100 text-sm">Kelola stok obat</p>
                </div>
                <svg class="w-12 h-12 opacity-50 group-hover:opacity-100 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
            </div>
        </a>

        <a href="laporan/index.php" class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-2xl p-6 text-white hover:shadow-lg transition-all group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold mb-2">Laporan</h3>
                    <p class="text-purple-100 text-sm">Lihat laporan lengkap</p>
                </div>
                <svg class="w-12 h-12 opacity-50 group-hover:opacity-100 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>