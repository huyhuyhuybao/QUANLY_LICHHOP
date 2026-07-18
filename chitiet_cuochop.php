<?php
require_once 'auth.php';
require_once 'meeting_helpers.php';

$cuochop_id = (int)($_GET['id'] ?? 0);
if ($cuochop_id <= 0) {
    stop_with_alert('Không tìm thấy cuộc họp!', 'lich_hop.php');
}

$stmtMeeting = $conn->prepare(
    "SELECT c.*, p.tenphong, nv.tennv AS nguoitao
     FROM cuochop c
     JOIN phong p ON c.phong_id = p.id
     JOIN nhanvien nv ON c.nguoitao_id = nv.id
     WHERE c.id = ?
       AND (
            c.nguoitao_id = ?
            OR EXISTS (
                SELECT 1
                FROM chitiet_thamgia ct_access
                WHERE ct_access.cuochop_id = c.id
                  AND ct_access.nhanvien_id = ?
            )
       )
     LIMIT 1"
);
$stmtMeeting->bind_param('iii', $cuochop_id, $user_id, $user_id);
$stmtMeeting->execute();
$cuochop = $stmtMeeting->get_result()->fetch_assoc();
$stmtMeeting->close();

if (!$cuochop) {
    stop_with_alert('Cuộc họp không tồn tại hoặc bạn không có quyền xem!', 'lich_hop.php');
}

$stmtParticipants = $conn->prepare(
    "SELECT nv.id, nv.tennv, nv.email, ct.trangthai_phanhoi
     FROM chitiet_thamgia ct
     JOIN nhanvien nv ON ct.nhanvien_id = nv.id
     WHERE ct.cuochop_id = ?
     ORDER BY nv.tennv ASC"
);
$stmtParticipants->bind_param('i', $cuochop_id);
$stmtParticipants->execute();
$resultParticipants = $stmtParticipants->get_result();

$participants = [];
$current_user_response = null;
while ($participant = $resultParticipants->fetch_assoc()) {
    $participants[] = $participant;
    if ((int)$participant['id'] === $user_id) {
        $current_user_response = $participant['trangthai_phanhoi'];
    }
}
$stmtParticipants->close();

function response_badge(string $status): string
{
    if ($status === 'Đồng ý') {
        return '<span class="badge bg-success">Đồng ý</span>';
    }
    if ($status === 'Từ chối') {
        return '<span class="badge bg-danger">Từ chối</span>';
    }
    return '<span class="badge bg-warning text-dark">Chờ xác nhận</span>';
}

$now = time();
$start = strtotime($cuochop['thoigian_batdau']);
$end = strtotime($cuochop['thoigian_ketthuc']);
$isCreator = (int)$cuochop['nguoitao_id'] === $user_id;
$canEditOrCancel = $isCreator && $cuochop['trangthai'] !== 'Đã hủy' && $now < $start;
$canRespond =
    !$isCreator
    && $current_user_response !== null
    && $cuochop['trangthai'] !== 'Đã hủy'
    && $now < $start;
