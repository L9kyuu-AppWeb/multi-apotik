<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin', 'kasir', 'manajer']);

$user = getUserData();
$db = db();

// Build query based on role
if ($user['role'] === 'manajer') {
    $query = "SELECT o.*, a.nama_apotik, k.nama_kategori,
              COALESCE(SUM(b.stok_sisa), 0) as total_stok,
              COUNT(b.id_batch) as jumlah_batch
              FROM obat o
              LEFT JOIN apotik a ON o.id_apotik = a.id_apotik
              LEFT JOIN kategori_obat k ON o.id_kategori = k.id_kategori
              LEFT JOIN batch_obat b ON o.id_obat = b.id_obat AND b.status = 'tersedia'
              GROUP BY o.id_obat
              ORDER BY o.nama_obat";
    $obatList = $db->query($query);
} else {
    $query = "SELECT o.*, k.nama_kategori,
              COALESCE(SUM(b.stok_sisa), 0) as total_stok,
              COUNT(b.id_batch) as jumlah_batch
              FROM obat o
              LEFT JOIN kategori_obat k ON o.id_kategori = k.id_kategori
              LEFT JOIN batch_obat b ON o.id_obat = b.id_obat AND b.status = 'tersedia'
              WHERE o.id_apotik = ?
              GROUP BY o.id_obat
              ORDER BY o.nama_obat";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $user['id_apotik']);
    $stmt->execute();
    $obatList = $stmt->get_result();
}

$pageTitle = 'Data Obat';
include '../includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Data Obat</h2>
            <p class="text-gray-600 mt-1">Kelola master data obat</p>
        </div>
        <?php if ($user['role'] === 'admin'): ?>
        <a href="create.php" class="px-6 py-3 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg transition-all">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Tambah Obat
        </a>
        <?php endif; ?>
    </div>

    <!-- Stats Cards -->
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
            <p class="text-2xl font-bold text-gray-800"><?= number_format($obatList->num_rows) ?></p>
        </div>

        <?php
        $obatList->data_seek(0);
        $stok_menipis = 0;
        $stok_habis = 0;
        while ($row = $obatList->fetch_assoc()) {
            if ($row['total_stok'] == 0) $stok_habis++;
            elseif ($row['total_stok'] < 10) $stok_menipis++;
        }
        $obatList->data_seek(0);
        ?>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Stok Menipis</h3>
                <div class="w-10 h-10 bg-yellow-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($stok_menipis) ?></p>
            <p class="text-xs text-gray-500 mt-1">Stok < 10</p>
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
            <p class="text-2xl font-bold text-gray-800"><?= number_format($stok_habis) ?></p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Obat Aktif</h3>
                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($obatList->num_rows) ?></p>
        </div>
    </div>

    <!-- Search -->
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <input type="text" id="searchObat" placeholder="Cari nama obat, kode, atau jenis..." 
               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
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
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Jenis</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Stok</th>
                        <th class="text-right py-4 px-6 text-sm font-semibold text-gray-600">Harga Jual</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Status</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100" id="obatTableBody">
                    <?php 
                    $obatList->data_seek(0);
                    while ($obat = $obatList->fetch_assoc()): 
                        $stok_class = $obat['total_stok'] == 0 ? 'text-red-600' : ($obat['total_stok'] < 10 ? 'text-yellow-600' : 'text-green-600');
                    ?>
                    <tr class="hover:bg-gray-50 obat-row" 
                        data-search="<?= strtolower($obat['kode_obat'] . ' ' . $obat['nama_obat'] . ' ' . $obat['jenis_obat']) ?>">
                        <td class="py-4 px-6">
                            <span class="font-mono text-sm text-gray-600"><?= htmlspecialchars($obat['kode_obat']) ?></span>
                        </td>
                        <td class="py-4 px-6">
                            <div>
                                <span class="font-semibold text-gray-800"><?= htmlspecialchars($obat['nama_obat']) ?></span>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($obat['satuan']) ?></p>
                            </div>
                        </td>
                        <?php if ($user['role'] === 'manajer'): ?>
                        <td class="py-4 px-6">
                            <span class="text-sm text-gray-600"><?= htmlspecialchars($obat['nama_apotik']) ?></span>
                        </td>
                        <?php endif; ?>
                        <td class="py-4 px-6">
                            <span class="text-sm text-gray-600"><?= htmlspecialchars($obat['nama_kategori'] ?? '-') ?></span>
                        </td>
                        <td class="py-4 px-6">
                            <span class="text-sm text-gray-600"><?= htmlspecialchars($obat['jenis_obat']) ?></span>
                        </td>
                        <td class="py-4 px-6 text-center">
                            <span class="font-bold <?= $stok_class ?>"><?= number_format($obat['total_stok']) ?></span>
                            <p class="text-xs text-gray-500"><?= $obat['jumlah_batch'] ?> batch</p>
                        </td>
                        <td class="py-4 px-6 text-right">
                            <span class="font-semibold text-gray-800"><?= formatRupiah($obat['harga_jual']) ?></span>
                        </td>
                        <td class="py-4 px-6 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?= $obat['status'] === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                <?= ucfirst($obat['status']) ?>
                            </span>
                        </td>
                        <td class="py-4 px-6">
                            <div class="flex items-center justify-center space-x-2">
                                <a href="batch.php?id=<?= $obat['id_obat'] ?>" 
                                   class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-all" 
                                   title="Kelola Batch">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </a>
                                <?php if ($user['role'] === 'admin'): ?>
                                <a href="edit.php?id=<?= $obat['id_obat'] ?>" 
                                   class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-all" 
                                   title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <button onclick="if(confirmDelete()) window.location='delete.php?id=<?= $obat['id_obat'] ?>'" 
                                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-all" 
                                        title="Hapus">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('searchObat').addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    document.querySelectorAll('.obat-row').forEach(row => {
        const searchData = row.dataset.search;
        row.style.display = searchData.includes(search) ? '' : 'none';
    });
});
</script>

<?php include '../includes/footer.php'; ?>