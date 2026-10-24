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
        $addon_name = trim($_POST['addon_name']);
        $price_per_booking = floatval($_POST['price_per_booking']);

        $stmt = $pdo->prepare("INSERT INTO addons (addon_name, price_per_booking) VALUES (?, ?)");
        $stmt->execute([$addon_name, $price_per_booking]);

        echo "<script>alert('เพิ่มอุปกรณ์เสริมสำเร็จ!'); window.location.href='index.php#booking';</script>";
        exit();
    } 
    elseif ($action === 'delete') {
        $addon_id = intval($_GET['id']);
        $stmt = $pdo->prepare("DELETE FROM addons WHERE addon_id = ?");
        $stmt->execute([$addon_id]);

        echo "<script>alert('ลบอุปกรณ์เสริมเรียบร้อยแล้ว'); window.location.href='index.php#booking';</script>";
        exit();
    }
} catch (PDOException $e) {
    echo "<script>alert('เกิดข้อผิดพลาด: " . addslashes($e->getMessage()) . "'); window.location.href='index.php#booking';</script>";
    exit();
}
?>