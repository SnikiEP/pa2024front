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
        $error = "error_adding_vehicle: " . $e->getMessage();
    }
}

if (isset($_POST['delete_vehicle_id'])) {
    $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = :id");
    try {
        $stmt->execute([':id' => $_POST['delete_vehicle_id']]);
        header('Location: vehicles.php');
        exit;
    } catch (PDOException $e) {
        $error = "error_deleting_vehicle: " . $e->getMessage();
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
                <h1 class="title has-text-centered" data-translate="vehicle_management">Vehicle Management</h1>

                <div class="box">
                    <h2 class="subtitle" data-translate="vehicles_list">Vehicles List</h2>
                    <div class="table-container">
                        <table class="table is-striped is-fullwidth">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th data-translate="id_plate">ID Plate</th>
                                    <th data-translate="fret_capacity">Fret Capacity</th>
                                    <th data-translate="human_capacity">Human Capacity</th>
                                    <th data-translate="model">Model</th>
                                    <th data-translate="actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($allVehicles)): ?>
                                    <tr>
                                        <td colspan="6" class="has-text-centered" data-translate="no_vehicles_found">No vehicles found.</td>
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
                                                    <button class="button is-info is-small" type="submit" data-translate="edit">Edit</button>
                                                </form>
                                                <form action="" method="POST" style="display:inline;">
                                                    <input type="hidden" name="delete_vehicle_id" value="<?= escape($vehicle['id']) ?>">
                                                    <button class="button is-danger is-small" type="submit" data-translate="delete" onclick="return confirm(translations.are_you_sure_delete);">Delete</button>
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
                    <h2 class="subtitle" data-translate="add_new_vehicle">Add New Vehicle</h2>
                    <form action="" method="POST">
                        <div class="field">
                            <label class="label" for="id_plate" data-translate="id_plate">ID Plate</label>
                            <div class="control">
                                <input class="input" type="text" name="id_plate" id="id_plate" placeholder="" required data-translate-placeholder="id_plate">
                            </div>
                        </div>
                        <div class="field">
                            <label class="label" for="fret_capacity" data-translate="fret_capacity">Fret Capacity</label>
                            <div class="control">
                                <input class="input" type="number" name="fret_capacity" id="fret_capacity" placeholder="" required data-translate-placeholder="fret_capacity">
                            </div>
                        </div>
                        <div class="field">
                            <label class="label" for="human_capacity" data-translate="human_capacity">Human Capacity</label>
                            <div class="control">
                                <input class="input" type="number" name="human_capacity" id="human_capacity" placeholder="" required data-translate-placeholder="human_capacity">
                            </div>
                        </div>
                        <div class="field">
                            <label class="label" for="model" data-translate="model">Model</label>
                            <div class="control">
                                <input class="input" type="text" name="model" id="model" placeholder="" required data-translate-placeholder="model">
                            </div>
                        </div>
                        <div class="control">
                            <button class="button is-success" type="submit" data-translate="add_vehicle">Add Vehicle</button>
                        </div>
                    </form>

                    <?php if (isset($error)): ?>
                        <p class="has-text-danger"><?= escape($error) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        <footer class="footer">
            <p data-translate="footer_text">&copy; 2024-<?= date("Y"), ($translations['footer_text']) ?></p>
        </footer>
    </div>
</body>

</html>
