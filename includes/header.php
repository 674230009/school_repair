<?php
// includes/header.php
require_once __DIR__ . '/../config/auth.php';
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบแจ้งซ่อมภายในโรงเรียน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fa-solid fa-wrench me-2"></i>ระบบแจ้งซ่อมโรงเรียน</a>
        <?php if ($user): ?>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php"><i class="fa-solid fa-gauge me-1"></i> Dashboard</a>
                </li>
                <?php if ($user['role'] === 'teacher' || $user['role'] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="create_request.php"><i class="fa-solid fa-plus-circle me-1"></i> แจ้งซ่อมใหม่</a>
                </li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center text-white">
                <span class="me-3">
                    <i class="fa-solid fa-circle-user me-1"></i> 
                    <?= htmlspecialchars($user['fullname']) ?> 
                    <span class="badge bg-light text-dark ms-1"><?= strtoupper($user['role']) ?></span>
                </span>
                <a href="login.php?logout=1" class="btn btn-outline-light btn-sm"><i class="fa-solid fa-right-from-bracket"></i> ออกจากระบบ</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</nav>
<div class="container my-4">