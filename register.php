<?php
// register.php
require_once 'config/database.php';
require_once 'config/auth.php';

// หากเข้าสู่ระบบแล้ว ให้ redirect ไปหน้าแรก
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username         = trim($_POST['username'] ?? '');
    $fullname         = trim($_POST['fullname'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role             = $_POST['role'] ?? 'teacher';

    // ตรวจสอบความถูกต้องของบทบาท (อนุญาต admin, teacher และ technician จากหน้าลงทะเบียน)
    $role = strtolower($role);
    if (!in_array($role, ['admin', 'teacher', 'technician'], true)) {
        $role = 'teacher';
    }

    if (!empty($username) && !empty($fullname) && !empty($password) && !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            $error = 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน!';
        } elseif (mb_strlen($password) < 6) {
            $error = 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร!';
        } else {
            try {
                $pdo = Database::getInstance();

                // ตรวจสอบว่าชื่อผู้ใช้ซ้ำหรือไม่
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
                $checkStmt->execute(['username' => $username]);
                if ($checkStmt->fetchColumn() > 0) {
                    $error = 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว กรุณาใช้ชื่อผู้ใช้อื่น!';
                } else {
                    // แฮชรหัสผ่าน
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    $insertStmt = $pdo->prepare("
                        INSERT INTO users (username, password, fullname, role, email)
                        VALUES (:username, :password, :fullname, :role, :email)
                    ");
                    $insertStmt->execute([
                        'username' => $username,
                        'password' => $hashedPassword,
                        'fullname' => $fullname,
                        'role'     => $role,
                        'email'    => $email
                    ]);

                    $success = true;
                }
            } catch (Exception $e) {
                $error = 'เกิดข้อผิดพลาดในการลงทะเบียน: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน!';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - ระบบแจ้งซ่อมโรงเรียน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h3 class="card-title text-center mb-4 text-primary fw-bold">
                        <i class="fa-solid fa-user-plus me-2"></i> สมัครสมาชิก
                    </h3>

                    <?php if ($success): ?>
                        <script>
                            Swal.fire({
                                icon: 'success',
                                title: 'สมัครสมาชิกสำเร็จ!',
                                text: 'สร้างบัญชีผู้ใช้เรียบร้อยแล้ว สามารถเข้าสู่ระบบได้ทันที',
                                confirmButtonColor: '#0d6efd'
                            }).then(() => {
                                window.location.href = 'login.php';
                            });
                        </script>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <script>
                            Swal.fire({
                                icon: 'error',
                                title: 'สมัครสมาชิกไม่สำเร็จ',
                                text: '<?= htmlspecialchars($error) ?>',
                                confirmButtonColor: '#0d6efd'
                            });
                        </script>
                    <?php endif; ?>

                    <form action="register.php" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">ชื่อผู้ใช้งาน (Username) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus placeholder="ภาษาอังกฤษหรือตัวเลข">
                        </div>

                        <div class="mb-3">
                            <label for="fullname" class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="fullname" name="fullname" value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" required placeholder="เช่น ครูสมชาย ใจดี">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">อีเมล</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="example@school.ac.th">
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">ประเภทผู้ใช้งาน <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="teacher" <?= (($_POST['role'] ?? '') === 'teacher') ? 'selected' : '' ?>>ครู / บุคลากรทางการศึกษา</option>
                                <option value="technician" <?= (($_POST['role'] ?? '') === 'technician') ? 'selected' : '' ?>>ช่างซ่อม / เจ้าหน้าที่พัสดุ</option>
                                <option value="admin" <?= (strtolower($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>ผู้ดูแลระบบ (Admin)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">รหัสผ่าน <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required placeholder="อย่างน้อย 6 ตัวอักษร">
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">ยืนยันรหัสผ่าน <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="กรอกรหัสผ่านซ้ำอีกครั้ง">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mb-3">
                            <i class="fa-solid fa-user-check me-1"></i> ยืนยันการสมัครสมาชิก
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <p class="mb-0 text-muted">มีบัญชีผู้ใช้อยู่แล้ว? <a href="login.php" class="text-decoration-none fw-bold text-primary">เข้าสู่ระบบ</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
