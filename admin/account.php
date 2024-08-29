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

function formatDate($timestamp) {
    if ($timestamp) {
        $date = new DateTime($timestamp);
        return $date->format('d/m/Y H:i');
    }
    return '';
}

try {
    $allAccounts = makeHttpRequest($baseUrl . "/account/all", "GET");
} catch (Exception $e) {
    $allAccounts = []; // En cas d'erreur, définir un tableau vide pour éviter que la page ne plante
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "Manage Accounts - HELIX";
    include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/head.php');
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.0/css/bulma.min.css">
</head>

<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/header.php') ?>
        <main class="section">
            <div class="container">
                <h1 class="title has-text-centered" data-translate="manage-accounts-title">Manage Accounts</h1>

                <div class="box">
                    <h2 class="subtitle" data-translate="search-accounts">Search Accounts</h2>
                    <form method="GET">
                        <div class="field">
                            <label class="label" for="userIdFilter" data-translate="user-id">User ID:</label>
                            <div class="control">
                                <input class="input" type="number" id="userIdFilter" name="user_id" value="<?= escape($_GET['user_id'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="usernameFilter" data-translate="username">Username:</label>
                            <div class="control">
                                <input class="input" type="text" id="usernameFilter" name="username" value="<?= escape($_GET['username'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="emailFilter" data-translate="email">Email:</label>
                            <div class="control">
                                <input class="input" type="text" id="emailFilter" name="email" value="<?= escape($_GET['email'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="control">
                            <button class="button is-primary" type="submit" data-translate="search-button">Search</button>
                        </div>
                    </form>
                </div>

                <div class="box">
                    <h2 class="subtitle" data-translate="accounts-list">Accounts List</h2>
                    <?php if (!empty($allAccounts)) : ?>
                        <table class="table is-striped is-fullwidth">
                            <thead>
                                <tr>
                                    <th data-translate="id">ID</th>
                                    <th data-translate="username">Username</th>
                                    <th data-translate="name">Name</th>
                                    <th data-translate="last-name">Last Name</th>
                                    <th data-translate="email">Email</th>
                                    <th data-translate="phone">Phone</th>
                                    <th data-translate="location">Location</th>
                                    <th data-translate="role">Role</th>
                                    <th data-translate="sex">Sex</th>
                                    <th data-translate="last-login">Last Login</th>
                                    <th data-translate="registered-date">Registered Date</th>
                                    <th data-translate="edit">Edit</th>
                                    <th data-translate="delete">Delete</th>
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
                                    <td><?= formatDate($account['last_login']) ?></td>
                                    <td><?= formatDate($account['register_date']) ?></td>
                                    <td>
                                        <button class="button is-info is-small" onclick="openEditModal(<?= $account['id'] ?>)" data-translate="edit">Edit</button>
                                    </td>
                                    <td>
                                        <button class="button is-danger is-small" onclick="confirmDeleteProfile(<?= $account['id'] ?>)" data-translate="delete">Delete</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p data-translate="no-accounts">No accounts found or error fetching accounts.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        <footer class="footer">
            <div class="content has-text-centered">
                <span data-translate="footer-copyright">&copy; <?= date('Y'); ?> HELIX. All Rights Reserved.</span>
            </div>
        </footer>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-background"></div>
        <div class="modal-card">
            <header class="modal-card-head">
                <p class="modal-card-title" data-translate="edit-account">Edit Account</p>
                <button class="delete" aria-label="close" onclick="closeEditModal()"></button>
            </header>
            <section class="modal-card-body">
                <form id="editProfileForm">
                    <input type="hidden" id="editProfileId" name="profileId">
                    <div class="field">
                        <label class="label" for="editUsername" data-translate="username">Username:</label>
                        <div class="control">
                            <input class="input" type="text" id="editUsername" name="username" required>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label" for="editEmail" data-translate="email">Email:</label>
                        <div class="control">
                            <input class="input" type="email" id="editEmail" name="email" required>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label" for="editPhone" data-translate="phone">Phone:</label>
                        <div class="control">
                            <input class="input" type="tel" id="editPhone" name="phone" required>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label" for="editName" data-translate="name">Name:</label>
                        <div class="control">
                            <input class="input" type="text" id="editName" name="name" required>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label" for="editLastName" data-translate="last-name">Last Name:</label>
                        <div class="control">
                            <input class="input" type="text" id="editLastName" name="lastName" required>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label" for="editLocation" data-translate="location">Location:</label>
                        <div class="control">
                            <input class="input" type="text" id="editLocation" name="location" required>
                        </div>
                    </div>
                    <div class="control">
                        <button type="submit" class="button is-success" data-translate="save-changes">Save Changes</button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <script>
        function openEditModal(profileId) {
            const profile = <?= json_encode($allAccounts) ?>.find(p => p.id == profileId);
            document.getElementById('editProfileId').value = profileId;
            document.getElementById('editUsername').value = profile.username;
            document.getElementById('editEmail').value = profile.email;
            document.getElementById('editPhone').value = profile.phone;
            document.getElementById('editName').value = profile.name;
            document.getElementById('editLastName').value = profile.lastName;
            document.getElementById('editLocation').value = profile.location;

            document.getElementById('editModal').classList.add('is-active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('is-active');
        }

        function confirmDeleteProfile(profileId) {
            if (confirm('Are you sure you want to delete this profile?')) {
                deleteProfile(profileId);
            }
        }

        function deleteProfile(profileId) {
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

        document.getElementById('editProfileForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const profileId = document.getElementById('editProfileId').value;
            const updatedProfile = {
                username: document.getElementById('editUsername').value,
                email: document.getElementById('editEmail').value,
                phone: document.getElementById('editPhone').value,
                name: document.getElementById('editName').value,
                lastName: document.getElementById('editLastName').value,
                location: document.getElementById('editLocation').value,
            };

            fetch('<?= $baseUrl ?>/account/' + profileId, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer <?= $_SESSION["accessToken"]; ?>'
                },
                body: JSON.stringify(updatedProfile),
            })
            .then(response => {
                if (response.ok) {
                    alert('Profile updated successfully.');
                    window.location.reload();
                } else {
                    console.error('Error updating profile:', response.statusText);
                }
            })
            .catch(error => console.error('Error updating profile:', error));
        });
    </script>
</body>

</html>
