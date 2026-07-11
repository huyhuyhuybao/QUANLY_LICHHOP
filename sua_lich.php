<?php
require_once 'auth.php';
require_once 'meeting_helpers.php';

$cuochop_id = (int)($_GET['id'] ?? 0);
if ($cuochop_id <= 0) {
    stop_with_alert('Không tìm thấy cuộc họp cần sửa!', 'lich_hop.php');
}

$stmtMeeting = $conn->prepare('SELECT * FROM cuochop WHERE id = ? LIMIT 1');
$stmtMeeting->bind_param('i', $cuochop_id);
$stmtMeeting->execute();
$cuochop = $stmtMeeting->get_result()->fetch_assoc();
$stmtMeeting->close();

if (!$cuochop) {
    stop_with_alert('Cuộc họp không tồn tại!', 'lich_hop.php');
}
if ((int)$cuochop['nguoitao_id'] !== $user_id) {
    stop_with_alert('Bạn không có quyền sửa cuộc họp này!', "chitiet_cuochop.php?id=$cuochop_id");
}
if ($cuochop['trangthai'] === 'Đã hủy') {
    stop_with_alert('Không thể sửa cuộc họp đã bị hủy!', "chitiet_cuochop.php?id=$cuochop_id");
}
if (strtotime($cuochop['thoigian_batdau']) <= time()) {
    stop_with_alert('Không thể sửa cuộc họp đã bắt đầu hoặc đã hoàn thành!', "chitiet_cuochop.php?id=$cuochop_id");
}

$invited_users = [];
$stmtInvited = $conn->prepare('SELECT nhanvien_id FROM chitiet_thamgia WHERE cuochop_id = ?');
$stmtInvited->bind_param('i', $cuochop_id);
$stmtInvited->execute();
$resultInvited = $stmtInvited->get_result();
while ($row = $resultInvited->fetch_assoc()) {
    $invited_users[] = (int)$row['nhanvien_id'];
}
$stmtInvited->close();

// Hiển thị tất cả phòng còn hoạt động; việc trùng lịch được kiểm tra khi lưu.
$sql_phong = "SELECT id, tenphong
              FROM phong
              WHERE id = " . (int)$cuochop['phong_id'] . "
                 OR trangthai IS NULL
                 OR trangthai NOT IN ('Ngừng hoạt động', 'Bảo trì', 'Đang bảo trì', 'Khóa')
              ORDER BY tenphong ASC";
$result_phong = $conn->query($sql_phong);

$creator_id = (int)$cuochop['nguoitao_id'];
$stmtEmployees = $conn->prepare("SELECT id, tennv, email FROM nhanvien WHERE id <> ? AND role = 'employee' ORDER BY tennv ASC");
$stmtEmployees->bind_param('i', $creator_id);
$stmtEmployees->execute();
$result_nv = $stmtEmployees->get_result();

$minDateTime = date('Y-m-d\TH:i');

include_once 'header.php';
include_once 'sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">

