<?php
require_once 'auth.php';

/**
 * Kiểm tra ngày theo định dạng YYYY-MM-DD.
 */
function calendar_is_valid_date(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

/**
 * Chuyển giá trị từ input datetime-local thành DateTimeImmutable.
 */
function calendar_parse_datetime_local(string $value): ?DateTimeImmutable
{
    if ($value === '') {
        return null;
    }

    foreach (['!Y-m-d\\TH:i', '!Y-m-d\\TH:i:s'] as $format) {
        $date = DateTimeImmutable::createFromFormat($format, $value);
        if ($date !== false) {
            $expected = $format === '!Y-m-d\\TH:i' ? $date->format('Y-m-d\\TH:i') : $date->format('Y-m-d\\TH:i:s');
            if ($expected === $value) {
                return $date;
            }
        }
    }

    return null;
}

$allowed_views = ['day', 'week', 'month'];
$view = $_GET['view'] ?? 'month';
if (!in_array($view, $allowed_views, true)) {
    $view = 'month';
}

$focus_date_string = $_GET['date'] ?? date('Y-m-d');
if (!calendar_is_valid_date($focus_date_string)) {
    $focus_date_string = date('Y-m-d');
}
$focus_date = new DateTimeImmutable($focus_date_string);

$organizer_id = isset($_GET['organizer_id']) ? max(0, (int)$_GET['organizer_id']) : 0;
$room_id = isset($_GET['room_id']) ? max(0, (int)$_GET['room_id']) : 0;

$filter_from_value = trim((string)($_GET['from_datetime'] ?? ''));
$filter_to_value = trim((string)($_GET['to_datetime'] ?? ''));
$filter_from = calendar_parse_datetime_local($filter_from_value);
$filter_to = calendar_parse_datetime_local($filter_to_value);
$filter_error = '';

if ($filter_from_value !== '' && $filter_from === null) {
    $filter_error = 'Thời gian bắt đầu lọc không hợp lệ.';
}
if ($filter_to_value !== '' && $filter_to === null) {
    $filter_error = $filter_error !== ''
        ? $filter_error . ' Thời gian kết thúc lọc không hợp lệ.'
        : 'Thời gian kết thúc lọc không hợp lệ.';
}
if ($filter_from !== null && $filter_to !== null && $filter_from > $filter_to) {
    $filter_error = 'Thời gian bắt đầu lọc phải nhỏ hơn hoặc bằng thời gian kết thúc.';
}

// Xác định khoảng thời gian của chế độ đang xem.
switch ($view) {
    case 'day':
        $range_start = $focus_date->setTime(0, 0, 0);
        $range_end = $range_start->modify('+1 day');
        $previous_date = $focus_date->modify('-1 day');
        $next_date = $focus_date->modify('+1 day');
        $period_title = 'Ngày ' . $focus_date->format('d/m/Y');
        break;

    case 'week':
        $days_from_monday = (int)$focus_date->format('N') - 1;
        $range_start = $focus_date->modify("-$days_from_monday days")->setTime(0, 0, 0);
        $range_end = $range_start->modify('+7 days');
        $previous_date = $focus_date->modify('-7 days');
        $next_date = $focus_date->modify('+7 days');
        $period_title = 'Tuần ' . $range_start->format('d/m/Y') . ' - ' . $range_end->modify('-1 day')->format('d/m/Y');
        break;

    default:
        $range_start = $focus_date->modify('first day of this month')->setTime(0, 0, 0);
        $range_end = $range_start->modify('+1 month');
        $previous_date = $focus_date->modify('-1 month');
        $next_date = $focus_date->modify('+1 month');
        $period_title = 'Tháng ' . $range_start->format('n') . ' năm ' . $range_start->format('Y');
        break;
}

/**
 * Tạo URL và giữ lại chế độ xem cùng các bộ lọc hiện tại.
 */
$build_url = function (array $changes = []) use (
    $view,
    $focus_date_string,
    $organizer_id,
    $room_id,
    $filter_from_value,
    $filter_to_value
): string {
    $params = [
        'view' => $view,
        'date' => $focus_date_string,
        'organizer_id' => $organizer_id > 0 ? $organizer_id : null,
        'room_id' => $room_id > 0 ? $room_id : null,
        'from_datetime' => $filter_from_value !== '' ? $filter_from_value : null,
        'to_datetime' => $filter_to_value !== '' ? $filter_to_value : null,
    ];

    foreach ($changes as $key => $value) {
        $params[$key] = $value;
    }

    $params = array_filter(
        $params,
        static fn($value): bool => $value !== null && $value !== ''
    );

    return 'lich_hop.php?' . http_build_query($params);
};

$user_id_sql = (int)$user_id;
$personal_meeting_condition = "(
    c.nguoitao_id = $user_id_sql
    OR EXISTS (
        SELECT 1
        FROM chitiet_thamgia ct_personal
        WHERE ct_personal.cuochop_id = c.id
          AND ct_personal.nhanvien_id = $user_id_sql
          AND ct_personal.trangthai_phanhoi != 'Từ chối'
    )
)";