include_once 'header.php';
include_once 'sidebar.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-xl-9">
        <div class="card p-5 border-0 shadow-sm rounded-4 bg-white">
            <h4 class="fw-bold text-dark mb-4 text-uppercase border-bottom pb-3">Chi tiết cuộc họp</h4>

            <div class="mb-4">
                <h3 class="fw-bold text-primary"><?= htmlspecialchars($cuochop['tieude']) ?></h3>
                <?php if ($cuochop['trangthai'] === 'Đã hủy'): ?>
                    <span class="badge bg-danger mb-2">Trạng thái: Đã hủy</span>
                <?php elseif ($now < $start): ?>
                    <span class="badge bg-primary mb-2">Trạng thái: Sắp diễn ra</span>
                <?php elseif ($now <= $end): ?>
                    <span class="badge bg-warning text-dark mb-2">Trạng thái: Đang diễn ra</span>
                <?php else: ?>
                    <span class="badge bg-success mb-2">Trạng thái: Đã hoàn thành</span>
                <?php endif; ?>
            </div>

            <div class="row mb-4 fs-6">
                <div class="col-md-6 mb-3">
                    <div class="text-muted mb-1"><i class="fa-regular fa-clock me-2"></i>Thời gian bắt đầu</div>
                    <div class="fw-bold text-dark"><?= date('d/m/Y - H:i', $start) ?></div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="text-muted mb-1"><i class="fa-regular fa-clock me-2"></i>Thời gian kết thúc</div>
                    <div class="fw-bold text-dark"><?= date('d/m/Y - H:i', $end) ?></div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="text-muted mb-1"><i class="fa-solid fa-location-dot me-2"></i>Phòng họp</div>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($cuochop['tenphong']) ?></div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="text-muted mb-1"><i class="fa-solid fa-user-tie me-2"></i>Người tổ chức</div>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($cuochop['nguoitao']) ?></div>
                </div>
            </div>

            <div class="mb-4">
                <div class="text-muted mb-2"><i class="fa-solid fa-align-left me-2"></i>Nội dung cuộc họp</div>
                <div class="p-3 bg-light rounded-3 text-dark">
                    <?= !empty($cuochop['noidung']) ? nl2br(htmlspecialchars($cuochop['noidung'])) : 'Không có mô tả.' ?>
                </div>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="text-muted"><i class="fa-solid fa-users me-2"></i>Danh sách người tham gia</div>
                    <span class="badge bg-light text-dark border"><?= count($participants) ?> người được mời</span>
                </div>

                <?php if (!empty($participants)): ?>
                    <div class="table-responsive border rounded-3">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nhân viên</th>
                                    <th>Email</th>
                                    <th class="text-center">Phản hồi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($participants as $participant): ?>
                                    <tr class="<?= (int)$participant['id'] === $user_id ? 'table-primary' : '' ?>">
                                        <td>
                                            <?= htmlspecialchars($participant['tennv']) ?>
                                            <?php if ((int)$participant['id'] === $user_id): ?>
                                                <span class="badge bg-primary ms-1">Bạn</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($participant['email']) ?></td>
                                        <td class="text-center"><?= response_badge($participant['trangthai_phanhoi']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-3 bg-light rounded-3 text-muted">Cuộc họp chưa mời thêm nhân viên nào.</div>
                <?php endif; ?>
            </div>

            <?php if (!$isCreator && $current_user_response !== null): ?>
                <div class="p-4 rounded-3 border bg-light mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div>
                            <h6 class="fw-bold mb-1">Phản hồi lời mời của bạn</h6>
                            <span class="text-muted">Trạng thái hiện tại: <?= response_badge($current_user_response) ?></span>
                        </div>
                    </div>

                    <?php if ($canRespond): ?>
                        <div class="d-flex gap-2 flex-wrap">
                            <form action="xuly_phanhoi.php" method="POST">
                                <input type="hidden" name="cuochop_id" value="<?= (int)$cuochop['id'] ?>">
                                <input type="hidden" name="phanhoi" value="Đồng ý">
                                <button type="submit" class="btn btn-success fw-bold">
                                    <i class="fa-solid fa-check me-1"></i> Đồng ý tham gia
                                </button>
                            </form>

                            <form action="xuly_phanhoi.php" method="POST" onsubmit="return confirm('Bạn có chắc muốn từ chối cuộc họp này không?');">
                                <input type="hidden" name="cuochop_id" value="<?= (int)$cuochop['id'] ?>">
                                <input type="hidden" name="phanhoi" value="Từ chối">
                                <button type="submit" class="btn btn-outline-danger fw-bold">
                                    <i class="fa-solid fa-xmark me-1"></i> Từ chối
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="text-muted">Cuộc họp đã bắt đầu hoặc đã bị hủy nên không thể thay đổi phản hồi.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top flex-wrap">
                <a href="lich_hop.php" class="btn btn-light fw-bold px-4">Quay lại</a>

                <?php if ($canEditOrCancel): ?>
                    <a href="sua_lich.php?id=<?= (int)$cuochop['id'] ?>" class="btn btn-primary fw-bold px-4">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Sửa
                    </a>

                    <form action="xuly_huy_lich.php" method="POST" class="m-0" onsubmit="return confirm('Bạn có chắc chắn muốn HỦY cuộc họp này không?');">
                        <input type="hidden" name="cuochop_id" value="<?= (int)$cuochop['id'] ?>">
                        <button type="submit" class="btn btn-danger fw-bold px-4">
                            <i class="fa-solid fa-trash me-1"></i> Hủy cuộc họp
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?>
