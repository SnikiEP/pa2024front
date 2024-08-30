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

function loadTranslations($lang = 'en') {
    $file = __DIR__ . "/locales/{$lang}.json";
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    return [];
}

$translations = loadTranslations('en');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = $translations['admin_panel'];
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
                <h1 class="title has-text-centered admin-title" data-translate="admin_panel"><?= $translations['admin_panel'] ?></h1>
                <section class="section">
                    <div class="container">
                        <div class="columns is-multiline">
                            <div class="column is-3">
                                <div class="box has-text-centered">
                                    <p class="title"><?= $totalUsers ?></p>
                                    <p class="subtitle" data-translate="total_users"><?= $translations['total_users'] ?></p>
                                </div>
                            </div>
                            <div class="column is-3">
                                <div class="box has-text-centered">
                                    <p class="title"><?= $totalLogs ?></p>
                                    <p class="subtitle" data-translate="total_logs"><?= $translations['total_logs'] ?></p>
                                </div>
                            </div>
                            <div class="column is-3">
                                <div class="box has-text-centered">
                                    <p class="title"><?= $totalEvents ?></p>
                                    <p class="subtitle" data-translate="total_events"><?= $translations['total_events'] ?></p>
                                </div>
                            </div>
                            <div class="column is-3">
                                <div class="box has-text-centered">
                                    <p class="title"><?= $totalVehicles ?></p>
                                    <p class="subtitle" data-translate="total_vehicles"><?= $translations['total_vehicles'] ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="section">
                    <div class="container">
                        <h2 class="title is-4" data-translate="recent_activity"><?= $translations['recent_activity'] ?></h2>
                        <div class="logs-list">
                            <table class="table is-striped is-fullwidth">
                                <thead>
                                    <tr>
                                        <th data-translate="user_id"><?= $translations['user_id'] ?></th>
                                        <th data-translate="action"><?= $translations['action'] ?></th>
                                        <th data-translate="description"><?= $translations['description'] ?></th>
                                        <th data-translate="method"><?= $translations['method'] ?></th>
                                        <th data-translate="response_code"><?= $translations['response_code'] ?></th>
                                        <th data-translate="timestamp"><?= $translations['timestamp'] ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $recentLogsStmt = $pdo->query("SELECT * FROM log ORDER BY timestamp DESC LIMIT 5");
                                    $recentLogs = $recentLogsStmt->fetchAll(PDO::FETCH_ASSOC);

                                    function escape($value) {
                                        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
                                    }

                                    foreach ($recentLogs as $log) : ?>
                                        <tr>
                                            <td><?= escape($log['user_id']) ?></td>
                                            <td><?= escape($log['action']) ?></td>
                                            <td><?= escape($log['description']) ?></td>
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
            <p data-translate="footer_text">&copy; 2024-<?= date("Y"), ($translations['footer_text']) ?></p>
        </footer>
    </div>
</body>

</html>