<style>
    .form-title { color: #2b3674; font-size: 1.25rem; letter-spacing: 0.5px; }
    .custom-label { color: #8f9bba; font-size: 0.9rem; margin-bottom: 8px; }
    .custom-input { border-radius: 8px; border: 1px solid #e0e5f2; padding: 12px 15px; font-size: 0.95rem; color: #2b3674; }
    .custom-input:focus { border-color: #0d6efd; box-shadow: none; }
    .btn-cancel { background-color: #f4f7fe; color: #2b3674; border: none; border-radius: 8px; }
    .btn-cancel:hover { background-color: #e0e5f2; color: #2b3674; }
    .btn-create { background-color: #0d6efd; color: white; border: none; border-radius: 8px; transition: 0.3s; }
    .btn-create:hover { background-color: #0b5ed7; color: white; }
    .choices { margin-bottom: 0; }
    .choices__inner { min-height: 50px; border-radius: 8px; border: 1px solid #e0e5f2; background: #fff; padding: 8px 10px; }
    .is-focused .choices__inner, .is-open .choices__inner { border-color: #0d6efd; }
    .choices__list--multiple .choices__item { background-color: #0d6efd; border-color: #0d6efd; border-radius: 20px; }
</style>

<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="card bg-white p-5 border-0 shadow-sm rounded-4">
            <h4 class="fw-bold form-title mb-4 text-uppercase">CẬP NHẬT CUỘC HỌP</h4>

            <form action="xuly_sua_lich.php" method="POST">
                <input type="hidden" name="cuochop_id" value="<?= (int)$cuochop['id'] ?>">

                <div class="row g-4 mb-3">
                    <div class="col-md-6">
                        <label class="fw-bold custom-label" for="tieude">Tên cuộc họp</label>
                        <input type="text" id="tieude" name="tieude" class="form-control custom-input" maxlength="200" value="<?= htmlspecialchars($cuochop['tieude']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold custom-label" for="thoigian_batdau">Bắt đầu</label>
                        <input type="datetime-local" id="thoigian_batdau" name="thoigian_batdau" class="form-control custom-input" value="<?= date('Y-m-d\TH:i', strtotime($cuochop['thoigian_batdau'])) ?>" min="<?= $minDateTime ?>" required>
                    </div>
                </div>

                <div class="row g-4 mb-3">
                    <div class="col-md-6">
                        <label class="fw-bold custom-label" for="phong_id">Phòng họp</label>
                        <select id="phong_id" name="phong_id" class="form-select custom-input" required>
                            <?php if ($result_phong): ?>
                                <?php while ($p = $result_phong->fetch_assoc()): ?>
                                    <option value="<?= (int)$p['id'] ?>" <?= (int)$p['id'] === (int)$cuochop['phong_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['tenphong']) ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold custom-label" for="thoigian_ketthuc">Kết thúc</label>
                        <input type="datetime-local" id="thoigian_ketthuc" name="thoigian_ketthuc" class="form-control custom-input" value="<?= date('Y-m-d\TH:i', strtotime($cuochop['thoigian_ketthuc'])) ?>" min="<?= $minDateTime ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="fw-bold custom-label" for="nguoi_tham_gia">Người tham gia</label>
                    <select id="nguoi_tham_gia" name="nguoi_tham_gia[]" multiple required>
                        <?php if ($result_nv): ?>
                            <?php while ($nv = $result_nv->fetch_assoc()): ?>
                                <option value="<?= (int)$nv['id'] ?>" <?= in_array((int)$nv['id'], $invited_users, true) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($nv['tennv']) ?> (<?= htmlspecialchars($nv['email']) ?>)
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                    <small class="text-muted mt-1 d-block">
                        <i class="fa-solid fa-circle-info me-1"></i>Gõ tên hoặc email để tìm; bấm dấu × trên thẻ để bỏ người đã chọn.
                    </small>
                </div>

                <div class="mb-5">
                    <label class="fw-bold custom-label" for="noidung">Mô tả</label>
                    <textarea id="noidung" name="noidung" class="form-control custom-input" rows="4"><?= htmlspecialchars($cuochop['noidung']) ?></textarea>
                </div>

                <div class="d-flex justify-content-end gap-3">
                    <a href="chitiet_cuochop.php?id=<?= (int)$cuochop['id'] ?>" class="btn btn-cancel px-4 py-2 fw-bold">Quay lại</a>
                    <button type="submit" class="btn btn-create px-4 py-2 fw-bold">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
    new Choices('#nguoi_tham_gia', {
        removeItemButton: true,
        searchEnabled: true,
        searchPlaceholderValue: 'Tìm theo tên hoặc email...',
        placeholder: true,
        placeholderValue: 'Chọn người tham gia',
        noResultsText: 'Không tìm thấy nhân viên',
        noChoicesText: 'Không còn nhân viên để chọn',
        itemSelectText: 'Bấm để chọn',
        shouldSort: false
    });


    const startInput = document.getElementById('thoigian_batdau');
    const endInput = document.getElementById('thoigian_ketthuc');

    endInput.min = startInput.value || '<?= $minDateTime ?>';

    startInput.addEventListener('change', function () {
        endInput.min = this.value || '<?= $minDateTime ?>';
        if (endInput.value && endInput.value <= this.value) {
            endInput.value = '';
        }
    });
</script>

<?php
$stmtEmployees->close();
include_once 'footer.php';
?>
