<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin']);

$db = db();

// Generate kode apotik
$kode_apotik = generateKode('APT', 'apotik', 'kode_apotik', 3);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $kode_apotik = sanitize($_POST['kode_apotik']);
        $nama_apotik = sanitize($_POST['nama_apotik']);
        $alamat = sanitize($_POST['alamat']);
        $no_telp = sanitize($_POST['no_telp']);
        $email = sanitize($_POST['email']);
        $status = sanitize($_POST['status']);
        
        // Check if kode already exists
        $checkStmt = $db->prepare("SELECT id_apotik FROM apotik WHERE kode_apotik = ?");
        $checkStmt->bind_param("s", $kode_apotik);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            throw new Exception('Kode apotik sudah digunakan!');
        }
        
        $stmt = $db->prepare("INSERT INTO apotik (
            kode_apotik, nama_apotik, alamat, no_telp, email, status
        ) VALUES (?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("ssssss",
            $kode_apotik, $nama_apotik, $alamat, $no_telp, $email, $status
        );
        
        if ($stmt->execute()) {
            alert('Data apotik berhasil ditambahkan!', 'success');
            redirect('index.php');
        } else {
            throw new Exception('Gagal menyimpan data: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        alert('Error: ' . $e->getMessage(), 'error');
    }
}

$pageTitle = 'Tambah Apotik';
include '../includes/header.php';
?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Tambah Apotik Baru</h2>
            <p class="text-gray-600 mt-1">Tambahkan cabang apotik baru ke sistem</p>
        </div>
        <a href="index.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all">
            Kembali
        </a>
    </div>

    <!-- Form -->
    <form method="POST" class="bg-white rounded-2xl shadow-sm p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Kode Apotik -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Kode Apotik <span class="text-red-500">*</span>
                </label>
                <input type="text" name="kode_apotik" value="<?= $kode_apotik ?>" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="APT001">
                <p class="text-xs text-gray-500 mt-1">Kode unik untuk identifikasi apotik</p>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Status <span class="text-red-500">*</span>
                </label>
                <select name="status" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Non-Aktif</option>
                </select>
            </div>

            <!-- Nama Apotik -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nama Apotik <span class="text-red-500">*</span>
                </label>
                <input type="text" name="nama_apotik" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="Contoh: Apotik Sehat Sentosa">
            </div>

            <!-- Alamat -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Alamat Lengkap <span class="text-red-500">*</span>
                </label>
                <textarea name="alamat" rows="3" required
                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                          placeholder="Jl. Nama Jalan No. XX, Kota, Provinsi"></textarea>
            </div>

            <!-- No Telepon -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    No. Telepon <span class="text-red-500">*</span>
                </label>
                <input type="text" name="no_telp" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="021-12345678">
                <p class="text-xs text-gray-500 mt-1">Format: 021-12345678 atau 08123456789</p>
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Email
                </label>
                <input type="email" name="email"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="apotik@email.com">
            </div>
        </div>

        <!-- Info Box -->
        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-xl">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-blue-800 mb-1">Informasi Penting:</p>
                    <ul class="text-sm text-blue-700 space-y-1 list-disc list-inside">
                        <li>Setelah apotik ditambahkan, Anda dapat menambahkan user untuk apotik ini</li>
                        <li>Data obat dan transaksi akan terikat dengan apotik ini</li>
                        <li>Kode apotik tidak dapat diubah setelah disimpan</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
            <a href="index.php" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-xl font-medium hover:bg-gray-300 transition-all">
                Batal
            </a>
            <button type="submit" class="px-6 py-3 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg transition-all">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Simpan Data
            </button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>