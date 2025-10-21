<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin']);

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
            $id_supplier = (int)$_POST['id_supplier'];
            $id_user = (int)$_POST['id_user'];
            $no_faktur = sanitize($_POST['no_faktur']);
            $tanggal_pembelian = $_POST['tanggal_pembelian'];
            $tanggal_jatuh_tempo = !empty($_POST['tanggal_jatuh_tempo']) ? $_POST['tanggal_jatuh_tempo'] : null;
            $total_item = (int)$_POST['total_item'];
            $subtotal = (float)$_POST['subtotal'];
            $diskon = (float)$_POST['diskon'];
            $pajak = (float)$_POST['pajak'];
            $total_bayar = (float)$_POST['total_bayar'];
            $status_pembayaran = sanitize($_POST['status_pembayaran']);
            $keterangan = sanitize($_POST['keterangan']);
            
            // Decode items
            $items = json_decode($_POST['items'], true);
            
            if (empty($items)) {
                throw new Exception('Daftar pembelian kosong!');
            }
            
            // Insert pembelian header
            $stmt = $db->prepare("INSERT INTO pembelian (
                id_apotik, id_supplier, id_user, no_faktur, tanggal_pembelian,
                tanggal_jatuh_tempo, total_item, subtotal, diskon, pajak,
                total_bayar, status_pembayaran, keterangan
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("iiississdddss",
                $id_apotik, $id_supplier, $id_user, $no_faktur, $tanggal_pembelian,
                $tanggal_jatuh_tempo, $total_item, $subtotal, $diskon, $pajak,
                $total_bayar, $status_pembayaran, $keterangan
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Gagal menyimpan pembelian: ' . $stmt->error);
            }
            
            $id_pembelian = $db->lastInsertId();
            
            // Process each item
            foreach ($items as $item) {
                $id_obat = (int)$item['id_obat'];
                $qty = (int)$item['qty'];
                $harga_beli = (float)$item['harga_beli'];
                $no_batch = sanitize($item['no_batch']);
                $tgl_produksi = !empty($item['tgl_produksi']) ? $item['tgl_produksi'] : null;
                $tgl_kadaluarsa = $item['tgl_kadaluarsa'];
                $subtotal_item = $qty * $harga_beli;
                
                // Create new batch
                $stmtBatch = $db->prepare("INSERT INTO batch_obat (
                    id_obat, no_batch, tanggal_produksi, tanggal_kadaluarsa,
                    stok_awal, stok_sisa, harga_beli_per_unit, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'tersedia')");
                
                $stmtBatch->bind_param("isssiid",
                    $id_obat, $no_batch, $tgl_produksi, $tgl_kadaluarsa,
                    $qty, $qty, $harga_beli
                );
                
                if (!$stmtBatch->execute()) {
                    throw new Exception('Gagal membuat batch: ' . $stmtBatch->error);
                }
                
                $id_batch = $db->lastInsertId();
                
                // Insert detail pembelian
                $stmtDetail = $db->prepare("INSERT INTO detail_pembelian (
                    id_pembelian, id_obat, id_batch, qty, harga_beli, diskon, subtotal
                ) VALUES (?, ?, ?, ?, ?, 0, ?)");
                
                $stmtDetail->bind_param("iiiddd",
                    $id_pembelian, $id_obat, $id_batch,
                    $qty, $harga_beli, $subtotal_item
                );
                
                if (!$stmtDetail->execute()) {
                    throw new Exception('Gagal menyimpan detail: ' . $stmtDetail->error);
                }
            }
            
            // Commit transaction
            $db->getConnection()->commit();
            
            alert('Pembelian berhasil disimpan!', 'success');
            redirect('pembelian/detail.php?id=' . $id_pembelian);
            
        } catch (Exception $e) {
            // Rollback on error
            $db->getConnection()->rollback();
            alert('Error: ' . $e->getMessage(), 'error');
            redirect('pembelian/create.php');
        }
    }
} else {
    redirect('pembelian/create.php');
}
?>