<?php
// ============================================================
// logout.php
// ============================================================
require_once 'session.php';
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
?>