<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin']);

$db = db();

$id_supplier = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get supplier data
$stmt = $db->prepare("SELECT * FROM supplier WHERE id_supplier = ?");
$stmt->bind_param("i", $id_supplier);
$stmt->execute();
$supplier = $stmt->get_result()->fetch_assoc();

if (!$supplier) {
    alert('Supplier tidak ditemukan', 'error');
    redirect('index.php');
}

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total_pembelian,
    COALESCE(SUM(total_bayar), 0) as total_nilai,
    MAX(tanggal_pembelian) as pembelian_terakhir
    FROM pembelian WHERE id_supplier = ?";
$stmtStats = $db->prepare($statsQuery);
$stmtStats->bind_param("i", $id_supplier);
$stmtStats->execute();
$stats = $stmtStats->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nama_supplier = sanitize($_POST['nama_supplier']);
        $alamat = sanitize($_POST['alamat']);
        $no_telp = sanitize($_POST['no_telp']);
        $email = sanitize($_POST['email']);
        $contact_person = sanitize($_POST['contact_person']);
        $status = sanitize($_POST['status']);
        
        $stmt = $db->prepare("UPDATE supplier SET 
            nama_supplier = ?, alamat = ?, no_telp = ?, email = ?, contact_person = ?, status = ?
            WHERE id_supplier = ?");
        
        $stmt->bind_param("ssssssi",
            $nama_supplier, $alamat, $no_telp, $email, $contact_person, $status, $id_supplier
        );
        
        if ($stmt->execute()) {
            alert('Data supplier berhasil diupdate!', 'success');
            redirect('index.php');
        } else {
            throw new Exception('Gagal update data: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        alert('Error: ' . $e->getMessage(), 'error');
    }
}

$pageTitle = 'Edit Supplier';
include '../includes/header.php';
?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Edit Supplier</h2>
            <p class="text-gray-600 mt-1">Ubah data pemasok obat</p>
        </div>
        <a href="index.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all">
            Kembali
        </a>
    </div>

    <!-- Stats Info -->
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl p-6 text-white">
        <div class="grid grid-cols-3 gap-6">
            <div class="text-center">
                <p class="text-3xl font-bold"><?= number_format($stats['total_pembelian']) ?></p>
                <p class="text-blue-100 text-sm mt-1">Total Pembelian</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold"><?= formatRupiah($stats['total_nilai']) ?></p>
                <p class="text-blue-100 text-sm mt-1">Total Nilai</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-bold"><?= $stats['pembelian_terakhir'] ? formatTanggal($stats['pembelian_terakhir'], 'd M Y') : '-' ?></p>
                <p class="text-blue-100 text-sm mt-1">Pembelian Terakhir</p>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" class="bg-white rounded-2xl shadow-sm p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Kode Supplier (Read Only) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Kode Supplier</label>
                <input type="text" value="<?= htmlspecialchars($supplier['kode_supplier']) ?>" readonly
                       class="w-full px-4 py-3 bg-gray-100 border border-gray-300 rounded-xl text-gray-600 cursor-not-allowed">
                <p class="text-xs text-gray-500 mt-1">Kode tidak dapat diubah</p>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Status <span class="text-red-500">*</span>
                </label>
                <select name="status" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                    <option value="aktif" <?= $supplier['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= $supplier['status'] === 'nonaktif' ? 'selected' : '' ?>>Non-Aktif</option>
                </select>
            </div>

            <!-- Nama Supplier -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nama Supplier <span class="text-red-500">*</span>
                </label>
                <input type="text" name="nama_supplier" value="<?= htmlspecialchars($supplier['nama_supplier']) ?>" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Contact Person -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Contact Person <span class="text-red-500">*</span>
                </label>
                <input type="text" name="contact_person" value="<?= htmlspecialchars($supplier['contact_person']) ?>" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- No Telepon -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    No. Telepon <span class="text-red-500">*</span>
                </label>
                <input type="text" name="no_telp" value="<?= htmlspecialchars($supplier['no_telp']) ?>" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Email -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($supplier['email']) ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Alamat -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Alamat Lengkap <span class="text-red-500">*</span>
                </label>
                <textarea name="alamat" rows="3" required
                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"><?= htmlspecialchars($supplier['alamat']) ?></textarea>
            </div>
        </div>

        <!-- Warning Box -->
        <?php if ($stats['total_pembelian'] > 0): ?>
        <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-yellow-800 mb-1">Perhatian!</p>
                    <p class="text-sm text-yellow-700">Supplier ini memiliki riwayat transaksi pembelian. Hati-hati saat mengubah status menjadi non-aktif.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Buttons -->
        <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
            <a href="index.php" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-xl font-medium hover:bg-gray-300 transition-all">
                Batal
            </a>
            <button type="submit" class="px-6 py-3 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg transition-all">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Update Data
            </button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>