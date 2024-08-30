<?php 
session_start();

if(!isset($_SESSION['role']) || !in_array('ROLE_ADMIN', $_SESSION['role'])){
    header("Location: login.php");
    exit;
}

$baseUrl = "http://ddns.callidos-mtf.fr:8085";
$authHeader = "Authorization: Bearer " . $_SESSION['accessToken'];

function makeHttpRequest($url, $method, $data = null) {
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

function escape($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$notValidatedAccounts = makeHttpRequest($baseUrl . "/account/not-validated", "GET");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        $title = "Home - HELIX";
        include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/head.php');
    ?>    
</head>
<style>
    .table-container table {
        margin-top: 20px;
        border: 1px solid #ccc;
        width: 100%;
        border-collapse: collapse;
    }
    .table-container th, .table-container td {
        padding: 8px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    .table-container th {
        background-color: #f2f2f2;
    }
</style>
<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/header.php') ?>
        <main>
            <div class="content">
                <img src="<?= '/assets/img/helix_white.png' ?>" alt="Helix_logo" width="600px" style="display: block; margin-left: auto; margin-right: auto; margin-top: 30px;">
                <div style="text-align: center;">
                    <h3 class="title is-3" style="margin-top: 10px;" data-translate="admin_panel">Admin Panel</h3>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th data-translate="id_header">ID</th>
                                <th data-translate="username_header">Username</th>
                                <th data-translate="action_header">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notValidatedAccounts as $account): ?>
                                <tr>
                                    <td><?= escape($account['id']) ?></td>
                                    <td><?= escape($account['username']) ?></td>
                                    <td>
                                        <button onclick="validateAccount(<?= $account['id'] ?>)">Validate</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>
            </div>
        </main>
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php')?>
    </div>
    <script>
        function validateAccount(accountId) {
            const url = '<?= $baseUrl ?>/account/validate/' + accountId;

            fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer <?= $_SESSION["accessToken"] ?>'
                },
            })
            .then(response => {
                if (response.ok) {
                    alert(translations.account_validated_successfully);
                    window.location.reload();
                } else {
                    console.error(translations.error_validating_account, response.statusText);
                }
            })
            .catch(error => console.error(translations.error_validating_account, error));
        }
    </script>
</body>
</html>

