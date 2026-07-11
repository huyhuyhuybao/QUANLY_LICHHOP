<?php
require_once 'auth.php';
require_once 'meeting_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    stop_with_alert('Yêu cầu hủy cuộc họp không hợp lệ!', 'lich_hop.php');
}

$cuochop_id = (int)($_POST['cuochop_id'] ?? 0);
if ($cuochop_id <= 0) {
    stop_with_alert('Cuộc họp không hợp lệ!', 'lich_hop.php');
}

$stmtCheck = $conn->prepare(
    'SELECT nguoitao_id, thoigian_batdau, trangthai FROM cuochop WHERE id = ? LIMIT 1'
);
$stmtCheck->bind_param('i', $cuochop_id);
$stmtCheck->execute();
$cuochop = $stmtCheck->get_result()->fetch_assoc();
$stmtCheck->close();

if (!$cuochop) {
    stop_with_alert('Cuộc họp không tồn tại!', 'lich_hop.php');
}
if ((int)$cuochop['nguoitao_id'] !== $user_id) {
    stop_with_alert('Bạn không có quyền hủy cuộc họp này!', 'lich_hop.php');
}
if ($cuochop['trangthai'] === 'Đã hủy') {
    stop_with_alert('Cuộc họp này đã bị hủy trước đó!', "chitiet_cuochop.php?id=$cuochop_id");
}
if (strtotime($cuochop['thoigian_batdau']) <= time()) {
    stop_with_alert('Không thể hủy cuộc họp đã bắt đầu hoặc đã hoàn thành!', "chitiet_cuochop.php?id=$cuochop_id");
}

$stmt = $conn->prepare(
    "UPDATE cuochop
     SET trangthai = 'Đã hủy'
     WHERE id = ?
       AND nguoitao_id = ?
       AND thoigian_batdau > NOW()
       AND trangthai <> 'Đã hủy'"
);
$stmt->bind_param('ii', $cuochop_id, $user_id);
$stmt->execute();
$success = $stmt->affected_rows === 1;
$stmt->close();

if ($success) {
    stop_with_alert('Đã hủy cuộc họp thành công!', 'lich_hop.php');
}

stop_with_alert('Không thể hủy cuộc họp. Dữ liệu có thể đã thay đổi, vui lòng thử lại!', "chitiet_cuochop.php?id=$cuochop_id");
