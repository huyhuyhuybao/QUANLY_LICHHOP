<?php
require_once 'auth.php';
$today = date('Y-m-d');

$sql_stats = "SELECT
                COUNT(DISTINCT c.id) AS tong_cuoc_hop,
                COUNT(DISTINCT CASE
                    WHEN c.trangthai != 'Đã hủy' AND c.thoigian_ketthuc < NOW() THEN c.id
                END) AS da_hoan_thanh,
                COUNT(DISTINCT CASE
                    WHEN c.trangthai = 'Đã hủy' THEN c.id
                END) AS da_huy,
                COUNT(DISTINCT CASE
                    WHEN c.trangthai != 'Đã hủy' AND c.thoigian_batdau > NOW() THEN c.id
                END) AS sap_toi
              FROM cuochop c
              LEFT JOIN chitiet_thamgia ct ON c.id = ct.cuochop_id
              WHERE c.nguoitao_id = $user_id
                 OR (ct.nhanvien_id = $user_id AND ct.trangthai_phanhoi != 'Từ chối')";
$result_stats = $conn->query($sql_stats);
$stats = $result_stats ? $result_stats->fetch_assoc() : [];
$tong_cuoc_hop = (int)($stats['tong_cuoc_hop'] ?? 0);
$da_hoan_thanh = (int)($stats['da_hoan_thanh'] ?? 0);
$da_huy = (int)($stats['da_huy'] ?? 0);
$sap_toi = (int)($stats['sap_toi'] ?? 0);

$sql_list = "SELECT DISTINCT c.id, c.tieude, c.thoigian_batdau, c.thoigian_ketthuc,
                    p.tenphong, c.trangthai
             FROM cuochop c
             JOIN phong p ON c.phong_id = p.id
             LEFT JOIN chitiet_thamgia ct ON c.id = ct.cuochop_id
             WHERE (
                    c.nguoitao_id = $user_id
                    OR (ct.nhanvien_id = $user_id AND ct.trangthai_phanhoi != 'Từ chối')
             )
             AND DATE(c.thoigian_batdau) <= '$today'
             AND DATE(c.thoigian_ketthuc) >= '$today'
             AND c.trangthai != 'Đã hủy'
             ORDER BY c.thoigian_batdau ASC";
$list_meetings = $conn->query($sql_list);

$sql_upcoming_list = "SELECT DISTINCT c.id, c.tieude, c.thoigian_batdau, p.tenphong
                      FROM cuochop c
                      JOIN phong p ON c.phong_id = p.id
                      LEFT JOIN chitiet_thamgia ct ON c.id = ct.cuochop_id
                      WHERE (
                            c.nguoitao_id = $user_id
                            OR (ct.nhanvien_id = $user_id AND ct.trangthai_phanhoi != 'Từ chối')
                      )
                      AND c.thoigian_batdau > NOW()
                      AND c.trangthai != 'Đã hủy'
                      ORDER BY c.thoigian_batdau ASC
                      LIMIT 4";
$upcoming_meetings = $conn->query($sql_upcoming_list);

include_once 'header.php';
include_once 'sidebar.php';
?>

