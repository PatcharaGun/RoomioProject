<?php
// 10. ระบบออกจากระบบ
session_start();
session_destroy();
header("Location: index.php");
exit();
?>