<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin', 'kasir']);

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $no_identitas = sanitize($_POST['no_identitas']);
        $nama_pelanggan = sanitize($_POST['nama_pelanggan']);
        $jenis_kelamin = sanitize($_POST['jenis_kelamin']);
        $tanggal_lahir = !empty($_POST['tanggal_lahir']) ? $_POST['tanggal_lahir'] : null;
        $alamat = sanitize($_POST['alamat']);
        $no_telp = sanitize($_POST['no_telp']);
        $email = sanitize($_POST['email']);
        
        $stmt = $db->prepare("INSERT INTO pelanggan (
            no_identitas, nama_pelanggan, jenis_kelamin, tanggal_lahir, 
            alamat, no_telp, email
        ) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("sssssss",
            $no_identitas, $nama_pelanggan, $jenis_kelamin, $tanggal_lahir,
            $alamat, $no_telp, $email
        );
        
        if ($stmt->execute()) {
            alert('Data pelanggan berhasil ditambahkan!', 'success');
            
            // If from modal, return to previous page
            if (isset($_POST['from_modal'])) {
                echo '<script>window.close();</script>';
            } else {
                redirect('../pelanggan/index.php');
            }
        } else {
            throw new Exception('Gagal menyimpan data: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        alert('Error: ' . $e->getMessage(), 'error');
    }
}

$pageTitle = 'Tambah Pelanggan';
include '../includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Tambah Pelanggan Baru</h2>
            <p class="text-gray-600 mt-1">Tambahkan data pelanggan/pasien</p>
        </div>
        <a href="javascript:history.back()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all">
            Kembali
        </a>
    </div>

    <!-- Form -->
    <form method="POST" class="bg-white rounded-2xl shadow-sm p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nama -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nama Lengkap <span class="text-red-500">*</span>
                </label>
                <input type="text" name="nama_pelanggan" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="Nama lengkap pelanggan">
            </div>

            <!-- No Identitas -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">No. Identitas (KTP/SIM)</label>
                <input type="text" name="no_identitas"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="1234567890123456">
            </div>

            <!-- Jenis Kelamin -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Kelamin</label>
                <select name="jenis_kelamin" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                    <option value="">- Pilih -</option>
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                </select>
            </div>

            <!-- Tanggal Lahir -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- No Telp -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">No. Telepon</label>
                <input type="text" name="no_telp"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="08123456789">
            </div>

            <!-- Email -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" name="email"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="email@example.com">
            </div>

            <!-- Alamat -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Alamat</label>
                <textarea name="alamat" rows="3"
                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                          placeholder="Alamat lengkap pelanggan"></textarea>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
            <a href="javascript:history.back()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-xl font-medium hover:bg-gray-300 transition-all">
                Batal
            </a>
            <button type="submit" class="px-6 py-3 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg transition-all">
                Simpan Data
            </button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>