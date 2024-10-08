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
    echo json_encode(['success' => false, 'message' => "Échec de la connexion à la base de données : " . $e->getMessage()]);
    exit;
}

$queryCollectionPoints = "SELECT id, name, address FROM collection_points";
$queryDonationPoints = "SELECT id, name, address FROM donation_points";

$title = "Gérer les Points de Collecte et de Don - NMW";
include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/head.php');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        body {
            color: #fff;
            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #333;
            text-align: left;
        }

        th {
            background-color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #ddd;
        }

        .form-group input, .form-group button, .form-group select {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #555;
            background-color: #222;
            color: #fff;
        }

        .form-group button {
            background-color: #007bff;
            color: white;
            cursor: pointer;
        }

        .form-group button:hover {
            background-color: #0056b3;
        }

        .action-buttons, .edit-buttons {
            display: flex;
            gap: 10px;
        }

        .edit-button, .save-button, .cancel-button {
            background-color: #ffc107;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }

        .edit-button:hover, .save-button:hover, .cancel-button:hover {
            background-color: #e0a800;
        }

        .delete-button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }

        .delete-button:hover {
            background-color: #c82333;
        }

        .edit-buttons {
            display: none;
        }

        tr.editing .display-field {
            display: none;
        }

        tr.editing .edit-field {
            display: block;
        }

        tr.editing .edit-buttons {
            display: flex;
        }

        tr.editing .action-buttons {
            display: none;
        }

        .display-field {
            color: #fff;
            font-weight: bold;
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'); ?>
        <main>
            <div class="container">
                <h1 data-translate="manage_collection_and_donation_points">Gérer les Points de Collecte et de Don</h1>

                <h2 data-translate="collection_points">Points de Collecte</h2>
                <table>
                    <thead>
                        <tr>
                            <th data-translate="name">Nom</th>
                            <th data-translate="address">Adresse</th>
                            <th data-translate="actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmtCollection = $pdo->query($queryCollectionPoints);
                        while ($row = $stmtCollection->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr data-id='" . htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') . "' data-type='collection'>";
                            echo "<td>
                                    <span class='display-field'>" . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . "</span>
                                    <input type='text' class='edit-field' value='" . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . "' style='display:none;'>
                                  </td>";
                            echo "<td>
                                    <span class='display-field'>" . htmlspecialchars($row['address'], ENT_QUOTES, 'UTF-8') . "</span>
                                    <input type='text' class='edit-field' value='" . htmlspecialchars($row['address'], ENT_QUOTES, 'UTF-8') . "' style='display:none;'>
                                  </td>";
                            echo "<td>
                                    <div class='action-buttons'>
                                        <button class='edit-button' data-translate='edit'>Modifier</button>
                                        <button class='delete-button' data-translate='delete'>Supprimer</button>
                                    </div>
                                    <div class='edit-buttons'>
                                        <button class='save-button' data-translate='save'>Enregistrer</button>
                                        <button class='cancel-button' data-translate='cancel'>Annuler</button>
                                    </div>
                                  </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <h2 data-translate="donation_points">Points de Don</h2>
                <table>
                    <thead>
                        <tr>
                            <th data-translate="name">Nom</th>
                            <th data-translate="address">Adresse</th>
                            <th data-translate="actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmtDonation = $pdo->query($queryDonationPoints);
                        while ($row = $stmtDonation->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr data-id='" . htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') . "' data-type='donation'>";
                            echo "<td>
                                    <span class='display-field'>" . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . "</span>
                                    <input type='text' class='edit-field' value='" . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . "' style='display:none;'>
                                  </td>";
                            echo "<td>
                                    <span class='display-field'>" . htmlspecialchars($row['address'], ENT_QUOTES, 'UTF-8') . "</span>
                                    <input type='text' class='edit-field' value='" . htmlspecialchars($row['address'], ENT_QUOTES, 'UTF-8') . "' style='display:none;'>
                                  </td>";
                            echo "<td>
                                    <div class='action-buttons'>
                                        <button class='edit-button' data-translate='edit'>Modifier</button>
                                        <button class='delete-button' data-translate='delete'>Supprimer</button>
                                    </div>
                                    <div class='edit-buttons'>
                                        <button class='save-button' data-translate='save'>Enregistrer</button>
                                        <button class='cancel-button' data-translate='cancel'>Annuler</button>
                                    </div>
                                  </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <h2 data-translate="add_new_point">Ajouter un nouveau Point</h2>
                <form id="add-point-form">
                    <div class="form-group">
                        <label for="point-type" data-translate="point_type">Type de point :</label>
                        <select id="point-type" name="point-type">
                            <option value="collection" data-translate="collection_point">Point de Collecte</option>
                            <option value="donation" data-translate="donation_point">Point de Don</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="point-name" data-translate="point_name">Nom :</label>
                        <input type="text" id="point-name" name="point-name" required>
                    </div>
                    <div class="form-group">
                        <label for="point-address" data-translate="point_address">Adresse :</label>
                        <input type="text" id="point-address" name="point-address" required>
                    </div>
                    <button type="submit" data-translate="add_point">Ajouter le Point</button>
                </form>
            </div>
        </main>
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'); ?>
    </div>

    <script>
        document.querySelectorAll('.edit-button').forEach(button => {
            button.addEventListener('click', function() {
                var row = this.closest('tr');
                row.classList.add('editing');
                row.querySelectorAll('.edit-field').forEach(field => field.style.display = 'block');
                row.querySelectorAll('.display-field').forEach(field => field.style.display = 'none');
                row.querySelector('.edit-buttons').style.display = 'flex';
                row.querySelector('.action-buttons').style.display = 'none';
            });
        });

        document.querySelectorAll('.cancel-button').forEach(button => {
            button.addEventListener('click', function() {
                var row = this.closest('tr');
                row.classList.remove('editing');
                row.querySelectorAll('.edit-field').forEach(field => field.style.display = 'none');
                row.querySelectorAll('.display-field').forEach(field => field.style.display = 'block');
                row.querySelector('.edit-buttons').style.display = 'none';
                row.querySelector('.action-buttons').style.display = 'flex';
            });
        });

        document.querySelectorAll('.save-button').forEach(button => {
            button.addEventListener('click', function() {
                var row = this.closest('tr');
                var id = row.getAttribute('data-id');
                var type = row.getAttribute('data-type');
                var newName = row.querySelector('td:nth-child(1) input.edit-field').value;
                var newAddress = row.querySelector('td:nth-child(2) input.edit-field').value;

                var formData = new FormData();
                formData.append('id', id);
                formData.append('type', type);
                formData.append('name', newName);
                formData.append('address', newAddress);

                fetch('manage_points_action.php?action=edit', {
                    method: 'POST',
                    body: formData
                }).then(response => response.json()).then(data => {
                    if (data.success) {
                        row.querySelector('td:nth-child(1) .display-field').textContent = newName;
                        row.querySelector('td:nth-child(2) .display-field').textContent = newAddress;
                        row.classList.remove('editing');
                        row.querySelectorAll('.edit-field').forEach(field => field.style.display = 'none');
                        row.querySelectorAll('.display-field').forEach(field => field.style.display = 'block');
                        row.querySelector('.edit-buttons').style.display = 'none';
                        row.querySelector('.action-buttons').style.display = 'flex';
                    } else {
                        alert('Erreur : ' + data.message);
                    }
                }).catch(error => {
                    console.error('Erreur :', error);
                });
            });
        });

        document.getElementById('add-point-form').addEventListener('submit', function(event) {
            event.preventDefault();
            var formData = new FormData(this);
            fetch('manage_points_action.php?action=add', {
                method: 'POST',
                body: formData
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur : ' + data.message);
                }
            }).catch(error => {
                console.error('Erreur :', error);
            });
        });

        document.querySelectorAll('.delete-button').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Êtes-vous sûr de vouloir supprimer ce point ?')) {
                    var pointId = this.closest('tr').getAttribute('data-id');
                    var pointType = this.closest('tr').getAttribute('data-type');
                    fetch('manage_points_action.php?action=delete&type=' + pointType + '&id=' + pointId, {
                        method: 'GET',
                    }).then(response => response.json()).then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Erreur : ' + data.message);
                        }
                    }).catch(error => {
                        console.error('Erreur :', error);
                    });
                }
            });
        });
    </script>
</body>
</html>
