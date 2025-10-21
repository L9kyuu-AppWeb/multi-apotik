<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin', 'kasir']);

$db = db();
$user = getUserData();

$keyword = strtolower($_GET['keyword'] ?? '');

$query = "SELECT o.*, COALESCE(SUM(b.stok_sisa), 0) as total_stok
          FROM obat o
          LEFT JOIN batch_obat b ON o.id_obat = b.id_obat AND b.status = 'tersedia'
          WHERE o.id_apotik = ? 
          AND o.status = 'aktif'
          AND LOWER(o.nama_obat) LIKE ?
          GROUP BY o.id_obat
          HAVING total_stok > 0
          ORDER BY o.nama_obat";

$stmt = $db->prepare($query);
$like = "%$keyword%";
$stmt->bind_param("is", $user['id_apotik'], $like);
$stmt->execute();
$result = $stmt->get_result();

$obats = [];
while ($row = $result->fetch_assoc()) {
    $obats[] = $row;
}

echo json_encode($obats);
?>
