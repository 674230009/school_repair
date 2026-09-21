<?php
// create_request.php
require_once 'config/database.php';
require_once 'includes/header.php';

checkRole(['admin', 'teacher']);
$user = currentUser();
$success = false;
$error = '';

// ฟังก์ชันเจนรหัสใบแจ้งซ่อม RP-YYYYMM-XXX
function generateRequestNo(PDO $pdo): string {
    $prefix = 'RP-' . date('Ym') . '-';
    $stmt = $pdo->prepare("SELECT request_no FROM repair_requests WHERE request_no LIKE :prefix ORDER BY id DESC LIMIT 1");
    $stmt->execute(['prefix' => $prefix . '%']);
    $lastNo = $stmt->fetchColumn();

    if ($lastNo) {
        $lastSeq = (int) substr($lastNo, -3);
        $newSeq = str_pad((string)($lastSeq + 1), 3, '0', STR_PAD_LEFT);
    } else {
        $newSeq = '001';
    }
    return $prefix . $newSeq;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $building     = trim($_POST['building'] ?? '');
    $room         = trim($_POST['room'] ?? '');
    $problem_type = $_POST['problem_type'] ?? 'other';
    $urgency      = $_POST['urgency'] ?? 'medium';
    $description  = trim($_POST['description'] ?? '');

    if (!empty($building) && !empty($room) && !empty($description)) {
        $pdo = null;
        try {
            $pdo = Database::getInstance();
            $pdo->beginTransaction();

            $request_no = generateRequestNo($pdo);

            $stmt = $pdo->prepare("
                INSERT INTO repair_requests (request_no, user_id, building, room, problem_type, description, urgency)
                VALUES (:req_no, :uid, :building, :room, :ptype, :desc, :urgency)
            ");
            $stmt->execute([
                'req_no'   => $request_no,
                'uid'      => $user['id'],
                'building' => $building,
                'room'     => $room,
                'ptype'    => $problem_type,
                'desc'     => $description,
                'urgency'  => $urgency
            ]);

            $repair_id = $pdo->lastInsertId();

            // จัดการอัปโหลดไฟล์รูปภาพ (ถ้ามี)
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];

                if (in_array($ext, $allowed, true)) {
                    $uploadDir = 'uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $filename = uniqid('before_') . '.' . $ext;
                    $targetPath = $uploadDir . $filename;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                        $imgStmt = $pdo->prepare("
                            INSERT INTO repair_images (repair_id, image_type, image_path)
                            VALUES (:rid, 'before', :path)
                        ");
                        $imgStmt->execute(['rid' => $repair_id, 'path' => $targetPath]);
                    }
                }
            }

            $pdo->commit();
            $success = true;
        } catch (Exception $e) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        }
    } else {
        $error = 'กรุณากรอกข้อมูลสำคัญให้ครบถ้วน!';
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h4 class="mb-0 text-primary"><i class="fa-solid fa-pen-to-square me-2"></i> แบบฟอร์มแจ้งซ่อมอุปกรณ์/สถานที่</h4>
            </div>
            <div class="card-body p-4">

                <?php if ($success): ?>
                    <script>
                        Swal.fire({
                            icon: 'success',
                            title: 'แจ้งซ่อมสำเร็จ!',
                            text: 'บันทึกใบแจ้งซ่อมเรียบร้อยแล้ว',
                            confirmButtonColor: '#0d6efd'
                        }).then(() => {
                            window.location.href = 'index.php';
                        });
                    </script>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <script>
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: '<?= htmlspecialchars($error) ?>',
                            confirmButtonColor: '#0d6efd'
                        });
                    </script>
                <?php endif; ?>

                <form action="create_request.php" method="POST" enctype="multipart/form-data">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">อาคาร / สถานที่ <span class="text-danger">*</span></label>
                            <input type="text" name="building" class="form-control" placeholder="เช่น อาคารเรียน 3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ห้อง / บริเวณ <span class="text-danger">*</span></label>
                            <input type="text" name="room" class="form-control" placeholder="เช่น ห้อง 302" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">ประเภทปัญหา <span class="text-danger">*</span></label>
                            <select name="problem_type" class="form-select" required>
                                <option value="electrical">ระบบไฟฟ้า</option>
                                <option value="furniture">ครุภัณฑ์ / เฟอร์นิเจอร์</option>
                                <option value="computer">ระบบคอมพิวเตอร์ / เครือข่าย</option>
                                <option value="aircon">เครื่องปรับอากาศ</option>
                                <option value="other">อื่นๆ</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ระดับความเร่งด่วน</label>
                            <select name="urgency" class="form-select">
                                <option value="low">ปกติ</option>
                                <option value="medium" selected>ปานกลาง</option>
                                <option value="high">ด่วนมาก</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">รายละเอียดปัญหา <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="4" placeholder="ระบุอาการเสีย หรือรายละเอียดเพิ่มเติม..." required></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">แนบรูปภาพประกอบ (ถ้ามี)</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <small class="text-muted">รองรับไฟล์ JPG, PNG ไม่เกิน 5MB</small>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left me-1"></i> ยกเลิก</a>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane me-1"></i> บันทึกแจ้งซ่อม</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>