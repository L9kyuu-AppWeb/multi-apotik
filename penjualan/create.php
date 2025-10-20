<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin', 'kasir']);

$user = getUserData();
$db = db();

// Generate nomor transaksi
$no_transaksi = generateKode('TRX', 'penjualan', 'no_transaksi', 8);

// Get data obat dengan stok
$query = "SELECT o.*, COALESCE(SUM(b.stok_sisa), 0) as total_stok
          FROM obat o
          LEFT JOIN batch_obat b ON o.id_obat = b.id_obat AND b.status = 'tersedia'
          WHERE o.id_apotik = ? AND o.status = 'aktif'
          GROUP BY o.id_obat
          HAVING total_stok > 0
          ORDER BY o.nama_obat";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $user['id_apotik']);
$stmt->execute();
$obatList = $stmt->get_result();

// Get data pelanggan
$pelangganQuery = "SELECT * FROM pelanggan ORDER BY nama_pelanggan";
$pelangganList = $db->query($pelangganQuery);

// Get data resep pending
$resepQuery = "SELECT r.*, p.nama_pelanggan, d.nama_dokter 
               FROM resep r
               LEFT JOIN pelanggan p ON r.id_pelanggan = p.id_pelanggan
               LEFT JOIN dokter d ON r.id_dokter = d.id_dokter
               WHERE r.id_apotik = ? AND r.status = 'pending'
               ORDER BY r.tanggal_resep DESC";
$stmtResep = $db->prepare($resepQuery);
$stmtResep->bind_param("i", $user['id_apotik']);
$stmtResep->execute();
$resepList = $stmtResep->get_result();

$pageTitle = 'Transaksi Penjualan Baru';
include '../includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Transaksi Penjualan Baru</h2>
            <p class="text-gray-600 mt-1">Buat transaksi penjualan obat</p>
        </div>
        <a href="index.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all">
            Kembali
        </a>
    </div>

    <form id="formPenjualan" method="POST" action="process.php">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="id_apotik" value="<?= $user['id_apotik'] ?>">
        <input type="hidden" name="id_user" value="<?= $user['id'] ?>">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Form -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Info Transaksi -->
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Informasi Transaksi</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">No. Transaksi</label>
                            <input type="text" name="no_transaksi" value="<?= $no_transaksi ?>" readonly class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 font-semibold">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal</label>
                            <input type="datetime-local" name="tanggal_penjualan" value="<?= date('Y-m-d\TH:i') ?>" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Penjualan</label>
                            <select name="tipe_penjualan" id="tipePenjualan" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                                <option value="bebas">Penjualan Bebas</option>
                                <option value="resep">Berdasarkan Resep</option>
                            </select>
                        </div>
                        
                        <div id="resepContainer" style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Resep</label>
                            <select name="id_resep" id="selectResep" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                                <option value="">- Pilih Resep -</option>
                                <?php while ($resep = $resepList->fetch_assoc()): ?>
                                <option value="<?= $resep['id_resep'] ?>">
                                    <?= htmlspecialchars($resep['no_resep']) ?> - <?= htmlspecialchars($resep['nama_pelanggan']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pelanggan (Opsional)</label>
                            <select name="id_pelanggan" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                                <option value="">- Pilih Pelanggan -</option>
                                <?php while ($pelanggan = $pelangganList->fetch_assoc()): ?>
                                <option value="<?= $pelanggan['id_pelanggan'] ?>">
                                    <?= htmlspecialchars($pelanggan['nama_pelanggan']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Metode Pembayaran</label>
                            <select name="metode_pembayaran" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                                <option value="tunai">Tunai</option>
                                <option value="debit">Debit</option>
                                <option value="kredit">Kredit</option>
                                <option value="transfer">Transfer</option>
                                <option value="ewallet">E-Wallet</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Pilih Obat -->
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Pilih Obat</h3>
                    
                    <div class="mb-4">
                        <input type="text" id="searchObat" placeholder="Cari nama obat..." class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-96 overflow-y-auto" id="obatContainer">
                        <?php 
                        $obatList->data_seek(0);
                        while ($obat = $obatList->fetch_assoc()): 
                        ?>
                        <div class="obat-item border border-gray-200 rounded-xl p-4 hover:border-purple-500 cursor-pointer transition-all" 
                             data-nama="<?= strtolower($obat['nama_obat']) ?>"
                             onclick="addObat(<?= htmlspecialchars(json_encode($obat)) ?>)">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-800"><?= htmlspecialchars($obat['nama_obat']) ?></h4>
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars($obat['jenis_obat']) ?></p>
                                    <p class="text-sm text-gray-500">Stok: <?= $obat['total_stok'] ?> <?= htmlspecialchars($obat['satuan']) ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-purple-600"><?= formatRupiah($obat['harga_jual']) ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column - Cart -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Keranjang Belanja</h3>
                    
                    <div id="cartItems" class="space-y-3 mb-4 max-h-96 overflow-y-auto">
                        <p class="text-center text-gray-500 py-8">Keranjang masih kosong</p>
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
                        
                        <div class="flex justify-between items-center">
                            <label class="text-sm text-gray-600">Dibayar (Rp)</label>
                            <input type="number" name="jumlah_dibayar" id="jumlahDibayar" value="0" min="0" required
                                   class="w-32 px-3 py-2 border border-gray-300 rounded-lg text-right"
                                   onchange="hitungKembalian()">
                        </div>
                        
                        <div class="flex justify-between text-lg">
                            <span class="text-gray-600">Kembalian</span>
                            <span class="font-bold text-green-600" id="kembalianText">Rp 0</span>
                        </div>
                    </div>
                    
                    <input type="hidden" name="subtotal" id="subtotalInput" value="0">
                    <input type="hidden" name="total_bayar" id="totalInput" value="0">
                    <input type="hidden" name="total_item" id="totalItemInput" value="0">
                    <input type="hidden" name="kembalian" id="kembalianInput" value="0">
                    <input type="hidden" name="items" id="itemsInput" value="[]">
                    
                    <button type="submit" id="btnProses" class="w-full mt-6 py-4 gradient-bg text-white rounded-xl font-bold hover:shadow-lg transition-all disabled:opacity-50" disabled>
                        Proses Transaksi
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let cart = [];

// Search obat
document.getElementById('searchObat').addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    document.querySelectorAll('.obat-item').forEach(item => {
        const nama = item.dataset.nama;
        item.style.display = nama.includes(search) ? 'block' : 'none';
    });
});

