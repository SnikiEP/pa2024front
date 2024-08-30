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

$categoriesStmt = $pdo->query("SELECT * FROM food_categories");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['warehouse_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM warehouses WHERE id = :id");
    $stmt->execute(['id' => $_GET['warehouse_id']]);
    $selectedWarehouse = $stmt->fetch(PDO::FETCH_ASSOC);

    $stockItemsStmt = $pdo->prepare("
        SELECT si.*, fi.name AS food_name, fi.unit, fi.price_per_unit, fi.barcode, fc.name AS category_name 
        FROM stock_items si 
        JOIN food_items fi ON si.food_item_id = fi.id
        JOIN food_categories fc ON fi.category_id = fc.id
        WHERE si.warehouse_id = :warehouse_id
    ");
    $stockItemsStmt->execute(['warehouse_id' => $_GET['warehouse_id']]);
    $stockItems = $stockItemsStmt->fetchAll(PDO::FETCH_ASSOC);

    $categorySummary = [];
    foreach ($stockItems as $item) {
        if (!isset($categorySummary[$item['category_name']])) {
            $categorySummary[$item['category_name']] = 0;
        }
        $categorySummary[$item['category_name']] += $item['quantity'];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $warehouseId = $_POST['warehouse_id'] ?? null;

    if (isset($_POST['add_item'])) {
        $categoryId = $_POST['category_id'];
        $foodName = $_POST['food_name'];
        $unit = $_POST['unit'];
        $weight = $_POST['weight'];
        $quantity = $_POST['quantity'];
        $pricePerUnit = $_POST['price_per_unit'];
        $barcode = $_POST['barcode'];

        $stmt = $pdo->prepare("
            SELECT si.id, si.quantity
            FROM stock_items si
            JOIN food_items fi ON si.food_item_id = fi.id
            WHERE fi.name = :food_name AND fi.category_id = :category_id AND si.warehouse_id = :warehouse_id
        ");
        $stmt->execute([
            ':food_name' => $foodName,
            ':category_id' => $categoryId,
            ':warehouse_id' => $warehouseId
        ]);
        $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingItem) {
            $stmt = $pdo->prepare("
                UPDATE stock_items 
                SET quantity = quantity + :quantity 
                WHERE id = :id
            ");
            $stmt->execute([
                ':quantity' => $quantity,
                ':id' => $existingItem['id']
            ]);
            $operationMessage = "La quantité de $foodName a été mise à jour.";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO food_items (category_id, name, unit, weight, price_per_unit, barcode)
                VALUES (:category_id, :name, :unit, :weight, :price_per_unit, :barcode)
            ");
            $stmt->execute([
                ':category_id' => $categoryId,
                ':name' => $foodName,
                ':unit' => $unit,
                ':weight' => $weight,
                ':price_per_unit' => $pricePerUnit,
                ':barcode' => $barcode
            ]);
            $foodItemId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("
                INSERT INTO stock_items (food_item_id, warehouse_id, quantity, unit_type)
                VALUES (:food_item_id, :warehouse_id, :quantity, :unit_type)
            ");
            $stmt->execute([
                ':food_item_id' => $foodItemId,
                ':warehouse_id' => $warehouseId,
                ':quantity' => $quantity,
                ':unit_type' => $unit
            ]);

            $operationMessage = "$foodName a été ajouté avec succès.";
        }
    } elseif (isset($_POST['modify_item'])) {
        $stockItemId = $_POST['stock_item_id'];
        $quantity = $_POST['quantity'];

        $stmt = $pdo->prepare("UPDATE stock_items SET quantity = :quantity WHERE id = :id");
        $stmt->execute([
            ':quantity' => $quantity,
            ':id' => $stockItemId
        ]);

        $operationMessage = "Aliment modifié avec succès.";
    } elseif (isset($_POST['delete_item'])) {
        $stockItemId = $_POST['stock_item_id'];

        $stmt = $pdo->prepare("DELETE FROM stock_items WHERE id = :id");
        $stmt->execute([':id' => $stockItemId]);

        $operationMessage = "Aliment supprimé avec succès.";
    } elseif (isset($_POST['modify_warehouse'])) {
        $location = $_POST['location'];
        $address = $_POST['address'];
        $warehouseId = $_POST['warehouse_id'];

        $stmt = $pdo->prepare("UPDATE warehouses SET location = :location, address = :address WHERE id = :id");
        $stmt->execute([
            ':location' => $location,
            ':address' => $address,
            ':id' => $warehouseId
        ]);

        $operationMessage = "Entrepôt modifié avec succès.";
        header("Location: stock.php?warehouse_id=" . $warehouseId);
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title data-translate="page_title">Warehouse Stock Management Dashboard - ATD</title>
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/head.php'); ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.0/css/bulma.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/assets/css/warehouse.css">
    <script src="/assets/js/translation.js"></script>
</head>
<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'); ?>
        <main class="section">
            <div class="container">
                <h1 class="title has-text-centered" data-translate="page_title_stock">Warehouse Stock Management Dashboard</h1>
                
                <?php if ($operationMessage): ?>
                    <div class="notification is-success">
                        <p data-translate="operation_message"><?= htmlspecialchars($operationMessage); ?></p>
                    </div>
                <?php endif; ?>

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
                                        <p><strong data-translate="current_stock_label">Current Stock:</strong> <?= htmlspecialchars($warehouse['current_stock']); ?></p>
                                        <p><strong data-translate="address_label">Address:</strong> <?= htmlspecialchars($warehouse['address']); ?></p>
                                    </div>
                                </div>
                                <footer class="card-footer">
                                    <a href="?warehouse_id=<?= $warehouse['id']; ?>" class="card-footer-item" data-translate="view_details_link">View Details</a>
                                    <a href="#edit-warehouse-<?= $warehouse['id']; ?>" class="card-footer-item" data-translate="modify_button">Modify</a>
                                </footer>
                            </div>

                            <div id="edit-warehouse-<?= $warehouse['id']; ?>" class="modal">
                                <div class="modal-background"></div>
                                <div class="modal-card">
                                    <header class="modal-card-head">
                                        <p class="modal-card-title">Modifier l'entrepôt</p>
                                        <button class="delete" aria-label="close"></button>
                                    </header>
                                    <form method="POST">
                                        <section class="modal-card-body">
                                            <input type="hidden" name="warehouse_id" value="<?= htmlspecialchars($warehouse['id']); ?>">
                                            <div class="field">
                                                <label class="label">Location</label>
                                                <div class="control">
                                                    <input class="input" type="text" name="location" value="<?= htmlspecialchars($warehouse['location']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="field">
                                                <label class="label">Address</label>
                                                <div class="control">
                                                    <input class="input" type="text" name="address" value="<?= htmlspecialchars($warehouse['address']); ?>">
                                                </div>
                                            </div>
                                        </section>
                                        <footer class="modal-card-foot">
                                            <button class="button is-success" type="submit" name="modify_warehouse">Save changes</button>
                                            <button class="button cancel-modal">Cancel</button>
                                        </footer>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p data-translate="no_warehouses_message">No warehouses available.</p>
                    <?php endif; ?>
                </div>

                <?php if ($selectedWarehouse): ?>
                    <div class="box">
                        <h2 class="subtitle" data-translate="details_title">Details for Warehouse #<?= htmlspecialchars($selectedWarehouse['id']); ?></h2>
                        <p><strong data-translate="location_label">Location:</strong> <?= htmlspecialchars($selectedWarehouse['location']); ?></p>
                        <p><strong data-translate="current_stock_label">Current Stock:</strong> <?= htmlspecialchars($selectedWarehouse['current_stock']); ?></p>
                        <p><strong data-translate="address_label">Address:</strong> <?= htmlspecialchars($selectedWarehouse['address']); ?></p>

                        <h3 class="subtitle" data-translate="stock_items_title">Stock Items</h3>
                        <?php if (!empty($stockItems)): ?>
                            <table class="table is-fullwidth is-striped">
                                <thead>
                                    <tr>
                                        <th data-translate="food_item_column">Food Item</th>
                                        <th data-translate="quantity_column">Quantity</th>
                                        <th data-translate="unit_column">Unit</th>
                                        <th data-translate="barcode_column">Barcode</th>
                                        <th data-translate="price_per_unit_column">Price per Unit</th>
                                        <th data-translate="total_value_column">Total Value</th>
                                        <th data-translate="actions_column">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stockItems as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['category_name']) . " - " . htmlspecialchars($item['food_name']); ?></td>
                                            <td><?= htmlspecialchars($item['quantity']); ?></td>
                                            <td><?= htmlspecialchars($item['unit']); ?></td>
                                            <td><?= htmlspecialchars($item['barcode']); ?></td>
                                            <td><?= htmlspecialchars($item['price_per_unit']); ?> €</td>
                                            <td><?= htmlspecialchars($item['quantity'] * $item['price_per_unit']); ?> €</td>
                                            <td>
                                                <form method="POST">
                                                    <input type="hidden" name="warehouse_id" value="<?= htmlspecialchars($selectedWarehouse['id']); ?>">
                                                    <input type="hidden" name="stock_item_id" value="<?= htmlspecialchars($item['id']); ?>">
                                                    <input type="number" name="quantity" value="<?= htmlspecialchars($item['quantity']); ?>" step="0.01">
                                                    <button class="button is-small is-info" type="submit" name="modify_item" data-translate="modify_button">Modify</button>
                                                    <button class="button is-small is-danger" type="submit" name="delete_item" data-translate="delete_button">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p data-translate="no_stock_items_message">No stock items available for this warehouse.</p>
                        <?php endif; ?>

                        <h3 class="subtitle" data-translate="add_item_title">Add New Food Item</h3>
                        <form method="POST">
                            <input type="hidden" name="warehouse_id" value="<?= htmlspecialchars($selectedWarehouse['id']); ?>">
                            <div class="field">
                                <label class="label" data-translate="food_type_label">Food Type</label>
                                <div class="control">
                                    <div class="select">
                                        <select name="category_id" required>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= htmlspecialchars($category['id']); ?>"><?= htmlspecialchars($category['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="field">
                                <label class="label" data-translate="food_name_label">Food Name</label>
                                <div class="control">
                                    <input class="input" type="text" name="food_name" placeholder="Enter food name" required>
                                </div>
                            </div>
                            <div class="field">
                                <label class="label" data-translate="unit_label">Unit</label>
                                <div class="control">
                                    <div class="select">
                                        <select name="unit" required>
                                            <option value="kg">Kilograms (kg)</option>
                                            <option value="litres">Litres (l)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="field">
                                <label class="label" data-translate="weight_label">Weight</label>
                                <div class="control">
                                    <input class="input" type="number" name="weight" step="0.01" required>
                                </div>
                            </div>
                            <div class="field">
                                <label class="label" data-translate="quantity_label">Quantity</label>
                                <div class="control">
                                    <input class="input" type="number" name="quantity" step="0.01" required>
                                </div>
                            </div>
                            <div class="field">
                                <label class="label" data-translate="price_per_unit_label">Price per Unit</label>
                                <div class="control">
                                    <input class="input" type="number" name="price_per_unit" step="0.01" required>
                                </div>
                            </div>
                            <div class="field">
                                <label class="label" data-translate="barcode_label">Barcode</label>
                                <div class="control">
                                    <input class="input" type="text" name="barcode" placeholder="Enter barcode" required>
                                </div>
                            </div>
                            <button class="button is-success" type="submit" name="add_item" data-translate="add_item_button">Add Item</button>
                        </form>
                    </div>

                    <div class="box">
                        <h3 class="subtitle" data-translate="utilization_title">Warehouse Stock Utilization by Category</h3>
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
        document.querySelectorAll('.modal').forEach(function(modal) {
            const closeModal = function() {
                modal.classList.remove('is-active');
            };

            modal.querySelector('.modal-background').addEventListener('click', closeModal);
            modal.querySelector('.delete').addEventListener('click', closeModal);
            modal.querySelector('.cancel-modal').addEventListener('click', closeModal);

            const openModalButton = document.querySelector(`[href="#${modal.id}"]`);
            if (openModalButton) {
                openModalButton.addEventListener('click', function(event) {
                    event.preventDefault();
                    modal.classList.add('is-active');
                });
            }
        });

        <?php if ($selectedWarehouse): ?>
            const stockUtilizationChartCtx = document.getElementById('stockUtilizationChart');
            if (stockUtilizationChartCtx) {
                const stockUtilizationChart = new Chart(stockUtilizationChartCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?= json_encode(array_keys($categorySummary)); ?>,
                        datasets: [{
                            data: <?= json_encode(array_values($categorySummary)); ?>,
                            backgroundColor: ['#00d1b2', '#ffdd57', '#ff3860', '#3273dc', '#23d160', '#ff851b']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(tooltipItem) {
                                        return tooltipItem.label + ': ' + tooltipItem.raw + ' units';
                                    }
                                }
                            }
                        }
                    }
                });
            } else {
                console.error('Canvas element not found for stock utilization chart.');
            }
        <?php endif; ?>
    </script>
</body>
</html>
