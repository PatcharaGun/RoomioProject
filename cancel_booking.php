<?php
// 7. ระบบยกเลิกการจองและแจ้งขอคืนเงิน

// 7.1เชื่อมต่อฐานข้อมูล
session_start();
require_once 'db.php';

// 7.2 ตรวจสอบว่ามีการส่งข้อมูลผ่าน POST หรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'];
    $refund_bank = trim($_POST['refund_bank']);
    $refund_account = trim($_POST['refund_account']);

    try {
      // อัปเดตสถานะการจองในตาราง bookings ให้เป็น 'cancelled' 
        $stmtBooking = $pdo->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE booking_id = ?");
        $stmtBooking->execute([$booking_id]);

      // อัปเดตตาราง payments ปรับสถานะเป็น 'rejected' 
        $stmtPayment = $pdo->prepare("UPDATE payments SET payment_status = 'rejected', refund_bank = ?, refund_account = ? WHERE booking_id = ?");
        $stmtPayment->execute([$refund_bank, $refund_account, $booking_id]);

      // แสดงข้อความแจ้งเตือนสำเร็จ และส่งผู้ใช้กลับไปยังส่วนประวัติการจองในหน้าแรก
        echo "<script>alert('ยกเลิกการจองและบันทึกข้อมูลขอคืนเงินเรียบร้อยแล้ว'); window.location.href='index.php#history';</script>";
        exit();
    } catch (PDOException $e) {
        // 7.3 จัดการกรณีเกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล แสดง Error และย้อนกลับไปหน้าเดิมv
        echo "<script>alert('เกิดข้อผิดพลาด: " . $e->getMessage() . "'); window.history.back();</script>";
    }
}
?>