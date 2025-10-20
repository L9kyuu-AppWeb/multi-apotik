<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin', 'kasir']);

$user = getUserData();
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'create') {
        try {
            // Begin transaction
            $db->getConnection()->begin_transaction();
            
            // Sanitize input
            $id_apotik = (int)$_POST['id_apotik'];
            $id_user = (int)$_POST['id_user'];
            $id_pelanggan = !empty($_POST['id_pelanggan']) ? (int)$_POST['id_pelanggan'] : null;
            $id_resep = !empty($_POST['id_resep']) ? (int)$_POST['id_resep'] : null;
            $no_transaksi = sanitize($_POST['no_transaksi']);
            $tanggal_penjualan = $_POST['tanggal_penjualan'];
            $tipe_penjualan = $_POST['tipe_penjualan'];
            $total_item = (int)$_POST['total_item'];
            $subtotal = (float)$_POST['subtotal'];
            $diskon = (float)$_POST['diskon'];
            $pajak = (float)$_POST['pajak'];
            $total_bayar = (float)$_POST['total_bayar'];
            $jumlah_dibayar = (float)$_POST['jumlah_dibayar'];
            $kembalian = (float)$_POST['kembalian'];
            $metode_pembayaran = sanitize($_POST['metode_pembayaran']);
            
            // Decode items
            $items = json_decode($_POST['items'], true);
            
            if (empty($items)) {
                throw new Exception('Keranjang kosong!');
            }
            
            // Insert penjualan header
            $stmt = $db->prepare("INSERT INTO penjualan (
                id_apotik, id_user, id_pelanggan, id_resep, no_transaksi, 
                tanggal_penjualan, tipe_penjualan, total_item, subtotal, 
                diskon, pajak, total_bayar, jumlah_dibayar, kembalian, 
                metode_pembayaran
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("iiissssidddddds", 
                $id_apotik, $id_user, $id_pelanggan, $id_resep, $no_transaksi,
                $tanggal_penjualan, $tipe_penjualan, $total_item, $subtotal,
                $diskon, $pajak, $total_bayar, $jumlah_dibayar, $kembalian,
                $metode_pembayaran
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Gagal menyimpan transaksi: ' . $stmt->error);
            }
            
            $id_penjualan = $db->lastInsertId();
            
            // Process each item with FEFO logic
            foreach ($items as $item) {
                $id_obat = (int)$item['id_obat'];
                $qty_needed = (int)$item['qty'];
                $harga_jual = (float)$item['harga_jual'];
                
                // Get available batches ordered by expiry date (FEFO)
                $batchQuery = "SELECT id_batch, stok_sisa, tanggal_kadaluarsa 
                              FROM batch_obat 
                              WHERE id_obat = ? 
                              AND status = 'tersedia' 
                              AND stok_sisa > 0 
                              AND tanggal_kadaluarsa >= CURDATE()
                              ORDER BY tanggal_kadaluarsa ASC, created_at ASC";
                
                $stmtBatch = $db->prepare($batchQuery);
                $stmtBatch->bind_param("i", $id_obat);
                $stmtBatch->execute();
                $batches = $stmtBatch->get_result();
                
                $remaining_qty = $qty_needed;
                
                while ($batch = $batches->fetch_assoc()) {
                    if ($remaining_qty <= 0) break;
                    
                    $id_batch = $batch['id_batch'];
                    $stok_batch = (int)$batch['stok_sisa'];
                    
                    // Determine how much to take from this batch
                    $qty_from_batch = min($remaining_qty, $stok_batch);
                    
                    // Calculate subtotal for this batch portion
                    $subtotal_item = $qty_from_batch * $harga_jual;
                    
                    // Insert detail penjualan
                    $stmtDetail = $db->prepare("INSERT INTO detail_penjualan (
                        id_penjualan, id_obat, id_batch, qty, harga_jual, diskon, subtotal
                    ) VALUES (?, ?, ?, ?, ?, 0, ?)");
                    
                    $stmtDetail->bind_param("iiiidd", 
                        $id_penjualan, $id_obat, $id_batch, 
                        $qty_from_batch, $harga_jual, $subtotal_item
                    );
                    
                    if (!$stmtDetail->execute()) {
                        throw new Exception('Gagal menyimpan detail: ' . $stmtDetail->error);
                    }
                    
                    // Update stok will be handled by trigger automatically
                    
                    $remaining_qty -= $qty_from_batch;
                }
                
                // Check if all quantity fulfilled
                if ($remaining_qty > 0) {
                    throw new Exception("Stok obat {$item['nama_obat']} tidak mencukupi!");
                }
            }
            
            // Update resep status if from resep
            if ($id_resep) {
                $stmtResep = $db->prepare("UPDATE resep SET status = 'selesai' WHERE id_resep = ?");
                $stmtResep->bind_param("i", $id_resep);
                $stmtResep->execute();
            }
            
            // Commit transaction
            $db->getConnection()->commit();
            
            alert('Transaksi berhasil disimpan!', 'success');
            redirect('penjualan/print.php?id=' . $id_penjualan);
            
        } catch (Exception $e) {
            // Rollback on error
            $db->getConnection()->rollback();
            alert('Error: ' . $e->getMessage(), 'error');
            // redirect('create.php');
            echo 'Error: ' . $e->getMessage();
        }
    }
} else {
    // redirect('create.php');
    echo "Invalid request.";
}
?>