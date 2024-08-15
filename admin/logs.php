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

$filters = [];
$sql = "SELECT * FROM log WHERE 1=1";

if (!empty($_GET['user_id'])) {
    $sql .= " AND user_id = :user_id";
    $filters[':user_id'] = $_GET['user_id'];
}
if (!empty($_GET['action'])) {
    $sql .= " AND action = :action";
    $filters[':action'] = $_GET['action'];
}
if (!empty($_GET['request_method'])) {
    $sql .= " AND request_method = :request_method";
    $filters[':request_method'] = $_GET['request_method'];
}

$logsPerPage = 10;
$totalLogsStmt = $pdo->prepare("SELECT COUNT(*) FROM log WHERE 1=1");
$totalLogsStmt->execute($filters);
$totalLogs = $totalLogsStmt->fetchColumn();

$totalPages = ceil($totalLogs / $logsPerPage);
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $logsPerPage;

$sql .= " LIMIT $logsPerPage OFFSET $offset";

$logsStmt = $pdo->prepare($sql);
$logsStmt->execute($filters);
$logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

function escape($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function getUsername($user_id) {
    $apiUrl = "http://ddns.callidos-mtf.fr:8085/account/" . $user_id;
    $accessToken = $_SESSION['accessToken'];

    $options = [
        "http" => [
            "method" => "GET",
            "header" => "Authorization: Bearer " . $accessToken
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($apiUrl, false, $context);

    if ($response === FALSE) {
        return "Unknown"; 
    }

    $userData = json_decode($response, true);
    return $userData['username'] ?? "Unknown"; 
}

function formatTimestamp($timestamp) {
    $date = new DateTime($timestamp);
    return $date->format('Y-m-d H:i:s');
}

function describeAction($action) {
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "Manage Logs - HELIX";
    include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/head.php');
    ?>
    <link rel="stylesheet" href="/assets/css/panel.css">
</head>

<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/header.php') ?>
        <main>
            <div class="content">
                <div class="filter-container">
                    <h2>Filter Logs</h2>
                    <form method="GET">
                        <label for="userIdFilter">User ID:</label>
                        <input type="number" id="userIdFilter" name="user_id" value="<?= escape($_GET['user_id'] ?? '') ?>">

                        <label for="actionFilter">Action:</label>
                        <input type="text" id="actionFilter" name="action" value="<?= escape($_GET['action'] ?? '') ?>">

                        <label for="methodFilter">HTTP Method:</label>
                        <select id="methodFilter" name="request_method">
                            <option value="">All Methods</option>
                            <option value="GET" <?= (isset($_GET['request_method']) && $_GET['request_method'] == 'GET') ? 'selected' : '' ?>>GET</option>
                            <option value="POST" <?= (isset($_GET['request_method']) && $_GET['request_method'] == 'POST') ? 'selected' : '' ?>>POST</option>
                            <option value="PUT" <?= (isset($_GET['request_method']) && $_GET['request_method'] == 'PUT') ? 'selected' : '' ?>>PUT</option>
                            <option value="DELETE" <?= (isset($_GET['request_method']) && $_GET['request_method'] == 'DELETE') ? 'selected' : '' ?>>DELETE</option>
                        </select>

                        <button type="submit">Filter</button>
                    </form>
                </div>

                <div class="logs-list">
                    <h2>Logs List</h2>
                    <table class="table is-striped is-fullwidth">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Username</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Method</th>
                                <th>Response Code</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log) : 
                                $username = getUsername($log['user_id']);
                                $formattedTimestamp = formatTimestamp($log['timestamp']);
                                $actionDescription = describeAction($log['action']);
                            ?>
                                <tr>
                                    <td><?= escape($log['user_id']) ?></td>
                                    <td><?= escape($username) ?></td>
                                    <td><?= escape($log['action']) ?></td>
                                    <td><?= escape($actionDescription) ?></td>
                                    <td><?= escape($log['request_method']) ?></td>
                                    <td><?= escape($log['response_code']) ?></td>
                                    <td><?= escape($formattedTimestamp) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <nav class="pagination is-centered" role="navigation" aria-label="pagination">
                    <a class="pagination-previous" <?= $current_page <= 1 ? 'disabled' : 'href="?page=' . ($current_page - 1) . '&' . http_build_query($_GET) . '"' ?>>Previous</a>
                    <a class="pagination-next" <?= $current_page >= $totalPages ? 'disabled' : 'href="?page=' . ($current_page + 1) . '&' . http_build_query($_GET) . '"' ?>>Next</a>
                    <ul class="pagination-list">
                        <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                            <li>
                                <a class="pagination-link <?= $i == $current_page ? 'is-current' : '' ?>" href="?page=<?= $i ?>&<?= http_build_query($_GET) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        </main>
        <footer class="footer">
            &copy; <?= date('Y'); ?> HELIX. All Rights Reserved.
        </footer>
    </div>
</body>

</html>
