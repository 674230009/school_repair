<?php
// config/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function checkRole(array $allowedRoles): void {
    checkLogin();
    if (!in_array($_SESSION['role'], $allowedRoles, true)) {
        header("HTTP/1.1 403 Forbidden");
        echo "<div style='text-align:center; padding:50px; font-family:sans-serif;'>
                <h1 style='color:red;'>403 Forbidden</h1>
                <p>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</p>
                <a href='index.php'>กลับหน้าหลัก</a>
              </div>";
        exit;
    }
}

function currentUser(): ?array {
    if (isset($_SESSION['user_id'])) {
        return [
            'id'       => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'fullname' => $_SESSION['fullname'],
            'role'     => $_SESSION['role'],
            'email'    => $_SESSION['email'] ?? ''
        ];
    }
    return null;
}
?>