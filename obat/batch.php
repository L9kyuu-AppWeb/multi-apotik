<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin', 'kasir', 'manajer']);

$user = getUserData();
$db = db();

$id_obat = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get obat data
$stmtObat = $db->prepare("SELECT o.*, a.nama_apotik 
                          FROM obat o 
                          LEFT JOIN apotik a ON o.id_apotik = a.id_apotik
                          WHERE o.id_obat = ?");
$stmtObat->bind_param("i", $id_obat);
$stmtObat->execute();
$obat = $stmtObat->get_result()->fetch_assoc();

if (!$obat) {
    alert('Obat tidak ditemukan', 'error');
    redirect('index.php');
}

// Get batch list
$queryBatch = "SELECT * FROM batch_obat WHERE id_obat = ? ORDER BY tanggal_kadaluarsa ASC";
$stmtBatch = $db->prepare($queryBatch);
$stmtBatch->bind_param("i", $id_obat);
$stmtBatch->execute();
$batchList = $stmtBatch->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_batch' && $user['role'] === 'admin') {
        try {
            $no_batch = sanitize($_POST['no_batch']);
            $tanggal_produksi = $_POST['tanggal_produksi'];
            $tanggal_kadaluarsa = $_POST['tanggal_kadaluarsa'];
            $stok_awal = (int)$_POST['stok_awal'];
            $harga_beli = (float)$_POST['harga_beli'];
            
            $stmt = $db->prepare("INSERT INTO batch_obat (
                id_obat, no_batch, tanggal_produksi, tanggal_kadaluarsa, 
                stok_awal, stok_sisa, harga_beli_per_unit
            ) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("isssiid", 
                $id_obat, $no_batch, $tanggal_produksi, $tanggal_kadaluarsa,
                $stok_awal, $stok_awal, $harga_beli
            );
            
            if ($stmt->execute()) {
                alert('Batch berhasil ditambahkan!', 'success');
                redirect('batch.php?id=' . $id_obat);
            }
        } catch (Exception $e) {
            alert('Error: ' . $e->getMessage(), 'error');
        }
    }
}

// Calculate total stock
$total_stok = 0;
$batchList->data_seek(0);
while ($b = $batchList->fetch_assoc()) {
    if ($b['status'] === 'tersedia') {
        $total_stok += $b['stok_sisa'];
    }
}
$batchList->data_seek(0);

$pageTitle = 'Kelola Batch - ' . $obat['nama_obat'];
include '../includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Kelola Batch Obat</h2>
            <p class="text-gray-600 mt-1"><?= htmlspecialchars($obat['nama_obat']) ?></p>
        </div>
        <a href="index.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all">
            Kembali
        </a>
    </div>

    <!-- Info Obat -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-2xl p-6 text-white">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <p class="text-purple-200 text-sm mb-1">Kode Obat</p>
                <p class="text-xl font-bold"><?= htmlspecialchars($obat['kode_obat']) ?></p>
            </div>
            <div>
                <p class="text-purple-200 text-sm mb-1">Jenis</p>
                <p class="text-xl font-bold"><?= htmlspecialchars($obat['jenis_obat']) ?></p>
            </div>
            <div>
                <p class="text-purple-200 text-sm mb-1">Total Stok</p>
                <p class="text-xl font-bold"><?= number_format($total_stok) ?> <?= htmlspecialchars($obat['satuan']) ?></p>
            </div>
            <div>
                <p class="text-purple-200 text-sm mb-1">Harga Jual</p>
                <p class="text-xl font-bold"><?= formatRupiah($obat['harga_jual']) ?></p>
            </div>
        </div>
    </div>

    <?php if ($user['role'] === 'admin'): ?>
    <!-- Add Batch Form -->
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Tambah Batch Baru</h3>
        
        <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="hidden" name="action" value="add_batch">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">No. Batch *</label>
                <input type="text" name="no_batch" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="Contoh: BATCH001">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Produksi</label>
                <input type="date" name="tanggal_produksi" value="<?= date('Y-m-d') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Kadaluarsa *</label>
                <input type="date" name="tanggal_kadaluarsa" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Stok Awal *</label>
                <input type="number" name="stok_awal" min="1" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="0">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Harga Beli per Unit *</label>
                <input type="number" name="harga_beli" step="0.01" min="0" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="0">
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg transition-all">
                    Tambah Batch
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Batch List -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-800">Daftar Batch</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">No. Batch</th>
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Tgl Produksi</th>
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Tgl Kadaluarsa</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Stok Awal</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Stok Sisa</th>
                        <th class="text-right py-4 px-6 text-sm font-semibold text-gray-600">Harga Beli</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if ($batchList->num_rows > 0): ?>
                        <?php while ($batch = $batchList->fetch_assoc()): 
                            $expired_soon = (strtotime($batch['tanggal_kadaluarsa']) - time()) / (60*60*24);
                            $is_expired = $batch['tanggal_kadaluarsa'] < date('Y-m-d');
                        ?>
                        <tr class="hover:bg-gray-50 <?= $is_expired ? 'bg-red-50' : ($expired_soon <= 30 ? 'bg-yellow-50' : '') ?>">
                            <td class="py-4 px-6">
                                <span class="font-mono font-semibold text-gray-800"><?= htmlspecialchars($batch['no_batch']) ?></span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="text-sm text-gray-600"><?= formatTanggal($batch['tanggal_produksi']) ?></span>
                            </td>
                            <td class="py-4 px-6">
                                <div>
                                    <span class="text-sm text-gray-800 font-medium"><?= formatTanggal($batch['tanggal_kadaluarsa']) ?></span>
                                    <?php if ($is_expired): ?>
                                        <p class="text-xs text-red-600 font-semibold">EXPIRED!</p>
                                    <?php elseif ($expired_soon <= 30): ?>
                                        <p class="text-xs text-yellow-600"><?= round($expired_soon) ?> hari lagi</p>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="text-sm text-gray-600"><?= number_format($batch['stok_awal']) ?></span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="font-bold <?= $batch['stok_sisa'] == 0 ? 'text-red-600' : 'text-green-600' ?>">
                                    <?= number_format($batch['stok_sisa']) ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <span class="text-sm text-gray-600"><?= formatRupiah($batch['harga_beli_per_unit']) ?></span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <?php
                                $status_colors = [
                                    'tersedia' => 'bg-green-100 text-green-800',
                                    'habis' => 'bg-gray-100 text-gray-800',
                                    'expired' => 'bg-red-100 text-red-800',
                                    'rusak' => 'bg-orange-100 text-orange-800'
                                ];
                                $color = $status_colors[$batch['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?= $color ?>">
                                    <?= ucfirst($batch['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                    <p class="text-gray-500 font-medium">Belum ada batch</p>
                                    <p class="text-gray-400 text-sm mt-1">Tambahkan batch untuk memulai tracking stok</p>
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