// Toggle resep container
document.getElementById('tipePenjualan').addEventListener('change', function() {
    const resepContainer = document.getElementById('resepContainer');
    const selectResep = document.getElementById('selectResep');
    
    if (this.value === 'resep') {
        resepContainer.style.display = 'block';
        selectResep.required = true;
    } else {
        resepContainer.style.display = 'none';
        selectResep.required = false;
        selectResep.value = '';
    }
});

function addObat(obat) {
    // Check if already in cart
    const existing = cart.find(item => item.id_obat === obat.id_obat);
    
    if (existing) {
        if (existing.qty < obat.total_stok) {
            existing.qty++;
            existing.subtotal = existing.qty * existing.harga_jual;
        } else {
            alert('Stok tidak mencukupi!');
            return;
        }
    } else {
        cart.push({
            id_obat: obat.id_obat,
            nama_obat: obat.nama_obat,
            harga_jual: parseFloat(obat.harga_jual),
            qty: 1,
            stok: parseInt(obat.total_stok),
            satuan: obat.satuan,
            subtotal: parseFloat(obat.harga_jual)
        });
    }
    
    renderCart();
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
    
    if (qty > cart[index].stok) {
        alert('Stok tidak mencukupi!');
        return;
    }
    
    cart[index].qty = parseInt(qty);
    cart[index].subtotal = cart[index].qty * cart[index].harga_jual;
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartItems');
    
    if (cart.length === 0) {
        container.innerHTML = '<p class="text-center text-gray-500 py-8">Keranjang masih kosong</p>';
        document.getElementById('btnProses').disabled = true;
        updateTotal();
        return;
    }
    
    container.innerHTML = cart.map((item, index) => `
        <div class="border border-gray-200 rounded-lg p-3">
            <div class="flex justify-between items-start mb-2">
                <h5 class="font-semibold text-sm text-gray-800 flex-1">${item.nama_obat}</h5>
                <button type="button" onclick="removeObat(${index})" class="text-red-500 hover:text-red-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="updateQty(${index}, ${item.qty - 1})" 
                            class="w-7 h-7 bg-gray-200 rounded-lg hover:bg-gray-300 flex items-center justify-center">
                        <span class="text-lg">-</span>
                    </button>
                    <input type="number" value="${item.qty}" min="1" max="${item.stok}"
                           onchange="updateQty(${index}, this.value)"
                           class="w-12 text-center border border-gray-300 rounded-lg py-1">
                    <button type="button" onclick="updateQty(${index}, ${item.qty + 1})" 
                            class="w-7 h-7 bg-gray-200 rounded-lg hover:bg-gray-300 flex items-center justify-center">
                        <span class="text-lg">+</span>
                    </button>
                </div>
                <span class="font-bold text-purple-600">${formatRupiah(item.subtotal)}</span>
            </div>
            <p class="text-xs text-gray-500 mt-1">@ ${formatRupiah(item.harga_jual)} / ${item.satuan}</p>
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
    
    hitungKembalian();
}

function hitungKembalian() {
    const total = parseFloat(document.getElementById('totalInput').value) || 0;
    const dibayar = parseFloat(document.getElementById('jumlahDibayar').value) || 0;
    const kembalian = dibayar - total;
    
    document.getElementById('kembalianText').textContent = formatRupiah(kembalian >= 0 ? kembalian : 0);
    document.getElementById('kembalianInput').value = kembalian >= 0 ? kembalian : 0;
    
    if (dibayar < total && dibayar > 0) {
        document.getElementById('kembalianText').classList.add('text-red-600');
        document.getElementById('kembalianText').classList.remove('text-green-600');
    } else {
        document.getElementById('kembalianText').classList.add('text-green-600');
        document.getElementById('kembalianText').classList.remove('text-red-600');
    }
}

// Form validation
document.getElementById('formPenjualan').addEventListener('submit', function(e) {
    if (cart.length === 0) {
        e.preventDefault();
        alert('Keranjang masih kosong!');
        return false;
    }
    
    const total = parseFloat(document.getElementById('totalInput').value) || 0;
    const dibayar = parseFloat(document.getElementById('jumlahDibayar').value) || 0;
    
    if (dibayar < total) {
        e.preventDefault();
        alert('Jumlah pembayaran kurang!');
        return false;
    }
    
    return true;
});
</script>

<?php include '../includes/footer.php'; ?>