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
    <link rel="stylesheet" href="/assets/css/admin.css">
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
                                            <button class="button is-info is-small" onclick="openEditModal(<?= $account['id'] ?>)">Edit</button>
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
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php')?>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Account</h2>
            <form id="editProfileForm">
                <input type="hidden" id="editProfileId" name="profileId">
                <div class="form-group">
                    <label for="editUsername">Username:</label>
                    <input type="text" id="editUsername" name="username" required>
                </div>
                <div class="form-group">
                    <label for="editEmail">Email:</label>
                    <input type="email" id="editEmail" name="email" required>
                </div>
                <div class="form-group">
                    <label for="editPhone">Phone:</label>
                    <input type="tel" id="editPhone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="editName">Name:</label>
                    <input type="text" id="editName" name="name" required>
                </div>
                <div class="form-group">
                    <label for="editLastName">Last Name:</label>
                    <input type="text" id="editLastName" name="lastName" required>
                </div>
                <div class="form-group">
                    <label for="editLocation">Location:</label>
                    <input type="text" id="editLocation" name="location" required>
                </div>
                <button type="submit" class="btn-submit">Save Changes</button>
            </form>
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

            document.getElementById('editModal').style.display = "block";
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = "none";
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
