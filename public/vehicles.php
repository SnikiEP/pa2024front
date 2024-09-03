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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_plate'])) {
    if (isset($_POST['id'])) {
        // Update vehicle
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
    } else {
        // Add new vehicle
        $stmt = $pdo->prepare("INSERT INTO vehicles (id_plate, fret_capacity, human_capacity, model, current_warehouse_id) VALUES (:id_plate, :fret_capacity, :human_capacity, :model, :current_warehouse_id)");
        try {
            $stmt->execute([
                ':id_plate' => $_POST['id_plate'],
                ':fret_capacity' => $_POST['fret_capacity'],
                ':human_capacity' => $_POST['human_capacity'],
                ':model' => $_POST['model'],
                ':current_warehouse_id' => $_POST['current_warehouse_id']
            ]);
            header('Location: vehicles.php');
            exit;
        } catch (PDOException $e) {
            $error = "error_adding_vehicle: " . $e->getMessage();
        }
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

$allVehiclesStmt = $pdo->query("
    SELECT vehicles.*, warehouses.location 
    FROM vehicles 
    LEFT JOIN warehouses ON vehicles.current_warehouse_id = warehouses.id
");
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
    include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/head.php');
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.0/css/bulma.min.css">
</head>

<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php') ?>
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
                                    <th data-translate="warehouse">Warehouse</th>
                                    <th data-translate="actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($allVehicles)): ?>
                                    <tr>
                                        <td colspan="7" class="has-text-centered" data-translate="no_vehicles_found">No vehicles found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($allVehicles as $vehicle): ?>
                                        <tr>
                                            <td><?= escape($vehicle['id']) ?></td>
                                            <td><?= escape($vehicle['id_plate']) ?></td>
                                            <td><?= escape($vehicle['fret_capacity']) ?></td>
                                            <td><?= escape($vehicle['human_capacity']) ?></td>
                                            <td><?= escape($vehicle['model']) ?></td>
                                            <td><?= escape($vehicle['location'] ?? 'Unknown') ?></td>
                                            <td>
                                                <button class="button is-info is-small edit-vehicle-button"
                                                        data-id="<?= escape($vehicle['id']) ?>"
                                                        data-id_plate="<?= escape($vehicle['id_plate']) ?>"
                                                        data-fret_capacity="<?= escape($vehicle['fret_capacity']) ?>"
                                                        data-human_capacity="<?= escape($vehicle['human_capacity']) ?>"
                                                        data-model="<?= escape($vehicle['model']) ?>"
                                                        data-current_warehouse_id="<?= escape($vehicle['current_warehouse_id']) ?>"
                                                        data-translate="edit">Edit</button>
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
                        <div class="field">
                            <label class="label" for="current_warehouse_id" data-translate="current_warehouse">Warehouse</label>
                            <div class="control">
                                <div class="select">
                                    <select name="current_warehouse_id" id="current_warehouse_id" required>
                                        <option value="" disabled selected>Select Warehouse</option>
                                        <?php
                                        $warehousesStmt = $pdo->query("SELECT id, location FROM warehouses");
                                        $warehouses = $warehousesStmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($warehouses as $warehouse) {
                                            echo '<option value="' . escape($warehouse['id']) . '">' . escape($warehouse['location']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
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

        <!-- Edit Vehicle Modal -->
        <div id="editVehicleModal" class="modal">
            <div class="modal-background"></div>
            <div class="modal-card">
                <header class="modal-card-head">
                    <p class="modal-card-title" data-translate="edit_vehicle">Edit Vehicle</p>
                    <button class="delete" aria-label="close"></button>
                </header>
                <section class="modal-card-body">
                    <form id="editVehicleForm" method="POST">
                        <input type="hidden" name="id" id="edit_vehicle_id">
                        <div class="field">
                            <label class="label" for="edit_id_plate" data-translate="id_plate">ID Plate</label>
                            <div class="control">
                                <input class="input" type="text" name="id_plate" id="edit_id_plate" required>
                            </div>
                        </div>
                        <div class="field">
                            <label class="label" for="edit_fret_capacity" data-translate="fret_capacity">Fret Capacity</label>
                            <div class="control">
                                <input class="input" type="number" name="fret_capacity" id="edit_fret_capacity" required>
                            </div>
                        </div>
                        <div class="field">
                            <label class="label" for="edit_human_capacity" data-translate="human_capacity">Human Capacity</label>
                            <div class="control">
                                <input class="input" type="number" name="human_capacity" id="edit_human_capacity" required>
                            </div>
                        </div>
                        <div class="field">
                            <label class="label" for="edit_model" data-translate="model">Model</label>
                            <div class="control">
                                <input class="input" type="text" name="model" id="edit_model" required>
                            </div>
                        </div>
                        <div class="field">
                            <label class="label" for="edit_current_warehouse_id" data-translate="current_warehouse">Warehouse</label>
                            <div class="control">
                                <div class="select">
                                    <select name="current_warehouse_id" id="edit_current_warehouse_id" required>
                                        <?php
                                        foreach ($warehouses as $warehouse) {
                                            echo '<option value="' . escape($warehouse['id']) . '">' . escape($warehouse['location']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </section>
                <footer class="modal-card-foot">
                    <button class="button is-success" onclick="submitEditForm()" data-translate="save_changes">Save changes</button>
                    <button class="button" onclick="closeModal()">Cancel</button>
                </footer>
            </div>
        </div>

        <footer class="footer">
            <p data-translate="footer_text">&copy; 2024-<?= date("Y"), ($translations['footer_text']) ?></p>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('editVehicleModal');
            const closeButton = modal.querySelector('.delete');
            const background = modal.querySelector('.modal-background');

            closeButton.addEventListener('click', closeModal);
            background.addEventListener('click', closeModal);

            const editButtons = document.querySelectorAll('.edit-vehicle-button');
            editButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const vehicleId = button.getAttribute('data-id');
                    const vehicleData = {
                        id: vehicleId,
                        id_plate: button.getAttribute('data-id_plate'),
                        fret_capacity: button.getAttribute('data-fret_capacity'),
                        human_capacity: button.getAttribute('data-human_capacity'),
                        model: button.getAttribute('data-model'),
                        current_warehouse_id: button.getAttribute('data-current_warehouse_id')
                    };

                    populateModal(vehicleData);
                    modal.classList.add('is-active');
                });
            });
        });

        function closeModal() {
            document.getElementById('editVehicleModal').classList.remove('is-active');
        }

        function populateModal(data) {
            document.getElementById('edit_vehicle_id').value = data.id;
            document.getElementById('edit_id_plate').value = data.id_plate;
            document.getElementById('edit_fret_capacity').value = data.fret_capacity;
            document.getElementById('edit_human_capacity').value = data.human_capacity;
            document.getElementById('edit_model').value = data.model;
            document.getElementById('edit_current_warehouse_id').value = data.current_warehouse_id;
        }

        function submitEditForm() {
            document.getElementById('editVehicleForm').submit();
        }
    </script>
</body>

</html>
