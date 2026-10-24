<?php
// 8. แผงควบคุมสำหรับผู้ดูแลระบบ

// 8.1 เชื่อมต่อฐานข้อมูล
session_start();
require_once 'db.php';

// 8.2 ตรวจสอบสิทธิ์การเข้าใช้งาน อนุญาตเฉพาะผู้ใช้ที่มี role เป็น admin เท่านั้น
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('เฉพาะผู้ดูแลระบบเท่านั้น!'); window.location.href='index.php';</script>";
    exit();
}

// 8.3 ดึงข้อมูลรายการจองทั้งหมด พร้อมข้อมูลผู้จอง ห้องซ้อม หลักฐานการโอน และบัญชีขอคืนเงิน
$bookings = $pdo->query("
    SELECT b.*, u.full_name, r.room_name, p.slip_image, p.payment_status, p.refund_bank, p.refund_account 
    FROM bookings b 
    JOIN users u ON b.user_id = u.user_id 
    JOIN rooms r ON b.room_id = r.room_id 
    LEFT JOIN payments p ON b.booking_id = p.booking_id 
    ORDER BY b.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | จัดการคิวและการคืนเงิน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Prompt', sans-serif; background: #fff174; color: #FFF; }</style>
</head>
<body class="p-4">
    <div class="container bg-white text-dark p-4 rounded-4 shadow">
        <!-- ส่วนหัวของหน้า Dashboard และปุ่มย้อนกลับหน้าแรก -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">🛠️ Admin Dashboard: ตรวจสอบสลิปและการคืนเงิน</h2>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">กลับหน้าเว็บหลัก</a>
        </div>

        <!-- ตารางแสดงรายการจองทั้งหมดสำหรับแอดมินตรวจสอบ -->
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#ID</th>
                        <th>ผู้จอง</th>
                        <th>ห้องซ้อม</th>
                        <th>วันที่ / ยอดเงิน</th>
                        <th>สลิปโอนเงิน</th>
                        <th>ข้อมูลบัญชีขอคืนเงิน</th>
                        <th>จัดการสถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- วนลูปแสดงรายการจองแต่ละรายการ -->
                    <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td>#BK-<?php echo $b['booking_id']; ?></td>
                        <td><?php echo htmlspecialchars($b['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($b['room_name']); ?></td>
                        <td><?php echo $b['booking_date']; ?><br><b>฿<?php echo number_format($b['total_price'], 2); ?></b></td>
                        <td>
                            <?php if(!empty($b['slip_image'])): ?>
                                <a href="uploads/<?php echo $b['slip_image']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">ดูสลิป</a>
                            <?php else: ?> - <?php endif; ?>
                        </td>
                        <!-- 8.3 แสดงข้อมูลธนาคารและเลขบัญชี (กรณีผู้ใช้แจ้งยกเลิกและขอคืนเงิน) -->
                        <td>
                            <?php if(!empty($b['refund_bank'])): ?>
                                <span class="text-danger fw-bold">ธนาคาร: <?php echo $b['refund_bank']; ?></span><br>
                                <small>เลขบัญชี: <?php echo $b['refund_account']; ?></small>
                            <?php else: ?>
                                <span class="text-muted">ปกติ</span>
                            <?php endif; ?>
                        </td>
                        <!-- 8.4แสดงสถานะปัจจุบัน พร้อมปุ่มสำหรับกดยืนยันอนุมัติ หรือปฏิเสธ/คืนเงิน -->
                        <td>
                            <span class="badge bg-<?php echo ($b['booking_status']=='confirmed')?'success':(($b['booking_status']=='cancelled')?'danger':'warning text-dark'); ?>">
                                <?php echo $b['booking_status']; ?>
                            </span>
                            <div class="mt-2">
                                <a href="admin_approve.php?id=<?php echo $b['booking_id']; ?>&status=verified" class="btn btn-sm btn-success py-0">อนุมัติ</a>
                                <a href="admin_approve.php?id=<?php echo $b['booking_id']; ?>&status=rejected" class="btn btn-sm btn-danger py-0">ปฏิเสธ/คืนเงิน</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>