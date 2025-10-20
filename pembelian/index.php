<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin', 'manajer']);

$user = getUserData();
$db = db();

// Filter parameters
$tanggal_dari = $_GET['tanggal_dari'] ?? date('Y-m-01');
$tanggal_sampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');
$id_supplier = $_GET['id_supplier'] ?? '';
$id_apotik_filter = $_GET['id_apotik'] ?? '';

// Build query based on role
if ($user['role'] === 'manajer') {
    $query = "SELECT p.*, a.nama_apotik, s.nama_supplier, u.nama_lengkap as nama_user
              FROM pembelian p
              LEFT JOIN apotik a ON p.id_apotik = a.id_apotik
              LEFT JOIN supplier s ON p.id_supplier = s.id_supplier
              LEFT JOIN users u ON p.id_user = u.id_user
              WHERE DATE(p.tanggal_pembelian) BETWEEN ? AND ?";
    
    if ($id_apotik_filter) {
        $query .= " AND p.id_apotik = " . (int)$id_apotik_filter;
    }
} else {
    $query = "SELECT p.*, s.nama_supplier, u.nama_lengkap as nama_user
              FROM pembelian p
              LEFT JOIN supplier s ON p.id_supplier = s.id_supplier
              LEFT JOIN users u ON p.id_user = u.id_user
              WHERE p.id_apotik = ? AND DATE(p.tanggal_pembelian) BETWEEN ? AND ?";
}

if ($id_supplier) {
    $query .= " AND p.id_supplier = " . (int)$id_supplier;
}

$query .= " ORDER BY p.tanggal_pembelian DESC";

// Execute query
if ($user['role'] === 'manajer') {
    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $tanggal_dari, $tanggal_sampai);
} else {
    $stmt = $db->prepare($query);
    $stmt->bind_param("iss", $user['id_apotik'], $tanggal_dari, $tanggal_sampai);
}

$stmt->execute();
$pembelianList = $stmt->get_result();

// Calculate summary
$total_pembelian = 0;
$total_transaksi = 0;
$pembelianList->data_seek(0);
while ($row = $pembelianList->fetch_assoc()) {
    $total_pembelian += $row['total_bayar'];
    $total_transaksi++;
}
$pembelianList->data_seek(0);

// Get supplier list for filter
$supplierList = $db->query("SELECT * FROM supplier WHERE status = 'aktif' ORDER BY nama_supplier");

// Get apotik list for manajer
if ($user['role'] === 'manajer') {
    $apotikList = $db->query("SELECT * FROM apotik WHERE status = 'aktif' ORDER BY nama_apotik");
}

$pageTitle = 'Riwayat Pembelian';
include '../includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Riwayat Pembelian</h2>
            <p class="text-gray-600 mt-1">Daftar transaksi pembelian obat</p>
        </div>
        <?php if ($user['role'] === 'admin'): ?>
        <a href="create.php" class="px-6 py-3 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg transition-all">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Pembelian Baru
        </a>
        <?php endif; ?>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Total Pembelian</h3>
                <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= formatRupiah($total_pembelian) ?></p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Total Transaksi</h3>
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($total_transaksi) ?></p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Rata-rata per Transaksi</h3>
                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800">
                <?= formatRupiah($total_transaksi > 0 ? $total_pembelian / $total_transaksi : 0) ?>
            </p>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-2xl shadow-sm p-6">
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
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Supplier</label>
                <select name="id_supplier" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                    <option value="">Semua Supplier</option>
                    <?php while ($supplier = $supplierList->fetch_assoc()): ?>
                    <option value="<?= $supplier['id_supplier'] ?>" <?= $id_supplier == $supplier['id_supplier'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($supplier['nama_supplier']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 px-4 py-2 gradient-bg text-white rounded-xl font-medium hover:shadow-lg transition-all">
                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Filter
                </button>
                <a href="index.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl font-medium hover:bg-gray-300 transition-all">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">No. Faktur</th>
                        <?php if ($user['role'] === 'manajer'): ?>
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Apotik</th>
                        <?php endif; ?>
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Tanggal</th>
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Supplier</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Item</th>
                        <th class="text-right py-4 px-6 text-sm font-semibold text-gray-600">Total</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Status</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if ($pembelianList->num_rows > 0): ?>
                        <?php 
                        $pembelianList->data_seek(0);
                        while ($pembelian = $pembelianList->fetch_assoc()): 
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-6">
                                <span class="font-semibold text-gray-800"><?= htmlspecialchars($pembelian['no_faktur']) ?></span>
                            </td>
                            <?php if ($user['role'] === 'manajer'): ?>
                            <td class="py-4 px-6">
                                <span class="text-sm text-gray-600"><?= htmlspecialchars($pembelian['nama_apotik']) ?></span>
                            </td>
                            <?php endif; ?>
                            <td class="py-4 px-6">
                                <span class="text-sm text-gray-600"><?= formatTanggal($pembelian['tanggal_pembelian']) ?></span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="text-sm text-gray-600"><?= htmlspecialchars($pembelian['nama_supplier']) ?></span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="font-semibold text-gray-800"><?= $pembelian['total_item'] ?> item</span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <span class="font-bold text-gray-800"><?= formatRupiah($pembelian['total_bayar']) ?></span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <?php
                                $status_colors = [
                                    'lunas' => 'bg-green-100 text-green-800',
                                    'belum_lunas' => 'bg-yellow-100 text-yellow-800',
                                    'kredit' => 'bg-blue-100 text-blue-800'
                                ];
                                $color = $status_colors[$pembelian['status_pembayaran']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?= $color ?>">
                                    <?= ucfirst(str_replace('_', ' ', $pembelian['status_pembayaran'])) ?>
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center justify-center space-x-2">
                                    <a href="detail.php?id=<?= $pembelian['id_pembelian'] ?>" 
                                       class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-all" 
                                       title="Detail">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $user['role'] === 'manajer' ? '8' : '7' ?>" class="py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    <p class="text-gray-500 font-medium">Belum ada data pembelian</p>
                                    <p class="text-gray-400 text-sm mt-1">Belum ada transaksi pembelian pada periode ini</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>