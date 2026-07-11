<?php
require_once 'auth.php';
require_once 'meeting_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    stop_with_alert('Yêu cầu không hợp lệ!', 'lich_hop.php');
}

$cuochop_id = (int)($_POST['cuochop_id'] ?? 0);
$phanhoi = $_POST['phanhoi'] ?? '';
$phanhoi_hop_le = ['Đồng ý', 'Từ chối'];

if ($cuochop_id <= 0 || !in_array($phanhoi, $phanhoi_hop_le, true)) {
    stop_with_alert('Dữ liệu phản hồi không hợp lệ!', 'lich_hop.php');
}

$check_stmt = $conn->prepare(
    "SELECT c.nguoitao_id, c.trangthai, c.thoigian_ketthuc
     FROM chitiet_thamgia ct
     JOIN cuochop c ON c.id = ct.cuochop_id
     WHERE ct.cuochop_id = ?
       AND ct.nhanvien_id = ?
     LIMIT 1"
);
$check_stmt->bind_param('ii', $cuochop_id, $user_id);
$check_stmt->execute();
$cuochop = $check_stmt->get_result()->fetch_assoc();
$check_stmt->close();

if (!$cuochop) {
    stop_with_alert('Bạn không nằm trong danh sách được mời của cuộc họp này!', 'lich_hop.php');
}
if ((int)$cuochop['nguoitao_id'] === $user_id) {
    stop_with_alert('Người tổ chức không cần phản hồi lời mời!', "chitiet_cuochop.php?id=$cuochop_id");
}
if ($cuochop['trangthai'] === 'Đã hủy') {
    stop_with_alert('Cuộc họp đã bị hủy nên không thể phản hồi!', "chitiet_cuochop.php?id=$cuochop_id");
}
if (strtotime($cuochop['thoigian_ketthuc']) <= time()) {
    stop_with_alert('Cuộc họp đã kết thúc nên không thể thay đổi phản hồi!', "chitiet_cuochop.php?id=$cuochop_id");
}

$stmt = $conn->prepare(
    'UPDATE chitiet_thamgia SET trangthai_phanhoi = ? WHERE cuochop_id = ? AND nhanvien_id = ?'
);
$stmt->bind_param('sii', $phanhoi, $cuochop_id, $user_id);
$stmt->execute();
$updated = $stmt->affected_rows >= 0;
$stmt->close();

if (!$updated) {
    stop_with_alert('Không thể cập nhật phản hồi!', "chitiet_cuochop.php?id=$cuochop_id");
}

$message = $phanhoi === 'Đồng ý'
    ? 'Bạn đã đồng ý tham gia cuộc họp!'
    : 'Bạn đã từ chối lời mời họp!';
stop_with_alert($message, "chitiet_cuochop.php?id=$cuochop_id");
