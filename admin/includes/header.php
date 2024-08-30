<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || !is_array($_SESSION['role']) || !in_array('ROLE_ADMIN', $_SESSION['role'])) {
    header("Location: ../public/login.php");
    exit;
}
?>

<header>
    
    <script src="/assets/js/translation.js"></script>

    <nav class="navbar" role="navigation" aria-label="main navigation">
        <div class="navbar-brand">
            <a class="navbar-item" href="<?= '/index.php' ?>">
                <img src="<?= '/assets/img/helix_white.png' ?>" alt="atd_header_logo">
            </a>
            <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="navbar">
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
            </a>
        </div>

        <div id="navbar" class="navbar-menu">
            <div class="navbar-start">
                <a class="navbar-item" data-translate="home" href="<?= '/admin/index.php' ?>">Home</a>
                <a class="navbar-item" data-translate="logs" href="<?= '/admin/logs.php' ?>">Logs</a>
                <a class="navbar-item" data-translate="accounts" href="<?= '/admin/account.php' ?>">Accounts</a>
                <a class="navbar-item" data-translate="account_not_validate" href="<?= '/admin/validate.php' ?>">Account not validate</a>
                <a class="navbar-item" data-translate="translation_manager" href="<?= '/admin/translation_manager.php' ?>">translation Manager</a>

            </div>
            <div class="navbar-end">
                <div class="navbar-item">
                    <div class="select is-rounded">
                        <select id="lang_switch">
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>
