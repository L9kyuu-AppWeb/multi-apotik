<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin']);

$user = getUserData();
$db = db();

// Generate no resep
$no_resep = generateKode('RSP', 'resep', 'no_resep', 6);

// Get data
$dokterList = $db->query("SELECT * FROM dokter WHERE status = 'aktif' ORDER BY nama_dokter");
$pelangganList = $db->query("SELECT * FROM pelanggan ORDER BY nama_pelanggan");
$obatQuery = "SELECT o.*, COALESCE(SUM(b.stok_sisa), 0) as total_stok
              FROM obat o
              LEFT JOIN batch_obat b ON o.id_obat = b.id_obat AND b.status = 'tersedia'
              WHERE o.id_apotik = ? AND o.status = 'aktif'
              GROUP BY o.id_obat
              HAVING total_stok > 0
              ORDER BY o.nama_obat";
$stmtObat = $db->prepare($obatQuery);
$stmtObat->bind_param("i", $user['id_apotik']);
$stmtObat->execute();
$obatList = $stmtObat->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->getConnection()->begin_transaction();
        
        $id_apotik = $user['id_apotik'];
        $id_dokter = (int)$_POST['id_dokter'];
        $id_pelanggan = (int)$_POST['id_pelanggan'];
        $no_resep = sanitize($_POST['no_resep']);
        $tanggal_resep = $_POST['tanggal_resep'];
        $diagnosa = sanitize($_POST['diagnosa']);
        $keterangan = sanitize($_POST['keterangan']);
        
        // Decode items
        $items = json_decode($_POST['items'], true);
        
        if (empty($items)) {
            throw new Exception('Belum ada obat yang ditambahkan!');
        }
        
        // Insert resep header
        $stmt = $db->prepare("INSERT INTO resep (
            id_apotik, id_dokter, id_pelanggan, no_resep, 
            tanggal_resep, diagnosa, keterangan, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        
        $stmt->bind_param("iiissss",
            $id_apotik, $id_dokter, $id_pelanggan, $no_resep,
            $tanggal_resep, $diagnosa, $keterangan
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Gagal menyimpan resep: ' . $stmt->error);
        }
        
        $id_resep = $db->lastInsertId();
        
        // Insert detail resep
        foreach ($items as $item) {
            $stmtDetail = $db->prepare("INSERT INTO detail_resep (
                id_resep, id_obat, qty, aturan_pakai, keterangan
            ) VALUES (?, ?, ?, ?, ?)");
            
            $stmtDetail->bind_param("iiiss",
                $id_resep, $item['id_obat'], $item['qty'],
                $item['aturan_pakai'], $item['keterangan']
            );
            
            if (!$stmtDetail->execute()) {
                throw new Exception('Gagal menyimpan detail: ' . $stmtDetail->error);
            }
        }
        
        $db->getConnection()->commit();
        
        alert('Resep berhasil disimpan!', 'success');
        redirect('index.php');
        
    } catch (Exception $e) {
        $db->getConnection()->rollback();
        alert('Error: ' . $e->getMessage(), 'error');
    }
}

$pageTitle = 'Input Resep Dokter';
include '../includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Input Resep Dokter</h2>
            <p class="text-gray-600 mt-1">Buat resep digital dari dokter</p>
        </div>
        <a href="index.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all">
            Kembali
        </a>
    </div>

    <form id="formResep" method="POST">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Form -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Info Resep -->
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Informasi Resep</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">No. Resep</label>
                            <input type="text" name="no_resep" value="<?= $no_resep ?>" readonly 
                                   class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl font-semibold">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Resep *</label>
                            <input type="date" name="tanggal_resep" value="<?= date('Y-m-d') ?>" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Dokter *</label>
                            <select name="id_dokter" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                                <option value="">- Pilih Dokter -</option>
                                <?php while ($dok = $dokterList->fetch_assoc()): ?>
                                <option value="<?= $dok['id_dokter'] ?>">
                                    <?= htmlspecialchars($dok['nama_dokter']) ?> 
                                    <?= $dok['spesialis'] ? '(' . htmlspecialchars($dok['spesialis']) . ')' : '' ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pasien *</label>
                            <select name="id_pelanggan" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                                <option value="">- Pilih Pasien -</option>
                                <?php while ($pel = $pelangganList->fetch_assoc()): ?>
                                <option value="<?= $pel['id_pelanggan'] ?>">
                                    <?= htmlspecialchars($pel['nama_pelanggan']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Diagnosa</label>
                            <textarea name="diagnosa" rows="2"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                                      placeholder="Diagnosa penyakit..."></textarea>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Keterangan</label>
                            <textarea name="keterangan" rows="2"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                                      placeholder="Keterangan tambahan..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Pilih Obat -->
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Pilih Obat</h3>
                    
                    <div class="mb-4">
                        <input type="text" id="searchObat" placeholder="Cari nama obat..." 
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-96 overflow-y-auto" id="obatContainer">
                        <?php while ($obat = $obatList->fetch_assoc()): ?>
                        <div class="obat-item border border-gray-200 rounded-xl p-4 hover:border-purple-500 cursor-pointer transition-all"
                             data-nama="<?= strtolower($obat['nama_obat']) ?>"
                             onclick="showObatModal(<?= htmlspecialchars(json_encode($obat)) ?>)">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-800"><?= htmlspecialchars($obat['nama_obat']) ?></h4>
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars($obat['jenis_obat']) ?></p>
                                    <p class="text-sm text-gray-500">Stok: <?= $obat['total_stok'] ?></p>
                                </div>
                                <button type="button" class="p-2 bg-purple-100 text-purple-600 rounded-lg hover:bg-purple-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column - List Obat -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Daftar Obat Resep</h3>
                    
                    <div id="resepItems" class="space-y-3 mb-4 max-h-96 overflow-y-auto">
                        <p class="text-center text-gray-500 py-8">Belum ada obat</p>
                    </div>
                    
                    <input type="hidden" name="items" id="itemsInput" value="[]">
                    
                    <button type="submit" id="btnSimpan" class="w-full mt-6 py-4 gradient-bg text-white rounded-xl font-bold hover:shadow-lg transition-all" disabled>
                        Simpan Resep
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal Add Obat -->
<div id="obatModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full">
        <h3 class="text-lg font-bold text-gray-800 mb-4" id="modalTitle">Tambah Obat</h3>
        
        <div class="space-y-4">
            <input type="hidden" id="modalObatId">
            <input type="hidden" id="modalObatNama">
            <input type="hidden" id="modalObatStok">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah (Qty) *</label>
                <input type="number" id="modalQty" min="1" value="1"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Aturan Pakai</label>
                <textarea id="modalAturan" rows="2"
                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                          placeholder="Contoh: 3x1 sehari sesudah makan"></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Keterangan</label>
                <textarea id="modalKeterangan" rows="2"
                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                          placeholder="Keterangan tambahan..."></textarea>
            </div>
        </div>
        
        <div class="flex space-x-4 mt-6">
            <button type="button" onclick="closeObatModal()" 
                    class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-xl font-medium hover:bg-gray-300">
                Batal
            </button>
            <button type="button" onclick="addObatToResep()" 
                    class="flex-1 px-4 py-3 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg">
                Tambahkan
            </button>
        </div>
    </div>
</div>

<script>
let resepItems = [];
let currentObat = null;

// Search obat
document.getElementById('searchObat').addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    document.querySelectorAll('.obat-item').forEach(item => {
        const nama = item.dataset.nama;
        item.style.display = nama.includes(search) ? 'block' : 'none';
    });
});

