<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin']);

$db = db();
$user = getUserData();

$keyword = isset($_GET['q']) ? '%' . $_GET['q'] . '%' : '';

$query = "SELECT * FROM obat 
          WHERE id_apotik = ? 
          AND status = 'aktif'
          AND nama_obat LIKE ?
          ORDER BY nama_obat";

$stmt = $db->prepare($query);
$stmt->bind_param("is", $user['id_apotik'], $keyword);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>
