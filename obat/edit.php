<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin']);

$user = getUserData();
$db = db();

$id_obat = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get obat data
$stmt = $db->prepare("SELECT * FROM obat WHERE id_obat = ?");
$stmt->bind_param("i", $id_obat);
$stmt->execute();
$obat = $stmt->get_result()->fetch_assoc();

if (!$obat) {
    alert('Obat tidak ditemukan', 'error');
    redirect('index.php');
}

// Get kategori
$kategoriList = $db->query("SELECT * FROM kategori_obat ORDER BY nama_kategori");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize input
        $id_kategori = !empty($_POST['id_kategori']) ? (int)$_POST['id_kategori'] : null;
        $kode_obat = sanitize($_POST['kode_obat']);
        $nama_obat = sanitize($_POST['nama_obat']);
        $jenis_obat = sanitize($_POST['jenis_obat']);
        $satuan = sanitize($_POST['satuan']);
        $harga_beli = (float)$_POST['harga_beli'];
        $harga_jual = (float)$_POST['harga_jual'];
        $margin_persen = $harga_beli > 0 ? (($harga_jual - $harga_beli) / $harga_beli * 100) : 0;
        $aturan_pakai = sanitize($_POST['aturan_pakai']);
        $dosis = sanitize($_POST['dosis']);
        $efek_samping = sanitize($_POST['efek_samping']);
        $golongan = sanitize($_POST['golongan']);
        $perlu_resep = isset($_POST['perlu_resep']) ? 1 : 0;
        $status = sanitize($_POST['status']);
        
        // Update obat
        $stmt = $db->prepare("UPDATE obat SET 
            id_kategori = ?, kode_obat = ?, nama_obat = ?, jenis_obat = ?, 
            satuan = ?, harga_beli = ?, harga_jual = ?, margin_persen = ?, 
            aturan_pakai = ?, dosis = ?, efek_samping = ?, golongan = ?, 
            perlu_resep = ?, status = ?
            WHERE id_obat = ?");
        
        $stmt->bind_param("issssdddssssssi",
            $id_kategori, $kode_obat, $nama_obat, $jenis_obat,
            $satuan, $harga_beli, $harga_jual, $margin_persen,
            $aturan_pakai, $dosis, $efek_samping, $golongan,
            $perlu_resep, $status, $id_obat
        );
        
        if ($stmt->execute()) {
            alert('Data obat berhasil diupdate!', 'success');
            redirect('index.php');
        } else {
            throw new Exception('Gagal update data: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        alert('Error: ' . $e->getMessage(), 'error');
    }
}

$pageTitle = 'Edit Obat';
include '../includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Edit Obat</h2>
            <p class="text-gray-600 mt-1">Ubah data obat</p>
        </div>
        <a href="index.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all">
            Kembali
        </a>
    </div>

    <!-- Form -->
    <form method="POST" class="bg-white rounded-2xl shadow-sm p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Kode Obat -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Kode Obat <span class="text-red-500">*</span>
                </label>
                <input type="text" name="kode_obat" value="<?= htmlspecialchars($obat['kode_obat']) ?>" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Nama Obat -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nama Obat <span class="text-red-500">*</span>
                </label>
                <input type="text" name="nama_obat" value="<?= htmlspecialchars($obat['nama_obat']) ?>" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Kategori -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                <select name="id_kategori" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                    <option value="">- Pilih Kategori -</option>
                    <?php while ($kat = $kategoriList->fetch_assoc()): ?>
                    <option value="<?= $kat['id_kategori'] ?>" <?= $obat['id_kategori'] == $kat['id_kategori'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($kat['nama_kategori']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Jenis Obat -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Jenis Obat <span class="text-red-500">*</span>
                </label>
                <input type="text" name="jenis_obat" value="<?= htmlspecialchars($obat['jenis_obat']) ?>" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Satuan -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Satuan <span class="text-red-500">*</span>
                </label>
                <select name="satuan" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                    <option value="">- Pilih Satuan -</option>
                    <?php
                    $satuans = ['Tablet', 'Kapsul', 'Botol', 'Box', 'Strip', 'Tube', 'Sachet', 'Vial', 'Ampul'];
                    foreach ($satuans as $s):
                    ?>
                    <option value="<?= $s ?>" <?= $obat['satuan'] == $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Golongan -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Golongan Obat <span class="text-red-500">*</span>
                </label>
                <select name="golongan" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                    <option value="bebas" <?= $obat['golongan'] == 'bebas' ? 'selected' : '' ?>>Bebas</option>
                    <option value="bebas_terbatas" <?= $obat['golongan'] == 'bebas_terbatas' ? 'selected' : '' ?>>Bebas Terbatas</option>
                    <option value="keras" <?= $obat['golongan'] == 'keras' ? 'selected' : '' ?>>Keras</option>
                    <option value="narkotika" <?= $obat['golongan'] == 'narkotika' ? 'selected' : '' ?>>Narkotika</option>
                </select>
            </div>

            <!-- Harga Beli -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Harga Beli <span class="text-red-500">*</span>
                </label>
                <input type="number" name="harga_beli" id="hargaBeli" step="0.01" min="0" 
                       value="<?= $obat['harga_beli'] ?>" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       onchange="hitungMargin()">
            </div>

            <!-- Harga Jual -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Harga Jual <span class="text-red-500">*</span>
                </label>
                <input type="number" name="harga_jual" id="hargaJual" step="0.01" min="0" 
                       value="<?= $obat['harga_jual'] ?>" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       onchange="hitungMargin()">
            </div>

            <!-- Margin -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Margin Keuntungan</label>
                <div class="px-4 py-3 bg-purple-50 border border-purple-200 rounded-xl">
                    <span class="text-lg font-bold text-purple-600" id="marginText"><?= number_format($obat['margin_persen'], 2) ?>%</span>
                    <span class="text-sm text-purple-600 ml-2" id="marginRupiah">(<?= formatRupiah($obat['harga_jual'] - $obat['harga_beli']) ?>)</span>
                </div>
            </div>

            <!-- Dosis -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Dosis</label>
                <input type="text" name="dosis" value="<?= htmlspecialchars($obat['dosis']) ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                    <option value="aktif" <?= $obat['status'] == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= $obat['status'] == 'nonaktif' ? 'selected' : '' ?>>Non-Aktif</option>
                </select>
            </div>

            <!-- Perlu Resep -->
            <div class="flex items-center md:col-span-2">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="perlu_resep" value="1" 
                           <?= $obat['perlu_resep'] ? 'checked' : '' ?>
                           class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                    <span class="ml-3 text-sm font-medium text-gray-700">Perlu Resep Dokter</span>
                </label>
            </div>

            <!-- Aturan Pakai -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Aturan Pakai</label>
                <textarea name="aturan_pakai" rows="3"
                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"><?= htmlspecialchars($obat['aturan_pakai']) ?></textarea>
            </div>

            <!-- Efek Samping -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Efek Samping</label>
                <textarea name="efek_samping" rows="3"
                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"><?= htmlspecialchars($obat['efek_samping']) ?></textarea>
            </div>
        </div>

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

<script>
function hitungMargin() {
    const hargaBeli = parseFloat(document.getElementById('hargaBeli').value) || 0;
    const hargaJual = parseFloat(document.getElementById('hargaJual').value) || 0;
    
    if (hargaBeli > 0 && hargaJual >= hargaBeli) {
        const margin = ((hargaJual - hargaBeli) / hargaBeli * 100);
        const marginRupiah = hargaJual - hargaBeli;
        
        document.getElementById('marginText').textContent = margin.toFixed(2) + '%';
        document.getElementById('marginRupiah').textContent = '(' + formatRupiah(marginRupiah) + ')';
    } else {
        document.getElementById('marginText').textContent = '0%';
        document.getElementById('marginRupiah').textContent = '(Rp 0)';
    }
}
</script>

<?php include '../includes/footer.php'; ?>