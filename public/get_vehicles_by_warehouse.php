<?php
$dsn = 'mysql:host=db;dbname=helix_db;charset=utf8';
$username = 'root';
$password = 'root_password';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$warehouseId = $_GET['warehouse_id'] ?? null;

if ($warehouseId) {
    $stmt = $pdo->prepare("SELECT id, id_plate, model FROM vehicles WHERE current_warehouse_id = :warehouse_id");
    $stmt->execute([':warehouse_id' => $warehouseId]);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($vehicles);
}
?>
