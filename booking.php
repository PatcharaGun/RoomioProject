<!-- 5. แบบฟอร์มยืนยันการจองและแนบหลักฐานการโอนเงิน  -->
<form action="booking_process.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="room_id" value="1">
    <input type="hidden" name="total_price" value="700">
   
    <!-- 5.1 ช่องสำหรับเลือกไฟล์และอัปโหลดภาพสลิปโอนเงิน -->
    <div class="mb-3">
        <label class="form-label fw-semibold">อัปโหลดสลิปโอนเงิน (Payment Slip)</label>
        <input type="file" name="slip_image" class="form-control" accept="image/*" required>
        <div class="form-text">รองรับไฟล์ภาพ .jpg, .png เท่านั้น</div>
    </div>

    <!-- 5.2 ปุ่มกดยืนยันการจองเพื่อส่งข้อมูลไปยังไฟล์ booking_process.php -->
    <button type="submit" class="btn btn-gold w-100">ยืนยันการจองและส่งสลิป</button>
</form>