<div class="top-header d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <h4 class="m-0 fw-bold text-dark">Tổng quan</h4>
    <div class="d-flex align-items-center">
        <div class="text-muted fw-semibold me-4">
            <i class="fa-regular fa-calendar me-2"></i><?= date('d/m/Y') ?>
        </div>
        <i class="fa-regular fa-bell fa-lg text-muted"></i>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-stat p-4">
            <div class="d-flex align-items-center mb-3">
                <div class="icon-box bg-light-blue me-3"><i class="fa-solid fa-calendar-days"></i></div>
                <h6 class="text-muted m-0 fw-semibold">Tổng cuộc họp</h6>
            </div>
            <h2 class="fw-bold m-0 text-dark"><?= $tong_cuoc_hop ?></h2>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-stat p-4">
            <div class="d-flex align-items-center mb-3">
                <div class="icon-box bg-light-green me-3"><i class="fa-solid fa-circle-check"></i></div>
                <h6 class="text-muted m-0 fw-semibold">Đã hoàn thành</h6>
            </div>
            <h2 class="fw-bold m-0 text-dark"><?= $da_hoan_thanh ?></h2>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-stat p-4">
            <div class="d-flex align-items-center mb-3">
                <div class="icon-box bg-light-red me-3"><i class="fa-solid fa-circle-xmark"></i></div>
                <h6 class="text-muted m-0 fw-semibold">Đã hủy</h6>
            </div>
            <h2 class="fw-bold m-0 text-dark"><?= $da_huy ?></h2>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-stat p-4">
            <div class="d-flex align-items-center mb-3">
                <div class="icon-box bg-light-yellow me-3"><i class="fa-solid fa-hourglass-start"></i></div>
                <h6 class="text-muted m-0 fw-semibold">Sắp tới</h6>
            </div>
            <h2 class="fw-bold m-0 text-dark"><?= $sap_toi ?></h2>
        </div>
    </div>
</div>

<div class="row g-4 justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="card table-card bg-white p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold text-dark m-0">Lịch họp hôm nay</h5>
                <a href="lich_hop.php" class="text-primary text-decoration-none fw-semibold">Xem tất cả</a>
            </div>

            <div class="table-responsive">
                <table class="table align-middle text-center">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Phòng</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($list_meetings && $list_meetings->num_rows > 0): ?>
                            <?php while ($row = $list_meetings->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <a href="chitiet_cuochop.php?id=<?= (int)$row['id'] ?>" class="text-decoration-none text-dark">
                                            <div class="fw-bold">
                                                <?= date('H:i', strtotime($row['thoigian_batdau'])) ?> - <?= date('H:i', strtotime($row['thoigian_ketthuc'])) ?>
                                            </div>
                                            <small class="text-muted"><?= htmlspecialchars($row['tieude']) ?></small>
                                        </a>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($row['tenphong']) ?></span></td>
                                    <td>
                                        <?php
                                        $now = time();
                                        $start = strtotime($row['thoigian_batdau']);
                                        $end = strtotime($row['thoigian_ketthuc']);

                                        if ($now < $start) {
                                            echo '<span class="status-badge bg-light-blue">Sắp diễn ra</span>';
                                        } elseif ($now <= $end) {
                                            echo '<span class="status-badge bg-warning text-dark"><i class="fa-solid fa-circle-play me-1"></i>Đang diễn ra</span>';
                                        } else {
                                            echo '<span class="status-badge bg-light-green">Hoàn thành</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-4 text-secondary">
                                    <i class="fa-solid fa-calendar-xmark fa-2x mb-2 d-block"></i>
                                    Bạn không có lịch họp nào trong ngày hôm nay.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-10">
        <div class="card table-card bg-white p-4 h-100">
            <h5 class="fw-bold text-dark mb-4">Sắp diễn ra</h5>
            <div class="timeline">
                <?php if ($upcoming_meetings && $upcoming_meetings->num_rows > 0): ?>
                    <?php while ($row_up = $upcoming_meetings->fetch_assoc()): ?>
                        <a href="chitiet_cuochop.php?id=<?= (int)$row_up['id'] ?>" class="text-decoration-none">
                            <div class="timeline-item">
                                <h6 class="fw-bold text-dark mb-1">
                                    <?php
                                    if (date('Y-m-d', strtotime($row_up['thoigian_batdau'])) === $today) {
                                        echo 'Hôm nay, ' . date('H:i', strtotime($row_up['thoigian_batdau']));
                                    } else {
                                        echo date('d/m/Y, H:i', strtotime($row_up['thoigian_batdau']));
                                    }
                                    ?>
                                </h6>
                                <p class="text-muted mb-0">
                                    <?= htmlspecialchars($row_up['tieude']) ?><br>
                                    <small><i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($row_up['tenphong']) ?></small>
                                </p>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted">Không có sự kiện sắp tới.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?>