// Chỉ hiển thị người tổ chức từng có cuộc họp liên quan đến Employee đang đăng nhập.
$organizers = [];
$sql_organizers = "SELECT DISTINCT nv.id, nv.tennv
                   FROM nhanvien nv
                   JOIN cuochop c ON c.nguoitao_id = nv.id
                   WHERE $personal_meeting_condition
                     AND c.trangthai != 'Đã hủy'
                   ORDER BY nv.tennv";
$result_organizers = $conn->query($sql_organizers);
if ($result_organizers) {
    while ($row = $result_organizers->fetch_assoc()) {
        $organizers[] = $row;
    }
}

// Chỉ hiển thị các phòng từng xuất hiện trong lịch cá nhân.
$rooms = [];
$sql_rooms = "SELECT DISTINCT p.id, p.tenphong
              FROM phong p
              JOIN cuochop c ON c.phong_id = p.id
              WHERE $personal_meeting_condition
                AND c.trangthai != 'Đã hủy'
              ORDER BY p.tenphong";
$result_rooms = $conn->query($sql_rooms);
if ($result_rooms) {
    while ($row = $result_rooms->fetch_assoc()) {
        $rooms[] = $row;
    }
}

$range_start_sql = $conn->real_escape_string($range_start->format('Y-m-d H:i:s'));
$range_end_sql = $conn->real_escape_string($range_end->format('Y-m-d H:i:s'));

$where_conditions = [
    $personal_meeting_condition,
    "c.trangthai != 'Đã hủy'",
    "c.thoigian_batdau < '$range_end_sql'",
    "c.thoigian_ketthuc >= '$range_start_sql'",
];

if ($organizer_id > 0) {
    $where_conditions[] = 'c.nguoitao_id = ' . $organizer_id;
}
if ($room_id > 0) {
    $where_conditions[] = 'c.phong_id = ' . $room_id;
}

// Chỉ áp dụng bộ lọc thời gian khi dữ liệu hợp lệ.
if ($filter_error === '') {
    if ($filter_from !== null) {
        $from_sql = $conn->real_escape_string($filter_from->format('Y-m-d H:i:s'));
        $where_conditions[] = "c.thoigian_ketthuc >= '$from_sql'";
    }
    if ($filter_to !== null) {
        $to_sql = $conn->real_escape_string($filter_to->format('Y-m-d H:i:s'));
        $where_conditions[] = "c.thoigian_batdau <= '$to_sql'";
    }
}

$sql_meetings = "SELECT c.id,
                        c.tieude,
                        c.thoigian_batdau,
                        c.thoigian_ketthuc,
                        c.trangthai,
                        p.tenphong,
                        nv.tennv AS nguoitochuc
                 FROM cuochop c
                 JOIN phong p ON c.phong_id = p.id
                 JOIN nhanvien nv ON c.nguoitao_id = nv.id
                 WHERE " . implode(' AND ', $where_conditions) . "
                 ORDER BY c.thoigian_batdau ASC";

$result_meetings = $conn->query($sql_meetings);
$meetings = [];
$events_by_date = [];
$query_error = '';

if ($result_meetings === false) {
    $query_error = 'Không thể tải dữ liệu lịch họp. Vui lòng kiểm tra lại cơ sở dữ liệu.';
} else {
    while ($row = $result_meetings->fetch_assoc()) {
        $meetings[] = $row;

        // Một cuộc họp kéo dài qua nhiều ngày sẽ xuất hiện ở từng ngày liên quan.
        $event_start_day = (new DateTimeImmutable($row['thoigian_batdau']))->setTime(0, 0, 0);
        $event_end_day = (new DateTimeImmutable($row['thoigian_ketthuc']))->modify('-1 second')->setTime(0, 0, 0);
        $cursor = $event_start_day < $range_start ? $range_start : $event_start_day;
        $last_day = $event_end_day >= $range_end ? $range_end->modify('-1 day') : $event_end_day;

        while ($cursor <= $last_day) {
            $events_by_date[$cursor->format('Y-m-d')][] = $row;
            $cursor = $cursor->modify('+1 day');
        }
    }
}

