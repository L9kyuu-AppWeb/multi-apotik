<?php
define('APP_ACCESS', true);
require_once '../config.php';
requireLogin();

$user = getUserData();
$db = db();

// Filter parameters
$hari_warning = $_GET['hari_warning'] ?? 30;
$id_apotik_filter = $_GET['id_apotik'] ?? '';

// Build query
if ($user['role'] === 'manajer') {
    $query = "SELECT 
                b.id_batch,
                b.no_batch,
                b.tanggal_kadaluarsa,
                b.stok_sisa,
                o.kode_obat,
                o.nama_obat,
                o.jenis_obat,
                o.satuan,
                o.harga_jual,
                a.nama_apotik,
                DATEDIFF(b.tanggal_kadaluarsa, CURDATE()) as hari_tersisa
              FROM batch_obat b
              JOIN obat o ON b.id_obat = o.id_obat
              JOIN apotik a ON o.id_apotik = a.id_apotik
              WHERE b.tanggal_kadaluarsa BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                AND b.status = 'tersedia'
                AND b.stok_sisa > 0";
    
    if ($id_apotik_filter) {
        $query .= " AND o.id_apotik = " . (int)$id_apotik_filter;
    }
    
    $query .= " ORDER BY b.tanggal_kadaluarsa ASC, a.nama_apotik, o.nama_obat";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $hari_warning);
} else {
    $query = "SELECT 
                b.id_batch,
                b.no_batch,
                b.tanggal_kadaluarsa,
                b.stok_sisa,
                o.kode_obat,
                o.nama_obat,
                o.jenis_obat,
                o.satuan,
                o.harga_jual,
                DATEDIFF(b.tanggal_kadaluarsa, CURDATE()) as hari_tersisa
              FROM batch_obat b
              JOIN obat o ON b.id_obat = o.id_obat
              WHERE o.id_apotik = ?
                AND b.tanggal_kadaluarsa BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                AND b.status = 'tersedia'
                AND b.stok_sisa > 0
              ORDER BY b.tanggal_kadaluarsa ASC, o.nama_obat";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $user['id_apotik'], $hari_warning);
}

$stmt->execute();
$laporan = $stmt->get_result();

// Calculate statistics
$stats = [
    'total_batch' => 0,
    'total_stok' => 0,
    'nilai_kerugian' => 0,
    'expired_7' => 0,
    'expired_14' => 0,
    'expired_30' => 0
];

$laporan->data_seek(0);
while ($row = $laporan->fetch_assoc()) {
    $stats['total_batch']++;
    $stats['total_stok'] += $row['stok_sisa'];
    $stats['nilai_kerugian'] += $row['stok_sisa'] * $row['harga_jual'];
    
    if ($row['hari_tersisa'] <= 7) {
        $stats['expired_7']++;
    } elseif ($row['hari_tersisa'] <= 14) {
        $stats['expired_14']++;
    } else {
        $stats['expired_30']++;
    }
}
$laporan->data_seek(0);

// Get apotik list for filter (manajer only)
if ($user['role'] === 'manajer') {
    $apotikList = $db->query("SELECT * FROM apotik WHERE status = 'aktif' ORDER BY nama_apotik");
}

