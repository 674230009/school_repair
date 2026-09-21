<?php
// index.php
require_once 'config/database.php';
require_once 'includes/header.php';

checkLogin();
$user = currentUser();

try {
    $pdo = Database::getInstance();

    // กรองการดึงข้อมูลตาม Role
    $whereClause = "";
    $params = [];

    if ($user['role'] === 'teacher') {
        $whereClause = " WHERE r.user_id = :uid";
        $params['uid'] = $user['id'];
    } elseif ($user['role'] === 'technician') {
        $whereClause = " WHERE r.assigned_to = :aid OR r.status = 'pending'";
        $params['aid'] = $user['id'];
    }

    // สถิติ
    $stats = [
        'total'       => $pdo->query("SELECT COUNT(*) FROM repair_requests")->fetchColumn(),
        'pending'     => $pdo->query("SELECT COUNT(*) FROM repair_requests WHERE status = 'pending'")->fetchColumn(),
        'in_progress' => $pdo->query("SELECT COUNT(*) FROM repair_requests WHERE status IN ('accepted', 'in_progress')")->fetchColumn(),
        'completed'   => $pdo->query("SELECT COUNT(*) FROM repair_requests WHERE status = 'completed'")->fetchColumn(),
    ];

    // รายการใบแจ้งซ่อม
    $stmt = $pdo->prepare("
        SELECT r.*, u.fullname AS reporter_name, t.fullname AS tech_name 
        FROM repair_requests r 
        JOIN users u ON r.user_id = u.id 
        LEFT JOIN users t ON r.assigned_to = t.id 
        $whereClause
        ORDER BY r.created_at DESC
    ");
    $stmt->execute($params);
    $requests = $stmt->fetchAll();
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
    $stats = ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'completed' => 0];
    $requests = [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fa-solid fa-chart-line me-2"></i> แผงควบคุม (Dashboard)</h2>
    <?php if ($user['role'] === 'teacher' || $user['role'] === 'admin'): ?>
        <a href="create_request.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> แจ้งซ่อมใหม่</a>
    <?php endif; ?>
</div>

<!-- สรุปสถิติ -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card bg-primary text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1">งานทั้งหมด</h6>
                    <h3 class="mb-0 fw-bold"><?= $stats['total'] ?></h3>
                </div>
                <i class="fa-solid fa-list-check fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-warning text-dark p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1">รอดำเนินการ</h6>
                    <h3 class="mb-0 fw-bold"><?= $stats['pending'] ?></h3>
                </div>
                <i class="fa-solid fa-clock fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-info text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1">กำลังดำเนินการ</h6>
                    <h3 class="mb-0 fw-bold"><?= $stats['in_progress'] ?></h3>
                </div>
                <i class="fa-solid fa-spinner fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-success text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1">เสร็จสิ้นแล้ว</h6>
                    <h3 class="mb-0 fw-bold"><?= $stats['completed'] ?></h3>
                </div>
                <i class="fa-solid fa-circle-check fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
</div>

<!-- ตารางแสดงรายการ -->
<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-secondary"><i class="fa-solid fa-list me-2"></i> รายการแจ้งซ่อม</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>รหัสแจ้งซ่อม</th>
                        <th>ผู้แจ้ง</th>
                        <th>สถานที่</th>
                        <th>ประเภทปัญหา</th>
                        <th>ความเร่งด่วน</th>
                        <th>ผู้รับผิดชอบ</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($requests) > 0): ?>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($req['request_no']) ?></strong></td>
                                <td><?= htmlspecialchars($req['reporter_name']) ?></td>
                                <td><?= htmlspecialchars($req['building'] . ' (' . $req['room'] . ')') ?></td>
                                <td>
                                    <?php
                                    $problemTypeBadges = [
                                        'electrical' => '<span class="badge bg-warning text-dark"><i class="fa-solid fa-bolt me-1"></i>ระบบไฟฟ้า</span>',
                                        'furniture'  => '<span class="badge bg-secondary"><i class="fa-solid fa-chair me-1"></i>ครุภัณฑ์</span>',
                                        'computer'   => '<span class="badge bg-info text-dark"><i class="fa-solid fa-desktop me-1"></i>คอมพิวเตอร์</span>',
                                        'aircon'     => '<span class="badge bg-primary"><i class="fa-solid fa-snowflake me-1"></i>เครื่องปรับอากาศ</span>',
                                        'other'      => '<span class="badge bg-dark"><i class="fa-solid fa-screwdriver-wrench me-1"></i>อื่นๆ</span>'
                                    ];
                                    echo $problemTypeBadges[$req['problem_type']] ?? htmlspecialchars($req['problem_type']);
                                    ?>
                                </td>
                                <td>
                                    <?php if ($req['urgency'] === 'high'): ?>
                                        <span class="badge bg-danger">ด่วนมาก</span>
                                    <?php elseif ($req['urgency'] === 'medium'): ?>
                                        <span class="badge bg-warning text-dark">ปานกลาง</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">ปกติ</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($req['tech_name'] ?? 'ยังไม่อนุมัติ/มอบหมาย') ?></td>
                                <td>
                                    <?php
                                    $statusBadges = [
                                        'pending'     => '<span class="badge bg-warning text-dark">รอรับเรื่อง</span>',
                                        'accepted'    => '<span class="badge bg-info">รับเรื่องแล้ว</span>',
                                        'in_progress' => '<span class="badge bg-primary">กำลังดำเนินการ</span>',
                                        'completed'   => '<span class="badge bg-success">เสร็จสมบูรณ์</span>'
                                    ];
                                    echo $statusBadges[$req['status']] ?? $req['status'];
                                    ?>
                                </td>
                                <td>
                                    <a href="view_request.php?id=<?= $req['id'] ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fa-solid fa-eye"></i> รายละเอียด
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">ยังไม่มีข้อมูลการแจ้งซ่อม</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>