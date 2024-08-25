<?php
session_start();

if (!in_array('ROLE_ADMIN', $_SESSION['role'])) {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_plate'])) {
    $stmt = $pdo->prepare("INSERT INTO vehicles (id_plate, fret_capacity, human_capacity, model) VALUES (:id_plate, :fret_capacity, :human_capacity, :model)");
    try {
        $stmt->execute([
            ':id_plate' => $_POST['id_plate'],
            ':fret_capacity' => $_POST['fret_capacity'],
            ':human_capacity' => $_POST['human_capacity'],
            ':model' => $_POST['model']
        ]);
        header('Location: vehicles.php');
        exit;
    } catch (PDOException $e) {
        $error = "Erreur lors de l'ajout du véhicule: " . $e->getMessage();
    }
}

if (isset($_POST['delete_vehicle_id'])) {
    $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = :id");
    try {
        $stmt->execute([':id' => $_POST['delete_vehicle_id']]);
        header('Location: vehicles.php');
        exit;
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression du véhicule: " . $e->getMessage();
    }
}

$allVehiclesStmt = $pdo->query("SELECT * FROM vehicles");
$allVehicles = $allVehiclesStmt->fetchAll(PDO::FETCH_ASSOC);

if (!$allVehicles) {
    $allVehicles = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "Vehicles - HELIX";
    include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/head.php');
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.0/css/bulma.min.css">
</head>

<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/header.php') ?>
        <main class="section">
            <div class="container">
                <h1 class="title has-text-centered">Vehicle Management</h1>

                <div class="box">
                    <h2 class="subtitle">Vehicles List</h2>
                    <div class="table-container">
                        <table class="table is-striped is-fullwidth">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ID Plate</th>
                                    <th>Fret Capacity</th>
                                    <th>Human Capacity</th>
                                    <th>Model</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($allVehicles)): ?>
                                    <tr>
                                        <td colspan="6" class="has-text-centered">No vehicles found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($allVehicles as $vehicle): ?>
                                        <tr>
                                            <td><?= escape($vehicle['id']) ?></td>
                                            <td><?= escape($vehicle['id_plate']) ?></td>
                                            <td><?= escape($vehicle['fret_capacity']) ?></td>
                                            <td><?= escape($vehicle['human_capacity']) ?></td>
                                            <td><?= escape($vehicle['model']) ?></td>
                                            <td>
                                                <form action="edit_vehicle.php" method="GET" style="display:inline;">
                                                    <input type="hidden" name="id" value="<?= escape($vehicle['id']) ?>">
                                                    <button class="button is-info is-small" type="submit">Edit</button>
                                                </form>
                                                <form action="" method="POST" style="display:inline;">
                                                    <input type="hidden" name="delete_vehicle_id" value="<?= escape($vehicle['id']) ?>">
                                                    <button class="button is-danger is-small" type="submit" onclick="return confirm('Are you sure you want to delete this vehicle?');">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="box">
                    <h2 class="subtitle">Add New Vehicle</h2>
                    <form action="" method="POST">
                        <div class="field">
                            <label class="label" for="id_plate">ID Plate</label>
                            <div class="control">
                                <input class="input" type="text" name="id_plate" id="id_plate" placeholder="ID Plate" required>
                            </div>
                        </div>
                        <div class="field">
                            <label class="label" for="fret_capacity">Fret Capacity</label>
                            <div class="control">
                                <input class="input" type="number" name="fret_capacity" id="fret_capacity" placeholder="Fret Capacity" required>
                            </div>
                        </div>
                        <div class="field">
                            <label class="label" for="human_capacity">Human Capacity</label>
                            <div class="control">
                                <input class="input" type="number" name="human_capacity" id="human_capacity" placeholder="Human Capacity" required>
                            </div>
                        </div>
                        <div class="field">
                            <label class="label" for="model">Model</label>
                            <div class="control">
                                <input class="input" type="text" name="model" id="model" placeholder="Model" required>
                            </div>
                        </div>
                        <div class="control">
                            <button class="button is-success" type="submit">Add Vehicle</button>
                        </div>
                    </form>
                    <?php if (isset($error)): ?>
                        <p class="has-text-danger"><?= escape($error) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        <footer class="footer">
            <div class="content has-text-centered">
                &copy; <?= date('Y'); ?> HELIX. All Rights Reserved.
            </div>
        </footer>
    </div>
</body>

</html>
