<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: chon_tai_khoan.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt_current_user = $conn->prepare("SELECT id, tennv, email, role FROM nhanvien WHERE id = ? AND role = 'employee' LIMIT 1");
$stmt_current_user->bind_param('i', $user_id);
$stmt_current_user->execute();
$result_current_user = $stmt_current_user->get_result();
$current_user = $result_current_user->fetch_assoc();
$stmt_current_user->close();

if (!$current_user) {
    session_unset();
    session_destroy();
    header('Location: chon_tai_khoan.php');
    exit;
}
