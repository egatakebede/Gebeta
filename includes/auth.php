<?php
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user() {
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

function is_logged_in() {
    return !empty($_SESSION['user']);
}

function require_login(array $roles = []) {
    if (!is_logged_in()) {
        redirect('/index.php');
    }
    if (!empty($roles) && !in_array($_SESSION['user']['role'], $roles, true)) {
        redirect('/index.php');
    }
}

function login_user(array $user) {
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'role' => $user['role'],
    ];
}

function logout_user() {
    session_start();
    $_SESSION = [];
    session_destroy();
}
