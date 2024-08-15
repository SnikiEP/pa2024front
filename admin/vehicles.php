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

<?php
include_once('includes/auth_admin.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "Vehicles - HELIX";
    include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/head.php');
    ?>
    <link rel="stylesheet" href="/assets/css/panel.css">
</head>

<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/header.php') ?>
        <main>
            <div class="content">
                <h1 class="title has-text-centered admin-title">Vehicle Management</h1>
                <section class="section">
                    <div class="container">
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
                </section>

                <section class="section">
                    <div class="container">
                        <div class="box">
                            <h2 class="title is-4">Add New Vehicle</h2>
                            <form action="" method="POST">
                                <input class="input" type="text" name="id_plate" placeholder="ID Plate" required>
                                <input class="input" type="number" name="fret_capacity" placeholder="Fret Capacity" required>
                                <input class="input" type="number" name="human_capacity" placeholder="Human Capacity" required>
                                <input class="input" type="text" name="model" placeholder="Model" required>
                                <button class="button is-success" type="submit">Add Vehicle</button>
                            </form>
                            <?php if (isset($error)): ?>
                                <p style="color: red;"><?= escape($error) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            </div>
        </main>
        <footer class="footer">
            &copy; <?= date('Y'); ?> HELIX. All Rights Reserved.
        </footer>
    </div>
</body>

</html>
