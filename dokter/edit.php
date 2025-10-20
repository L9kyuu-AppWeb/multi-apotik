<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin']);

$db = db();

$id_dokter = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get dokter data
$stmt = $db->prepare("SELECT * FROM dokter WHERE id_dokter = ?");
$stmt->bind_param("i", $id_dokter);
$stmt->execute();
$dokter = $stmt->get_result()->fetch_assoc();

if (!$dokter) {
    alert('Dokter tidak ditemukan', 'error');
    redirect('index.php');
}

// Get statistics
$statsQuery = "SELECT COUNT(*) as total_resep FROM resep WHERE id_dokter = ?";
$stmtStats = $db->prepare($statsQuery);
$stmtStats->bind_param("i", $id_dokter);
$stmtStats->execute();
$stats = $stmtStats->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nama_dokter = sanitize($_POST['nama_dokter']);
        $spesialis = sanitize($_POST['spesialis']);
        $no_str = sanitize($_POST['no_str']);
        $no_telp = sanitize($_POST['no_telp']);
        $alamat = sanitize($_POST['alamat']);
        $status = sanitize($_POST['status']);
        
        $stmt = $db->prepare("UPDATE dokter SET 
            nama_dokter = ?, spesialis = ?, no_str = ?, no_telp = ?, alamat = ?, status = ?
            WHERE id_dokter = ?");
        
        $stmt->bind_param("ssssssi",
            $nama_dokter, $spesialis, $no_str, $no_telp, $alamat, $status, $id_dokter
        );
        
        if ($stmt->execute()) {
            alert('Data dokter berhasil diupdate!', 'success');
            redirect('index.php');
        } else {
            throw new Exception('Gagal update data: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        alert('Error: ' . $e->getMessage(), 'error');
    }
}

$pageTitle = 'Edit Dokter';
include '../includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Edit Dokter</h2>
            <p class="text-gray-600 mt-1">Ubah data dokter</p>
        </div>
        <a href="index.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all">
            Kembali
        </a>
    </div>

    <!-- Stats -->
    <div class="bg-gradient-to-r from-teal-500 to-teal-600 rounded-2xl p-6 text-white">
        <div class="text-center">
            <p class="text-4xl font-bold"><?= number_format($stats['total_resep']) ?></p>
            <p class="text-teal-100 text-sm mt-1">Total Resep yang Pernah Dibuat</p>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" class="bg-white rounded-2xl shadow-sm p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Kode Dokter (Read Only) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Kode Dokter</label>
                <input type="text" value="<?= htmlspecialchars($dokter['kode_dokter']) ?>" readonly
                       class="w-full px-4 py-3 bg-gray-100 border border-gray-300 rounded-xl text-gray-600 cursor-not-allowed">
                <p class="text-xs text-gray-500 mt-1">Kode tidak dapat diubah</p>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Status <span class="text-red-500">*</span>
                </label>
                <select name="status" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                    <option value="aktif" <?= $dokter['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= $dokter['status'] === 'nonaktif' ? 'selected' : '' ?>>Non-Aktif</option>
                </select>
            </div>

            <!-- Nama Dokter -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nama Dokter <span class="text-red-500">*</span>
                </label>
                <input type="text" name="nama_dokter" value="<?= htmlspecialchars($dokter['nama_dokter']) ?>" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Spesialis -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Spesialis</label>
                <input type="text" name="spesialis" value="<?= htmlspecialchars($dokter['spesialis']) ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- No STR -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">No. STR</label>
                <input type="text" name="no_str" value="<?= htmlspecialchars($dokter['no_str']) ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- No Telp -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">No. Telepon</label>
                <input type="text" name="no_telp" value="<?= htmlspecialchars($dokter['no_telp']) ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Alamat -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Alamat</label>
                <textarea name="alamat" rows="3"
                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"><?= htmlspecialchars($dokter['alamat']) ?></textarea>
            </div>
        </div>

        <!-- Warning -->
        <?php if ($stats['total_resep'] > 0): ?>
        <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <p class="text-sm text-yellow-700">Dokter ini memiliki riwayat resep. Hati-hati saat mengubah status.</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Buttons -->
        <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
            <a href="index.php" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-xl font-medium hover:bg-gray-300 transition-all">
                Batal
            </a>
            <button type="submit" class="px-6 py-3 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg transition-all">
                Update Data
            </button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>