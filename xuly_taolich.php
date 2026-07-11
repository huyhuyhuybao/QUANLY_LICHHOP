<?php
require_once 'auth.php';
require_once 'meeting_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    stop_with_alert('Truy cập không hợp lệ!', 'tao_lich.php');
}

$nguoitao_id = $user_id;
$tieude = trim($_POST['tieude'] ?? '');
$phong_id = (int)($_POST['phong_id'] ?? 0);
$noidung = trim($_POST['noidung'] ?? '');
$nguoi_tham_gia_input = (array)($_POST['nguoi_tham_gia'] ?? []);
$batdau_input = $_POST['thoigian_batdau'] ?? '';
$ketthuc_input = $_POST['thoigian_ketthuc'] ?? '';

if ($tieude === '' || mb_strlen($tieude) > 200 || $phong_id <= 0 || $batdau_input === '' || $ketthuc_input === '') {
    stop_with_alert('Vui lòng nhập đầy đủ thông tin bắt buộc; tên cuộc họp tối đa 200 ký tự!');
}

$nguoi_tham_gia = normalize_participant_ids($nguoi_tham_gia_input, $nguoitao_id);
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
    stop_with_alert('Không được chọn thời gian bắt đầu trong quá khứ!');
}
if ($start_timestamp >= $end_timestamp) {
    stop_with_alert('Thời gian kết thúc phải lớn hơn thời gian bắt đầu!');
}

$thoigian_batdau = date('Y-m-d H:i:s', $start_timestamp);
$thoigian_ketthuc = date('Y-m-d H:i:s', $end_timestamp);

if (!active_room_exists($conn, $phong_id)) {
    stop_with_alert('Phòng họp không tồn tại hoặc đang ngừng hoạt động/bảo trì!');
}

if (room_has_conflict($conn, $phong_id, $thoigian_batdau, $thoigian_ketthuc)) {
    stop_with_alert('Phòng họp này đã có người đặt trong khoảng thời gian bạn chọn!');
}

$employeesToCheck = array_merge([$nguoitao_id], $nguoi_tham_gia);
$busyEmployees = find_busy_employees($conn, $employeesToCheck, $thoigian_batdau, $thoigian_ketthuc);
if (!empty($busyEmployees)) {
    stop_with_alert(
        "Không thể tạo lịch vì các nhân viên sau đang bận:\n" .
        format_busy_employee_message($busyEmployees)
    );
}

$conn->begin_transaction();
try {
    $stmtMeeting = $conn->prepare(
        "INSERT INTO cuochop
            (tieude, noidung, thoigian_batdau, thoigian_ketthuc, nguoitao_id, phong_id, trangthai)
         VALUES (?, ?, ?, ?, ?, ?, 'Sắp diễn ra')"
    );
    $stmtMeeting->bind_param(
        'ssssii',
        $tieude,
        $noidung,
        $thoigian_batdau,
        $thoigian_ketthuc,
        $nguoitao_id,
        $phong_id
    );
    if (!$stmtMeeting->execute()) {
        throw new RuntimeException($stmtMeeting->error);
    }
    $cuochop_id = $stmtMeeting->insert_id;
    $stmtMeeting->close();

    $trangthai_phanhoi = 'Chờ xác nhận';
    $stmtParticipant = $conn->prepare(
        'INSERT INTO chitiet_thamgia (cuochop_id, nhanvien_id, trangthai_phanhoi) VALUES (?, ?, ?)'
    );

    foreach ($nguoi_tham_gia as $nhanvien_id) {
        $stmtParticipant->bind_param('iis', $cuochop_id, $nhanvien_id, $trangthai_phanhoi);
        if (!$stmtParticipant->execute()) {
            throw new RuntimeException($stmtParticipant->error);
        }
    }
    $stmtParticipant->close();

    $conn->commit();
    stop_with_alert('Tạo lịch họp thành công!', "chitiet_cuochop.php?id=$cuochop_id");
} catch (Throwable $e) {
    $conn->rollback();
    stop_with_alert('Có lỗi khi lưu cuộc họp. Vui lòng kiểm tra dữ liệu và thử lại!');
}
