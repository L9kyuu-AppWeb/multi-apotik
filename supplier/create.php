<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin']);

$db = db();

// Generate kode supplier
$kode_supplier = generateKode('SUP', 'supplier', 'kode_supplier', 3);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $kode_supplier = sanitize($_POST['kode_supplier']);
        $nama_supplier = sanitize($_POST['nama_supplier']);
        $alamat = sanitize($_POST['alamat']);
        $no_telp = sanitize($_POST['no_telp']);
        $email = sanitize($_POST['email']);
        $contact_person = sanitize($_POST['contact_person']);
        $status = sanitize($_POST['status']);
        
        // Check if kode already exists
        $checkStmt = $db->prepare("SELECT id_supplier FROM supplier WHERE kode_supplier = ?");
        $checkStmt->bind_param("s", $kode_supplier);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            throw new Exception('Kode supplier sudah digunakan!');
        }
        
        $stmt = $db->prepare("INSERT INTO supplier (
            kode_supplier, nama_supplier, alamat, no_telp, email, contact_person, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("sssssss",
            $kode_supplier, $nama_supplier, $alamat, $no_telp, $email, $contact_person, $status
        );
        
        if ($stmt->execute()) {
            alert('Data supplier berhasil ditambahkan!', 'success');
            redirect('index.php');
        } else {
            throw new Exception('Gagal menyimpan data: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        alert('Error: ' . $e->getMessage(), 'error');
    }
}

$pageTitle = 'Tambah Supplier';
include '../includes/header.php';
?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Tambah Supplier Baru</h2>
            <p class="text-gray-600 mt-1">Tambahkan data pemasok obat</p>
        </div>
        <a href="index.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all">
            Kembali
        </a>
    </div>

    <!-- Form -->
    <form method="POST" class="bg-white rounded-2xl shadow-sm p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Kode Supplier -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Kode Supplier <span class="text-red-500">*</span>
                </label>
                <input type="text" name="kode_supplier" value="<?= $kode_supplier ?>" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="SUP001">
                <p class="text-xs text-gray-500 mt-1">Kode unik untuk identifikasi supplier</p>
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

            <!-- Nama Supplier -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nama Supplier <span class="text-red-500">*</span>
                </label>
                <input type="text" name="nama_supplier" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="Contoh: PT. Kimia Farma">
            </div>

            <!-- Contact Person -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Contact Person <span class="text-red-500">*</span>
                </label>
                <input type="text" name="contact_person" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="Nama PIC">
            </div>

            <!-- No Telepon -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    No. Telepon <span class="text-red-500">*</span>
                </label>
                <input type="text" name="no_telp" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="021-12345678">
            </div>

            <!-- Email -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Email
                </label>
                <input type="email" name="email"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="supplier@email.com">
            </div>

            <!-- Alamat -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Alamat Lengkap <span class="text-red-500">*</span>
                </label>
                <textarea name="alamat" rows="3" required
                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                          placeholder="Alamat kantor/gudang supplier"></textarea>
            </div>
        </div>

        <!-- Info Box -->
        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-xl">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-blue-800 mb-1">Tips:</p>
                    <ul class="text-sm text-blue-700 space-y-1 list-disc list-inside">
                        <li>Pastikan data contact person dan nomor telepon benar</li>
                        <li>Data supplier akan digunakan untuk transaksi pembelian obat</li>
                        <li>Anda dapat mengubah status menjadi non-aktif jika tidak lagi bekerja sama</li>
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