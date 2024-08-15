<?php
session_start();

$dsn = 'mysql:host=db;dbname=helix_db;charset=utf8';
$username = 'root';
$password = 'root_password';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$totalUsersStmt = $pdo->query("SELECT COUNT(*) FROM users");
$totalUsers = $totalUsersStmt->fetchColumn();

$totalLogsStmt = $pdo->query("SELECT COUNT(*) FROM log");
$totalLogs = $totalLogsStmt->fetchColumn();

$totalEventsStmt = $pdo->query("SELECT COUNT(*) FROM events");
$totalEvents = $totalEventsStmt->fetchColumn();

$totalVehiclesStmt = $pdo->query("SELECT COUNT(*) FROM vehicles");
$totalVehicles = $totalVehiclesStmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "Admin Panel - HELIX";
    include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/head.php');
    ?>
    <link rel="stylesheet" href="/assets/css/panel.css">
    <style>
        .admin-title {
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/header.php') ?>
        <main>
            <div class="content">
                <h1 class="title has-text-centered admin-title">Admin Panel - HELIX</h1>
                <section class="section">
                    <div class="container">
                        <div class="columns is-multiline">
                            <div class="column is-3">
                                <div class="box has-text-centered">
                                    <p class="title"><?= $totalUsers ?></p>
                                    <p class="subtitle">Total Users</p>
                                </div>
                            </div>
                            <div class="column is-3">
                                <div class="box has-text-centered">
                                    <p class="title"><?= $totalLogs ?></p>
                                    <p class="subtitle">Total Logs</p>
                                </div>
                            </div>
                            <div class="column is-3">
                                <div class="box has-text-centered">
                                    <p class="title"><?= $totalEvents ?></p>
                                    <p class="subtitle">Total Events</p>
                                </div>
                            </div>
                            <div class="column is-3">
                                <div class="box has-text-centered">
                                    <p class="title"><?= $totalVehicles ?></p>
                                    <p class="subtitle">Total Vehicles</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="section">
                    <div class="container">
                        <h2 class="title is-4">Recent Activity</h2>
                        <div class="logs-list">
                            <table class="table is-striped is-fullwidth">
                                <thead>
                                    <tr>
                                        <th>User ID</th>
                                        <th>Action</th>
                                        <th>Description</th>
                                        <th>Method</th>
                                        <th>Response Code</th>
                                        <th>Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $recentLogsStmt = $pdo->query("SELECT * FROM log ORDER BY timestamp DESC LIMIT 5");
                                    $recentLogs = $recentLogsStmt->fetchAll(PDO::FETCH_ASSOC);

                                    function escape($value)
                                    {
                                        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
                                    }

                                    function describeAction($action)
                                    {
                                        switch ($action) {
                                            case 'login_attempt':
                                                return "Tentative de connexion à l'application.";
                                            case 'login_success':
                                                return "Connexion réussie à l'application.";
                                            case 'update_profile':
                                                return "Mise à jour des informations du profil.";
                                            default:
                                                return "Action non spécifiée.";
                                        }
                                    }

                                    foreach ($recentLogs as $log) : ?>
                                        <tr>
                                            <td><?= escape($log['user_id']) ?></td>
                                            <td><?= escape($log['action']) ?></td>
                                            <td><?= escape(describeAction($log['action'])) ?></td>
                                            <td><?= escape($log['request_method']) ?></td>
                                            <td><?= escape($log['response_code']) ?></td>
                                            <td><?= escape($log['timestamp']) ?></td>
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
</body>

</html>
