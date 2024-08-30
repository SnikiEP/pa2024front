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

function loadTranslations($lang = 'en') {
    $file = __DIR__ . "/locales/{$lang}.json";
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    return [];
}

$translations = loadTranslations('en');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id_plate'])) {
        if (isset($_POST['edit_vehicle_id'])) {
            $stmt = $pdo->prepare("UPDATE vehicles SET id_plate = :id_plate, fret_capacity = :fret_capacity, human_capacity = :human_capacity, model = :model WHERE id = :id");
            try {
                $stmt->execute([
                    ':id' => $_POST['edit_vehicle_id'],
                    ':id_plate' => $_POST['id_plate'],
                    ':fret_capacity' => $_POST['fret_capacity'],
                    ':human_capacity' => $_POST['human_capacity'],
                    ':model' => $_POST['model']
                ]);
                $successMessage = $translations['success_update'];
            } catch (PDOException $e) {
                $errorMessage = $translations['error_update'] . $e->getMessage();
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO vehicles (id_plate, fret_capacity, human_capacity, model) VALUES (:id_plate, :fret_capacity, :human_capacity, :model)");
            try {
                $stmt->execute([
                    ':id_plate' => $_POST['id_plate'],
                    ':fret_capacity' => $_POST['fret_capacity'],
                    ':human_capacity' => $_POST['human_capacity'],
                    ':model' => $_POST['model']
                ]);
                $successMessage = $translations['success_add'];
            } catch (PDOException $e) {
                $errorMessage = $translations['error_add'] . $e->getMessage();
            }
        }
    } elseif (isset($_POST['delete_vehicle_id'])) {
        $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = :id");
        try {
            $stmt->execute([':id' => $_POST['delete_vehicle_id']]);
            $successMessage = $translations['success_delete'];
        } catch (PDOException $e) {
            $errorMessage = $translations['error_delete'] . $e->getMessage();
        }
    }
}

$allVehiclesStmt = $pdo->query("SELECT * FROM vehicles");
$allVehicles = $allVehiclesStmt->fetchAll(PDO::FETCH_ASSOC);

$editVehicle = null;
if (isset($_GET['edit_vehicle_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = :id");
    $stmt->execute([':id' => $_GET['edit_vehicle_id']]);
    $editVehicle = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    $title = $translations['vehicles_management'];
    include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/head.php');
    ?>
    <link rel="stylesheet" href="/assets/css/panel.css">
</head>
<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/header.php') ?>
        <main>
            <div class="content">
                <h1 class="title has-text-centered admin-title" data-translate="manage_vehicles"><?= $translations['manage_vehicles'] ?></h1>

                <?php if (isset($successMessage)): ?>
                    <div class="notification is-success"><?= escape($successMessage) ?></div>
                <?php endif; ?>
                <?php if (isset($errorMessage)): ?>
                    <div class="notification is-danger"><?= escape($errorMessage) ?></div>
                <?php endif; ?>

                <section class="section">
                    <div class="container">
                        <div class="table-container">
                            <table class="table is-striped is-fullwidth">
                                <thead>
                                    <tr>
                                        <th data-translate="id"><?= $translations['id'] ?></th>
                                        <th data-translate="id_plate"><?= $translations['id_plate'] ?></th>
                                        <th data-translate="fret_capacity"><?= $translations['fret_capacity'] ?></th>
                                        <th data-translate="human_capacity"><?= $translations['human_capacity'] ?></th>
                                        <th data-translate="model"><?= $translations['model'] ?></th>
                                        <th data-translate="actions"><?= $translations['actions'] ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($allVehicles)): ?>
                                        <tr>
                                            <td colspan="6" class="has-text-centered" data-translate="no_vehicles_found"><?= $translations['no_vehicles_found'] ?></td>
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
                                                    <form action="" method="GET" style="display:inline;">
                                                        <input type="hidden" name="edit_vehicle_id" value="<?= escape($vehicle['id']) ?>">
                                                        <button class="button is-info is-small" type="submit" data-translate="edit"><?= $translations['edit'] ?></button>
                                                    </form>
                                                    <form action="" method="POST" style="display:inline;">
                                                        <input type="hidden" name="delete_vehicle_id" value="<?= escape($vehicle['id']) ?>">
                                                        <button class="button is-danger is-small" type="submit" onclick="return confirm('<?= $translations['confirm_delete'] ?>');" data-translate="delete"><?= $translations['delete'] ?></button>
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
                            <h2 class="title is-4" data-translate="<?= $editVehicle ? 'edit_vehicle' : 'add_vehicle' ?>"><?= $editVehicle ? $translations['edit_vehicle'] : $translations['add_vehicle'] ?></h2>
                            <form action="" method="POST">
                                <input type="hidden" name="edit_vehicle_id" value="<?= escape($editVehicle['id'] ?? '') ?>">
                                <input class="input" type="text" name="id_plate" placeholder="<?= $translations['id_plate'] ?>" value="<?= escape($editVehicle['id_plate'] ?? '') ?>" required>
                                <input class="input" type="number" name="fret_capacity" placeholder="<?= $translations['fret_capacity'] ?>" value="<?= escape($editVehicle['fret_capacity'] ?? '') ?>" required>
                                <input class="input" type="number" name="human_capacity" placeholder="<?= $translations['human_capacity'] ?>" value="<?= escape($editVehicle['human_capacity'] ?? '') ?>" required>
                                <input class="input" type="text" name="model" placeholder="<?= $translations['model'] ?>" value="<?= escape($editVehicle['model'] ?? '') ?>" required>
                                <button class="button is-success" type="submit" data-translate="<?= $editVehicle ? 'update_vehicle' : 'add_vehicle_btn' ?>"><?= $editVehicle ? $translations['update_vehicle'] : $translations['add_vehicle_btn'] ?></button>
                            </form>
                        </div>
                    </div>
                </section>
            </div>
        </main>
        <footer class="footer">
            <p data-translate="footer_text">&copy; 2024-<?= date("Y"), ($translations['footer_text']) ?></p>
        </footer>
    </div>
</body>
</html>
