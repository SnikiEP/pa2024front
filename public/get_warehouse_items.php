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
    $stmt = $pdo->prepare("
        SELECT si.id, fi.name, si.quantity, fi.unit
        FROM stock_items si
        JOIN food_items fi ON si.food_item_id = fi.id
        WHERE si.warehouse_id = :warehouse_id
    ");
    $stmt->execute([':warehouse_id' => $warehouseId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($items) {
        foreach ($items as $item) {
            echo '<div>';
            echo '<label>';
            echo '<input type="checkbox" name="items[]" value="' . htmlspecialchars($item['id']) . '">';
            echo htmlspecialchars($item['name']) . ' - ' . htmlspecialchars($item['quantity']) . ' ' . htmlspecialchars($item['unit']);
            echo '</label>';
            echo '</div>';
        }
    } else {
        echo '<p>Aucun article disponible dans cet entrepôt.</p>';
    }
}
?>
