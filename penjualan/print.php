<?php
define('APP_ACCESS', true);
require_once '../config.php';
requireLogin();

$user = getUserData();
$db = db();

$id_penjualan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get transaction data
$query = "SELECT p.*, a.nama_apotik, a.alamat, a.no_telp as telp_apotik,
          u.nama_lengkap as nama_kasir, 
          pel.nama_pelanggan, 
          r.no_resep, d.nama_dokter
          FROM penjualan p
          LEFT JOIN apotik a ON p.id_apotik = a.id_apotik
          LEFT JOIN users u ON p.id_user = u.id_user
          LEFT JOIN pelanggan pel ON p.id_pelanggan = pel.id_pelanggan
          LEFT JOIN resep r ON p.id_resep = r.id_resep
          LEFT JOIN dokter d ON r.id_dokter = d.id_dokter
          WHERE p.id_penjualan = ?";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $id_penjualan);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    alert('Transaksi tidak ditemukan', 'error');
    redirect('index.php');
}

$trx = $result->fetch_assoc();

// Get detail items
$detailQuery = "SELECT dp.*, o.nama_obat, o.satuan, b.no_batch
                FROM detail_penjualan dp
                JOIN obat o ON dp.id_obat = o.id_obat
                JOIN batch_obat b ON dp.id_batch = b.id_batch
                WHERE dp.id_penjualan = ?
                ORDER BY o.nama_obat";

$stmtDetail = $db->prepare($detailQuery);
$stmtDetail->bind_param("i", $id_penjualan);
$stmtDetail->execute();
$details = $stmtDetail->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Penjualan - <?= $trx['no_transaksi'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        * { font-family: 'Inter', sans-serif; }
        
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .print-area { width: 80mm; margin: 0 auto; }
        }
        
        .print-area {
            width: 80mm;
            margin: 0 auto;
            background: white;
            padding: 10mm;
        }
        
        .struk-line {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-8 no-print">
        <div class="flex justify-center space-x-4 mb-4">
            <button onclick="window.print()" class="px-6 py-3 bg-purple-600 text-white rounded-xl font-semibold hover:bg-purple-700">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Cetak Struk
            </button>
            <a href="index.php" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-xl font-semibold hover:bg-gray-300">
                Kembali
            </a>
        </div>
    </div>

    <div class="print-area">
        <!-- Header -->
        <div class="text-center mb-4">
            <h1 class="text-xl font-bold"><?= htmlspecialchars($trx['nama_apotik']) ?></h1>
            <p class="text-xs"><?= htmlspecialchars($trx['alamat']) ?></p>
            <p class="text-xs">Telp: <?= htmlspecialchars($trx['telp_apotik']) ?></p>
        </div>

        <div class="struk-line"></div>

        <!-- Info Transaksi -->
        <div class="text-xs space-y-1 mb-3">
            <div class="flex justify-between">
                <span>No. Transaksi</span>
                <span class="font-semibold"><?= htmlspecialchars($trx['no_transaksi']) ?></span>
            </div>
            <div class="flex justify-between">
                <span>Tanggal</span>
                <span><?= formatTanggalWaktu($trx['tanggal_penjualan'], 'd/m/Y H:i') ?></span>
            </div>
            <div class="flex justify-between">
                <span>Kasir</span>
                <span><?= htmlspecialchars($trx['nama_kasir']) ?></span>
            </div>
            <?php if ($trx['nama_pelanggan']): ?>
            <div class="flex justify-between">
                <span>Pelanggan</span>
                <span><?= htmlspecialchars($trx['nama_pelanggan']) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($trx['no_resep']): ?>
            <div class="flex justify-between">
                <span>No. Resep</span>
                <span><?= htmlspecialchars($trx['no_resep']) ?></span>
            </div>
            <div class="flex justify-between">
                <span>Dokter</span>
                <span><?= htmlspecialchars($trx['nama_dokter']) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="struk-line"></div>

        <!-- Items -->
        <div class="text-xs mb-3">
            <?php 
            // Group items by obat
            $grouped_items = [];
            $details->data_seek(0);
            while ($item = $details->fetch_assoc()) {
                $key = $item['id_obat'];
                if (!isset($grouped_items[$key])) {
                    $grouped_items[$key] = [
                        'nama' => $item['nama_obat'],
                        'satuan' => $item['satuan'],
                        'harga' => $item['harga_jual'],
                        'qty' => 0,
                        'subtotal' => 0
                    ];
                }
                $grouped_items[$key]['qty'] += $item['qty'];
                $grouped_items[$key]['subtotal'] += $item['subtotal'];
            }
            
            foreach ($grouped_items as $item): 
            ?>
            <div class="mb-2">
                <div class="font-semibold"><?= htmlspecialchars($item['nama']) ?></div>
                <div class="flex justify-between">
                    <span><?= $item['qty'] ?> <?= htmlspecialchars($item['satuan']) ?> x <?= formatRupiah($item['harga']) ?></span>
                    <span class="font-semibold"><?= formatRupiah($item['subtotal']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="struk-line"></div>

        <!-- Total -->
        <div class="text-xs space-y-1 mb-3">
            <div class="flex justify-between">
                <span>Subtotal</span>
                <span><?= formatRupiah($trx['subtotal']) ?></span>
            </div>
            <?php if ($trx['diskon'] > 0): ?>
            <div class="flex justify-between">
                <span>Diskon</span>
                <span>-<?= formatRupiah($trx['diskon']) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($trx['pajak'] > 0): ?>
            <div class="flex justify-between">
                <span>Pajak</span>
                <span><?= formatRupiah($trx['pajak']) ?></span>
            </div>
            <?php endif; ?>
            <div class="flex justify-between font-bold text-sm pt-2 border-t">
                <span>TOTAL</span>
                <span><?= formatRupiah($trx['total_bayar']) ?></span>
            </div>
            <div class="flex justify-between">
                <span>Dibayar (<?= ucfirst($trx['metode_pembayaran']) ?>)</span>
                <span><?= formatRupiah($trx['jumlah_dibayar']) ?></span>
            </div>
            <div class="flex justify-between font-semibold">
                <span>Kembalian</span>
                <span><?= formatRupiah($trx['kembalian']) ?></span>
            </div>
        </div>

        <div class="struk-line"></div>

        <!-- Footer -->
        <div class="text-center text-xs mt-4">
            <p class="font-semibold">TERIMA KASIH</p>
            <p>Semoga Lekas Sembuh</p>
            <p class="mt-2">Barang yang sudah dibeli<br>tidak dapat dikembalikan</p>
        </div>

        <div class="text-center text-xs mt-4 text-gray-500">
            <p>Powered by <?= APP_NAME ?></p>
            <p>Dicetak: <?= date('d/m/Y H:i:s') ?></p>
        </div>
    </div>

    <script>
        // Auto print on load (optional)
        // window.onload = function() { window.print(); };
    </script>
</body>
</html>