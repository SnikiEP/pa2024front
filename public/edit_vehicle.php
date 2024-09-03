<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || 
    !is_array($_SESSION['role']) || 
    !(in_array('ROLE_ADMIN', $_SESSION['role']) || in_array('ROLE_BENEV', $_SESSION['role']))) {
    
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

function escape($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $stmt = $pdo->prepare("UPDATE vehicles SET id_plate = :id_plate, fret_capacity = :fret_capacity, human_capacity = :human_capacity, model = :model, current_warehouse_id = :current_warehouse_id WHERE id = :id");
    try {
        $stmt->execute([
            ':id' => $_POST['id'],
            ':id_plate' => $_POST['id_plate'],
            ':fret_capacity' => $_POST['fret_capacity'],
            ':human_capacity' => $_POST['human_capacity'],
            ':model' => $_POST['model'],
            ':current_warehouse_id' => $_POST['current_warehouse_id']
        ]);
        header('Location: vehicles.php');
        exit;
    } catch (PDOException $e) {
        $error = "error_updating_vehicle: " . $e->getMessage();
    }
}

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = :id");
    $stmt->execute([':id' => $_GET['id']]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$vehicle) {
        die('Vehicle not found');
    }
} else {
    die('Invalid vehicle ID');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Vehicle</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.0/css/bulma.min.css">
</head>
<body>
    <div class="container">
        <h1 class="title">Edit Vehicle</h1>
        <form action="edit_vehicle.php" method="POST">
            <input type="hidden" name="id" value="<?= escape($vehicle['id']) ?>">
            <div class="field">
                <label class="label" for="id_plate">ID Plate</label>
                <div class="control">
                    <input class="input" type="text" name="id_plate" id="id_plate" value="<?= escape($vehicle['id_plate']) ?>" required>
                </div>
            </div>
            <div class="field">
                <label class="label" for="fret_capacity">Fret Capacity</label>
                <div class="control">
                    <input class="input" type="number" name="fret_capacity" id="fret_capacity" value="<?= escape($vehicle['fret_capacity']) ?>" required>
                </div>
            </div>
            <div class="field">
                <label class="label" for="human_capacity">Human Capacity</label>
                <div class="control">
                    <input class="input" type="number" name="human_capacity" id="human_capacity" value="<?= escape($vehicle['human_capacity']) ?>" required>
                </div>
            </div>
            <div class="field">
                <label class="label" for="model">Model</label>
                <div class="control">
                    <input class="input" type="text" name="model" id="model" value="<?= escape($vehicle['model']) ?>" required>
                </div>
            </div>
            <div class="field">
                <label class="label" for="current_warehouse_id">Warehouse</label>
                <div class="control">
                    <div class="select">
                        <select name="current_warehouse_id" id="current_warehouse_id" required>
                            <?php
                            $warehousesStmt = $pdo->query("SELECT id, location FROM warehouses");
                            $warehouses = $warehousesStmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($warehouses as $warehouse) {
                                $selected = ($warehouse['id'] == $vehicle['current_warehouse_id']) ? 'selected' : '';
                                echo '<option value="' . escape($warehouse['id']) . '" ' . $selected . '>' . escape($warehouse['location']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="control">
                <button class="button is-success" type="submit">Update Vehicle</button>
            </div>
        </form>

        <?php if (isset($error)): ?>
            <p class="has-text-danger"><?= escape($error) ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
