<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin', 'manajer']);

$user = getUserData();
$db = db();

$id_pembelian = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get pembelian data
$query = "SELECT p.*, a.nama_apotik, s.nama_supplier, s.contact_person, s.no_telp as telp_supplier,
          u.nama_lengkap as nama_user
          FROM pembelian p
          LEFT JOIN apotik a ON p.id_apotik = a.id_apotik
          LEFT JOIN supplier s ON p.id_supplier = s.id_supplier
          LEFT JOIN users u ON p.id_user = u.id_user
          WHERE p.id_pembelian = ?";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $id_pembelian);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    alert('Pembelian tidak ditemukan', 'error');
    redirect('index.php');
}

$pembelian = $result->fetch_assoc();

// Get detail items
$detailQuery = "SELECT dp.*, o.nama_obat, o.satuan, b.no_batch, b.tanggal_kadaluarsa
                FROM detail_pembelian dp
                JOIN obat o ON dp.id_obat = o.id_obat
                LEFT JOIN batch_obat b ON dp.id_batch = b.id_batch
                WHERE dp.id_pembelian = ?
                ORDER BY o.nama_obat";

$stmtDetail = $db->prepare($detailQuery);
$stmtDetail->bind_param("i", $id_pembelian);
$stmtDetail->execute();
$details = $stmtDetail->get_result();

$pageTitle = 'Detail Pembelian';
include '../includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between no-print">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Detail Pembelian</h2>
            <p class="text-gray-600 mt-1">Informasi lengkap transaksi pembelian</p>
        </div>
        <div class="flex space-x-2">
            <button onclick="window.print()" class="px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-all">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Cetak
            </button>
            <a href="index.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all">
                Kembali
            </a>
        </div>
    </div>

    <!-- Info Header -->
    <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-2xl font-bold mb-2"><?= htmlspecialchars($pembelian['no_faktur']) ?></h3>
                <p class="text-orange-100">Tanggal: <?= formatTanggal($pembelian['tanggal_pembelian']) ?></p>
            </div>
            <div class="text-right">
                <p class="text-orange-100 text-sm mb-1">Total Pembelian</p>
                <p class="text-3xl font-bold"><?= formatRupiah($pembelian['total_bayar']) ?></p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Supplier Info -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Informasi Supplier</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Nama Supplier</p>
                        <p class="font-semibold text-gray-800"><?= htmlspecialchars($pembelian['nama_supplier']) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Contact Person</p>
                        <p class="font-semibold text-gray-800"><?= htmlspecialchars($pembelian['contact_person']) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">No. Telepon</p>
                        <p class="font-semibold text-gray-800"><?= htmlspecialchars($pembelian['telp_supplier']) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Status Pembayaran</p>
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
                    </div>
                </div>
            </div>

            <!-- Detail Items -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800">Detail Obat</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left py-3 px-6 text-sm font-semibold text-gray-600">Nama Obat</th>
                                <th class="text-left py-3 px-6 text-sm font-semibold text-gray-600">Batch</th>
                                <th class="text-center py-3 px-6 text-sm font-semibold text-gray-600">Qty</th>
                                <th class="text-right py-3 px-6 text-sm font-semibold text-gray-600">Harga Beli</th>
                                <th class="text-right py-3 px-6 text-sm font-semibold text-gray-600">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php while ($item = $details->fetch_assoc()): ?>
                            <tr>
                                <td class="py-3 px-6">
                                    <div>
                                        <p class="font-semibold text-gray-800"><?= htmlspecialchars($item['nama_obat']) ?></p>
                                        <p class="text-xs text-gray-500">Satuan: <?= htmlspecialchars($item['satuan']) ?></p>
                                    </div>
                                </td>
                                <td class="py-3 px-6">
                                    <div>
                                        <p class="text-sm font-mono text-gray-800"><?= htmlspecialchars($item['no_batch']) ?></p>
                                        <p class="text-xs text-gray-500">Exp: <?= formatTanggal($item['tanggal_kadaluarsa']) ?></p>
                                    </div>
                                </td>
                                <td class="py-3 px-6 text-center">
                                    <span class="font-semibold text-gray-800"><?= $item['qty'] ?></span>
                                </td>
                                <td class="py-3 px-6 text-right">
                                    <span class="text-gray-600"><?= formatRupiah($item['harga_beli']) ?></span>
                                </td>
                                <td class="py-3 px-6 text-right">
                                    <span class="font-bold text-gray-800"><?= formatRupiah($item['subtotal']) ?></span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($pembelian['keterangan']): ?>
            <!-- Keterangan -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-3">Keterangan</h3>
                <p class="text-gray-600"><?= nl2br(htmlspecialchars($pembelian['keterangan'])) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column - Summary -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Info Pembelian -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Informasi Pembelian</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">No. Faktur</p>
                        <p class="font-mono font-semibold text-gray-800"><?= htmlspecialchars($pembelian['no_faktur']) ?></p>
                    </div>
                    <?php if ($user['role'] === 'manajer'): ?>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Apotik</p>
                        <p class="font-semibold text-gray-800"><?= htmlspecialchars($pembelian['nama_apotik']) ?></p>
                    </div>
                    <?php endif; ?>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Tanggal Pembelian</p>
                        <p class="font-semibold text-gray-800"><?= formatTanggal($pembelian['tanggal_pembelian']) ?></p>
                    </div>
                    <?php if ($pembelian['tanggal_jatuh_tempo']): ?>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Jatuh Tempo</p>
                        <p class="font-semibold text-gray-800"><?= formatTanggal($pembelian['tanggal_jatuh_tempo']) ?></p>
                    </div>
                    <?php endif; ?>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Dibuat Oleh</p>
                        <p class="font-semibold text-gray-800"><?= htmlspecialchars($pembelian['nama_user']) ?></p>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Ringkasan</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Item</span>
                        <span class="font-semibold text-gray-800"><?= $pembelian['total_item'] ?> item</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-semibold text-gray-800"><?= formatRupiah($pembelian['subtotal']) ?></span>
                    </div>
                    <?php if ($pembelian['diskon'] > 0): ?>
                    <div class="flex justify-between text-red-600">
                        <span>Diskon</span>
                        <span class="font-semibold">-<?= formatRupiah($pembelian['diskon']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($pembelian['pajak'] > 0): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Pajak</span>
                        <span class="font-semibold text-gray-800"><?= formatRupiah($pembelian['pajak']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between pt-3 border-t border-gray-200">
                        <span class="text-lg font-bold text-gray-800">Total Bayar</span>
                        <span class="text-lg font-bold text-purple-600"><?= formatRupiah($pembelian['total_bayar']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>