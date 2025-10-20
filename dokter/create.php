<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin']);

$db = db();

// Generate kode dokter
$kode_dokter = generateKode('DOK', 'dokter', 'kode_dokter', 4);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $kode_dokter = sanitize($_POST['kode_dokter']);
        $nama_dokter = sanitize($_POST['nama_dokter']);
        $spesialis = sanitize($_POST['spesialis']);
        $no_str = sanitize($_POST['no_str']);
        $no_telp = sanitize($_POST['no_telp']);
        $alamat = sanitize($_POST['alamat']);
        
        $stmt = $db->prepare("INSERT INTO dokter (
            kode_dokter, nama_dokter, spesialis, no_str, no_telp, alamat
        ) VALUES (?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("ssssss",
            $kode_dokter, $nama_dokter, $spesialis, $no_str, $no_telp, $alamat
        );
        
        if ($stmt->execute()) {
            alert('Data dokter berhasil ditambahkan!', 'success');
            redirect('../dokter/index.php');
        } else {
            throw new Exception('Gagal menyimpan data: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        alert('Error: ' . $e->getMessage(), 'error');
    }
}

$pageTitle = 'Tambah Dokter';
include '../includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Tambah Dokter Baru</h2>
            <p class="text-gray-600 mt-1">Tambahkan data dokter</p>
        </div>
        <a href="index.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all">
            Kembali
        </a>
    </div>

    <!-- Form -->
    <form method="POST" class="bg-white rounded-2xl shadow-sm p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Kode Dokter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Kode Dokter <span class="text-red-500">*</span>
                </label>
                <input type="text" name="kode_dokter" value="<?= $kode_dokter ?>" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Nama Dokter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nama Dokter <span class="text-red-500">*</span>
                </label>
                <input type="text" name="nama_dokter" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="dr. Nama Dokter">
            </div>

            <!-- Spesialis -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Spesialis</label>
                <input type="text" name="spesialis"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="Contoh: Sp.PD, Sp.A, Sp.OG">
            </div>

            <!-- No STR -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">No. STR</label>
                <input type="text" name="no_str"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="Nomor Surat Tanda Registrasi">
            </div>

            <!-- No Telp -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">No. Telepon</label>
                <input type="text" name="no_telp"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="08123456789">
            </div>

            <!-- Alamat -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Alamat</label>
                <textarea name="alamat" rows="3"
                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                          placeholder="Alamat praktik dokter"></textarea>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
            <a href="index.php" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-xl font-medium hover:bg-gray-300 transition-all">
                Batal
            </a>
            <button type="submit" class="px-6 py-3 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg transition-all">
                Simpan Data
            </button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>