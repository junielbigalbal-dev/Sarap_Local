<?php
session_start();

// Simple admin authentication - redirect to admin folder
$_SESSION['admin_access'] = true;
header("Location: admin/index.php");
exit();
?>
