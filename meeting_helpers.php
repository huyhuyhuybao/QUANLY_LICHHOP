<?php
/**
 * Các hàm dùng chung cho module quản lý lịch họp.
 */

function stop_with_alert(string $message, string $redirect = 'history'): void
{
    $encodedMessage = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

    if ($redirect === 'history') {
        die("<script>alert($encodedMessage); window.history.back();</script>");
    }

    $encodedRedirect = json_encode($redirect, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    die("<script>alert($encodedMessage); window.location.href=$encodedRedirect;</script>");
}

/**
 * Chuẩn hóa danh sách ID nhân viên và loại người tổ chức khỏi danh sách mời.
 */
function normalize_participant_ids(array $input, int $creatorId): array
{
    return array_values(array_unique(array_filter(
        array_map('intval', $input),
        static fn(int $id): bool => $id > 0 && $id !== $creatorId
    )));
}

/**
 * Kiểm tra phòng có bị trùng lịch không.
 */
function room_has_conflict(
    mysqli $conn,
    int $roomId,
    string $startTime,
    string $endTime,
    int $excludeMeetingId = 0
): bool {
    $stmt = $conn->prepare(
        "SELECT id
         FROM cuochop
         WHERE phong_id = ?
           AND id <> ?
           AND trangthai <> 'Đã hủy'
           AND thoigian_batdau < ?
           AND thoigian_ketthuc > ?
         LIMIT 1"
    );
    $stmt->bind_param('iiss', $roomId, $excludeMeetingId, $endTime, $startTime);
    $stmt->execute();
    $hasConflict = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $hasConflict;
}

/**
 * Tìm các nhân viên bị trùng lịch.
 *
 * Một nhân viên được xem là bận khi họ:
 * - là người tổ chức của cuộc họp khác; hoặc
 * - là người tham gia và chưa từ chối cuộc họp khác.
 */
function find_busy_employees(
    mysqli $conn,
    array $employeeIds,
    string $startTime,
    string $endTime,
    int $excludeMeetingId = 0
): array {
    $employeeIds = array_values(array_unique(array_filter(
        array_map('intval', $employeeIds),
        static fn(int $id): bool => $id > 0
    )));

    if (empty($employeeIds)) {
        return [];
    }

    $stmt = $conn->prepare(
        "SELECT nv.tennv, c.tieude
         FROM nhanvien nv
         JOIN cuochop c ON 1 = 1
         LEFT JOIN chitiet_thamgia ct
           ON ct.cuochop_id = c.id
          AND ct.nhanvien_id = nv.id
         WHERE nv.id = ?
           AND c.id <> ?
           AND c.trangthai <> 'Đã hủy'
           AND c.thoigian_batdau < ?
           AND c.thoigian_ketthuc > ?
           AND (
                c.nguoitao_id = nv.id
                OR (
                    ct.nhanvien_id = nv.id
                    AND ct.trangthai_phanhoi <> 'Từ chối'
                )
           )
         ORDER BY c.thoigian_batdau ASC
         LIMIT 1"
    );

    $busyEmployees = [];
    foreach ($employeeIds as $employeeId) {
        $stmt->bind_param('iiss', $employeeId, $excludeMeetingId, $endTime, $startTime);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row) {
            $busyEmployees[] = [
                'id' => $employeeId,
                'tennv' => $row['tennv'],
                'tieude' => $row['tieude'],
            ];
        }
    }
    $stmt->close();

    return $busyEmployees;
}

function format_busy_employee_message(array $busyEmployees): string
{
    $lines = [];
    foreach ($busyEmployees as $employee) {
        $lines[] = '- ' . $employee['tennv'] . ' (đang vướng: ' . $employee['tieude'] . ')';
    }

    return implode("\n", $lines);
}

/**
 * Kiểm tra phòng còn hoạt động hay không.
 */
function active_room_exists(mysqli $conn, int $roomId): bool
{
    $stmt = $conn->prepare(
        "SELECT id
         FROM phong
         WHERE id = ?
           AND (
                trangthai IS NULL
                OR trangthai NOT IN ('Ngừng hoạt động', 'Bảo trì', 'Đang bảo trì', 'Khóa')
           )
         LIMIT 1"
    );
    $stmt->bind_param('i', $roomId);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows === 1;
    $stmt->close();

    return $exists;
}

/**
 * Trả về các ID không phải tài khoản Employee hợp lệ.
 */
function find_invalid_employee_ids(mysqli $conn, array $employeeIds): array
{
    $employeeIds = array_values(array_unique(array_filter(
        array_map('intval', $employeeIds),
        static fn(int $id): bool => $id > 0
    )));

    if (empty($employeeIds)) {
        return [];
    }

    $stmt = $conn->prepare("SELECT id FROM nhanvien WHERE id = ? AND role = 'employee' LIMIT 1");
    $invalidIds = [];

    foreach ($employeeIds as $employeeId) {
        $stmt->bind_param('i', $employeeId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows !== 1) {
            $invalidIds[] = $employeeId;
        }
    }

    $stmt->close();
    return $invalidIds;
}
