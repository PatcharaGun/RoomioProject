<?php
session_start();
require_once 'db.php';

$rooms = $pdo->query("SELECT * FROM rooms WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
$addons = $pdo->query("SELECT * FROM addons")->fetchAll(PDO::FETCH_ASSOC);

$all_bookings = $pdo->query("SELECT room_id, booking_date, start_hour, end_hour FROM bookings WHERE booking_status != 'cancelled'")->fetchAll(PDO::FETCH_ASSOC);
$schedule_data = [];
foreach($all_bookings as $ab) {
    $schedule_data[$ab['room_id']][$ab['booking_date']][] = [$ab['start_hour'], $ab['end_hour']];
}

$user_bookings = [];
if (isset($_SESSION['user_id'])) {
    $stmtHistory = $pdo->prepare("
        SELECT b.*, r.room_name, p.payment_status, p.refund_bank, p.refund_account 
        FROM bookings b 
        JOIN rooms r ON b.room_id = r.room_id 
        LEFT JOIN payments p ON b.booking_id = p.booking_id 
        WHERE b.user_id = ? 
        ORDER BY b.created_at DESC
    ");
    $stmtHistory->execute([$_SESSION['user_id']]);
    $user_bookings = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);
}
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roomio's Studio Rental | จองห้องซ้อมรายชั่วโมง</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-color: #FDFCF7;
            --card-bg: #FFFFFF;
            --primary-gold: #D4AF37;
            --light-gold: #F3EAB3;
            --dark-gold: #AA8C2C;
            --text-dark: #2C2C2C;
            --text-muted: #6C757D;
            --border-color: #EFECE6;
        }
        body { font-family: 'Prompt', sans-serif; background-color: var(--bg-color); color: var(--text-dark); }
        .navbar { background-color: #FFFFFF; box-shadow: 0 2px 15px rgba(212, 175, 55, 0.1); border-bottom: 2px solid var(--light-gold); }
        .navbar-brand { font-weight: 700; color: var(--dark-gold) !important; }
        .nav-link { font-weight: 500; color: var(--text-dark) !important; }
        .nav-link:hover { color: var(--primary-gold) !important; }
        .btn-gold { background: linear-gradient(135deg, #D4AF37 0%, #AA8C2C 100%); color: #FFFFFF; border: none; font-weight: 500; }
        .btn-gold:hover { background: linear-gradient(135deg, #C5A028, #967822); color: #FFFFFF; }
        .btn-outline-gold { border: 2px solid var(--primary-gold); color: var(--dark-gold); font-weight: 500; background: transparent; }
        .btn-outline-gold:hover { background-color: var(--primary-gold); color: #FFFFFF; }
        .studio-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; transition: transform 0.2s; }
        .studio-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(212,175,55,0.15); }
        .hero-section { background: linear-gradient(135deg, #FFFDF9 0%, #F6F1E3 100%); border-bottom: 1px solid var(--light-gold); padding: 50px 0; margin-bottom: 30px; }
        .section-title { font-weight: 700; color: #2C2C2C; position: relative; }
        .slot-box { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; border-radius: 6px; margin: 2px; }
        .slot-free { background: #198754; color: #fff; }
        .slot-booked { background: #dc3545; color: #fff; }
    </style>
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fa-solid fa-music text-warning me-2"></i>Roomio's Studio Rental</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"> 
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-3">
                    <li class="nav-item">
                        <button class="btn btn-outline-warning btn-sm rounded-pill px-3 text-dark fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#calendarModal">
                            <i class="fa-solid fa-calendar-days me-1 text-warning"></i> ตารางสถานะการจองใน 24 ชม.
                        </button>
                    </li>
                    <?php if($is_admin): ?>
                        <li class="nav-item"><a class="nav-link text-danger fw-bold" href="admin_dashboard.php"><i class="fa-solid fa-gauge me-1"></i> จัดการสลิป/คิว</a></li>
                    <?php endif; ?>
                    
                    <?php if(isset($_SESSION['full_name'])): ?>
                        <li class="nav-item fw-semibold text-warning">สวัสดี, <?php echo htmlspecialchars($_SESSION['full_name']); ?></li>
                        <li class="nav-item"><a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">ออกจากระบบ</a></li>
                    <?php else: ?>
                        <li class="nav-item"><button class="btn btn-gold btn-sm rounded-pill px-3 py-2" data-bs-toggle="modal" data-bs-target="#loginModal">เข้าสู่ระบบ</button></li>
                        <li class="nav-item"><a href="register.php" class="btn btn-outline-gold btn-sm rounded-pill px-3 py-2">สมัครสมาชิก</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero-section text-center">
        <div class="container">
            <span class="badge mb-3 px-3 py-2 rounded-pill fw-semibold" style="background-color: var(--light-gold) !important; color: var(--dark-gold) !important;">
                <i class="fa-solid fa-wand-magic-sparkles me-1"></i> ระบบจองห้องซ้อมดนตรีและเต้นรายชั่วโมงแบบเรียลไทม์
            </span>
            <h1 class="display-5 fw-bold mb-3">ปลดปล่อยพลังดนตรีและสเต็ปของคุณ</h1>
            <p class="text-muted col-lg-8 mx-auto lead fs-6">เลือกห้องซ้อม 6 สไตล์ เลือกระยะเวลาตามต้องการ พร้อมระบบตรวจสอบสลิปและคืนเงิน</p>
        </div>
    </header>

    <!-- Modal ตารางสถานะ 24 ชั่วโมง -->
    <div class="modal fade" id="calendarModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content p-4 rounded-4 shadow border-0">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">
                    <h4 class="fw-bold text-dark mb-0">📅 ตารางสถานะคิวรายชั่วโมง (24 ชั่วโมง)</h4>
                    <div class="d-flex align-items-center gap-2">
                        <label class="form-label fw-semibold mb-0 small">เลือกวันที่ต้องการดู:</label>
                        <input type="date" id="checkDateInput" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>" onchange="updateScheduleTable()">
                    </div>
                </div>
                <p class="small text-muted mb-3"><span class="badge bg-success">เขียว</span> = ว่าง, <span class="badge bg-danger">แดง</span> = ถูกจองแล้ว</p>
                <div class="table-responsive">
                    <table class="table table-bordered text-center align-middle small">
                        <thead class="table-dark">
                            <tr>
                                <th style="min-width: 180px;">ห้องซ้อม</th>
                                <?php for($h=0; $h<24; $h++): ?>
                                    <th style="min-width: 45px;"><?php echo sprintf("%02d", $h); ?></th>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody id="scheduleTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal เพิ่มห้องซ้อมใหม่ (สำหรับ Admin) -->
    <?php if($is_admin): ?>
    <div class="modal fade text-dark" id="addRoomModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="admin_room_action.php" method="POST" class="modal-content p-4 rounded-4 shadow border-0">
                <input type="hidden" name="action" value="add">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-warning mb-0"><i class="fa-solid fa-plus-circle me-1"></i> เพิ่มห้องซ้อมใหม่</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label fw-semibold small">ชื่อห้องซ้อม</label>
                    <input type="text" name="room_name" class="form-control" placeholder="เช่น Room G: Studio Pro" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3 text-start">
                        <label class="form-label fw-semibold small">ประเภท</label>
                        <select name="room_type" class="form-select">
                            <option value="music">Music (ดนตรี)</option>
                            <option value="dance">Dance (เต้น)</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3 text-start">
                        <label class="form-label fw-semibold small">ความจุ (คน)</label>
                        <input type="number" name="capacity" class="form-control" value="5" required>
                    </div>
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label fw-semibold small">ราคาต่อชั่วโมง (บาท)</label>
                    <input type="number" step="0.01" name="hourly_rate" class="form-control" placeholder="350.00" required>
                </div>
                <div class="mb-4 text-start">
                    <label class="form-label fw-semibold small">รายละเอียดห้อง</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="รายละเอียดอุปกรณ์และจุดเด่น"></textarea>
                </div>
                <button type="submit" class="btn btn-gold w-100 rounded-pill py-2 fw-semibold">บันทึกเพิ่มห้อง</button>
            </form>
        </div>
    </div>

    <!-- Modal เพิ่ม Add-on ใหม่ (สำหรับ Admin) -->
    <div class="modal fade text-dark" id="addAddonModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="admin_addon_action.php" method="POST" class="modal-content p-4 rounded-4 shadow border-0">
                <input type="hidden" name="action" value="add">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-warning mb-0"><i class="fa-solid fa-puzzle-piece me-1"></i> เพิ่มอุปกรณ์เสริมใหม่</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label fw-semibold small">ชื่ออุปกรณ์เสริม</label>
                    <input type="text" name="addon_name" class="form-control" placeholder="เช่น กีตาร์โปร่งเสริม" required>
                </div>
                <div class="mb-4 text-start">
                    <label class="form-label fw-semibold small">ราคาต่อการจอง (บาท)</label>
                    <input type="number" step="0.01" name="price_per_booking" class="form-control" placeholder="100.00" required>
                </div>
                <button type="submit" class="btn btn-gold w-100 rounded-pill py-2 fw-semibold">บันทึกเพิ่มอุปกรณ์</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <main class="container">
        <!-- 1. Room Catalog (แยกรูปแต่ละห้องอิสระ - รูปด้านบน / Info ด้านล่าง) -->
        <section id="catalog" class="mb-5">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <h2 class="section-title mb-0">ห้องซ้อมทั้งหมดในสตูดิโอ (เรทรายชั่วโมง)</h2>
                <?php if($is_admin): ?>
                    <button class="btn btn-warning btn-sm rounded-pill px-3 py-2 fw-semibold shadow-sm text-dark" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                        <i class="fa-solid fa-circle-plus me-1"></i> เพิ่มห้องซ้อมใหม่
                    </button>
                <?php endif; ?>
            </div>

            <div class="row g-4">
                <?php foreach($rooms as $index => $r): 
                    // กำหนด src แยกตามห้อง (คุณสามารถเปลี่ยนลิงก์รูปภาพในนี้ได้เลย)
                    $room_images = [
                        0 => "https://i.pinimg.com/736x/d0/98/64/d09864e4c378481fafb8f64ac3ef5f22.jpg", // รูป Room A
                        1 => "be.jpg", // รูป Room B
                        2 => "https://i.pinimg.com/1200x/f5/78/22/f57822c2e56fa4190cd09153fdaee825.jpg", // รูป Room C
                        3 => "https://i.pinimg.com/736x/d0/98/64/d09864e4c378481fafb8f64ac3ef5f22.jpg", // รูป Room D
                        4 => "be.jpg", // รูป Room E
                        5 => "https://i.pinimg.com/1200x/f5/78/22/f57822c2e56fa4190cd09153fdaee825.jpg"  // รูป Room F
                    ];
                    $current_room_img = $room_images[$index] ?? "";
                ?>
                <div class="col-md-4">
                    <div class="studio-card h-100 d-flex flex-column justify-content-between position-relative">
                        <?php if($is_admin): ?>
                            <div class="position-absolute top-0 end-0 p-3 z-3">
                                <button class="btn btn-sm btn-outline-primary rounded-circle me-1 bg-white" data-bs-toggle="modal" data-bs-target="#editRoomModal<?php echo $r['room_id']; ?>" title="แก้ไขห้อง"><i class="fa-solid fa-pen"></i></button>
                                <a href="admin_room_action.php?action=delete&id=<?php echo $r['room_id']; ?>" class="btn btn-sm btn-outline-danger rounded-circle bg-white" onclick="return confirm('ยืนยันการลบห้องนี้?');" title="ลบห้อง"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        <?php endif; ?>

                        <!-- รูปภาพห้องอยู่ด้านบน (แยก src แต่ละห้อง) -->
                        <div>
                            <div class="w-100 h-48 bg-light d-flex align-items-center justify-content-center overflow-hidden">
                                <img src="<?php echo $current_room_img; ?>" alt="<?php echo htmlspecialchars($r['room_name']); ?>" class="w-100 h-100 object-fit-cover">
                            </div>
                            
                            <!-- ข้อมูลอยู่ด้านล่าง -->
                            <div class="p-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-<?php echo ($r['room_type']=='music')?'primary':'info'; ?> text-white"><?php echo strtoupper($r['room_type']); ?></span>
                                </div>
                                <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($r['room_name']); ?></h4>
                                <p class="text-muted small mb-3"><?php echo htmlspecialchars($r['description']); ?></p>
                            </div>
                        </div>

                        <div class="p-4 pt-0">
                            <div class="fs-5 fw-bold text-dark mb-3">
                                ฿<?php echo number_format($r['hourly_rate'], 2); ?> 
                                <small class="text-muted fs-6">/ ชั่วโมง</small>
                                <span class="text-muted fs-6 fw-normal ms-1">(ความจุ <?php echo $r['capacity']; ?> คน)</span>
                            </div>
                            <button class="btn btn-outline-gold btn-sm rounded-pill w-100" onclick="selectRoom('<?php echo $r['room_name']; ?>', <?php echo $r['hourly_rate']; ?>, <?php echo $r['room_id']; ?>)">เลือกห้องนี้</button>
                        </div>
                    </div>
                </div>

                <!-- Modal แก้ไขห้อง (สำหรับ Admin แต่ละห้อง) -->
                <?php if($is_admin): ?>
                <div class="modal fade text-dark" id="editRoomModal<?php echo $r['room_id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <form action="admin_room_action.php" method="POST" class="modal-content p-4 rounded-4 shadow border-0">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="room_id" value="<?php echo $r['room_id']; ?>">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold text-primary mb-0"><i class="fa-solid fa-pen-to-square me-1"></i> แก้ไขข้อมูล: <?php echo $r['room_name']; ?></h5>
                                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="mb-3 text-start">
                                <label class="form-label fw-semibold small">ชื่อห้องซ้อม</label>
                                <input type="text" name="room_name" class="form-control" value="<?php echo htmlspecialchars($r['room_name']); ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3 text-start">
                                    <label class="form-label fw-semibold small">ประเภท</label>
                                    <select name="room_type" class="form-select">
                                        <option value="music" <?php echo ($r['room_type']=='music')?'selected':''; ?>>Music</option>
                                        <option value="dance" <?php echo ($r['room_type']=='dance')?'selected':''; ?>>Dance</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3 text-start">
                                    <label class="form-label fw-semibold small">ความจุ (คน)</label>
                                    <input type="number" name="capacity" class="form-control" value="<?php echo $r['capacity']; ?>" required>
                                </div>
                            </div>
                            <div class="mb-3 text-start">
                                <label class="form-label fw-semibold small">ราคาต่อชั่วโมง (บาท)</label>
                                <input type="number" step="0.01" name="hourly_rate" class="form-control" value="<?php echo $r['hourly_rate']; ?>" required>
                            </div>
                            <div class="mb-4 text-start">
                                <label class="form-label fw-semibold small">รายละเอียดห้อง</label>
                                <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($r['description']); ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold">บันทึกการแก้ไข</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- 2. Booking Form & Add-ons -->
        <section id="booking" class="mb-5 pt-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <h2 class="section-title mb-0">ระบบจองห้องซ้อมรายชั่วโมง</h2>
                <?php if($is_admin): ?>
                    <button class="btn btn-warning btn-sm rounded-pill px-3 py-2 fw-semibold shadow-sm text-dark" data-bs-toggle="modal" data-bs-target="#addAddonModal">
                        <i class="fa-solid fa-puzzle-piece me-1"></i> เพิ่มอุปกรณ์เสริมใหม่
                    </button>
                <?php endif; ?>
            </div>

            <div class="studio-card p-4 p-md-5">
                <form action="booking_process.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="room_id" id="room_id_hidden" value="<?php echo $rooms[0]['room_id'] ?? 1; ?>">
                    <input type="hidden" name="total_price" id="total_price_hidden" value="<?php echo ($rooms[0]['hourly_rate'] ?? 350); ?>">

                    <div class="row g-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">1. ห้องที่เลือก</label>
                            <input type="text" id="selectedRoomInput" class="form-control bg-light" value="<?php echo $rooms[0]['room_name'] ?? 'Room A'; ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">2. วันที่ต้องการใช้บริการ</label>
                            <input type="date" name="booking_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">เวลาเริ่มต้น</label>
                            <select name="start_hour" id="start_hour" class="form-select" onchange="calculateTotal()">
                                <?php for($h=0; $h<23; $h++): ?>
                                    <option value="<?php echo $h; ?>"><?php echo sprintf("%02d:00", $h); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">เวลาสิ้นสุด</label>
                            <select name="end_hour" id="end_hour" class="form-select" onchange="calculateTotal()">
                                <?php for($h=1; $h<=24; $h++): ?>
                                    <option value="<?php echo $h; ?>" <?php echo ($h==1)?'selected':''; ?>><?php echo sprintf("%02d:00", $h); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Add-on Items (แยกรูปแต่ละชิ้นอิสระ - อยู่ด้านข้างในกล่อง) -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">3. เลือกอุปกรณ์เสริม (Add-on Items)</label>
                            <div class="row g-3">
                                <?php foreach($addons as $index => $ad): 
                                    // กำหนด src แยกตาม Add-on (เปลี่ยนลิงก์รูปภาพในนี้ได้เลย)
                                    $addon_images = [
                                        0 => "https://fortismusic.com/wp-content/uploads/2026/08/V-TECH-JR360-pp-website.jpg", // กระเดื่องคู่
                                        1 => "https://i.pinimg.com/1200x/81/42/4e/81424ee5f643a8f7a57447edd779e2a7.jpg", // ไมค์ลอยเสริม
                                        2 => "https://i.pinimg.com/1200x/98/09/0a/98090a34f07bc58eadb68061c2209361.jpg",         // ขาตั้งกล้องมือถือ/ไฟไลฟ์สตรีม
                                        3 => "https://i.pinimg.com/1200x/77/99/f2/7799f2e2edc79e4528ebcecff039052c.jpg",// กีตาร์ไฟฟ้าเสริม
                                        4 => "https://i.pinimg.com/736x/0d/90/57/0d9057f10fab56f41eba5a6b30fcfca6.jpg",      // ไฟสตูดิโอเปลี่ยนสี RGB
                                        5 => "images/addon-test.jpg"            // test
                                    ];
                                    $current_addon_img = $addon_images[$index] ?? "";
                                ?>
                                <div class="col-md-4">
                                    <label class="d-flex align-items-center p-3 border rounded-3 bg-white position-relative shadow-sm hover:border-warning transition cursor-pointer h-100 w-100" style="cursor: pointer;">
                                        <!-- รูปอุปกรณ์อยู่ด้านข้าง ซ้าย (แยก src แต่ละชิ้น) -->
                                        <div class="bg-light rounded-2 flex-shrink-0 d-flex align-items-center justify-content-center overflow-hidden me-3" style="width: 60px; height: 60px;">
                                            <img src="<?php echo $current_addon_img; ?>" alt="<?php echo htmlspecialchars($ad['addon_name']); ?>" class="w-100 h-100 object-fit-cover">
                                        </div>
                                        
                                        <!-- ข้อมูลอุปกรณ์ -->
                                        <div class="flex-grow-1 pe-3">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="text-sm font-medium text-dark fw-semibold"><?php echo htmlspecialchars($ad['addon_name']); ?></span>
                                                <input class="form-check-input addon-checkbox m-0" type="checkbox" name="addons[]" value="<?php echo $ad['addon_id']; ?>" data-price="<?php echo $ad['price_per_booking']; ?>" id="addon_<?php echo $ad['addon_id']; ?>" onchange="calculateTotal()">
                                            </div>
                                            <div class="text-xs text-warning fw-bold mt-1">+฿<?php echo number_format($ad['price_per_booking'], 2); ?></div>
                                        </div>

                                        <?php if($is_admin): ?>
                                            <a href="admin_addon_action.php?action=delete&id=<?php echo $ad['addon_id']; ?>" class="text-danger position-absolute top-0 end-0 p-2 small z-3" onclick="return confirm('ลบอุปกรณ์เสริมนี้?');" title="ลบอุปกรณ์"><i class="fa-solid fa-xmark"></i></a>
                                        <?php endif; ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Live Calculation & Upload -->
                        <div class="col-12 bg-light p-4 rounded border">
                            <div class="row align-items-center">
                                <div class="col-md-7">
                                    <h5 class="fw-bold mb-1"><i class="fa-solid fa-receipt text-warning me-2"></i>สรุปยอดชำระเงินอัตโนมัติ</h5>
                                </div>
                                <div class="col-md-5 text-md-end">
                                    <span class="fs-3 fw-bold text-dark" id="totalPriceDisplay">฿<?php echo number_format($rooms[0]['hourly_rate'] ?? 350, 2); ?></span> <span class="text-muted">บาท</span>
                                </div>
                            </div>
                            <hr>
                            <div class="row g-3 align-items-center">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">4. อัปโหลดสลิปโอนเงิน (Payment Slip)</label>
                                    <input type="file" name="slip_image" class="form-control" accept="image/*" required>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <button type="submit" class="btn btn-gold btn-lg rounded-pill px-5 py-3 w-100 shadow-sm">
                                        <i class="fa-solid fa-check-circle me-2"></i> ยืนยันการจองห้องซ้อม
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <!-- 3. Customer History -->
        <section id="history" class="mb-5">
            <h2 class="section-title mb-4">ประวัติการจองของฉัน (My Booking History)</h2>
            <div class="studio-card p-4">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>รหัส</th>
                                <th>ห้องซ้อม</th>
                                <th>วันที่</th>
                                <th>เวลา</th>
                                <th>ยอดรวม</th>
                                <th>สถานะ</th>
                                <th>จัดการ / ขอคืนเงิน</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!isset($_SESSION['user_id'])): ?>
                                <tr><td colspan="7" class="text-center text-muted py-3">กรุณาเข้าสู่ระบบเพื่อดูประวัติการจอง</td></tr>
                            <?php elseif(empty($user_bookings)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-3">ยังไม่มีประวัติการจองในระบบ</td></tr>
                            <?php else: ?>
                                <?php foreach($user_bookings as $b): ?>
                                <tr>
                                    <td class="fw-bold">#BK-<?php echo $b['booking_id']; ?></td>
                                    <td><?php echo htmlspecialchars($b['room_name']); ?></td>
                                    <td><?php echo $b['booking_date']; ?></td>
                                    <td><?php echo sprintf("%02d:00 - %02d:00", $b['start_hour'], $b['end_hour']); ?> (<?php echo $b['total_hours']; ?> ชม.)</td>
                                    <td>฿<?php echo number_format($b['total_price'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($b['booking_status']=='confirmed')?'success':(($b['booking_status']=='cancelled')?'danger':'warning text-dark'); ?>">
                                            <?php echo $b['booking_status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($b['booking_status'] === 'pending'): ?>
                                            <button class="btn btn-outline-danger btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $b['booking_id']; ?>">
                                                ยกเลิก / ขอคืนเงิน
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <!-- Modals สำหรับขอยกเลิกและคืนเงิน -->
    <?php if(!empty($user_bookings)): ?>
        <?php foreach($user_bookings as $b): ?>
            <?php if($b['booking_status'] === 'pending'): ?>
            <div class="modal fade text-dark" id="cancelModal<?php echo $b['booking_id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form action="cancel_booking.php" method="POST" class="modal-content p-4 border-0 rounded-4 shadow">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="fw-bold text-danger mb-0">ยกเลิกการจองและแจ้งคืนเงิน</h5>
                            <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <p class="small text-muted mb-3">กรุณากรอกข้อมูลบัญชีธนาคารสำหรับรับเงินโอนคืน (ยอด ฿<?php echo number_format($b['total_price'], 2); ?>)</p>
                        
                        <div class="mb-3 text-start">
                            <label class="form-label fw-semibold small">ชื่อธนาคาร</label>
                            <input type="text" name="refund_bank" class="form-control" placeholder="เช่น กสิกรไทย, ไทยพาณิชย์" required>
                        </div>
                        <div class="mb-4 text-start">
                            <label class="form-label fw-semibold small">เลขที่บัญชี</label>
                            <input type="text" name="refund_account" class="form-control" placeholder="เลขที่บัญชีธนาคาร" required>
                        </div>
                        
                        <input type="hidden" name="booking_id" value="<?php echo $b['booking_id']; ?>">
                        <button type="submit" class="btn btn-danger w-100 rounded-pill py-2 fw-semibold shadow-sm">ยืนยันการยกเลิกคิว</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 p-4 border-0 shadow">
                <div class="modal-header border-0">
                    <h4 class="modal-title fw-bold">เข้าสู่ระบบ</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="login_process.php" method="POST">
                        <div class="mb-3"><label class="form-label">อีเมล</label><input type="email" name="email" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">รหัสผ่าน</label><input type="password" name="password" class="form-control" required></div>
                        <button type="submit" class="btn btn-gold w-100 rounded-pill py-2.5 mt-3 fw-semibold">เข้าสู่ระบบ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const scheduleData = <?php echo json_encode($schedule_data); ?>;
        const roomsData = <?php echo json_encode($rooms); ?>;

        document.addEventListener("DOMContentLoaded", function() {
            updateScheduleTable();
        });

        function updateScheduleTable() {
            const selectedDate = document.getElementById('checkDateInput').value;
            const tbody = document.getElementById('scheduleTableBody');
            tbody.innerHTML = '';

            roomsData.forEach(room => {
                let tr = document.createElement('tr');
                let tdName = document.createElement('td');
                tdName.className = 'fw-bold text-start text-nowrap';
                tdName.innerText = room.room_name;
                tr.appendChild(tdName);

                let bookedHours = [];
                if (scheduleData[room.room_id] && scheduleData[room.room_id][selectedDate]) {
                    scheduleData[room.room_id][selectedDate].forEach(range => {
                        for(let h = range[0]; h < range[1]; h++) {
                            bookedHours.push(h);
                        }
                    });
                }

                for(let h = 0; h < 24; h++) {
                    let td = document.createElement('td');
                    let span = document.createElement('span');
                    let isBooked = bookedHours.includes(h);
                    
                    span.className = `slot-box ${isBooked ? 'slot-booked' : 'slot-free'}`;
                    span.innerText = h;
                    span.title = `${String(h).padStart(2, '0')}:00 - ${isBooked ? 'ถูกจองแล้ว' : 'ว่าง'}`;
                    
                    td.appendChild(span);
                    tr.appendChild(td);
                }
                tbody.appendChild(tr);
            });
        }

        let currentHourlyRate = <?php echo $rooms[0]['hourly_rate'] ?? 350; ?>;

        function selectRoom(roomName, hourlyRate, roomId) {
            document.getElementById('selectedRoomInput').value = roomName;
            document.getElementById('room_id_hidden').value = roomId;
            currentHourlyRate = hourlyRate;
            calculateTotal();
            document.getElementById('booking').scrollIntoView({ behavior: 'smooth' });
        }

        function calculateTotal() {
            let start = parseInt(document.getElementById('start_hour').value);
            let end = parseInt(document.getElementById('end_hour').value);
            if(end <= start) {
                alert('เวลาสิ้นสุดต้องมากกว่าเวลาเริ่มต้น');
                document.getElementById('end_hour').value = start + 1;
                end = start + 1;
            }
            let hours = end - start;
            let total = currentHourlyRate * hours;

            document.querySelectorAll('.addon-checkbox:checked').forEach(cb => {
                total += parseFloat(cb.getAttribute('data-price'));
            });
            document.getElementById('totalPriceDisplay').innerText = '฿' + total.toFixed(2);
            document.getElementById('total_price_hidden').value = total;
        }
    </script>
</body>
</html>