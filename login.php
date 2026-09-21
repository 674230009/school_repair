<?php
// login.php
require_once 'config/database.php';
require_once 'config/auth.php';

$error = '';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role']     = $user['role'];
                $_SESSION['email']    = $user['email'];

                header("Location: index.php");
                exit;
            } else {
                $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง!';
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน!';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบแจ้งซ่อมโรงเรียน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

<div class="container">
    <div class="card login-card shadow">
        <div class="card-body p-4">
            <h3 class="card-title text-center mb-4 text-primary">
                <i class="fa-solid fa-wrench"></i> เข้าสู่ระบบ
            </h3>

            <?php if (!empty($error)): ?>
                <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'เข้าสู่ระบบไม่สำเร็จ',
                        text: '<?= htmlspecialchars($error) ?>',
                        confirmButtonColor: '#0d6efd'
                    });
                </script>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">ชื่อผู้ใช้งาน (Username)</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">รหัสผ่าน (Password)</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">เข้าสู่ระบบ</button>
            </form>
            <div class="text-center mt-3">
                <p class="mb-0 text-muted">ยังไม่มีบัญชีผู้ใช้? <a href="register.php" class="text-decoration-none fw-bold text-primary">สมัครสมาชิก</a></p>
            </div>
            <div class="mt-3 text-muted text-center" style="font-size: 0.85rem;">
                <p class="mb-0"><strong>บัญชีทดสอบ:</strong></p>
                <p class="mb-0">Admin: admin / 123456</p>
                <p class="mb-0">Teacher: teacher1 / 123456</p>
                <p class="mb-0">Technician: tech1 / 123456</p>
            </div>
        </div>
    </div>
</div>

</body>
</html>