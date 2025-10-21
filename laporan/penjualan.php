<?php
define('APP_ACCESS', true);
require_once '../config.php';
requireLogin();

$user = getUserData();
$db = db();

// Filter parameters
$tanggal_dari = $_GET['tanggal_dari'] ?? date('Y-m-01');
$tanggal_sampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');
$id_apotik_filter = $_GET['id_apotik'] ?? '';

// Build query
if ($user['role'] === 'manajer') {
    $query = "SELECT 
                DATE(p.tanggal_penjualan) as tanggal,
                a.nama_apotik,
                COUNT(p.id_penjualan) as total_transaksi,
                SUM(p.total_item) as total_item,
                SUM(p.subtotal) as subtotal,
                SUM(p.diskon) as diskon,
                SUM(p.pajak) as pajak,
                SUM(p.total_bayar) as total_bayar
              FROM penjualan p
              LEFT JOIN apotik a ON p.id_apotik = a.id_apotik
              WHERE DATE(p.tanggal_penjualan) BETWEEN ? AND ?";
    
    if ($id_apotik_filter) {
        $query .= " AND p.id_apotik = " . (int)$id_apotik_filter;
    }
    
    $query .= " GROUP BY DATE(p.tanggal_penjualan), p.id_apotik
                ORDER BY tanggal DESC, a.nama_apotik";
} else {
    $query = "SELECT 
                DATE(p.tanggal_penjualan) as tanggal,
                COUNT(p.id_penjualan) as total_transaksi,
                SUM(p.total_item) as total_item,
                SUM(p.subtotal) as subtotal,
                SUM(p.diskon) as diskon,
                SUM(p.pajak) as pajak,
                SUM(p.total_bayar) as total_bayar
              FROM penjualan p
              WHERE p.id_apotik = ? AND DATE(p.tanggal_penjualan) BETWEEN ? AND ?
              GROUP BY DATE(p.tanggal_penjualan)
              ORDER BY tanggal DESC";
}

// Execute query
if ($user['role'] === 'manajer') {
    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $tanggal_dari, $tanggal_sampai);
} else {
    $stmt = $db->prepare($query);
    $stmt->bind_param("iss", $user['id_apotik'], $tanggal_dari, $tanggal_sampai);
}

$stmt->execute();
$laporan = $stmt->get_result();

// Calculate totals
$grand_total = [
    'transaksi' => 0,
    'item' => 0,
    'subtotal' => 0,
    'diskon' => 0,
    'pajak' => 0,
    'total' => 0
];

$laporan->data_seek(0);
while ($row = $laporan->fetch_assoc()) {
    $grand_total['transaksi'] += $row['total_transaksi'];
    $grand_total['item'] += $row['total_item'];
    $grand_total['subtotal'] += $row['subtotal'];
    $grand_total['diskon'] += $row['diskon'];
    $grand_total['pajak'] += $row['pajak'];
    $grand_total['total'] += $row['total_bayar'];
}
$laporan->data_seek(0);

// Get apotik list for filter (manajer only)
if ($user['role'] === 'manajer') {
    $apotikList = $db->query("SELECT * FROM apotik WHERE status = 'aktif' ORDER BY nama_apotik");
}

$pageTitle = 'Laporan Penjualan';
include '../includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between no-print">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Laporan Penjualan</h2>
            <p class="text-gray-600 mt-1">Laporan penjualan per periode</p>
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Dari Tanggal</label>
                <input type="date" name="tanggal_dari" value="<?= $tanggal_dari ?>" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sampai Tanggal</label>
                <input type="date" name="tanggal_sampai" value="<?= $tanggal_sampai ?>" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
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

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Total Transaksi</h3>
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($grand_total['transaksi']) ?></p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Total Item</h3>
                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($grand_total['item']) ?></p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Rata-rata/Transaksi</h3>
                <div class="w-10 h-10 bg-yellow-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800">
                <?= formatRupiah($grand_total['transaksi'] > 0 ? $grand_total['total'] / $grand_total['transaksi'] : 0) ?>
            </p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Total Penjualan</h3>
                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= formatRupiah($grand_total['total']) ?></p>
        </div>
    </div>

    <!-- Laporan Table -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Tanggal</th>
                        <?php if ($user['role'] === 'manajer'): ?>
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Apotik</th>
                        <?php endif; ?>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Transaksi</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Item</th>
                        <th class="text-right py-4 px-6 text-sm font-semibold text-gray-600">Subtotal</th>
                        <th class="text-right py-4 px-6 text-sm font-semibold text-gray-600">Diskon</th>
                        <th class="text-right py-4 px-6 text-sm font-semibold text-gray-600">Pajak</th>
                        <th class="text-right py-4 px-6 text-sm font-semibold text-gray-600">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if ($laporan->num_rows > 0): ?>
                        <?php 
                        $laporan->data_seek(0);
                        while ($row = $laporan->fetch_assoc()): 
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-6">
                                <span class="font-medium text-gray-800"><?= formatTanggal($row['tanggal']) ?></span>
                            </td>
                            <?php if ($user['role'] === 'manajer'): ?>
                            <td class="py-4 px-6">
                                <span class="text-sm text-gray-600"><?= htmlspecialchars($row['nama_apotik']) ?></span>
                            </td>
                            <?php endif; ?>
                            <td class="py-4 px-6 text-center">
                                <span class="font-semibold text-gray-800"><?= number_format($row['total_transaksi']) ?></span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="text-gray-600"><?= number_format($row['total_item']) ?></span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <span class="text-gray-800"><?= formatRupiah($row['subtotal']) ?></span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <span class="text-red-600"><?= formatRupiah($row['diskon']) ?></span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <span class="text-gray-600"><?= formatRupiah($row['pajak']) ?></span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <span class="font-bold text-gray-800"><?= formatRupiah($row['total_bayar']) ?></span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <!-- Grand Total Row -->
                        <tr class="bg-gray-50 font-bold">
                            <td class="py-4 px-6" colspan="<?= $user['role'] === 'manajer' ? '2' : '1' ?>">
                                <span class="text-gray-800">GRAND TOTAL</span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="text-gray-800"><?= number_format($grand_total['transaksi']) ?></span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="text-gray-800"><?= number_format($grand_total['item']) ?></span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <span class="text-gray-800"><?= formatRupiah($grand_total['subtotal']) ?></span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <span class="text-red-600"><?= formatRupiah($grand_total['diskon']) ?></span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <span class="text-gray-800"><?= formatRupiah($grand_total['pajak']) ?></span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <span class="text-purple-600"><?= formatRupiah($grand_total['total']) ?></span>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $user['role'] === 'manajer' ? '8' : '7' ?>" class="py-12 text-center">
                                <p class="text-gray-500">Tidak ada data untuk periode ini</p>
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
        <p>Periode: <?= formatTanggal($tanggal_dari) ?> s/d <?= formatTanggal($tanggal_sampai) ?></p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>