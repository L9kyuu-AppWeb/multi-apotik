<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin', 'kasir']);

$db = db();

$id_pelanggan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get pelanggan data
$stmt = $db->prepare("SELECT * FROM pelanggan WHERE id_pelanggan = ?");
$stmt->bind_param("i", $id_pelanggan);
$stmt->execute();
$pelanggan = $stmt->get_result()->fetch_assoc();

if (!$pelanggan) {
    alert('Pelanggan tidak ditemukan', 'error');
    redirect('index.php');
}

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total_transaksi,
    COALESCE(SUM(total_bayar), 0) as total_pembelian,
    MAX(tanggal_penjualan) as transaksi_terakhir
    FROM penjualan WHERE id_pelanggan = ?";
$stmtStats = $db->prepare($statsQuery);
$stmtStats->bind_param("i", $id_pelanggan);
$stmtStats->execute();
$stats = $stmtStats->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $no_identitas = sanitize($_POST['no_identitas']);
        $nama_pelanggan = sanitize($_POST['nama_pelanggan']);
        $jenis_kelamin = sanitize($_POST['jenis_kelamin']);
        $tanggal_lahir = !empty($_POST['tanggal_lahir']) ? $_POST['tanggal_lahir'] : null;
        $alamat = sanitize($_POST['alamat']);
        $no_telp = sanitize($_POST['no_telp']);
        $email = sanitize($_POST['email']);
        
        $stmt = $db->prepare("UPDATE pelanggan SET 
            no_identitas = ?, nama_pelanggan = ?, jenis_kelamin = ?, 
            tanggal_lahir = ?, alamat = ?, no_telp = ?, email = ?
            WHERE id_pelanggan = ?");
        
        $stmt->bind_param("sssssss i",
            $no_identitas, $nama_pelanggan, $jenis_kelamin, $tanggal_lahir,
            $alamat, $no_telp, $email, $id_pelanggan
        );
        
        if ($stmt->execute()) {
            alert('Data pelanggan berhasil diupdate!', 'success');
            redirect('index.php');
        } else {
            throw new Exception('Gagal update data: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        alert('Error: ' . $e->getMessage(), 'error');
    }
}

$pageTitle = 'Edit Pelanggan';
include '../includes/header.php';
?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Edit Pelanggan</h2>
            <p class="text-gray-600 mt-1">Ubah data pelanggan/pasien</p>
        </div>
        <a href="index.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all">
            Kembali
        </a>
    </div>

    <!-- Stats Info -->
    <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-2xl p-6 text-white">
        <div class="grid grid-cols-3 gap-6">
            <div class="text-center">
                <p class="text-3xl font-bold"><?= number_format($stats['total_transaksi']) ?></p>
                <p class="text-indigo-100 text-sm mt-1">Total Transaksi</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold"><?= formatRupiah($stats['total_pembelian']) ?></p>
                <p class="text-indigo-100 text-sm mt-1">Total Belanja</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-bold">
                    <?= $stats['transaksi_terakhir'] ? formatTanggal($stats['transaksi_terakhir'], 'd M Y') : '-' ?>
                </p>
                <p class="text-indigo-100 text-sm mt-1">Transaksi Terakhir</p>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" class="bg-white rounded-2xl shadow-sm p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nama -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nama Lengkap <span class="text-red-500">*</span>
                </label>
                <input type="text" name="nama_pelanggan" value="<?= htmlspecialchars($pelanggan['nama_pelanggan']) ?>" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- No Identitas -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">No. Identitas (KTP/SIM)</label>
                <input type="text" name="no_identitas" value="<?= htmlspecialchars($pelanggan['no_identitas']) ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Jenis Kelamin -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Kelamin</label>
                <select name="jenis_kelamin" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                    <option value="">- Pilih -</option>
                    <option value="L" <?= $pelanggan['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                    <option value="P" <?= $pelanggan['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                </select>
            </div>

            <!-- Tanggal Lahir -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" value="<?= $pelanggan['tanggal_lahir'] ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- No Telp -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">No. Telepon</label>
                <input type="text" name="no_telp" value="<?= htmlspecialchars($pelanggan['no_telp']) ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Email -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($pelanggan['email']) ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Alamat -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Alamat</label>
                <textarea name="alamat" rows="3"
                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"><?= htmlspecialchars($pelanggan['alamat']) ?></textarea>
            </div>
        </div>

        <!-- Warning -->
        <?php if ($stats['total_transaksi'] > 0): ?>
        <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <p class="text-sm text-yellow-700">Pelanggan ini memiliki riwayat transaksi. Perubahan data tidak akan mempengaruhi transaksi yang sudah ada.</p>
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