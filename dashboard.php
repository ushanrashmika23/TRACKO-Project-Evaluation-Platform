<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'student') {
    header('Location: ./student/layout.php');
    exit();
} else if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'supervisor') {
    header('Location: ./supervisor/layout.php');
    exit();
} else if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'admin') {
    header('Location: ./admin/layout.php');
    exit();
} else {
    header('Location: ./index.php');
    exit();
}

?>