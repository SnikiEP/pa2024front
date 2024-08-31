<?php 
include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/header.php');

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
    $url = $baseUrl . "/account/all";
    $allAccounts = makeHttpRequest($url, "GET");  
} catch (Exception $e) {
    $allAccounts = [];
    error_log("Error fetching accounts: " . $e->getMessage());
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
    <style>
        @media (max-width: 768px) {
            table.is-fullwidth {
                font-size: 0.9rem;
            }

            table.is-fullwidth th, table.is-fullwidth td {
                white-space: nowrap;
            }

            table.is-fullwidth td button {
                font-size: 0.8rem;
                padding: 0.25rem 0.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/header.php') ?>
        <main class="section">
            <div class="container">
                <h1 class="title has-text-centered">Manage Accounts</h1>

                <div class="box">
                    <h2 class="subtitle">Search Accounts</h2>
                    <form method="GET" onsubmit="return false;">
                        <div class="field">
                            <label class="label" for="userIdFilter">User ID:</label>
                            <div class="control">
                                <input class="input" type="number" id="userIdFilter">
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="usernameFilter">Username:</label>
                            <div class="control">
                                <input class="input" type="text" id="usernameFilter">
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="emailFilter">Email:</label>
                            <div class="control">
                                <input class="input" type="text" id="emailFilter">
                            </div>
                        </div>

                        <div class="control">
                            <button class="button is-primary" id="searchButton">Search</button>
                        </div>
                    </form>
                </div>

                <div class="box">
                    <h2 class="subtitle">Accounts List</h2>
                    <div class="table-container">
                        <table class="table is-striped is-fullwidth" id="accountsTable">
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
                                    <td><?= formatDate($account['last_login']) ?></td>
                                    <td><?= formatDate($account['register_date']) ?></td>
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
            </div>
        </main>
        <footer class="footer">
            <p>&copy; 2024-<?= date("Y") ?> Your Company</p>
        </footer>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-background"></div>
        <div class="modal-card">
            <header class="modal-card-head">
                <p class="modal-card-title">Edit Account</p>
                <button class="delete" aria-label="close" onclick="closeEditModal()"></button>
            </header>
            <section class="modal-card-body">
                <form id="editProfileForm">
                    <input type="hidden" id="editProfileId" name="profileId">
                    <div class="field">
                        <label class="label" for="editUsername">Username:</label>
                        <div class="control">
                            <input class="input" type="text" id="editUsername" name="username" required>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label" for="editEmail">Email:</label>
                        <div class="control">
                            <input class="input" type="email" id="editEmail" name="email" required>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label" for="editPhone">Phone:</label>
                        <div class="control">
                            <input class="input" type="tel" id="editPhone" name="phone" required>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label" for="editName">Name:</label>
                        <div class="control">
                            <input class="input" type="text" id="editName" name="name" required>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label" for="editLastName">Last Name:</label>
                        <div class="control">
                            <input class="input" type="text" id="editLastName" name="lastName" required>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label" for="editLocation">Location:</label>
                        <div class="control">
                            <input class="input" type="text" id="editLocation" name="location" required>
                        </div>
                    </div>
                    <div class="control">
                        <button type="submit" class="button is-success">Save Changes</button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            console.log("Document loaded, all event listeners are active.");

            document.getElementById('searchButton').addEventListener('click', function() {
                const userIdFilter = document.getElementById('userIdFilter').value.toLowerCase();
                const usernameFilter = document.getElementById('usernameFilter').value.toLowerCase();
                const emailFilter = document.getElementById('emailFilter').value.toLowerCase();

                const table = document.getElementById('accountsTable').getElementsByTagName('tbody')[0];
                const rows = table.getElementsByTagName('tr');

                for (let i = 0; i < rows.length; i++) {
                    const idCell = rows[i].getElementsByTagName('td')[0].innerText.toLowerCase();
                    const usernameCell = rows[i].getElementsByTagName('td')[1].innerText.toLowerCase();
                    const emailCell = rows[i].getElementsByTagName('td')[4].innerText.toLowerCase();

                    if ((userIdFilter === "" || idCell.includes(userIdFilter)) &&
                        (usernameFilter === "" || usernameCell.includes(usernameFilter)) &&
                        (emailFilter === "" || emailCell.includes(emailFilter))) {
                        rows[i].style.display = ""; 
                    } else {
                        rows[i].style.display = "none";
                    }
                }
            });

            window.openEditModal = function(profileId) {
                console.log(`Opening edit modal for profile ID: ${profileId}`);
                const profile = <?= json_encode($allAccounts) ?>.find(p => p.id == profileId);
                if (profile) {
                    document.getElementById('editProfileId').value = profileId;
                    document.getElementById('editUsername').value = profile.username;
                    document.getElementById('editEmail').value = profile.email;
                    document.getElementById('editPhone').value = profile.phone;
                    document.getElementById('editName').value = profile.name;
                    document.getElementById('editLastName').value = profile.lastName;
                    document.getElementById('editLocation').value = profile.location;

                    document.getElementById('editModal').classList.add('is-active');
                } else {
                    console.error('Profile not found.');
                }
            }

            window.closeEditModal = function() {
                console.log("Closing edit modal.");
                document.getElementById('editModal').classList.remove('is-active');
            }

            window.confirmDeleteProfile = function(profileId) {
                console.log(`Confirming deletion for profile ID: ${profileId}`);
                if (confirm('Are you sure you want to delete this profile?')) {
                    deleteProfile(profileId);
                } else {
                    console.log(`Deletion canceled for profile ID: ${profileId}`);
                }
            }

            window.deleteProfile = function(profileId) {
                console.log(`Sending DELETE request for profile ID: ${profileId}`);
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
                        document.getElementById('account-row-' + profileId).remove();
                    } else {
                        return response.json().then(data => {
                            console.error('Error deleting profile:', data);
                            alert('Error deleting profile: ' + data.message);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error during fetch operation:', error);
                    alert('Error deleting profile: ' + error.message);
                });
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

                console.log(`Sending PUT request to update profile ID: ${profileId}`, updatedProfile);

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
                        return response.json().then(data => {
                            console.error('Error updating profile:', data);
                            alert('Error updating profile: ' + data.message);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error during fetch operation:', error);
                    alert('Error updating profile: ' + error.message);
                });
            });
        });
    </script>
</body>
</html>
