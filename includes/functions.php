<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}


function is_logged_in() {
    return isset($_SESSION['user_id']);
}


function require_login() {
    if (!is_logged_in()) {
        header("Location: ../auth/login.php");
        exit();
    }
}


function redirect($url) {
    header("Location: " . $url);
    exit();
}


function set_flash_message($type, $message) {
    $_SESSION['flash'][$type] = $message;
}


function display_flash_message() {
    if (isset($_SESSION['flash'])) {
        foreach ($_SESSION['flash'] as $type => $message) {
            $alertClass = ($type === 'success') ? 'alert-success' : 'alert-danger';
            echo "<div class='alert {$alertClass} alert-dismissible fade show' role='alert'>
                    {$message}
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                  </div>";
        }
        unset($_SESSION['flash']);
    }
}