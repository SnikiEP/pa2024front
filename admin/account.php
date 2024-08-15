<?php 
session_start();
if (!in_array('ROLE_ADMIN', $_SESSION['role'])) {
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

$allAccounts = makeHttpRequest($baseUrl . "/account/all", "GET");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "Manage Accounts - HELIX";
    include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/head.php');
    ?>
    <link rel="stylesheet" href="/assets/css/panel.css">
    <style>
        .admin-title {
            margin-top: 20px;
        }

        .table-container {
            margin-top: 30px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 auto;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            white-space: nowrap; /* Prevent text wrapping */
        }

        th {
            background-color: #f2f2f2;
        }

        /* Limit the width of certain columns */
        th:nth-child(5), td:nth-child(5), /* Email */
        th:nth-child(6), td:nth-child(6), /* Phone */
        th:nth-child(7), td:nth-child(7), /* Location */
        th:nth-child(8), td:nth-child(8), /* Role */
        th:nth-child(9), td:nth-child(9)  /* Sex */
        {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .button {
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/header.php') ?>
        <main>
            <div class="content">
                <h1 class="title has-text-centered admin-title">Manage Accounts</h1>
                <section class="section">
                    <div class="container">
                        <div class="table-container">
                            <table class="table is-striped is-fullwidth">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Name</th>
                                        <th>Last Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Location</th>
                                        <th>Role</th>
                                        <th>Sex</th>
                                        <th>Last Login</th>
                                        <th>Registered Date</th>
                                        <th>Edit</th>
                                        <th>Delete</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allAccounts as $account): ?>
                                    <tr>
                                        <td><?= escape($account['id']) ?></td>
                                        <td><?= escape($account['username']) ?></td>
                                        <td><?= escape($account['name']) ?></td>
                                        <td><?= escape($account['lastName']) ?></td>
                                        <td><?= escape($account['email']) ?></td>
                                        <td><?= escape($account['phone']) ?></td>
                                        <td><?= escape($account['location']) ?></td>
                                        <td><?= escape($account['role']) ?></td>
                                        <td><?= escape($account['sex']) ?></td>
                                        <td><?= escape($account['last_login']) ?></td>
                                        <td><?= escape($account['register_date']) ?></td>
                                        <td>
                                            <button class="button is-info is-small" onclick="redirectToEditProfile(<?= $account['id'] ?>)">Edit</button>
                                        </td>
                                        <td>
                                            <button class="button is-danger is-small" onclick="confirmDeleteProfile(<?= $account['id'] ?>)">Delete</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </main>
        <footer class="footer">
            &copy; <?= date('Y'); ?> HELIX. All Rights Reserved.
        </footer>
    </div>

    <script>
        function redirectToEditProfile(profileId) {
            window.location.href = 'edit_profile.php?id=' + profileId;
        }

        function confirmDeleteProfile(profileId) {
            if (confirm('Are you sure you want to delete this profile?')) {
                deleteProfile(profileId);
            }
        }

        function deleteProfile(profileId) {
            const url = `http://ddns.callidos-mtf.fr:8085`;

            fetch('<?= $baseUrl ?>/account/' + profileId, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer <?= $_SESSION["accessToken"]; ?>'
                },
            })
            .then(response => {
                if (response.ok) {
                    alert('Profile deleted successfully.');
                    window.location.reload();
                } else {
                    console.error('Error deleting profile:', response.statusText);
                }
            })
            .catch(error => console.error('Error deleting profile:', error));
        }
    </script>
</body>

</html>
