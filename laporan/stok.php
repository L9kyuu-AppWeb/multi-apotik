<?php
define('APP_ACCESS', true);
require_once '../config.php';
requireLogin();

$user = getUserData();
$db = db();

// Filter parameters
$filter_stok = $_GET['filter_stok'] ?? 'semua';
$id_apotik_filter = $_GET['id_apotik'] ?? '';
$id_kategori = $_GET['id_kategori'] ?? '';

// Build query
if ($user['role'] === 'manajer') {
    $query = "SELECT 
                o.id_obat,
                o.kode_obat,
                o.nama_obat,
                o.jenis_obat,
                o.satuan,
                o.harga_jual,
                a.nama_apotik,
                k.nama_kategori,
                COALESCE(SUM(CASE WHEN b.status = 'tersedia' THEN b.stok_sisa ELSE 0 END), 0) as total_stok,
                COUNT(CASE WHEN b.status = 'tersedia' THEN b.id_batch END) as jumlah_batch,
                MIN(CASE WHEN b.status = 'tersedia' THEN b.tanggal_kadaluarsa END) as expired_terdekat
              FROM obat o
              LEFT JOIN apotik a ON o.id_apotik = a.id_apotik
              LEFT JOIN kategori_obat k ON o.id_kategori = k.id_kategori
              LEFT JOIN batch_obat b ON o.id_obat = b.id_obat
              WHERE o.status = 'aktif'";
    
    if ($id_apotik_filter) {
        $query .= " AND o.id_apotik = " . (int)$id_apotik_filter;
    }
} else {
    $query = "SELECT 
                o.id_obat,
                o.kode_obat,
                o.nama_obat,
                o.jenis_obat,
                o.satuan,
                o.harga_jual,
                k.nama_kategori,
                COALESCE(SUM(CASE WHEN b.status = 'tersedia' THEN b.stok_sisa ELSE 0 END), 0) as total_stok,
                COUNT(CASE WHEN b.status = 'tersedia' THEN b.id_batch END) as jumlah_batch,
                MIN(CASE WHEN b.status = 'tersedia' THEN b.tanggal_kadaluarsa END) as expired_terdekat
              FROM obat o
              LEFT JOIN kategori_obat k ON o.id_kategori = k.id_kategori
              LEFT JOIN batch_obat b ON o.id_obat = b.id_obat
              WHERE o.id_apotik = ? AND o.status = 'aktif'";
}

if ($id_kategori) {
    $query .= " AND o.id_kategori = " . (int)$id_kategori;
}

$query .= " GROUP BY o.id_obat";

// Filter by stok status
if ($filter_stok === 'habis') {
    $query .= " HAVING total_stok = 0";
} elseif ($filter_stok === 'menipis') {
    $query .= " HAVING total_stok > 0 AND total_stok < 10";
}

$query .= " ORDER BY o.nama_obat";

// Execute query
if ($user['role'] === 'manajer') {
    $laporan = $db->query($query);
} else {
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $user['id_apotik']);
    $stmt->execute();
    $laporan = $stmt->get_result();
}

// Calculate statistics
$stats = [
    'total_obat' => 0,
    'stok_tersedia' => 0,
    'stok_habis' => 0,
    'stok_menipis' => 0,
    'total_nilai' => 0
];

$laporan->data_seek(0);
while ($row = $laporan->fetch_assoc()) {
    $stats['total_obat']++;
    if ($row['total_stok'] == 0) {
        $stats['stok_habis']++;
    } elseif ($row['total_stok'] < 10) {
        $stats['stok_menipis']++;
    }
    $stats['total_nilai'] += $row['total_stok'] * $row['harga_jual'];
}
$stats['stok_tersedia'] = $stats['total_obat'] - $stats['stok_habis'];
$laporan->data_seek(0);

// Get apotik list for filter (manajer only)
if ($user['role'] === 'manajer') {
    $apotikList = $db->query("SELECT * FROM apotik WHERE status = 'aktif' ORDER BY nama_apotik");
}

