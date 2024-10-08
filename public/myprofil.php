<?php 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || !is_array($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$authHeader = "Authorization: Bearer " . $_SESSION['accessToken'];
$options = [
    "http" => [
        "method" => "GET",
        "header" => $authHeader
    ]
];
$context = stream_context_create($options);
$url = "http://ddns.callidos-mtf.fr:8085/account/me";

$response = file_get_contents($url, false, $context);
if ($response === FALSE) {
    echo "Failed to retrieve profile information.";
    exit();
}

$profileData = json_decode($response, true);

function logAction($pdo, $user_id, $action, $method, $url, $response_code, $request_data = null, $response_body = null) {
    $stmt = $pdo->prepare("
        INSERT INTO log (user_id, action, request_method, request_url, request_data, response_code, response_body)
        VALUES (:user_id, :action, :request_method, :request_url, :request_data, :response_code, :response_body)
    ");
    $stmt->execute([
        ':user_id' => $user_id,
        ':action' => $action,
        ':request_method' => $method,
        ':request_url' => $url,
        ':request_data' => $request_data,
        ':response_code' => $response_code,
        ':response_body' => $response_body
    ]);
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

$message = ''; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $updatedData = [
        "username" => $_POST['username'],
        "phone" => $_POST['phone'],
        "name" => $_POST['name'],
        "lastName" => $_POST['lastName'],
        "location" => $_POST['location'],
        "password" => $_POST['password']
    ];

    $putOptions = [
        "http" => [
            "method" => "PUT",
            "header" => $authHeader . "\r\n" . "Content-Type: application/json",
            "content" => json_encode($updatedData)
        ]
    ];
    $putContext = stream_context_create($putOptions);
    $putUrl = "http://ddns.callidos-mtf.fr:8085/account/" . $profileData['id'];

    $putResponse = file_get_contents($putUrl, false, $putContext);

    if ($putResponse === FALSE) {
        logAction($pdo, $profileData['id'], 'update_profile', 'PUT', $putUrl, 500, json_encode($updatedData), 'Failed to update profile information.');
        $message = "Failed to update profile information.";
    } else {
        $response = file_get_contents($url, false, $context);
        $profileData = json_decode($response, true);
        logAction($pdo, $profileData['id'], 'update_profile', 'PUT', $putUrl, 200, json_encode($updatedData), $putResponse);
        $message = "Profile updated successfully!";
    }
}

$imagePath = '../assets/img/default.jpg'; 

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php
        $title = "My Profile - ATD";
        include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/head.php');
    ?>
    <style>
        .profile-id-card {
            background-color: #333; 
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            max-width: 800px;
            margin: 20px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #444; 
        }

        .profile-id-card img {
            border-radius: 50%;
            width: 120px;
            height: 120px;
            object-fit: cover;
            margin-right: 30px;
            border: 3px solid #777; 
        }

        .profile-id-card .info {
            flex-grow: 1;
            color: #f0f0f0; 
        }

        .profile-id-card .info h2 {
            margin-bottom: 10px;
            font-size: 28px;
            color: #ffffff; 
            font-weight: bold;
        }

        .profile-id-card .info p {
            margin: 8px 0;
            font-size: 16px;
            color: #dcdcdc; 
        }

        .profile-id-card .info p span {
            font-weight: bold;
            color: #ffffff; 
        }

        .notification {
            margin-bottom: 20px;
        }

    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'); ?>
        <main>
            <div class="content">
                <div class="container is-max-desktop">
                    <div class="profile-id-card">
                        <img src="<?= $imagePath ?>" alt="User Photo">
                        <div class="info">
                            <h2><?= htmlspecialchars($profileData['name']) . ' ' . htmlspecialchars($profileData['lastName']); ?></h2>
                            <p><span data-translate="username_label">Username:</span> <?= htmlspecialchars($profileData['username']); ?></p>
                            <p><span data-translate="email_label">Email:</span> <?= htmlspecialchars($profileData['email']); ?></p>
                            <p><span data-translate="location_label">Location:</span> <?= htmlspecialchars($profileData['location']); ?></p>
                            <p><span data-translate="phone_label">Phone:</span> <?= htmlspecialchars($profileData['phone']); ?></p>
                            <p><span data-translate="role_label">Role:</span> <?= htmlspecialchars($_SESSION['role'][0]); ?></p>
                        </div>
                    </div>
                    <h1 class="title has-text-centered">
                        <span data-translate="welcome">Welcome, </span>
                        <span data-no-translate="true"><?= htmlspecialchars($profileData['username']); ?></span>
                    </h1>
                    <form id="profileForm" method="POST">
                        <?php if (!empty($message)): ?>
                            <div class="notification is-info">
                                <?= htmlspecialchars($message) ?>
                            </div>
                        <?php endif; ?>
                        <div class="field">
                            <label class="label" for="username" data-translate="username_label">Username</label>
                            <div class="control">
                                <input class="input" type="text" id="username" name="username" value="<?= htmlspecialchars($profileData['username']); ?>" disabled>
                            </div>
                        </div>
                        <div class="field">
                            <label class="label" for="name" data-translate="first_name_label">First Name</label>
                            <div class="control">
                                <input class="input" type="text" id="name" name="name" value="<?= htmlspecialchars($profileData['name']); ?>" disabled>
                            </div>
                        </div>
                        <div class="field">
                            <label class="label" for="lastName" data-translate="last_name_label">Last Name</label>
                            <div class="control">
                                <input class="input" type="text" id="lastName" name="lastName" value="<?= htmlspecialchars($profileData['lastName']); ?>" disabled>
                            </div>
                        </div>
                        <div class="field">
                            <label class="label" for="email" data-translate="email_label">Email</label>
                            <div class="control">
                                <input class="input" type="email" id="email" name="email" value="<?= htmlspecialchars($profileData['email']); ?>" disabled>
                            </div>
                        </div>
                        <div class="field">
                            <label class="label" for="location" data-translate="location_label">Location</label>
                            <div class="control">
                                <input class="input" type="text" id="location" name="location" value="<?= htmlspecialchars($profileData['location']); ?>" disabled>
                            </div>
                        </div>
                        <div class="field">
                            <label class="label" for="phone" data-translate="phone_label">Phone</label>
                            <div class="control">
                                <input class="input" type="text" id="phone" name="phone" value="<?= htmlspecialchars($profileData['phone']); ?>" disabled>
                            </div>
                        </div>
                        <div class="field">
                            <label class="label" for="password" data-translate="password_label">New Password (leave blank to keep current password)</label>
                            <div class="control">
                                <input class="input" type="password" id="password" name="password" disabled>
                            </div>
                        </div>
                        <div class="control">
                            <button type="button" class="button is-info" id="editBtn" data-translate="edit_button">Edit</button>
                            <button type="submit" class="button is-success is-hidden" id="saveBtn" name="save" data-translate="save_button">Save</button>
                            <button type="button" class="button is-danger is-hidden" id="cancelBtn" data-translate="cancel_button">Cancel</button>
                        </div>
                    </form>
                    <a href="calendar.php" class="button is-link" style="margin-top: 20px;" data-translate="view_calendar">View Event Calendar</a>
                </div>
            </div>
        </main>
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'); ?>
    </div>

    <script>
        document.getElementById('editBtn').addEventListener('click', function () {
            document.getElementById('username').disabled = false;
            document.getElementById('name').disabled = false;
            document.getElementById('lastName').disabled = false;
            document.getElementById('location').disabled = false;
            document.getElementById('phone').disabled = false;
            document.getElementById('password').disabled = false;
            document.getElementById('editBtn').classList.add('is-hidden');
            document.getElementById('saveBtn').classList.remove('is-hidden');
            document.getElementById('cancelBtn').classList.remove('is-hidden');
        });

        document.getElementById('cancelBtn').addEventListener('click', function () {
            document.getElementById('username').disabled = true;
            document.getElementById('name').disabled = true;
            document.getElementById('lastName').disabled = true;
            document.getElementById('location').disabled = true;
            document.getElementById('phone').disabled = true;
            document.getElementById('password').disabled = true;
            document.getElementById('editBtn').classList.remove('is-hidden');
            document.getElementById('saveBtn').classList.add('is-hidden');
            document.getElementById('cancelBtn').classList.add('is-hidden');
        });
    </script>
</body>
</html>
