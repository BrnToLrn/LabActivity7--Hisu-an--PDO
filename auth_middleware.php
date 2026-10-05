<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_auth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function require_guest() {
    if (isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit;
    }
}