$meeting_status = static function (array $meeting): array {
    $now = time();
    $start = strtotime($meeting['thoigian_batdau']);
    $end = strtotime($meeting['thoigian_ketthuc']);

    if ($now > $end) {
        return ['Hoàn thành', 'status-completed', 'bg-green'];
    }
    if ($now >= $start) {
        return ['Đang diễn ra', 'status-ongoing', 'bg-purple'];
    }

    return ['Sắp diễn ra', 'status-upcoming', 'bg-blue'];
};

$render_event = static function (array $event) use ($meeting_status): string {
    [, , $color_class] = $meeting_status($event);
    $start = strtotime($event['thoigian_batdau']);
    $end = strtotime($event['thoigian_ketthuc']);

    return "<a href='chitiet_cuochop.php?id=" . (int)$event['id'] . "' class='text-decoration-none'>"
        . "<div class='event-block $color_class shadow-sm'>"
        . "<span class='event-title'>" . htmlspecialchars($event['tieude']) . "</span>"
        . "<span>" . date('H:i', $start) . ' - ' . date('H:i', $end) . "</span>"
        . "<span>" . htmlspecialchars($event['tenphong']) . "</span>"
        . "<span class='event-organizer'>" . htmlspecialchars($event['nguoitochuc']) . "</span>"
        . "</div></a>";
};

include_once 'header.php';
include_once 'sidebar.php';
?>

