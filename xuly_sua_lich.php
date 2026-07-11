<?php
require_once 'auth.php';
require_once 'meeting_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    stop_with_alert('Truy cập không hợp lệ!', 'lich_hop.php');
}

$cuochop_id = (int)($_POST['cuochop_id'] ?? 0);
$tieude = trim($_POST['tieude'] ?? '');
$phong_id = (int)($_POST['phong_id'] ?? 0);
$noidung = trim($_POST['noidung'] ?? '');
$nguoi_tham_gia_input = (array)($_POST['nguoi_tham_gia'] ?? []);
$batdau_input = $_POST['thoigian_batdau'] ?? '';
$ketthuc_input = $_POST['thoigian_ketthuc'] ?? '';

if ($cuochop_id <= 0 || $tieude === '' || mb_strlen($tieude) > 200 || $phong_id <= 0 || $batdau_input === '' || $ketthuc_input === '') {
    stop_with_alert('Dữ liệu cập nhật không hợp lệ!');
}

$stmtCurrent = $conn->prepare(
    'SELECT id, nguoitao_id, phong_id, thoigian_batdau, thoigian_ketthuc, trangthai
     FROM cuochop
     WHERE id = ?
     LIMIT 1'
);
$stmtCurrent->bind_param('i', $cuochop_id);
$stmtCurrent->execute();
$cuochop = $stmtCurrent->get_result()->fetch_assoc();
$stmtCurrent->close();

if (!$cuochop) {
    stop_with_alert('Cuộc họp không tồn tại!', 'lich_hop.php');
}
if ((int)$cuochop['nguoitao_id'] !== $user_id) {
    stop_with_alert('Bạn không có quyền cập nhật cuộc họp này!', "chitiet_cuochop.php?id=$cuochop_id");
}
if ($cuochop['trangthai'] === 'Đã hủy') {
    stop_with_alert('Không thể sửa cuộc họp đã bị hủy!', "chitiet_cuochop.php?id=$cuochop_id");
}
if (strtotime($cuochop['thoigian_batdau']) <= time()) {
    stop_with_alert('Không thể sửa cuộc họp đã bắt đầu hoặc đã hoàn thành!', "chitiet_cuochop.php?id=$cuochop_id");
}

$creator_id = (int)$cuochop['nguoitao_id'];
$nguoi_tham_gia = normalize_participant_ids($nguoi_tham_gia_input, $creator_id);
if (empty($nguoi_tham_gia)) {
    stop_with_alert('Vui lòng chọn ít nhất một người tham gia khác người tổ chức!');
}

$invalidEmployeeIds = find_invalid_employee_ids($conn, $nguoi_tham_gia);
if (!empty($invalidEmployeeIds)) {
    stop_with_alert('Danh sách người tham gia chứa tài khoản không hợp lệ hoặc không thuộc vai trò Employee!');
}

$start_timestamp = strtotime($batdau_input);
$end_timestamp = strtotime($ketthuc_input);
if ($start_timestamp === false || $end_timestamp === false) {
    stop_with_alert('Thời gian cuộc họp không hợp lệ!');
}
if ($start_timestamp < time()) {
    stop_with_alert('Không được cập nhật thời gian bắt đầu về quá khứ!');
}
if ($start_timestamp >= $end_timestamp) {
    stop_with_alert('Thời gian kết thúc phải lớn hơn thời gian bắt đầu!');
}

$thoigian_batdau = date('Y-m-d H:i:s', $start_timestamp);
$thoigian_ketthuc = date('Y-m-d H:i:s', $end_timestamp);

if (!active_room_exists($conn, $phong_id)) {
    stop_with_alert('Phòng họp không tồn tại hoặc đang ngừng hoạt động/bảo trì!');
}

if (room_has_conflict($conn, $phong_id, $thoigian_batdau, $thoigian_ketthuc, $cuochop_id)) {
    stop_with_alert('Phòng họp đã bị đặt bởi cuộc họp khác trong khoảng thời gian này!');
}

