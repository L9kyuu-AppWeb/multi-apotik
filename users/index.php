<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin']);

$user = getUserData();
$db = db();

// Get users list
$query = "SELECT u.*, a.nama_apotik,
          (SELECT COUNT(*) FROM penjualan WHERE id_user = u.id_user) as total_transaksi,
          (SELECT MAX(tanggal_penjualan) FROM penjualan WHERE id_user = u.id_user) as transaksi_terakhir
          FROM users u
          LEFT JOIN apotik a ON u.id_apotik = a.id_apotik
          ORDER BY u.role, u.nama_lengkap";
$userList = $db->query($query);

// Count by role
$count_admin = 0;
$count_kasir = 0;
$count_manajer = 0;
$count_aktif = 0;

$userList->data_seek(0);
while ($row = $userList->fetch_assoc()) {
    if ($row['role'] === 'admin') $count_admin++;
    if ($row['role'] === 'kasir') $count_kasir++;
    if ($row['role'] === 'manajer') $count_manajer++;
    if ($row['status'] === 'aktif') $count_aktif++;
}
$userList->data_seek(0);

$pageTitle = 'Manajemen Pengguna';
include '../includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Manajemen Pengguna</h2>
            <p class="text-gray-600 mt-1">Kelola akses pengguna sistem</p>
        </div>
        <a href="create.php" class="px-6 py-3 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg transition-all">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Tambah User
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Total User</h3>
                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($userList->num_rows) ?></p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Admin</h3>
                <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($count_admin) ?></p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Kasir</h3>
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($count_kasir) ?></p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm text-gray-600 font-medium">Manajer</h3>
                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800"><?= number_format($count_manajer) ?></p>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Username</th>
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Nama Lengkap</th>
                        <th class="text-left py-4 px-6 text-sm font-semibold text-gray-600">Apotik</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Role</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Transaksi</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Last Login</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Status</th>
                        <th class="text-center py-4 px-6 text-sm font-semibold text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php 
                    $userList->data_seek(0);
                    while ($u = $userList->fetch_assoc()): 
                        $role_colors = [
                            'admin' => 'bg-red-100 text-red-800',
                            'kasir' => 'bg-blue-100 text-blue-800',
                            'manajer' => 'bg-green-100 text-green-800'
                        ];
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-4 px-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                    <span class="text-purple-600 font-bold text-sm">
                                        <?= strtoupper(substr($u['nama_lengkap'], 0, 2)) ?>
                                    </span>
                                </div>
                                <span class="font-semibold text-gray-800"><?= htmlspecialchars($u['username']) ?></span>
                            </div>
                        </td>
                        <td class="py-4 px-6">
                            <div>
                                <p class="font-medium text-gray-800"><?= htmlspecialchars($u['nama_lengkap']) ?></p>
                                <?php if ($u['email']): ?>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($u['email']) ?></p>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="py-4 px-6">
                            <span class="text-sm text-gray-600"><?= $u['nama_apotik'] ? htmlspecialchars($u['nama_apotik']) : '<span class="text-gray-400">Semua Apotik</span>' ?></span>
                        </td>
                        <td class="py-4 px-6 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?= $role_colors[$u['role']] ?>">
                                <?= ucfirst($u['role']) ?>
                            </span>
                        </td>
                        <td class="py-4 px-6 text-center">
                            <span class="font-semibold text-gray-800"><?= number_format($u['total_transaksi']) ?></span>
                        </td>
                        <td class="py-4 px-6 text-center">
                            <?php if ($u['last_login']): ?>
                            <span class="text-sm text-gray-600"><?= formatTanggalWaktu($u['last_login'], 'd/m/y H:i') ?></span>
                            <?php else: ?>
                            <span class="text-sm text-gray-400">Belum login</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-6 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?= $u['status'] === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                <?= ucfirst($u['status']) ?>
                            </span>
                        </td>
                        <td class="py-4 px-6">
                            <div class="flex items-center justify-center space-x-2">
                                <a href="edit.php?id=<?= $u['id_user'] ?>" 
                                   class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-all" 
                                   title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <a href="change_password.php?id=<?= $u['id_user'] ?>" 
                                   class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-all" 
                                   title="Ganti Password">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                    </svg>
                                </a>
                                <?php if ($u['id_user'] != $user['id']): ?>
                                <button onclick="if(confirmDelete()) window.location='delete.php?id=<?= $u['id_user'] ?>'" 
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

<?php include '../includes/footer.php'; ?>