function showObatModal(obat) {
    currentObat = obat;
    document.getElementById('modalObatId').value = obat.id_obat;
    document.getElementById('modalObatNama').value = obat.nama_obat;
    document.getElementById('modalObatStok').value = obat.total_stok;
    document.getElementById('modalTitle').textContent = 'Tambah ' + obat.nama_obat;
    document.getElementById('modalQty').value = 1;
    document.getElementById('modalQty').max = obat.total_stok;
    document.getElementById('modalAturan').value = obat.aturan_pakai || '';
    document.getElementById('modalKeterangan').value = '';
    
    document.getElementById('obatModal').classList.remove('hidden');
}

function closeObatModal() {
    document.getElementById('obatModal').classList.add('hidden');
    currentObat = null;
}

function addObatToResep() {
    const id_obat = parseInt(document.getElementById('modalObatId').value);
    const nama_obat = document.getElementById('modalObatNama').value;
    const qty = parseInt(document.getElementById('modalQty').value);
    const stok = parseInt(document.getElementById('modalObatStok').value);
    const aturan_pakai = document.getElementById('modalAturan').value;
    const keterangan = document.getElementById('modalKeterangan').value;
    
    if (!qty || qty < 1) {
        alert('Jumlah harus lebih dari 0!');
        return;
    }
    
    if (qty > stok) {
        alert('Stok tidak mencukupi!');
        return;
    }
    
    // Check if already exists
    const existing = resepItems.find(item => item.id_obat === id_obat);
    if (existing) {
        existing.qty = qty;
        existing.aturan_pakai = aturan_pakai;
        existing.keterangan = keterangan;
    } else {
        resepItems.push({
            id_obat: id_obat,
            nama_obat: nama_obat,
            qty: qty,
            aturan_pakai: aturan_pakai,
            keterangan: keterangan
        });
    }
    
    renderResepItems();
    closeObatModal();
}

function removeObat(index) {
    resepItems.splice(index, 1);
    renderResepItems();
}

function renderResepItems() {
    const container = document.getElementById('resepItems');
    
    if (resepItems.length === 0) {
        container.innerHTML = '<p class="text-center text-gray-500 py-8">Belum ada obat</p>';
        document.getElementById('btnSimpan').disabled = true;
        document.getElementById('itemsInput').value = '[]';
        return;
    }
    
    container.innerHTML = resepItems.map((item, index) => `
        <div class="border border-gray-200 rounded-lg p-3">
            <div class="flex justify-between items-start mb-2">
                <h5 class="font-semibold text-sm text-gray-800 flex-1">${item.nama_obat}</h5>
                <button type="button" onclick="removeObat(${index})" class="text-red-500 hover:text-red-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="space-y-1">
                <p class="text-sm text-gray-600">Jumlah: <span class="font-semibold">${item.qty}</span></p>
                ${item.aturan_pakai ? `<p class="text-xs text-gray-500">Aturan: ${item.aturan_pakai}</p>` : ''}
                ${item.keterangan ? `<p class="text-xs text-gray-500">Ket: ${item.keterangan}</p>` : ''}
            </div>
        </div>
    `).join('');
    
    document.getElementById('btnSimpan').disabled = false;
    document.getElementById('itemsInput').value = JSON.stringify(resepItems);
}

// Form validation
document.getElementById('formResep').addEventListener('submit', function(e) {
    if (resepItems.length === 0) {
        e.preventDefault();
        alert('Belum ada obat yang ditambahkan!');
        return false;
    }
    return true;
});

// Close modal on outside click
document.getElementById('obatModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeObatModal();
    }
});
</script>

<?php include '../includes/footer.php'; ?>