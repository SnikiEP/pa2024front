<?php
session_start();

$dsn = 'mysql:host=db;dbname=helix_db;charset=utf8';
$username = 'root';
$password = 'root_password';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if (!isset($_SESSION['accessToken'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deliver'])) {
    $warehouseId = $_POST['start-warehouse'];
    $items = $_POST['items'] ?? [];

    foreach ($items as $itemId) {
        $stmt = $pdo->prepare("UPDATE stock_items SET quantity = quantity - 1 WHERE id = :item_id AND warehouse_id = :warehouse_id AND quantity > 0");
        $stmt->execute([':item_id' => $itemId, ':warehouse_id' => $warehouseId]);
    }

    header("Location: delivery_success.php");
    exit;
} else {
    header("Location: index.php");
    exit;
}
?>