// Get kategori list
$kategoriList = $db->query("SELECT * FROM kategori_obat ORDER BY nama_kategori");

$pageTitle = 'Laporan Stok Obat';
include '../includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between no-print">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Laporan Stok Obat</h2>
            <p class="text-gray-600 mt-1">Monitoring stok obat realtime</p>
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
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status Stok</label>
                <select name="filter_stok" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                    <option value="semua" <?= $filter_stok === 'semua' ? 'selected' : '' ?>>Semua Stok</option>
                    <option value="menipis" <?= $filter_stok === 'menipis' ? 'selected' : '' ?>>Stok Menipis (< 10)</option>
                    <option value="habis" <?= $filter_stok === 'habis' ? 'selected' : '' ?>>Stok Habis</option>
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
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                <select name="id_kategori" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                    <option value="">Semua Kategori</option>
                    <?php while ($kat = $kategoriList->fetch_assoc()): ?>
                    <option value="<?= $kat['id_kategori'] ?>" <?= $id_kategori == $kat['id_kategori'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($kat['nama_kategori']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
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

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Total Obat</h3>
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($stats['total_obat']) ?></p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Stok Tersedia</h3>
                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($stats['stok_tersedia']) ?></p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Stok Menipis</h3>
                <div class="w-10 h-10 bg-yellow-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($stats['stok_menipis']) ?></p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Stok Habis</h3>
                <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($stats['stok_habis']) ?></p>
        </div>
    </div>

    <!-- Total Nilai Stok -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-100 text-sm mb-1">Total Nilai Stok (berdasarkan harga jual)</p>
                <p class="text-3xl font-bold"><?= formatRupiah($stats['total_nilai']) ?></p>
            </div>
            <div class="w-16 h-16 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
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
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Kategori</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Stok</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Batch</th>
                        <th class="text-right py-4 px-6 text-sm font-semibold text-gray-600">Harga</th>
                        <th class="text-right py-4 px-6 text-sm font-semibold text-gray-600">Nilai Stok</th>
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Exp Terdekat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if ($laporan->num_rows > 0): ?>
                        <?php 
                        $laporan->data_seek(0);
                        while ($row = $laporan->fetch_assoc()): 
                            $stok_class = $row['total_stok'] == 0 ? 'text-red-600' : ($row['total_stok'] < 10 ? 'text-yellow-600' : 'text-green-600');
                            $expired_soon = $row['expired_terdekat'] ? (strtotime($row['expired_terdekat']) - time()) / (60*60*24) : null;
                        ?>
                        <tr class="hover:bg-gray-50">
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
                                <span class="text-sm text-gray-600"><?= htmlspecialchars($row['nama_kategori'] ?? '-') ?></span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="font-bold <?= $stok_class ?>"><?= number_format($row['total_stok']) ?></span>
                                <span class="text-xs text-gray-500 ml-1"><?= htmlspecialchars($row['satuan']) ?></span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="text-sm text-gray-600"><?= $row['jumlah_batch'] ?> batch</span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <span class="text-sm text-gray-600"><?= formatRupiah($row['harga_jual']) ?></span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <span class="font-semibold text-gray-800"><?= formatRupiah($row['total_stok'] * $row['harga_jual']) ?></span>
                            </td>
                            <td class="py-4 px-6">
                                <?php if ($row['expired_terdekat']): ?>
                                <div>
                                    <p class="text-sm text-gray-800"><?= formatTanggal($row['expired_terdekat']) ?></p>
                                    <?php if ($expired_soon !== null && $expired_soon <= 30): ?>
                                    <p class="text-xs text-red-600 font-semibold">⚠ <?= round($expired_soon) ?> hari</p>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <span class="text-sm text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $user['role'] === 'manajer' ? '9' : '8' ?>" class="py-12 text-center">
                                <p class="text-gray-500">Tidak ada data</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Print Info -->
    <div class="hidden print:block mt-8 text-center text-sm text-gray-500">
        <p>Dicetak pada: <?= date('d/m/Y H:i:s') ?></p>
        <p>Filter: <?= ucfirst($filter_stok) ?></p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>