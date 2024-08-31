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
    die("Échec de la connexion à la base de données : " . $e->getMessage());
}

$title = "Gérer les Points de Collecte et de Don - NMW";
include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/head.php');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les Points de Collecte et de Don</title>
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
                <h1>Gérer les Points de Collecte et de Don</h1>

                <h2>Points de Collecte</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Adresse</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT id, name, address FROM collection_points";
                        $stmt = $pdo->query($query);
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr data-id='" . $row['id'] . "' data-type='collection'>";
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
                                        <button class='edit-button'>Modifier</button>
                                        <button class='delete-button'>Supprimer</button>
                                    </div>
                                    <div class='edit-buttons'>
                                        <button class='save-button'>Enregistrer</button>
                                        <button class='cancel-button'>Annuler</button>
                                    </div>
                                  </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <h2>Points de Don</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Adresse</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT id, name, address FROM donation_points";
                        $stmt = $pdo->query($query);
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr data-id='" . $row['id'] . "' data-type='donation'>";
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
                                        <button class='edit-button'>Modifier</button>
                                        <button class='delete-button'>Supprimer</button>
                                    </div>
                                    <div class='edit-buttons'>
                                        <button class='save-button'>Enregistrer</button>
                                        <button class='cancel-button'>Annuler</button>
                                    </div>
                                  </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <h2>Ajouter un nouveau Point</h2>
                <form id="add-point-form">
                    <div class="form-group">
                        <label for="point-type">Type de point :</label>
                        <select id="point-type" name="point-type">
                            <option value="collection">Point de Collecte</option>
                            <option value="donation">Point de Don</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="point-name">Nom :</label>
                        <input type="text" id="point-name" name="point-name" required>
                    </div>
                    <div class="form-group">
                        <label for="point-address">Adresse :</label>
                        <input type="text" id="point-address" name="point-address" required>
                    </div>
                    <button type="submit">Ajouter le Point</button>
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
            });
        });

        document.querySelectorAll('.cancel-button').forEach(button => {
            button.addEventListener('click', function() {
                var row = this.closest('tr');
                row.classList.remove('editing');
            });
        });

        document.querySelectorAll('.save-button').forEach(button => {
            button.addEventListener('click', function() {
                var row = this.closest('tr');
                var id = row.getAttribute('data-id');
                var type = row.getAttribute('data-type');
                var newName = row.querySelector('input[type="text"]:nth-child(1)').value;
                var newAddress = row.querySelector('input[type="text"]:nth-child(2)').value;

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
                        row.querySelector('.display-field:nth-child(1)').textContent = newName;
                        row.querySelector('.display-field:nth-child(2)').textContent = newAddress;
                        row.classList.remove('editing');
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
