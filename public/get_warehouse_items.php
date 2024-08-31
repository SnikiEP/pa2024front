<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || !is_array($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$dsn = 'mysql:host=db;dbname=helix_db;charset=utf8';
$username = 'root';
$password = 'root_password';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$warehouseId = $_GET['warehouse_id'] ?? null;

if ($warehouseId) {
    try {
        $stmt = $pdo->prepare("
            SELECT si.id, fi.name, si.quantity, fi.unit
            FROM stock_items si
            JOIN food_items fi ON si.food_item_id = fi.id
            WHERE si.warehouse_id = :warehouse_id AND si.quantity > 0
        ");
        $stmt->execute([':warehouse_id' => $warehouseId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($items) {
            echo json_encode($items);
        } else {
            echo json_encode([]);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'No warehouse ID provided']);
}
?>
