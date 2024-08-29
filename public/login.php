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

$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    $data = array(
        "username" => $username,
        "password" => $password
    );

    $url = "http://ddns.callidos-mtf.fr:8085/account/login";
    $options = array(
        "http" => array(
            "header" => "Content-type: application/json",
            "method" => "POST",
            "content" => json_encode($data)
        )
    );
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);

    if ($result === FALSE) {
        $errorMessage = "Échec de la connexion. Veuillez réessayer.";
        logAction($pdo, null, 'login_attempt', 'POST', $url, 401, json_encode($data), 'Login failed');
    } else {
        $userData = json_decode($result, true);

        if (isset($userData['message']) && $userData['message'] === "This account has not yet been validated.") {
            $errorMessage = "Le compte n'est pas vérifié.";
            logAction($pdo, null, 'login_attempt', 'POST', $url, 401, json_encode($data), 'Account not validated');
        }
        else if (isset($userData['id']) && isset($userData['username'])) {
            if (!isset($userData['roles']) || empty($userData['roles'])) {
                $errorMessage = "Veuillez valider votre compte auprès d'un administrateur.";
                logAction($pdo, null, 'login_attempt', 'POST', $url, 401, json_encode($data), 'Account not validated');
            } else {
                $_SESSION['user_id'] = $userData['id'];
                $_SESSION['username'] = $userData['username'];
                $_SESSION['email'] = $userData['email'];
                $_SESSION['role'] = $userData['roles'];
                $_SESSION['accessToken'] = $userData['accessToken'];
                $_SESSION['tokenType'] = $userData['tokenType'];

                logAction($pdo, $userData['id'], 'login_success', 'POST', $url, 200, json_encode($data), $result);

                header("Location: myprofil.php");
                exit();
            }
        } else {
            $errorMessage = "Échec de la connexion. Réponse invalide du serveur.";
            logAction($pdo, null, 'login_attempt', 'POST', $url, 401, json_encode($data), 'Invalid response from server');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    $title = "Login - ATD";
    include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/head.php');
    ?>    
    <script src="/assets/js/translation.js"></script>
    <style>
        .error-message {
            color: red;
            font-size: 16px;
            margin-bottom: 15px;
            text-align: center;
            padding: 10px;
            border: 1px solid red;
            border-radius: 5px;
            background-color: #ffe5e5;
            margin-top: 20px; 
        }

        .content img {
            margin-bottom: 40px; 
        }

        .container {
            max-width: 500px;
            margin-top: 50px;
            padding-top: 20px; 
        }

        main {
            padding-top: 30px; 
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php') ?>
        <main>
            <div class="content">
                <img src="/assets/img/helix_white.png" alt="Helix_logo" width="600px" style="display: block; margin-left: auto; margin-right: auto; margin-top: 30px;">
                
                <section class="container is-max-desktop">
                    <?php if (!empty($errorMessage)): ?>
                        <div class="error-message"><?= $errorMessage ?></div>
                    <?php endif; ?>
                    <form action="login.php" method="post">
                        <div class="field">
                            <label class="label" data-translate="username">Nom d'utilisateur</label>
                            <div class="control">
                                <input class="input" type="text" name="username" placeholder="Emperor Palpatine" required>
                            </div>
                        </div>
                        <div class="field">
                            <label class="label" data-translate="password">Mot de passe</label>
                            <div class="control">
                                <input class="input" type="password" name="password" id="password" placeholder="Your super password" required>
                            </div>
                        </div>
                        <div class="control">
                            <button type="submit" class="button is-info" id="btn" data-translate="log_in">Connexion</button>
                        </div>
                    </form>
                </section>
            </div>
        </main>
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php') ?>
    </div>
</body>
</html>