$pageTitle = 'Laporan Obat Expired';
include '../includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between no-print">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Laporan Obat Mendekati Expired</h2>
            <p class="text-gray-600 mt-1">Monitoring obat yang akan kadaluarsa</p>
        </div>
        <button onclick="window.print()" class="px-6 py-3 bg-purple-600 text-white rounded-xl font-semibold hover:shadow-lg transition-all">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
            </svg>
            Cetak Laporan
        </button>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-2xl shadow-sm p-6 no-print">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Periode Warning (Hari)</label>
                <select name="hari_warning" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                    <option value="7" <?= $hari_warning == 7 ? 'selected' : '' ?>>7 Hari</option>
                    <option value="14" <?= $hari_warning == 14 ? 'selected' : '' ?>>14 Hari</option>
                    <option value="30" <?= $hari_warning == 30 ? 'selected' : '' ?>>30 Hari</option>
                    <option value="60" <?= $hari_warning == 60 ? 'selected' : '' ?>>60 Hari</option>
                    <option value="90" <?= $hari_warning == 90 ? 'selected' : '' ?>>90 Hari</option>
                </select>
            </div>

            <?php if ($user['role'] === 'manajer'): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Apotik</label>
                <select name="id_apotik" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                    <option value="">Semua Apotik</option>
                    <?php while ($apotik = $apotikList->fetch_assoc()): ?>
                    <option value="<?= $apotik['id_apotik'] ?>" <?= $id_apotik_filter == $apotik['id_apotik'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($apotik['nama_apotik']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 gradient-bg text-white rounded-xl font-medium hover:shadow-lg transition-all">
                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Alert Box -->
    <?php if ($stats['expired_7'] > 0): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-6 rounded-xl">
        <div class="flex items-start space-x-3">
            <svg class="w-6 h-6 text-red-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div class="flex-1">
                <p class="text-sm font-bold text-red-800 mb-1">⚠️ PERINGATAN KRITIS!</p>
                <p class="text-sm text-red-700">Ada <strong><?= $stats['expired_7'] ?> batch</strong> obat yang akan expired dalam 7 hari. Segera lakukan tindakan!</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Total Batch</h3>
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($stats['total_batch']) ?></p>
            <p class="text-xs text-gray-500 mt-1">Batch obat</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Expired ≤ 7 Hari</h3>
                <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-red-600"><?= number_format($stats['expired_7']) ?></p>
            <p class="text-xs text-red-500 mt-1 font-semibold">KRITIS!</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Total Stok</h3>
                <div class="w-10 h-10 bg-yellow-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($stats['total_stok']) ?></p>
            <p class="text-xs text-gray-500 mt-1">Unit obat</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Est. Nilai Kerugian</h3>
                <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold text-orange-600"><?= formatRupiah($stats['nilai_kerugian']) ?></p>
            <p class="text-xs text-gray-500 mt-1">Jika tidak terjual</p>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Kode</th>
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Nama Obat</th>
                        <?php if ($user['role'] === 'manajer'): ?>
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Apotik</th>
                        <?php endif; ?>
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Batch</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Stok</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Tgl Expired</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Hari Tersisa</th>
                        <th class="text-right py-4 px-6 text-sm font-semibold text-gray-600">Harga Satuan</th>
                        <th class="text-right py-4 px-6 text-sm font-semibold text-gray-600">Total Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if ($laporan->num_rows > 0): ?>
                        <?php 
                        $laporan->data_seek(0);
                        while ($row = $laporan->fetch_assoc()): 
                            // Determine urgency class
                            if ($row['hari_tersisa'] <= 7) {
                                $urgency_class = 'bg-red-50';
                                $badge_class = 'bg-red-500 text-white';
                                $badge_text = 'KRITIS';
                            } elseif ($row['hari_tersisa'] <= 14) {
                                $urgency_class = 'bg-orange-50';
                                $badge_class = 'bg-orange-500 text-white';
                                $badge_text = 'URGENT';
                            } else {
                                $urgency_class = 'bg-yellow-50';
                                $badge_class = 'bg-yellow-500 text-white';
                                $badge_text = 'WARNING';
                            }
                        ?>
                        <tr class="hover:bg-gray-50 <?= $urgency_class ?>">
                            <td class="py-4 px-6">
                                <span class="font-mono text-sm text-gray-600"><?= htmlspecialchars($row['kode_obat']) ?></span>
                            </td>
                            <td class="py-4 px-6">
                                <div>
                                    <p class="font-semibold text-gray-800"><?= htmlspecialchars($row['nama_obat']) ?></p>
                                    <p class="text-xs text-gray-500"><?= htmlspecialchars($row['jenis_obat']) ?></p>
                                </div>
                            </td>
                            <?php if ($user['role'] === 'manajer'): ?>
                            <td class="py-4 px-6">
                                <span class="text-sm text-gray-600"><?= htmlspecialchars($row['nama_apotik']) ?></span>
                            </td>
                            <?php endif; ?>
                            <td class="py-4 px-6">
                                <span class="font-mono text-sm text-gray-800"><?= htmlspecialchars($row['no_batch']) ?></span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="font-bold text-gray-800"><?= number_format($row['stok_sisa']) ?></span>
                                <span class="text-xs text-gray-500 ml-1"><?= htmlspecialchars($row['satuan']) ?></span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="text-sm text-gray-800"><?= formatTanggal($row['tanggal_kadaluarsa']) ?></span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold <?= $badge_class ?>">
                                    <?= $row['hari_tersisa'] ?> Hari
                                </span>
                                <p class="text-xs text-gray-500 mt-1"><?= $badge_text ?></p>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <span class="text-sm text-gray-600"><?= formatRupiah($row['harga_jual']) ?></span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <span class="font-bold text-gray-800"><?= formatRupiah($row['stok_sisa'] * $row['harga_jual']) ?></span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <!-- Total Row -->
                        <tr class="bg-gray-50 font-bold">
                            <td class="py-4 px-6" colspan="<?= $user['role'] === 'manajer' ? '4' : '3' ?>">
                                <span class="text-gray-800">TOTAL</span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="text-gray-800"><?= number_format($stats['total_stok']) ?></span>
                            </td>
                            <td class="py-4 px-6" colspan="3"></td>
                            <td class="py-4 px-6 text-right">
                                <span class="text-red-600"><?= formatRupiah($stats['nilai_kerugian']) ?></span>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $user['role'] === 'manajer' ? '9' : '8' ?>" class="py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-16 h-16 text-green-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="text-green-600 font-medium text-lg">✓ Tidak ada obat yang mendekati expired</p>
                                    <p class="text-gray-400 text-sm mt-1">Semua batch obat aman dalam periode <?= $hari_warning ?> hari</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Action Recommendations -->
    <?php if ($laporan->num_rows > 0): ?>
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">💡 Rekomendasi Tindakan</h3>
        <div class="space-y-3">
            <div class="flex items-start space-x-3">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <span class="text-purple-600 font-bold">1</span>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-800">Prioritaskan Penjualan</p>
                    <p class="text-sm text-gray-600">Jual obat yang mendekati expired terlebih dahulu (First Expired First Out - FEFO)</p>
                </div>
            </div>
            
            <div class="flex items-start space-x-3">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <span class="text-purple-600 font-bold">2</span>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-800">Berikan Diskon Khusus</p>
                    <p class="text-sm text-gray-600">Pertimbangkan memberikan diskon untuk mempercepat penjualan obat yang hampir expired</p>
                </div>
            </div>
            
            <div class="flex items-start space-x-3">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <span class="text-purple-600 font-bold">3</span>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-800">Koordinasi dengan Supplier</p>
                    <p class="text-sm text-gray-600">Untuk batch dengan stok besar, hubungi supplier untuk kemungkinan retur atau tukar barang</p>
                </div>
            </div>
            
            <div class="flex items-start space-x-3">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <span class="text-purple-600 font-bold">4</span>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-800">Review Pembelian</p>
                    <p class="text-sm text-gray-600">Evaluasi pola pembelian untuk menghindari overstock di masa mendatang</p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Print Info -->
    <div class="hidden print:block mt-8 text-center text-sm text-gray-500">
        <p>Dicetak pada: <?= date('d/m/Y H:i:s') ?></p>
        <p>Periode Warning: <?= $hari_warning ?> Hari</p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>