<style>
    .page-title { color: #2b3674; font-size: 1.25rem; font-weight: 800; letter-spacing: 0.5px; }
    .calendar-subtitle { color: #8f9bba; font-weight: 600; }
    .btn-add-meeting { background-color: #d90429; color: #fff; border-radius: 8px; padding: 8px 20px; font-weight: 600; }
    .btn-add-meeting:hover { background-color: #b00320; color: #fff; }
    .calendar-nav-btn { background: #f4f7fe; border: none; padding: 7px 13px; border-radius: 6px; color: #2b3674; text-decoration: none; }
    .calendar-nav-btn:hover { background: #e0e5f2; color: #2b3674; }
    .view-switch .btn { border: 1px solid #dfe5f1; color: #2b3674; font-weight: 600; min-width: 85px; }
    .view-switch .btn.active { background: #1677ff; border-color: #1677ff; color: #fff; }
    .filter-box { background: #f8faff; border: 1px solid #edf0f7; border-radius: 12px; }
    .filter-summary { color: #6c757d; font-size: 0.9rem; }
    .calendar-container { border: 1px solid #eef0f3; border-radius: 12px; overflow: hidden; }
    .calendar-header { display: grid; grid-template-columns: repeat(7, minmax(130px, 1fr)); background-color: #f8f9fa; border-bottom: 1px solid #eef0f3; }
    .calendar-header div { padding: 15px; text-align: center; font-weight: 600; color: #a0a5ba; }
    .calendar-body, .week-body { display: grid; grid-template-columns: repeat(7, minmax(130px, 1fr)); background-color: #eef0f3; gap: 1px; }
    .calendar-cell, .week-cell { background-color: #fff; min-height: 120px; padding: 10px; transition: 0.2s; }
    .week-cell { min-height: 300px; }
    .calendar-cell:hover, .week-cell:hover { background-color: #f8f9fa; }
    .calendar-cell.other-month { background-color: #fdfdfd; color: #a0a5ba; }
    .date-number { font-weight: 600; color: #2b3674; margin-bottom: 8px; display: inline-block; }
    .date-today { background-color: #d90429; color: #fff; border-radius: 50%; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; }
    .event-block { border-radius: 6px; padding: 7px 8px; margin-bottom: 6px; font-size: 0.75rem; color: #fff; display: flex; flex-direction: column; line-height: 1.4; }
    .event-title { font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .event-organizer { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; opacity: 0.9; }
    .bg-purple { background-color: #7b61ff; }
    .bg-blue { background-color: #3b82f6; }
    .bg-green { background-color: #10b981; }
    .day-meeting { border: 1px solid #e9edf5; border-left: 5px solid #1677ff; border-radius: 10px; padding: 16px; transition: 0.2s; }
    .day-meeting:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(43, 54, 116, 0.08); }
    .meeting-status { border-radius: 999px; padding: 5px 11px; font-size: 0.78rem; font-weight: 700; white-space: nowrap; }
    .status-completed { background: #dcfce7; color: #15803d; }
    .status-ongoing { background: #ede9fe; color: #6d28d9; }
    .status-upcoming { background: #dbeafe; color: #1d4ed8; }

    @media (max-width: 991px) {
        .calendar-container { overflow-x: auto; }
        .calendar-header, .calendar-body, .week-body { min-width: 910px; }
        .calendar-cell { min-height: 95px; padding: 6px; }
        .event-block { font-size: 0.68rem; }
    }
</style>

<div class="row justify-content-center">
    <div class="col-12 col-xl-11">
        <div class="card form-card bg-white p-4 border-0 shadow-sm rounded-4">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h4 class="page-title m-0 text-uppercase">QUẢN LÝ CUỘC HỌP</h4>
                    <div class="calendar-subtitle mt-1">Xem lịch cá nhân theo ngày, tuần hoặc tháng</div>
                </div>
                <a href="tao_lich.php" class="btn btn-add-meeting text-decoration-none">
                    <i class="fa-solid fa-plus me-1"></i> Thêm lịch họp
                </a>
            </div>

            <div class="view-switch btn-group mb-3" role="group" aria-label="Chọn chế độ xem lịch">
                <a class="btn <?= $view === 'day' ? 'active' : '' ?>"
                   href="<?= htmlspecialchars($build_url(['view' => 'day'])) ?>">Ngày</a>
                <a class="btn <?= $view === 'week' ? 'active' : '' ?>"
                   href="<?= htmlspecialchars($build_url(['view' => 'week'])) ?>">Tuần</a>
                <a class="btn <?= $view === 'month' ? 'active' : '' ?>"
                   href="<?= htmlspecialchars($build_url(['view' => 'month'])) ?>">Tháng</a>
            </div>

            <form method="GET" class="filter-box p-3 mb-4">
                <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
                <input type="hidden" name="date" value="<?= htmlspecialchars($focus_date_string) ?>">

                <div class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label fw-semibold">Người tổ chức</label>
                        <select name="organizer_id" class="form-select">
                            <option value="0">Tất cả người tổ chức</option>
                            <?php foreach ($organizers as $organizer): ?>
                                <option value="<?= (int)$organizer['id'] ?>"
                                    <?= $organizer_id === (int)$organizer['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($organizer['tennv']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label class="form-label fw-semibold">Phòng họp</label>
                        <select name="room_id" class="form-select">
                            <option value="0">Tất cả phòng họp</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?= (int)$room['id'] ?>"
                                    <?= $room_id === (int)$room['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($room['tenphong']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label class="form-label fw-semibold">Từ thời gian</label>
                        <input type="datetime-local" name="from_datetime" class="form-control"
                               value="<?= htmlspecialchars($filter_from_value) ?>">
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label class="form-label fw-semibold">Đến thời gian</label>
                        <input type="datetime-local" name="to_datetime" class="form-control"
                               value="<?= htmlspecialchars($filter_to_value) ?>">
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button class="btn btn-primary px-4" type="submit">
                            <i class="fa-solid fa-filter me-1"></i> Lọc lịch
                        </button>
                        <a class="btn btn-outline-secondary"
                           href="<?= htmlspecialchars($build_url([
                               'organizer_id' => null,
                               'room_id' => null,
                               'from_datetime' => null,
                               'to_datetime' => null,
                           ])) ?>">
                            <i class="fa-solid fa-rotate-left me-1"></i> Xóa bộ lọc
                        </a>
                    </div>
                </div>

                <?php if ($filter_error !== ''): ?>
                    <div class="alert alert-danger mt-3 mb-0">
                        <?= htmlspecialchars($filter_error) ?>
                    </div>
                <?php elseif ($organizer_id > 0 || $room_id > 0 || $filter_from !== null || $filter_to !== null): ?>
                    <div class="filter-summary mt-3">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Bộ lọc đang được áp dụng cho lịch cá nhân trong khoảng đang xem.
                    </div>
                <?php endif; ?>
            </form>

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <h5 class="fw-bold m-0" style="color: #2b3674;">
                    <?= htmlspecialchars($period_title) ?>
                </h5>
                <div class="d-flex gap-2 align-items-center">
                    <a class="calendar-nav-btn"
                       href="<?= htmlspecialchars($build_url(['date' => $previous_date->format('Y-m-d')])) ?>"
                       aria-label="Kỳ trước">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>
                    <a class="calendar-nav-btn fw-bold"
                       href="<?= htmlspecialchars($build_url(['date' => date('Y-m-d')])) ?>">
                        Hôm nay
                    </a>
                    <a class="calendar-nav-btn"
                       href="<?= htmlspecialchars($build_url(['date' => $next_date->format('Y-m-d')])) ?>"
                       aria-label="Kỳ sau">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>
            </div>

            <?php if ($query_error !== ''): ?>
                <div class="alert alert-danger mb-0"><?= htmlspecialchars($query_error) ?></div>

            <?php elseif ($view === 'day'): ?>
                <div class="d-flex flex-column gap-3">
                    <?php if (empty($meetings)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fa-regular fa-calendar-xmark fs-2 d-block mb-2"></i>
                            Không có cuộc họp phù hợp trong ngày này.
                        </div>
                    <?php else: ?>
                        <?php foreach ($meetings as $meeting): ?>
                            <?php [$status_text, $status_class] = $meeting_status($meeting); ?>
                            <a href="chitiet_cuochop.php?id=<?= (int)$meeting['id'] ?>"
                               class="text-decoration-none text-dark">
                                <div class="day-meeting">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                        <div>
                                            <h6 class="fw-bold mb-2"><?= htmlspecialchars($meeting['tieude']) ?></h6>
                                            <div class="mb-1">
                                                <i class="fa-regular fa-clock me-2"></i>
                                                <?= date('d/m/Y H:i', strtotime($meeting['thoigian_batdau'])) ?>
                                                -
                                                <?= date('d/m/Y H:i', strtotime($meeting['thoigian_ketthuc'])) ?>
                                            </div>
                                            <div class="text-muted">
                                                <i class="fa-solid fa-location-dot me-2"></i>
                                                <?= htmlspecialchars($meeting['tenphong']) ?>
                                                <span class="mx-2">•</span>
                                                <i class="fa-solid fa-user me-2"></i>
                                                <?= htmlspecialchars($meeting['nguoitochuc']) ?>
                                            </div>
                                        </div>
                                        <span class="meeting-status <?= $status_class ?>">
                                            <?= htmlspecialchars($status_text) ?>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            <?php elseif ($view === 'week'): ?>
                <div class="calendar-container">
                    <div class="calendar-header">
                        <?php for ($i = 0; $i < 7; $i++): ?>
                            <?php $day_date = $range_start->modify("+$i days"); ?>
                            <div>
                                <?= ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'CN'][$i] ?><br>
                                <strong><?= $day_date->format('d/m') ?></strong>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div class="week-body">
                        <?php for ($i = 0; $i < 7; $i++): ?>
                            <?php
                            $day_date = $range_start->modify("+$i days");
                            $day_key = $day_date->format('Y-m-d');
                            $today_class = $day_key === date('Y-m-d') ? 'date-today' : '';
                            ?>
                            <div class="week-cell">
                                <span class="date-number <?= $today_class ?>"><?= $day_date->format('d') ?></span>
                                <?php if (!empty($events_by_date[$day_key])): ?>
                                    <?php foreach ($events_by_date[$day_key] as $event): ?>
                                        <?= $render_event($event) ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-muted small">Không có lịch</div>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

            <?php else: ?>
                <div class="calendar-container">
                    <div class="calendar-header">
                        <div>Thứ 2</div>
                        <div>Thứ 3</div>
                        <div>Thứ 4</div>
                        <div>Thứ 5</div>
                        <div>Thứ 6</div>
                        <div>Thứ 7</div>
                        <div>CN</div>
                    </div>

                    <div class="calendar-body">
                        <?php
                        $current_month = $range_start;
                        $previous_month = $current_month->modify('-1 month');
                        $days_in_month = (int)$current_month->format('t');
                        $day_of_week = (int)$current_month->format('N');
                        $previous_month_days = (int)$previous_month->format('t');

                        for ($i = 1; $i < $day_of_week; $i++) {
                            $previous_date_number = $previous_month_days - ($day_of_week - 1 - $i);
                            echo "<div class='calendar-cell other-month'><span class='date-number'>$previous_date_number</span></div>";
                        }

                        for ($day = 1; $day <= $days_in_month; $day++) {
                            $current_date_string = $current_month->format('Y-m-') . str_pad((string)$day, 2, '0', STR_PAD_LEFT);
                            $today_class = $current_date_string === date('Y-m-d') ? 'date-today' : '';

                            echo "<div class='calendar-cell'>";
                            echo "<span class='date-number $today_class'>$day</span>";

                            if (!empty($events_by_date[$current_date_string])) {
                                foreach ($events_by_date[$current_date_string] as $event) {
                                    echo $render_event($event);
                                }
                            }

                            echo '</div>';
                        }

                        $total_cells = ($day_of_week - 1) + $days_in_month;
                        $remaining_cells = ($total_cells <= 35 ? 35 : 42) - $total_cells;
                        for ($i = 1; $i <= $remaining_cells; $i++) {
                            echo "<div class='calendar-cell other-month'><span class='date-number'>$i</span></div>";
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?>
