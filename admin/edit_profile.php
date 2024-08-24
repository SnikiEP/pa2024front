<?php 
session_start();

if(!in_array('ROLE_ADMIN', $_SESSION['role'])){
  header("Location: login.php");
  exit;
}

$baseUrl = "http://ddns.callidos-mtf.fr:8085";
$authHeader = "Authorization: Bearer " . $_SESSION['accessToken'];

function makeHttpRequest($url, $method, $data = null)
{
    global $authHeader;
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_ENCODING => "",
        CURLOPT_AUTOREFERER => true,
        CURLOPT_CONNECTTIMEOUT => 120,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            $authHeader
        ]
    ];

    if ($method === "POST") {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    } elseif ($method === "PUT") {
        $options[CURLOPT_CUSTOMREQUEST] = "PUT";
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    } elseif ($method === "DELETE") {
        $options[CURLOPT_CUSTOMREQUEST] = "DELETE";
    }

    $curl = curl_init($url);
    curl_setopt_array($curl, $options);
    $result = curl_exec($curl);

    if ($result === false) {
        throw new Exception(curl_error($curl), curl_errno($curl));
    }

    curl_close($curl);

    return json_decode($result, true);
}

function escape($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

if (!isset($_GET['id'])) {
    echo "ID de l'utilisateur manquant.";
    exit;
}

$profileId = $_GET['id'];

$profileDetails = makeHttpRequest($baseUrl . "/account/{$profileId}", "GET");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $updatedProfile = [
        "username" => $_POST['username'],
        "email" => $_POST['email'],
        "phone" => $_POST['phone'],
        "name" => $_POST['name'],
        "lastName" => $_POST['lastName'],
        "location" => $_POST['location']
    ];

    $existingUsernameProfile = makeHttpRequest($baseUrl . "/account/username/{$_POST['username']}", "GET");
    $existingemailProfile = makeHttpRequest($baseUrl . "/account/email/{$_POST['email']}", "GET");

    if ($existingUsernameProfile ) {
        echo "Le nom d'utilisateur existe déjà. Veuillez choisir un autre nom.";
    } elseif ($existingemailProfile) {
        echo "L'email existe déjà. Veuillez choisir un autre email.";
    } else {
        $jsonData = json_encode($updatedProfile);

        $curl = curl_init($baseUrl . "/account/{$profileId}");
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData),
            $authHeader
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($curl);

        if ($result !== false) {
            $responseData = json_decode($result, true);
            if ($responseData && isset($responseData['success']) && $responseData['success']) {
                header("Location: account.php");
                exit;
            } else {
                echo "Une erreur s'est produite lors de la mise à jour du profil.";
            }
        }

        curl_close($curl);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        $title = "Modifier le profil - HELIX";
        include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/head.php');
    ?>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>

<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/header.php') ?>
        <main>
            <div class="content">
                <h2>Modifier le profil de <?= escape($profileDetails['username'] ?? '') ?></h2>
                <form id="updateProfileForm" method="POST">
                    <div class="form-group">
                        <label for="username">Nom d'utilisateur:</label>
                        <input type="text" id="username" name="username" value="<?= escape($profileDetails['username'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?= escape($profileDetails['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Téléphone:</label>
                        <input type="tel" id="phone" name="phone" value="<?= escape($profileDetails['phone'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="name">Nom:</label>
                        <input type="text" id="name" name="name" value="<?= escape($profileDetails['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="lastName">Nom de famille:</label>
                        <input type="text" id="lastName" name="lastName" value="<?= escape($profileDetails['lastName'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="location">Location:</label>
                        <input type="text" id="location" name="location" value="<?= escape($profileDetails['location'] ?? '') ?>" required>
                    </div>
                    <button type="submit" class="btn-submit">Enregistrer les modifications</button>
                </form>
            </div>
        </main>
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php')?>
    </div>

</body>

</html>
