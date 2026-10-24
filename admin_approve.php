<?php
// ระบบประมวลผลการอนุมัติหรือปฏิเสธคำขอจองสำหรับผู้ดูแลระบบ
session_start();
require_once 'db.php';

// ตรวจสอบสิทธิ์การเข้าใช้งาน (เฉพาะแอดมินเท่านั้น)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('เฉพาะผู้ดูแลระบบเท่านั้น!'); window.location.href='index.php';</script>";
    exit();
}

// ตรวจสอบว่ามีการส่งค่ารหัสการจอง id และ status มาหรือไม่
if (isset($_GET['id']) && isset($_GET['status'])) {
    $booking_id = $_GET['id'];
    $status = $_GET['status']; // 'verified' หรือ 'rejected'

    // แปลงค่าสถานะให้ตรงตามโครงสร้างฐานข้อมูล
    $payment_status = ($status === 'verified') ? 'verified' : 'rejected';
    $booking_status = ($status === 'verified') ? 'confirmed' : 'cancelled';

    try {
        // อัปเดตสถานะการชำระเงินในตาราง payments พร้อมบันทึกเวลาที่ตรวจสอบ
        $stmt1 = $pdo->prepare("UPDATE payments SET payment_status = ?, verified_at = NOW() WHERE booking_id = ?");
        $stmt1->execute([$payment_status, $booking_id]);
   
        // อัปเดตสถานะการจองในตาราง bookings
        $stmt2 = $pdo->prepare("UPDATE bookings SET booking_status = ? WHERE booking_id = ?");
        $stmt2->execute([$booking_status, $booking_id]);

        // กำหนดข้อความแจ้งเตือนแยกตามสถานะที่กด
        if ($status === 'verified') {
            $alert_message = "อนุมัติการจองและยืนยันการชำระเงินเรียบร้อยแล้ว";
        } else {
            $alert_message = "ปฏิเสธการจอง / บันทึกข้อมูลการคืนเงินเรียบร้อยแล้ว";
        }

        // แจ้งเตือนและพาตลบกลับไปหน้าแดชบอร์ด
        echo "<script>alert('$alert_message'); window.location.href='admin_dashboard.php';</script>";
        exit();
    } catch (PDOException $e) {
        echo "<script>alert('เกิดข้อผิดพลาด: " . addslashes($e->getMessage()) . "'); window.location.href='admin_dashboard.php';</script>";
        exit();
    }
} else {
    header("Location: admin_dashboard.php");
    exit();
}
?>