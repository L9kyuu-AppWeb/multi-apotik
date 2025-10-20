<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin', 'kasir', 'manajer']);

$user = getUserData();
$db = db();

// Build query based on role
if ($user['role'] === 'manajer') {
    $query = "SELECT r.*, a.nama_apotik, d.nama_dokter, p.nama_pelanggan,
              (SELECT COUNT(*) FROM detail_resep WHERE id_resep = r.id_resep) as jumlah_obat
              FROM resep r
              LEFT JOIN apotik a ON r.id_apotik = a.id_apotik
              LEFT JOIN dokter d ON r.id_dokter = d.id_dokter
              LEFT JOIN pelanggan p ON r.id_pelanggan = p.id_pelanggan
              ORDER BY r.tanggal_resep DESC";
    $resepList = $db->query($query);
} else {
    $query = "SELECT r.*, d.nama_dokter, p.nama_pelanggan,
              (SELECT COUNT(*) FROM detail_resep WHERE id_resep = r.id_resep) as jumlah_obat
              FROM resep r
              LEFT JOIN dokter d ON r.id_dokter = d.id_dokter
              LEFT JOIN pelanggan p ON r.id_pelanggan = p.id_pelanggan
              WHERE r.id_apotik = ?
              ORDER BY r.tanggal_resep DESC";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $user['id_apotik']);
    $stmt->execute();
    $resepList = $stmt->get_result();
}

// Count by status
$pending = 0;
$selesai = 0;
$resepList->data_seek(0);
while ($r = $resepList->fetch_assoc()) {
    if ($r['status'] == 'pending') $pending++;
    if ($r['status'] == 'selesai') $selesai++;
}
$resepList->data_seek(0);

$pageTitle = 'Resep Dokter';
include '../includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Resep Dokter</h2>
            <p class="text-gray-600 mt-1">Kelola resep dari dokter</p>
        </div>
        <?php if ($user['role'] === 'admin'): ?>
        <a href="create.php" class="px-6 py-3 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg transition-all">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Input Resep
        </a>
        <?php endif; ?>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Total Resep</h3>
                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($resepList->num_rows) ?></p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Pending</h3>
                <div class="w-10 h-10 bg-yellow-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($pending) ?></p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Selesai</h3>
                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($selesai) ?></p>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">No. Resep</th>
                        <?php if ($user['role'] === 'manajer'): ?>
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Apotik</th>
                        <?php endif; ?>
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Tanggal</th>
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Dokter</th>
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Pasien</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Jml Obat</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Status</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if ($resepList->num_rows > 0): ?>
                        <?php 
                        $resepList->data_seek(0);
                        while ($resep = $resepList->fetch_assoc()): 
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-6">
                                <span class="font-semibold text-gray-800"><?= htmlspecialchars($resep['no_resep']) ?></span>
                            </td>
                            <?php if ($user['role'] === 'manajer'): ?>
                            <td class="py-4 px-6">
                                <span class="text-sm text-gray-600"><?= htmlspecialchars($resep['nama_apotik']) ?></span>
                            </td>
                            <?php endif; ?>
                            <td class="py-4 px-6">
                                <span class="text-sm text-gray-600"><?= formatTanggal($resep['tanggal_resep']) ?></span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="text-sm text-gray-600"><?= htmlspecialchars($resep['nama_dokter']) ?></span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="text-sm text-gray-600"><?= htmlspecialchars($resep['nama_pelanggan']) ?></span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="text-sm font-semibold text-gray-800"><?= $resep['jumlah_obat'] ?> obat</span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <?php
                                $status_colors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'diproses' => 'bg-blue-100 text-blue-800',
                                    'selesai' => 'bg-green-100 text-green-800'
                                ];
                                $color = $status_colors[$resep['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?= $color ?>">
                                    <?= ucfirst($resep['status']) ?>
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center justify-center space-x-2">
                                    <a href="detail.php?id=<?= $resep['id_resep'] ?>" 
                                       class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-all" 
                                       title="Detail">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    <?php if ($resep['status'] == 'pending' && $user['role'] !== 'manajer'): ?>
                                    <a href="../penjualan/create.php?resep=<?= $resep['id_resep'] ?>" 
                                       class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-all" 
                                       title="Proses Penjualan">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $user['role'] === 'manajer' ? '8' : '7' ?>" class="py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="text-gray-500 font-medium">Belum ada resep</p>
                                    <p class="text-gray-400 text-sm mt-1">Input resep dokter untuk memulai</p>
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