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

$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Action non reconnue.'];

if ($action === 'add') {
    $type = $_POST['point-type'];
    $name = $_POST['point-name'];
    $address = $_POST['point-address'];

    if (empty($name) || empty($address)) {
        $response['message'] = 'Le nom et l\'adresse sont obligatoires.';
    } else {
        if ($type === 'collection') {
            $query = "INSERT INTO collection_points (name, address) VALUES (:name, :address)";
        } else {
            $query = "INSERT INTO donation_points (name, address) VALUES (:name, :address)";
        }

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':address', $address);

        if ($stmt->execute()) {
            $response = ['success' => true];
        } else {
            $errorInfo = $stmt->errorInfo();
            $response['message'] = 'Échec de l\'ajout du point : ' . $errorInfo[2];
        }
    }

} elseif ($action === 'delete') {
    $id = $_GET['id'];
    $type = $_GET['type'];

    if (empty($id)) {
        $response['message'] = 'L\'ID est obligatoire pour la suppression.';
    } else {
        if ($type === 'collection') {
            $query = "DELETE FROM collection_points WHERE id = :id";
        } else {
            $query = "DELETE FROM donation_points WHERE id = :id";
        }

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = ['success' => true];
        } else {
            $errorInfo = $stmt->errorInfo();
            $response['message'] = 'Échec de la suppression du point : ' . $errorInfo[2];
        }
    }

} elseif ($action === 'edit') {
    $id = $_POST['id'];
    $type = $_POST['type'];
    $name = $_POST['name'];
    $address = $_POST['address'];

    if (empty($id) || empty($name) || empty($address)) {
        $response['message'] = 'L\'ID, le nom, et l\'adresse sont obligatoires pour la modification.';
    } else {
        if ($type === 'collection') {
            $query = "UPDATE collection_points SET name = :name, address = :address WHERE id = :id";
        } else {
            $query = "UPDATE donation_points SET name = :name, address = :address WHERE id = :id";
        }

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':address', $address);

        if ($stmt->execute()) {
            $response = ['success' => true];
        } else {
            $errorInfo = $stmt->errorInfo();
            $response['message'] = 'Échec de la modification du point : ' . $errorInfo[2];
        }
    }
}

echo json_encode($response);
