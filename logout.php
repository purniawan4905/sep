<?php
session_start();
session_unset();
session_destroy();

// Optional: header agar tidak cache halaman terakhir
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

header('Location: /pencatatansep/login.php');
exit;
?>