<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || !is_array($_SESSION['role']) || !in_array('ROLE_ADMIN', $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}


if (isset($_POST['maintenance_mode_toggle']) && $_POST['maintenance_mode_toggle'] == 'on') {
    $_SESSION['maintenance_mode'] = 1;
} else {
    $_SESSION['maintenance_mode'] = 0;
}

if (isset($_SESSION['maintenance_mode']) && $_SESSION['maintenance_mode'] == 1) {
    header("Location: maintenance.php");
    exit;
}

?>
