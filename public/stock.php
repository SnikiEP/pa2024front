<?php
session_start();
include_once('maintenance_check.php');

if (!isset($_SESSION['accessToken'])) {
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
    die("Database connection failed: " . $e->getMessage());
}

$operationMessage = "";

$warehousesStmt = $pdo->query("SELECT * FROM warehouses");
$warehouses = $warehousesStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_warehouse'])) {
    $location = $_POST['location'];
    $rack_capacity = $_POST['rack_capacity'];

    $stmt = $pdo->prepare("INSERT INTO warehouses (location, rack_capacity) VALUES (:location, :rack_capacity)");
    $stmt->execute([
        ':location' => $location,
        ':rack_capacity' => $rack_capacity
    ]);

    $operationMessage = "Entrepôt ajouté avec succès.";
}

$selectedWarehouse = null;
$foodItems = [];
$foodTypesStmt = $pdo->query("SELECT * FROM food_types");
$foodTypes = $foodTypesStmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['warehouse_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM warehouses WHERE id = :id");
    $stmt->execute(['id' => $_GET['warehouse_id']]);
    $selectedWarehouse = $stmt->fetch(PDO::FETCH_ASSOC);

    $foodItemsStmt = $pdo->prepare("
        SELECT fi.*, ft.name AS food_name, ft.unit, ft.price_per_unit 
        FROM food_items fi 
        JOIN food_types ft ON fi.food_type_id = ft.id
        WHERE fi.warehouse_id = :warehouse_id
    ");
    $foodItemsStmt->execute(['warehouse_id' => $_GET['warehouse_id']]);
    $foodItems = $foodItemsStmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $warehouseId = $_POST['warehouse_id'] ?? null;

    if (isset($_POST['add_item'])) {
        $foodTypeId = $_POST['food_type_id'];
        $quantity = $_POST['quantity'];

        $stmt = $pdo->prepare("
            INSERT INTO food_items (food_type_id, warehouse_id, quantity)
            VALUES (:food_type_id, :warehouse_id, :quantity)
        ");
        $stmt->execute([
            ':food_type_id' => $foodTypeId,
            ':warehouse_id' => $warehouseId,
            ':quantity' => $quantity
        ]);

        $operationMessage = "Aliment ajouté avec succès.";
    } elseif (isset($_POST['modify_item'])) {
        $foodItemId = $_POST['food_item_id'];
        $quantity = $_POST['quantity'];

        $stmt = $pdo->prepare("UPDATE food_items SET quantity = :quantity WHERE id = :id");
        $stmt->execute([
            ':quantity' => $quantity,
            ':id' => $foodItemId
        ]);

        $operationMessage = "Aliment modifié avec succès.";
    } elseif (isset($_POST['delete_item'])) {
        $foodItemId = $_POST['food_item_id'];

        $stmt = $pdo->prepare("DELETE FROM food_items WHERE id = :id");
        $stmt->execute([':id' => $foodItemId]);

        $operationMessage = "Aliment supprimé avec succès.";
    }

    header("Location: stock.php?warehouse_id=" . $warehouseId);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Warehouse Stock Management Dashboard - ATD</title>
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/head.php'); ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.0/css/bulma.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/assets/css/warehouse.css">
</head>
<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'); ?>
        <main class="section">
            <div class="container">
                <h1 class="title has-text-centered">Warehouse Stock Management Dashboard</h1>
                
                <?php if ($operationMessage): ?>
                    <div class="notification is-success">
                        <?= htmlspecialchars($operationMessage); ?>
                    </div>
                <?php endif; ?>

                <div class="box">
                    <h2 class="subtitle">Add New Warehouse</h2>
                    <form method="POST">
                        <div class="field">
                            <label class="label">Location</label>
                            <div class="control">
                                <input class="input" type="text" name="location" placeholder="Enter warehouse location" required>
                            </div>
                        </div>
                        <div class="field">
                            <label class="label">Rack Capacity</label>
                            <div class="control">
                                <input class="input" type="number" name="rack_capacity" placeholder="Enter rack capacity" required>
                            </div>
                        </div>
                        <button class="button is-success" type="submit" name="add_warehouse">Add Warehouse</button>
                    </form>
                </div>

                <div class="dashboard-container">
                    <?php if (!empty($warehouses)): ?>
                        <?php foreach ($warehouses as $warehouse): ?>
                            <div class="card dashboard-card">
                                <header class="card-header">
                                    <p class="card-header-title">
                                        <?= htmlspecialchars($warehouse['location']); ?>
                                    </p>
                                </header>
                                <div class="card-content">
                                    <div class="content">
                                        <p><strong>Capacity:</strong> <?= htmlspecialchars($warehouse['rack_capacity']); ?></p>
                                        <p><strong>Current Stock:</strong> <?= htmlspecialchars($warehouse['current_stock']); ?></p>
                                        <p><strong>Utilization:</strong> <?= round(($warehouse['current_stock'] / $warehouse['rack_capacity']) * 100, 2); ?>%</p>
                                    </div>
                                </div>
                                <footer class="card-footer">
                                    <a href="?warehouse_id=<?= $warehouse['id']; ?>" class="card-footer-item">View Details</a>
                                </footer>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No warehouses available.</p>
                    <?php endif; ?>
                </div>

                <?php if ($selectedWarehouse): ?>
                    <div class="box">
                        <h2 class="subtitle">Details for Warehouse #<?= htmlspecialchars($selectedWarehouse['id']); ?></h2>
                        <p><strong>Location:</strong> <?= htmlspecialchars($selectedWarehouse['location']); ?></p>
                        <p><strong>Capacity:</strong> <?= htmlspecialchars($selectedWarehouse['rack_capacity']); ?></p>
                        <p><strong>Current Stock:</strong> <?= htmlspecialchars($selectedWarehouse['current_stock']); ?></p>

                        <h3 class="subtitle">Stock Items</h3>
                        <?php if (!empty($foodItems)): ?>
                            <table class="table is-fullwidth is-striped">
                                <thead>
                                    <tr>
                                        <th>Food Item</th>
                                        <th>Quantity</th>
                                        <th>Unit</th>
                                        <th>Price per Unit</th>
                                        <th>Total Value</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($foodItems as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['food_name']); ?></td>
                                            <td><?= htmlspecialchars($item['quantity']); ?></td>
                                            <td><?= htmlspecialchars($item['unit']); ?></td>
                                            <td><?= htmlspecialchars($item['price_per_unit']); ?> €</td>
                                            <td><?= htmlspecialchars($item['quantity'] * $item['price_per_unit']); ?> €</td>
                                            <td>
                                                <form method="POST">
                                                    <input type="hidden" name="warehouse_id" value="<?= htmlspecialchars($selectedWarehouse['id']); ?>">
                                                    <input type="hidden" name="food_item_id" value="<?= htmlspecialchars($item['id']); ?>">
                                                    <input type="number" name="quantity" value="<?= htmlspecialchars($item['quantity']); ?>" step="0.01">
                                                    <button class="button is-small is-info" type="submit" name="modify_item">Modify</button>
                                                    <button class="button is-small is-danger" type="submit" name="delete_item">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No stock items available for this warehouse.</p>
                        <?php endif; ?>

                        <h3 class="subtitle">Add New Food Item</h3>
                        <form method="POST">
                            <input type="hidden" name="warehouse_id" value="<?= htmlspecialchars($selectedWarehouse['id']); ?>">
                            <div class="field">
                                <label class="label">Food Type</label>
                                <div class="control">
                                    <div class="select">
                                        <select name="food_type_id" required>
                                            <?php foreach ($foodTypes as $type): ?>
                                                <option value="<?= htmlspecialchars($type['id']); ?>">
                                                    <?= htmlspecialchars($type['name']); ?> (<?= htmlspecialchars($type['unit']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="field">
                                <label class="label">Quantity</label>
                                <div class="control">
                                    <input class="input" type="number" name="quantity" step="0.01" required>
                                </div>
                            </div>
                            <button class="button is-success" type="submit" name="add_item">Add Item</button>
                        </form>
                    </div>

                    <div class="box">
                        <h3 class="subtitle">Warehouse Stock Utilization</h3>
                        <div class="chart-container">
                            <canvas id="stockUtilizationChart"></canvas>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'); ?>
    </div>

    <script>
        <?php if ($selectedWarehouse): ?>
            const stockUtilizationChartCtx = document.getElementById('stockUtilizationChart').getContext('2d');
            const stockUtilizationChart = new Chart(stockUtilizationChartCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Current Stock', 'Available Capacity'],
                    datasets: [{
                        data: [<?= $selectedWarehouse['current_stock']; ?>, <?= $selectedWarehouse['rack_capacity'] - $selectedWarehouse['current_stock']; ?>],
                        backgroundColor: ['#00d1b2', '#ffdd57']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>
