<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin', 'manajer']);

$user = getUserData();
$db = db();

// Get dokter list with statistics
$query = "SELECT d.*,
          (SELECT COUNT(*) FROM resep WHERE id_dokter = d.id_dokter) as total_resep,
          (SELECT MAX(tanggal_resep) FROM resep WHERE id_dokter = d.id_dokter) as resep_terakhir
          FROM dokter d
          ORDER BY d.nama_dokter";
$dokterList = $db->query($query);

// Calculate totals
$total_dokter = $dokterList->num_rows;
$total_aktif = 0;
$total_nonaktif = 0;
$dokterList->data_seek(0);
while ($row = $dokterList->fetch_assoc()) {
    if ($row['status'] === 'aktif') $total_aktif++;
    else $total_nonaktif++;
}
$dokterList->data_seek(0);

$pageTitle = 'Data Dokter';
include '../includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Data Dokter</h2>
            <p class="text-gray-600 mt-1">Kelola data dokter yang bekerja sama</p>
        </div>
        <?php if ($user['role'] === 'admin'): ?>
        <a href="create.php" class="px-6 py-3 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg transition-all">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Tambah Dokter
        </a>
        <?php endif; ?>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Total Dokter</h3>
                <div class="w-10 h-10 bg-teal-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($total_dokter) ?></p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Dokter Aktif</h3>
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

    <!-- Search -->
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <input type="text" id="searchDokter" placeholder="Cari nama dokter, kode, atau spesialis..." 
               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
    </div>

    <!-- Dokter Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php 
        $dokterList->data_seek(0);
        while ($dokter = $dokterList->fetch_assoc()): 
        ?>
        <div class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden dokter-card" 
             data-search="<?= strtolower($dokter['kode_dokter'] . ' ' . $dokter['nama_dokter'] . ' ' . $dokter['spesialis']) ?>">
            
            <!-- Header -->
            <div class="bg-gradient-to-r from-teal-500 to-teal-600 p-6 text-white">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <h3 class="text-xl font-bold mb-1"><?= htmlspecialchars($dokter['nama_dokter']) ?></h3>
                        <p class="text-teal-100 text-sm font-mono"><?= htmlspecialchars($dokter['kode_dokter']) ?></p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?= $dokter['status'] === 'aktif' ? 'bg-green-500 text-white' : 'bg-gray-400 text-white' ?>">
                        <?= ucfirst($dokter['status']) ?>
                    </span>
                </div>
                
                <?php if ($dokter['spesialis']): ?>
                <div class="inline-flex items-center px-3 py-1 bg-teal-400 bg-opacity-30 rounded-full">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <span class="text-xs font-medium"><?= htmlspecialchars($dokter['spesialis']) ?></span>
                </div>
                <?php endif; ?>
                
                <!-- Stats -->
                <div class="mt-4 pt-4 border-t border-teal-400">
                    <p class="text-2xl font-bold"><?= number_format($dokter['total_resep']) ?></p>
                    <p class="text-xs text-teal-100">Total Resep</p>
                </div>
            </div>

            <!-- Body -->
            <div class="p-6">
                <!-- Info -->
                <div class="space-y-3 mb-4">
                    <?php if ($dokter['no_str']): ?>
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                        </svg>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500">No. STR</p>
                            <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($dokter['no_str']) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($dokter['no_telp']): ?>
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        <p class="text-sm text-gray-600"><?= htmlspecialchars($dokter['no_telp']) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($dokter['alamat']): ?>
                    <div class="flex items-start space-x-3">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <p class="text-sm text-gray-600 line-clamp-2"><?= htmlspecialchars($dokter['alamat']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Last Prescription -->
                <?php if ($dokter['resep_terakhir']): ?>
                <div class="pt-3 border-t border-gray-200">
                    <p class="text-xs text-gray-500">Resep Terakhir:</p>
                    <p class="text-sm font-medium text-gray-800"><?= formatTanggal($dokter['resep_terakhir'], 'd M Y') ?></p>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <?php if ($user['role'] === 'admin'): ?>
                <div class="flex space-x-2 mt-4">
                    <a href="edit.php?id=<?= $dokter['id_dokter'] ?>" 
                       class="flex-1 px-4 py-2 bg-blue-50 text-blue-600 rounded-xl text-center font-medium hover:bg-blue-100 transition-all">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </a>
                    <button onclick="if(confirmDelete()) window.location='delete.php?id=<?= $dokter['id_dokter'] ?>'" 
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

    <?php if ($dokterList->num_rows === 0): ?>
    <div class="bg-white rounded-2xl shadow-sm p-12 text-center">
        <svg class="w-24 h-24 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <p class="text-gray-500 font-medium text-lg">Belum ada data dokter</p>
        <p class="text-gray-400 mt-2">Tambahkan dokter untuk memulai</p>
    </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('searchDokter').addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    document.querySelectorAll('.dokter-card').forEach(card => {
        const searchData = card.dataset.search;
        card.style.display = searchData.includes(search) ? '' : 'none';
    });
});
</script>

<?php include '../includes/footer.php'; ?>