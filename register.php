<?php
    //2.บันทึกข้อมูลการลงทะเบียนเข้าสู่ฐานข้อมูล

    //เชื่อมต่อ database
require_once 'db.php';

    //กำหนดค่าเริ่มต้นให้กับตัวแปร
$message = '';
 
    //2.3 ตรวจสอบว่ามีการกดส่งข้อมูลจากฟอร์มหรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // get form data
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // ตรวจสอบว่ารหัสผ่านกับยืนยันรหัสผ่านตรงกันหรือไม่
    if ($password !== $confirm_password) {
        $message = "เกิดข้อผิดพลาด: รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน";
    } else {
        // แปลงรหัสผ่านเป็นรหัสแฮชก่อนบันทึก
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        //2.4 นำข้อมูลบันทึกลงในฐานข้อมูล
        try {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, 'customer')");
            $stmt->execute([$full_name, $email, $phone, $hashed_password]);
            echo "<script>alert('สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ'); window.location.href='index.php';</script>";
            exit();
        } catch (PDOException $e) {
            $message = "เกิดข้อผิดพลาด: อีเมลนี้อาจถูกใช้งานแล้ว";
        }
    }
}
?>
<!--. ส่วนโครงสร้างหน้าเว็บและดีไซน์ Frontend -->
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สมัครสมาชิก | Roomio's Studio Rental </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Prompt', sans-serif; background-color: #FDFCF7; }</style>
</head>
<body class="d-flex align-items-center py-5 bg-light" style="min-height: 100vh;">
    <div class="container" style="max-width: 500px;">
        <div class="card shadow-sm border-0 rounded-4 p-4">
            <h3 class="fw-bold text-center mb-4 text-warning">สมัครสมาชิก Roomio's Studio</h3>
            <!-- ส่วนแสดงข้อความแจ้งเตือนเมื่อเกิด Error เช่น  ถ้าเกิดอีเมลซ้ำ หรือรหัสไม่ตรงกัน -->
            <?php if($message): ?><div class="alert alert-danger"><?php echo $message; ?></div><?php endif; ?>

            <!-- ฟอร์มกรอกข้อมูลสมัครสมาชิก ส่งค่าแบบ POST ไปที่ไฟล์ register.php -->
            <form action="register.php" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold">ชื่อ-นามสกุล</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">อีเมล</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">เบอร์โทรศัพท์</label>
                    <input type="text" name="phone" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">รหัสผ่าน</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">ยืนยันรหัสผ่าน</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-warning w-100 py-2 fw-semibold text-white" style="background: #D4AF37;">ลงทะเบียน</button>
                <div class="text-center mt-3">
                    <a href="index.php" class="text-decoration-none text-muted">กลับหน้าแรก</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>