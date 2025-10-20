<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin', 'kasir', 'manajer']);

$user = getUserData();
$db = db();

// Get pelanggan list with statistics
$query = "SELECT p.*,
          (SELECT COUNT(*) FROM penjualan WHERE id_pelanggan = p.id_pelanggan) as total_transaksi,
          (SELECT COALESCE(SUM(total_bayar), 0) FROM penjualan WHERE id_pelanggan = p.id_pelanggan) as total_pembelian,
          (SELECT MAX(tanggal_penjualan) FROM penjualan WHERE id_pelanggan = p.id_pelanggan) as transaksi_terakhir
          FROM pelanggan p
          ORDER BY p.nama_pelanggan";
$pelangganList = $db->query($query);

// Calculate statistics
$total_pelanggan = $pelangganList->num_rows;
$total_laki = 0;
$total_perempuan = 0;
$pelangganList->data_seek(0);
while ($row = $pelangganList->fetch_assoc()) {
    if ($row['jenis_kelamin'] === 'L') $total_laki++;
    if ($row['jenis_kelamin'] === 'P') $total_perempuan++;
}
$pelangganList->data_seek(0);

$pageTitle = 'Data Pelanggan';
include '../includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Data Pelanggan</h2>
            <p class="text-gray-600 mt-1">Kelola data pelanggan/pasien</p>
        </div>
        <?php if ($user['role'] !== 'manajer'): ?>
        <a href="create.php" class="px-6 py-3 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg transition-all">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Tambah Pelanggan
        </a>
        <?php endif; ?>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Total Pelanggan</h3>
                <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($total_pelanggan) ?></p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Laki-laki</h3>
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($total_laki) ?></p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Perempuan</h3>
                <div class="w-10 h-10 bg-pink-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($total_perempuan) ?></p>
        </div>
    </div>

    <!-- Search -->
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <input type="text" id="searchPelanggan" placeholder="Cari nama, nomor identitas, atau telepon..." 
               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
    </div>

    <!-- Pelanggan Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php 
        $pelangganList->data_seek(0);
        while ($pelanggan = $pelangganList->fetch_assoc()): 
            $umur = null;
            if ($pelanggan['tanggal_lahir']) {
                $lahir = new DateTime($pelanggan['tanggal_lahir']);
                $sekarang = new DateTime();
                $umur = $sekarang->diff($lahir)->y;
            }
        ?>
        <div class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden pelanggan-card" 
             data-search="<?= strtolower($pelanggan['nama_pelanggan'] . ' ' . $pelanggan['no_identitas'] . ' ' . $pelanggan['no_telp']) ?>">
            
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 p-6 text-white">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center space-x-3 flex-1">
                        <div class="w-14 h-14 <?= $pelanggan['jenis_kelamin'] === 'L' ? 'bg-blue-400' : 'bg-pink-400' ?> bg-opacity-30 rounded-full flex items-center justify-center">
                            <span class="text-2xl font-bold">
                                <?= strtoupper(substr($pelanggan['nama_pelanggan'], 0, 2)) ?>
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-bold truncate"><?= htmlspecialchars($pelanggan['nama_pelanggan']) ?></h3>
                            <?php if ($pelanggan['no_identitas']): ?>
                            <p class="text-indigo-100 text-xs font-mono"><?= htmlspecialchars($pelanggan['no_identitas']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($pelanggan['jenis_kelamin']): ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $pelanggan['jenis_kelamin'] === 'L' ? 'bg-blue-500' : 'bg-pink-500' ?> text-white">
                        <?= $pelanggan['jenis_kelamin'] === 'L' ? 'L' : 'P' ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <!-- Stats Mini -->
                <div class="grid grid-cols-2 gap-3 pt-3 border-t border-indigo-400">
                    <div>
                        <p class="text-2xl font-bold"><?= number_format($pelanggan['total_transaksi']) ?></p>
                        <p class="text-xs text-indigo-100">Transaksi</p>
                    </div>
                    <div>
                        <p class="text-lg font-bold"><?= formatRupiah($pelanggan['total_pembelian']) ?></p>
                        <p class="text-xs text-indigo-100">Total Belanja</p>
                    </div>
                </div>
            </div>

            <!-- Body -->
            <div class="p-6">
                <!-- Info -->
                <div class="space-y-3 mb-4">
                    <?php if ($umur !== null): ?>
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <div>
                            <p class="text-xs text-gray-500">Usia</p>
                            <p class="text-sm font-medium text-gray-800"><?= $umur ?> tahun</p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($pelanggan['no_telp']): ?>
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        <p class="text-sm text-gray-600"><?= htmlspecialchars($pelanggan['no_telp']) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($pelanggan['email']): ?>
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-sm text-gray-600 truncate"><?= htmlspecialchars($pelanggan['email']) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($pelanggan['alamat']): ?>
                    <div class="flex items-start space-x-3">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <p class="text-sm text-gray-600 line-clamp-2"><?= htmlspecialchars($pelanggan['alamat']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Last Transaction -->
                <?php if ($pelanggan['transaksi_terakhir']): ?>
                <div class="pt-3 border-t border-gray-200">
                    <p class="text-xs text-gray-500">Transaksi Terakhir:</p>
                    <p class="text-sm font-medium text-gray-800"><?= formatTanggal($pelanggan['transaksi_terakhir'], 'd M Y') ?></p>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <?php if ($user['role'] !== 'manajer'): ?>
                <div class="flex space-x-2 mt-4">
                    <a href="edit.php?id=<?= $pelanggan['id_pelanggan'] ?>" 
                       class="flex-1 px-4 py-2 bg-blue-50 text-blue-600 rounded-xl text-center font-medium hover:bg-blue-100 transition-all">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </a>
                    <button onclick="if(confirmDelete()) window.location='delete.php?id=<?= $pelanggan['id_pelanggan'] ?>'" 
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

    <?php if ($pelangganList->num_rows === 0): ?>
    <div class="bg-white rounded-2xl shadow-sm p-12 text-center">
        <svg class="w-24 h-24 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <p class="text-gray-500 font-medium text-lg">Belum ada data pelanggan</p>
        <p class="text-gray-400 mt-2">Tambahkan pelanggan untuk memulai</p>
    </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('searchPelanggan').addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    document.querySelectorAll('.pelanggan-card').forEach(card => {
        const searchData = card.dataset.search;
        card.style.display = searchData.includes(search) ? '' : 'none';
    });
});
</script>

<?php include '../includes/footer.php'; ?>