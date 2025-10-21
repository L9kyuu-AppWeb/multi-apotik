<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin']);

$user = getUserData();
$db = db();

// Generate nomor faktur
$no_faktur = generateKode('PBL', 'pembelian', 'no_faktur', 8);

// Get data supplier
$supplierQuery = "SELECT * FROM supplier WHERE status = 'aktif' ORDER BY nama_supplier";
$supplierList = $db->query($supplierQuery);

// Get data obat
$obatQuery = "SELECT * FROM obat WHERE id_apotik = ? AND status = 'aktif' ORDER BY nama_obat LIMIT 5";
$stmtObat = $db->prepare($obatQuery);
$stmtObat->bind_param("i", $user['id_apotik']);
$stmtObat->execute();
$obatList = $stmtObat->get_result();

$pageTitle = 'Transaksi Pembelian Baru';
include '../includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Transaksi Pembelian Baru</h2>
            <p class="text-gray-600 mt-1">Buat transaksi pembelian obat dari supplier</p>
        </div>
        <a href="index.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all">
            Kembali
        </a>
    </div>

    <form id="formPembelian" method="POST" action="process.php">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="id_apotik" value="<?= $user['id_apotik'] ?>">
        <input type="hidden" name="id_user" value="<?= $user['id'] ?>">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Form -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Info Pembelian -->
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Informasi Pembelian</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">No. Faktur</label>
                            <input type="text" name="no_faktur" value="<?= $no_faktur ?>" readonly 
                                   class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 font-semibold">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Pembelian *</label>
                            <input type="date" name="tanggal_pembelian" value="<?= date('Y-m-d') ?>" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Supplier *</label>
                            <select name="id_supplier" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                                <option value="">- Pilih Supplier -</option>
                                <?php while ($supplier = $supplierList->fetch_assoc()): ?>
                                <option value="<?= $supplier['id_supplier'] ?>">
                                    <?= htmlspecialchars($supplier['nama_supplier']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Jatuh Tempo</label>
                            <input type="date" name="tanggal_jatuh_tempo"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status Pembayaran *</label>
                            <select name="status_pembayaran" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                                <option value="lunas">Lunas</option>
                                <option value="belum_lunas">Belum Lunas</option>
                                <option value="kredit">Kredit</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Keterangan</label>
                            <textarea name="keterangan" rows="2"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                                      placeholder="Keterangan pembelian..."></textarea>
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
                        <?php 
                        $obatList->data_seek(0);
                        while ($obat = $obatList->fetch_assoc()): 
                        ?>
                        <div class="obat-item border border-gray-200 rounded-xl p-4 hover:border-purple-500 cursor-pointer transition-all" 
                             data-nama="<?= strtolower($obat['nama_obat']) ?>"
                             onclick="showObatModal(<?= htmlspecialchars(json_encode($obat)) ?>)">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-800"><?= htmlspecialchars($obat['nama_obat']) ?></h4>
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars($obat['jenis_obat']) ?></p>
                                    <p class="text-sm text-gray-500">Satuan: <?= htmlspecialchars($obat['satuan']) ?></p>
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

            <!-- Right Column - Cart -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Daftar Pembelian</h3>
                    
                    <div id="cartItems" class="space-y-3 mb-4 max-h-96 overflow-y-auto">
                        <p class="text-center text-gray-500 py-8">Belum ada obat</p>
                    </div>
                    
                    <div class="border-t border-gray-200 pt-4 space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-semibold" id="subtotalText">Rp 0</span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <label class="text-sm text-gray-600">Diskon (Rp)</label>
                            <input type="number" name="diskon" id="diskon" value="0" min="0" 
                                   class="w-32 px-3 py-2 border border-gray-300 rounded-lg text-right"
                                   onchange="updateTotal()">
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <label class="text-sm text-gray-600">Pajak (Rp)</label>
                            <input type="number" name="pajak" id="pajak" value="0" min="0" 
                                   class="w-32 px-3 py-2 border border-gray-300 rounded-lg text-right"
                                   onchange="updateTotal()">
                        </div>
                        
                        <div class="flex justify-between text-lg font-bold pt-2 border-t">
                            <span>Total</span>
                            <span class="text-purple-600" id="totalText">Rp 0</span>
                        </div>
                    </div>
                    
                    <input type="hidden" name="subtotal" id="subtotalInput" value="0">
                    <input type="hidden" name="total_bayar" id="totalInput" value="0">
                    <input type="hidden" name="total_item" id="totalItemInput" value="0">
                    <input type="hidden" name="items" id="itemsInput" value="[]">
                    
                    <button type="submit" id="btnProses" class="w-full mt-6 py-4 gradient-bg text-white rounded-xl font-bold hover:shadow-lg transition-all disabled:opacity-50" disabled>
                        Proses Pembelian
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
            <input type="hidden" id="modalObatSatuan">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah (Qty) *</label>
                <input type="number" id="modalQty" min="1" value="1"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Harga Beli per Unit *</label>
                <input type="number" id="modalHargaBeli" min="0" step="0.01" value="0"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">No. Batch *</label>
                <input type="text" id="modalNoBatch" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="Nomor batch obat">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tgl Produksi</label>
                    <input type="date" id="modalTglProduksi" value="<?= date('Y-m-d') ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tgl Kadaluarsa *</label>
                    <input type="date" id="modalTglKadaluarsa" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
        </div>
        
        <div class="flex space-x-4 mt-6">
            <button type="button" onclick="closeObatModal()" 
                    class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-xl font-medium hover:bg-gray-300">
                Batal
            </button>
            <button type="button" onclick="addObatToPembelian()" 
                    class="flex-1 px-4 py-3 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg">
                Tambahkan
            </button>
        </div>
    </div>
</div>

<script>
let cart = [];

// Search obat
const searchInput = document.getElementById('searchObat');
const obatContainer = document.getElementById('obatContainer');

searchInput.addEventListener('input', function () {
    const keyword = this.value;

    // Kirim AJAX
    fetch('../ajax/search_obat_pembelian.php?q=' + keyword)
        .then(res => res.json())
        .then(data => {
            obatContainer.innerHTML = '';

            if (data.length === 0) {
                obatContainer.innerHTML = `<p class="text-gray-500 text-center">Tidak ada obat ditemukan</p>`;
                return;
            }

            data.forEach(obat => {
                obatContainer.innerHTML += `
                    <div class="obat-item border border-gray-200 rounded-xl p-4 hover:border-purple-500 cursor-pointer transition-all" 
                        onclick='showObatModal(${JSON.stringify(obat)})'>
                        <div class="flex items-start justify-between">
                            <div>
                                <h4 class="font-semibold text-gray-800">${obat.nama_obat}</h4>
                                <p class="text-sm text-gray-500">${obat.jenis_obat}</p>
                                <p class="text-sm text-gray-500">Satuan: ${obat.satuan}</p>
                            </div>
                        </div>
                    </div>
                `;
            });
        })
        .catch(err => console.error(err));
});

function showObatModal(obat) {
    document.getElementById('modalObatId').value = obat.id_obat;
    document.getElementById('modalObatNama').value = obat.nama_obat;
    document.getElementById('modalObatSatuan').value = obat.satuan;
    document.getElementById('modalTitle').textContent = 'Tambah ' + obat.nama_obat;
    document.getElementById('modalQty').value = 1;
    document.getElementById('modalHargaBeli').value = obat.harga_beli || 0;
    document.getElementById('modalNoBatch').value = '';
    document.getElementById('modalTglProduksi').value = '<?= date('Y-m-d') ?>';
    document.getElementById('modalTglKadaluarsa').value = '';
    
    document.getElementById('obatModal').classList.remove('hidden');
}

function closeObatModal() {
    document.getElementById('obatModal').classList.add('hidden');
}

function addObatToPembelian() {
    const id_obat = parseInt(document.getElementById('modalObatId').value);
    const nama_obat = document.getElementById('modalObatNama').value;
    const satuan = document.getElementById('modalObatSatuan').value;
    const qty = parseInt(document.getElementById('modalQty').value);
    const harga_beli = parseFloat(document.getElementById('modalHargaBeli').value);
    const no_batch = document.getElementById('modalNoBatch').value;
    const tgl_produksi = document.getElementById('modalTglProduksi').value;
    const tgl_kadaluarsa = document.getElementById('modalTglKadaluarsa').value;
    
    if (!qty || qty < 1) {
        alert('Jumlah harus lebih dari 0!');
        return;
    }
    
    if (!harga_beli || harga_beli < 0) {
        alert('Harga beli harus diisi!');
        return;
    }
    
    if (!no_batch) {
        alert('No. Batch harus diisi!');
        return;
    }
    
    if (!tgl_kadaluarsa) {
        alert('Tanggal kadaluarsa harus diisi!');
        return;
    }
    
    // Check if already exists
    const existing = cart.find(item => item.id_obat === id_obat && item.no_batch === no_batch);
    if (existing) {
        existing.qty += qty;
        existing.subtotal = existing.qty * existing.harga_beli;
    } else {
        cart.push({
            id_obat: id_obat,
            nama_obat: nama_obat,
            satuan: satuan,
            qty: qty,
            harga_beli: harga_beli,
            no_batch: no_batch,
            tgl_produksi: tgl_produksi,
            tgl_kadaluarsa: tgl_kadaluarsa,
            subtotal: qty * harga_beli
        });
    }
    
    renderCart();
    closeObatModal();
}

function removeObat(index) {
    cart.splice(index, 1);
    renderCart();
}

function updateQty(index, qty) {
    if (qty < 1) {
        removeObat(index);
        return;
    }
    
    cart[index].qty = parseInt(qty);
    cart[index].subtotal = cart[index].qty * cart[index].harga_beli;
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartItems');
    
    if (cart.length === 0) {
        container.innerHTML = '<p class="text-center text-gray-500 py-8">Belum ada obat</p>';
        document.getElementById('btnProses').disabled = true;
        updateTotal();
        return;
    }
    
    container.innerHTML = cart.map((item, index) => `
        <div class="border border-gray-200 rounded-lg p-3">
            <div class="flex justify-between items-start mb-2">
                <div class="flex-1">
                    <h5 class="font-semibold text-sm text-gray-800">${item.nama_obat}</h5>
                    <p class="text-xs text-gray-500">Batch: ${item.no_batch}</p>
                    <p class="text-xs text-gray-500">Exp: ${item.tgl_kadaluarsa}</p>
                </div>
                <button type="button" onclick="removeObat(${index})" class="text-red-500 hover:text-red-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="updateQty(${index}, ${item.qty - 1})" 
                            class="w-7 h-7 bg-gray-200 rounded-lg hover:bg-gray-300">-</button>
                    <input type="number" value="${item.qty}" min="1"
                           onchange="updateQty(${index}, this.value)"
                           class="w-12 text-center border border-gray-300 rounded-lg py-1">
                    <button type="button" onclick="updateQty(${index}, ${item.qty + 1})" 
                            class="w-7 h-7 bg-gray-200 rounded-lg hover:bg-gray-300">+</button>
                </div>
                <span class="font-bold text-purple-600">${formatRupiah(item.subtotal)}</span>
            </div>
            <p class="text-xs text-gray-500 mt-1">@ ${formatRupiah(item.harga_beli)} / ${item.satuan}</p>
        </div>
    `).join('');
    
    document.getElementById('btnProses').disabled = false;
    updateTotal();
}

function updateTotal() {
    const subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
    const diskon = parseFloat(document.getElementById('diskon').value) || 0;
    const pajak = parseFloat(document.getElementById('pajak').value) || 0;
    const total = subtotal - diskon + pajak;
    
    document.getElementById('subtotalText').textContent = formatRupiah(subtotal);
    document.getElementById('totalText').textContent = formatRupiah(total);
    document.getElementById('subtotalInput').value = subtotal;
    document.getElementById('totalInput').value = total;
    document.getElementById('totalItemInput').value = cart.length;
    document.getElementById('itemsInput').value = JSON.stringify(cart);
}

// Form validation
document.getElementById('formPembelian').addEventListener('submit', function(e) {
    if (cart.length === 0) {
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