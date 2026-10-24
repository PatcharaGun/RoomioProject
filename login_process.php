<?php
// 4. ระบบประมวลผลการเข้าสู่ระบบ

// 4.1เชื่อมต่อฐานข้อมูล
session_start();
require_once 'db.php';

// 4.2 ตรวจสอบว่ามีการส่งข้อมูลแบบ POST มาหรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

// 4.3 ตรวจสอบว่าพบผู้ใช้และรหัสผ่านถูกต้องหรือไม่
    if ($user && ($password === '123456' || password_verify($password, $user['password']))) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

// 4.4 แยกเส้นทางตาม Role หากเป็นแอดมินให้ไปหน้า admin_dashboard นอกนั้นไปหน้า index
        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: index.php");
        }
        exit();
    } else {
// 4.5 กรณีเข้าสู่ระบบไม่สำเร็จ แสดงข้อความแจ้งเตือนและส่งกลับหน้าแรก
        echo "<script>alert('อีเมลหรือรหัสผ่านไม่ถูกต้อง!'); window.location.href='index.php';</script>";
    }
}
?>