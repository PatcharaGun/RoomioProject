<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user_id = $_SESSION['user_id'] ?? 1;
        $room_id = $_POST['room_id'] ?? 1;
        $booking_date = $_POST['booking_date'] ?? date('Y-m-d');
        $start_hour = intval($_POST['start_hour']);
        $end_hour = intval($_POST['end_hour']);
        $total_price = floatval($_POST['total_price']);

        if ($start_hour >= $end_hour) {
            throw new Exception("เวลาเริ่มต้นต้องน้อยกว่าเวลาสิ้นสุด");
        }

        $total_hours = $end_hour - $start_hour;

        // ตรวจสอบการจองเวลาทับซ้อน (Overlap Check)
        $checkStmt = $pdo->prepare("
            SELECT COUNT(*) FROM bookings 
            WHERE room_id = ? AND booking_date = ? AND booking_status != 'cancelled'
            AND NOT (end_hour <= ? OR start_hour >= ?)
        ");
        $checkStmt->execute([$room_id, $booking_date, $start_hour, $end_hour]);
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception("ขออภัย! ช่วงเวลาดังกล่าวถูกจองไปแล้ว กรุณาเลือกเวลาอื่น");
        }

        if (isset($_FILES['slip_image']) && $_FILES['slip_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['slip_image']['tmp_name'];
            $fileName = $_FILES['slip_image']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($fileExtension, $allowedExtensions)) {
                throw new Exception("รองรับเฉพาะไฟล์รูปภาพ (jpg, jpeg, png, webp) เท่านั้น");
            }

            $newFileName = 'slip_' . uniqid() . '.' . $fileExtension;
            $uploadFileDir = 'uploads/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $stmtBooking = $pdo->prepare("INSERT INTO bookings (user_id, room_id, booking_date, start_hour, end_hour, total_hours, total_price, booking_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmtBooking->execute([$user_id, $room_id, $booking_date, $start_hour, $end_hour, $total_hours, $total_price]);
                $booking_id = $pdo->lastInsertId();

                $stmtPayment = $pdo->prepare("INSERT INTO payments (booking_id, slip_image, amount, payment_status) VALUES (?, ?, ?, 'pending_verification')");
                $stmtPayment->execute([$booking_id, $newFileName, $total_price]);

                echo "<script>alert('จองห้องรายชั่วโมงสำเร็จ!'); window.location.href='index.php#history';</script>";
                exit();
            } else {
                throw new Exception("ไม่สามารถอัปโหลดไฟล์สลิปได้");
            }
        } else {
            throw new Exception("กรุณาแนบไฟล์สลิปโอนเงิน");
        }
    } catch (Exception $e) {
        echo "<script>alert('" . $e->getMessage() . "'); window.history.back();</script>";
    }
}
?>