$employeesToCheck = array_merge([$creator_id], $nguoi_tham_gia);
$busyEmployees = find_busy_employees(
    $conn,
    $employeesToCheck,
    $thoigian_batdau,
    $thoigian_ketthuc,
    $cuochop_id
);
if (!empty($busyEmployees)) {
    stop_with_alert(
        "Không thể cập nhật lịch vì các nhân viên sau đang bận:\n" .
        format_busy_employee_message($busyEmployees)
    );
}

$oldResponses = [];
$stmtOldParticipants = $conn->prepare(
    'SELECT nhanvien_id, trangthai_phanhoi FROM chitiet_thamgia WHERE cuochop_id = ?'
);
$stmtOldParticipants->bind_param('i', $cuochop_id);
$stmtOldParticipants->execute();
$resultOldParticipants = $stmtOldParticipants->get_result();
while ($row = $resultOldParticipants->fetch_assoc()) {
    $oldResponses[(int)$row['nhanvien_id']] = $row['trangthai_phanhoi'];
}
$stmtOldParticipants->close();

$scheduleChanged =
    (int)$cuochop['phong_id'] !== $phong_id ||
    date('Y-m-d H:i:s', strtotime($cuochop['thoigian_batdau'])) !== $thoigian_batdau ||
    date('Y-m-d H:i:s', strtotime($cuochop['thoigian_ketthuc'])) !== $thoigian_ketthuc;

$conn->begin_transaction();
try {
    $stmtUpdate = $conn->prepare(
        'UPDATE cuochop
         SET tieude = ?, noidung = ?, thoigian_batdau = ?, thoigian_ketthuc = ?, phong_id = ?, trangthai = \'Sắp diễn ra\'
         WHERE id = ? AND nguoitao_id = ?'
    );
    $stmtUpdate->bind_param(
        'ssssiii',
        $tieude,
        $noidung,
        $thoigian_batdau,
        $thoigian_ketthuc,
        $phong_id,
        $cuochop_id,
        $creator_id
    );
    if (!$stmtUpdate->execute() || $stmtUpdate->affected_rows < 0) {
        throw new RuntimeException($stmtUpdate->error);
    }
    $stmtUpdate->close();

    $stmtDelete = $conn->prepare('DELETE FROM chitiet_thamgia WHERE cuochop_id = ?');
    $stmtDelete->bind_param('i', $cuochop_id);
    if (!$stmtDelete->execute()) {
        throw new RuntimeException($stmtDelete->error);
    }
    $stmtDelete->close();

    $stmtInsert = $conn->prepare(
        'INSERT INTO chitiet_thamgia (cuochop_id, nhanvien_id, trangthai_phanhoi) VALUES (?, ?, ?)'
    );
    foreach ($nguoi_tham_gia as $nhanvien_id) {
        $response = (!$scheduleChanged && isset($oldResponses[$nhanvien_id]))
            ? $oldResponses[$nhanvien_id]
            : 'Chờ xác nhận';

        $stmtInsert->bind_param('iis', $cuochop_id, $nhanvien_id, $response);
        if (!$stmtInsert->execute()) {
            throw new RuntimeException($stmtInsert->error);
        }
    }
    $stmtInsert->close();

    $conn->commit();

    $message = $scheduleChanged
        ? 'Cập nhật cuộc họp thành công! Vì thời gian hoặc phòng đã thay đổi, phản hồi của người tham gia được đặt lại thành Chờ xác nhận.'
        : 'Cập nhật cuộc họp thành công! Trạng thái phản hồi cũ của người tham gia đã được giữ nguyên.';
    stop_with_alert($message, "chitiet_cuochop.php?id=$cuochop_id");
} catch (Throwable $e) {
    $conn->rollback();
    stop_with_alert('Có lỗi khi cập nhật cuộc họp. Vui lòng thử lại!');
}
