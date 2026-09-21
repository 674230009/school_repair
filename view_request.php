<?php
// view_request.php
require_once 'config/database.php';
require_once 'includes/header.php';

checkLogin();
$user = currentUser();
$id = (int)($_GET['id'] ?? 0);

$pdo = Database::getInstance();

// ดึงข้อมูลใบแจ้งซ่อม
$stmt = $pdo->prepare("
    SELECT r.*, u.fullname AS reporter_name, t.fullname AS tech_name 
    FROM repair_requests r 
    JOIN users u ON r.user_id = u.id 
    LEFT JOIN users t ON r.assigned_to = t.id 
    WHERE r.id = :id
");
$stmt->execute(['id' => $id]);
$req = $stmt->fetch();

if (!$req) {
    echo "<div class='alert alert-danger'>ไม่พบรายการแจ้งซ่อมนี้</div>";
    require_once 'includes/footer.php';
    exit;
}

// ดึงช่างเทคนิคทั้งหมด (สำหรับมอบหมายงาน)
$techs = $pdo->query("SELECT id, fullname FROM users WHERE role = 'technician'")->fetchAll();

// จัดการการส่งฟอร์ม Action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. เพิ่มความคิดเห็น
    if ($action === 'add_comment') {
        $comment = trim($_POST['comment'] ?? '');
        if (!empty($comment)) {
            $cStmt = $pdo->prepare("INSERT INTO comments (repair_id, user_id, comment) VALUES (:rid, :uid, :comment)");
            $cStmt->execute(['rid' => $id, 'uid' => $user['id'], 'comment' => $comment]);
            header("Location: view_request.php?id=$id");
            exit;
        }
    }

    // 2. มอบหมายงาน (Admin เท่านั้น)
    if ($action === 'assign_job' && $user['role'] === 'admin') {
        $tech_id  = (int)$_POST['assigned_to'];
        $due_date = $_POST['due_date'] ?? null;

        $aStmt = $pdo->prepare("
            UPDATE repair_requests 
            SET assigned_to = :aid, due_date = :due, status = 'accepted' 
            WHERE id = :id
        ");
        $aStmt->execute(['aid' => $tech_id, 'due' => $due_date, 'id' => $id]);
        header("Location: view_request.php?id=$id");
        exit;
    }

    // 3. อัปเดตสถานะและผลการซ่อม (Technician/Admin)
    if ($action === 'update_status' && ($user['role'] === 'technician' || $user['role'] === 'admin')) {
        $new_status = $_POST['status'];
        $result     = trim($_POST['result'] ?? '');
        $cost       = !empty($_POST['cost']) ? (float)$_POST['cost'] : null;

        $completed_date = ($new_status === 'completed') ? date('Y-m-d') : null;

        $uStmt = $pdo->prepare("
            UPDATE repair_requests 
            SET status = :status, result = :result, cost = :cost, completion_date = :comp
            WHERE id = :id
        ");
        $uStmt->execute(['status' => $new_status, 'result' => $result, 'cost' => $cost, 'comp' => $completed_date, 'id' => $id]);

        // อัปโหลดรูปหลังซ่อม (After Image)
        if (isset($_FILES['after_image']) && $_FILES['after_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['after_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($ext, $allowed, true)) {
                $uploadDir = 'uploads/';
                $filename = uniqid('after_') . '.' . $ext;
                $targetPath = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['after_image']['tmp_name'], $targetPath)) {
                    $imgStmt = $pdo->prepare("
                        INSERT INTO repair_images (repair_id, image_type, image_path)
                        VALUES (:rid, 'after', :path)
                    ");
                    $imgStmt->execute(['rid' => $id, 'path' => $targetPath]);
                }
            }
        }
        header("Location: view_request.php?id=$id");
        exit;
    }
}

// ดึงรูปภาพทั้งหมด
$imagesStmt = $pdo->prepare("SELECT * FROM repair_images WHERE repair_id = :rid");
$imagesStmt->execute(['rid' => $id]);
$images = $imagesStmt->fetchAll();

// ดึงความคิดเห็นทั้งหมด
$commentsStmt = $pdo->prepare("
    SELECT c.*, u.fullname, u.role 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.repair_id = :rid 
    ORDER BY c.created_at ASC
");
$commentsStmt->execute(['rid' => $id]);
$comments = $commentsStmt->fetchAll();
?>

<div class="row">
    <!-- รายละเอียดหลัก -->
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 text-primary">ใบแจ้งซ่อมเลขที่: <?= htmlspecialchars($req['request_no']) ?></h5>
                <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> ย้อนกลับ</a>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6"><strong>ผู้แจ้งซ่อม:</strong> <?= htmlspecialchars($req['reporter_name']) ?></div>
                    <div class="col-md-6"><strong>วันที่แจ้ง:</strong> <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6"><strong>สถานที่:</strong> <?= htmlspecialchars($req['building'] . ' (ห้อง ' . $req['room'] . ')') ?></div>
                    <div class="col-md-6">
                        <strong>ประเภทปัญหา:</strong> 
                        <?php
                        $problemTypes = [
                            'electrical' => 'ระบบไฟฟ้า',
                            'furniture'  => 'ครุภัณฑ์ / เฟอร์นิเจอร์',
                            'computer'   => 'ระบบคอมพิวเตอร์ / เครือข่าย',
                            'aircon'     => 'เครื่องปรับอากาศ',
                            'other'      => 'อื่นๆ'
                        ];
                        echo htmlspecialchars($problemTypes[$req['problem_type']] ?? $req['problem_type']);
                        ?>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>สถานะ:</strong> 
                        <?php
                        $statusBadges = [
                            'pending'     => '<span class="badge bg-warning text-dark">รอรับเรื่อง</span>',
                            'accepted'    => '<span class="badge bg-info">รับเรื่องแล้ว</span>',
                            'in_progress' => '<span class="badge bg-primary">กำลังดำเนินการ</span>',
                            'completed'   => '<span class="badge bg-success">เสร็จสมบูรณ์</span>'
                        ];
                        echo $statusBadges[$req['status']] ?? htmlspecialchars($req['status']);
                        ?>
                    </div>
                    <div class="col-md-6"><strong>ช่างที่รับผิดชอบ:</strong> <?= htmlspecialchars($req['tech_name'] ?? 'ยังไม่กำหนด') ?></div>
                </div>
                <div class="mb-3">
                    <strong>รายละเอียดปัญหา:</strong>
                    <p class="border p-2 rounded bg-light mt-1"><?= nl2br(htmlspecialchars($req['description'])) ?></p>
                </div>
                <?php if (!empty($req['result'])): ?>
                <div class="mb-3">
                    <strong>ผลการแก้ไข:</strong>
                    <p class="border p-2 rounded bg-light mt-1 text-success"><?= nl2br(htmlspecialchars($req['result'])) ?></p>
                </div>
                <?php endif; ?>
                <?php if ($req['cost'] !== null): ?>
                <div><strong>ค่าใช้จ่าย:</strong> <?= number_format($req['cost'], 2) ?> บาท</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- รูปภาพประกอบ -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0"><i class="fa-solid fa-images me-2"></i> รูปภาพประกอบ</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php if (count($images) > 0): ?>
                        <?php foreach ($images as $img): ?>
                            <div class="col-md-4 text-center">
                                <img src="<?= htmlspecialchars($img['image_path']) ?>" class="repair-img border mb-1" alt="รูปประกอบ">
                                <span class="badge <?= $img['image_type'] === 'before' ? 'bg-danger' : 'bg-success' ?>">
                                    <?= $img['image_type'] === 'before' ? 'ก่อนซ่อม' : 'หลังซ่อม' ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">ไม่มีรูปภาพประกอบ</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ความคิดเห็น -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0"><i class="fa-solid fa-comments me-2"></i> ความคิดเห็นและการอัปเดต</h6>
            </div>
            <div class="card-body">
                <?php foreach ($comments as $c): ?>
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between">
                            <strong><?= htmlspecialchars($c['fullname']) ?> <small class="text-muted">(<?= strtoupper($c['role']) ?>)</small></strong>
                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></small>
                        </div>
                        <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($c['comment'])) ?></p>
                    </div>
                <?php endforeach; ?>

                <form action="view_request.php?id=<?= $id ?>" method="POST" class="mt-3">
                    <input type="hidden" name="action" value="add_comment">
                    <div class="mb-2">
                        <textarea name="comment" class="form-control" rows="2" placeholder="เพิ่มความคิดเห็น..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-paper-plane me-1"></i> ส่งความคิดเห็น</button>
                </form>
            </div>
        </div>
    </div>

    <!-- แผงการจัดการ (Action Panel) -->
    <div class="col-md-4">
        <?php if ($user['role'] === 'admin'): ?>
        <!-- ฟอร์มมอบหมายงาน -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0"><i class="fa-solid fa-user-gear me-2"></i> มอบหมายงาน (Admin)</h6>
            </div>
            <div class="card-body">
                <form action="view_request.php?id=<?= $id ?>" method="POST">
                    <input type="hidden" name="action" value="assign_job">
                    <div class="mb-3">
                        <label class="form-label">เลือกช่างผู้รับผิดชอบ</label>
                        <select name="assigned_to" class="form-select" required>
                            <option value="">-- เลือกช่าง --</option>
                            <?php foreach ($techs as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= $req['assigned_to'] == $t['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['fullname']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">กำหนดเสร็จ (Due Date)</label>
                        <input type="date" name="due_date" class="form-control" value="<?= $req['due_date'] ?>">
                    </div>
                    <button type="submit" class="btn btn-success w-100"><i class="fa-solid fa-check me-1"></i> มอบหมายงาน</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($user['role'] === 'technician' || $user['role'] === 'admin'): ?>
        <!-- ฟอร์มอัปเดตสถานะการซ่อม -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0"><i class="fa-solid fa-list-check me-2"></i> อัปเดตการซ่อม</h6>
            </div>
            <div class="card-body">
                <form action="view_request.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_status">
                    <div class="mb-3">
                        <label class="form-label">สถานะการซ่อม</label>
                        <select name="status" class="form-select" required>
                            <option value="accepted" <?= $req['status'] === 'accepted' ? 'selected' : '' ?>>รับเรื่องแล้ว</option>
                            <option value="in_progress" <?= $req['status'] === 'in_progress' ? 'selected' : '' ?>>กำลังดำเนินการ</option>
                            <option value="completed" <?= $req['status'] === 'completed' ? 'selected' : '' ?>>ซ่อมเสร็จสิ้น</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ผลการแก้ไข / บันทึกช่าง</label>
                        <textarea name="result" class="form-control" rows="3"><?= htmlspecialchars($req['result'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ค่าใช้จ่าย (บาท)</label>
                        <input type="number" step="0.01" name="cost" class="form-control" value="<?= $req['cost'] ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">แนบรูปหลังซ่อม (After)</label>
                        <input type="file" name="after_image" class="form-control" accept="image/*">
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-floppy-disk me-1"></i> บันทึกอัปเดต</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>