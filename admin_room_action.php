<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('เฉพาะผู้ดูแลระบบเท่านั้น!'); window.location.href='index.php';</script>";
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($action === 'add') {
        $room_name = trim($_POST['room_name']);
        $room_type = $_POST['room_type'];
        $capacity = intval($_POST['capacity']);
        $hourly_rate = floatval($_POST['hourly_rate']);
        $description = trim($_POST['description']);

        $stmt = $pdo->prepare("INSERT INTO rooms (room_name, room_type, capacity, hourly_rate, description, status) VALUES (?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$room_name, $room_type, $capacity, $hourly_rate, $description]);
        
        echo "<script>alert('เพิ่มห้องซ้อมสำเร็จ!'); window.location.href='index.php#catalog';</script>";
        exit();
    } 
    elseif ($action === 'edit') {
        $room_id = intval($_POST['room_id']);
        $room_name = trim($_POST['room_name']);
        $room_type = $_POST['room_type'];
        $capacity = intval($_POST['capacity']);
        $hourly_rate = floatval($_POST['hourly_rate']);
        $description = trim($_POST['description']);

        $stmt = $pdo->prepare("UPDATE rooms SET room_name = ?, room_type = ?, capacity = ?, hourly_rate = ?, description = ? WHERE room_id = ?");
        $stmt->execute([$room_name, $room_type, $capacity, $hourly_rate, $description, $room_id]);

        echo "<script>alert('แก้ไขข้อมูลห้องซ้อมสำเร็จ!'); window.location.href='index.php#catalog';</script>";
        exit();
    }
    elseif ($action === 'delete') {
        $room_id = intval($_GET['id']);
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE room_id = ?");
        $stmt->execute([$room_id]);

        echo "<script>alert('ลบห้องซ้อมเรียบร้อยแล้ว'); window.location.href='index.php#catalog';</script>";
        exit();
    }
} catch (PDOException $e) {
    echo "<script>alert('เกิดข้อผิดพลาด: " . addslashes($e->getMessage()) . "'); window.location.href='index.php#catalog';</script>";
    exit();
}
?>