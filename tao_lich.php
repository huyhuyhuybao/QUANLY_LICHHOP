<?php
require_once 'auth.php';

// Hiển thị tất cả phòng còn hoạt động. Trạng thái bận/trống theo thời điểm sẽ được kiểm tra khi tạo lịch.
$sql_phong = "SELECT id, tenphong
              FROM phong
              WHERE trangthai IS NULL
                 OR trangthai NOT IN ('Ngừng hoạt động', 'Bảo trì', 'Đang bảo trì', 'Khóa')
              ORDER BY tenphong ASC";
$result_phong = $conn->query($sql_phong);

// Không đưa chính người tạo vào danh sách mời.
$sql_nv = "SELECT id, tennv, email FROM nhanvien WHERE id != $user_id AND role = 'employee' ORDER BY tennv ASC";
$result_nv = $conn->query($sql_nv);
$minDateTime = date('Y-m-d\TH:i');

include_once 'header.php';
include_once 'sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">

<style>
    .form-title { color: #2b3674; font-size: 1.25rem; letter-spacing: 0.5px; }
    .custom-label { color: #8f9bba; font-size: 0.9rem; margin-bottom: 8px; }
    .custom-input {
        border-radius: 8px;
        border: 1px solid #e0e5f2;
        padding: 12px 15px;
        font-size: 0.95rem;
        color: #2b3674;
    }
    .custom-input:focus { border-color: #0d6efd; box-shadow: none; }
    .btn-cancel {
        background-color: #f4f7fe;
        color: #2b3674;
        border: none;
        border-radius: 8px;
    }
    .btn-cancel:hover { background-color: #e0e5f2; color: #2b3674; }
    .btn-create {
        background-color: #d90429;
        color: white;
        border: none;
        border-radius: 8px;
        transition: 0.3s;
    }
    .btn-create:hover { background-color: #b00320; color: white; }
    .form-card { border-radius: 16px; }

    /* Đồng bộ Choices.js với giao diện hiện tại */
    .choices { margin-bottom: 0; }
    .choices__inner {
        min-height: 50px;
        border-radius: 8px;
        border: 1px solid #e0e5f2;
        background: #fff;
        padding: 8px 10px;
    }
    .is-focused .choices__inner,
    .is-open .choices__inner { border-color: #0d6efd; }
    .choices__list--multiple .choices__item {
        background-color: #0d6efd;
        border-color: #0d6efd;
        border-radius: 20px;
    }
</style>

<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="card form-card bg-white p-5 border-0 shadow-sm">
            <h4 class="fw-bold form-title mb-4">TẠO CUỘC HỌP</h4>

            <form action="xuly_taolich.php" method="POST">
                <div class="row g-4 mb-3">
                    <div class="col-md-6">
                        <label class="fw-bold custom-label" for="tieude">Tên cuộc họp</label>
                        <input type="text" id="tieude" name="tieude" class="form-control custom-input" placeholder="Nhập tên cuộc họp" maxlength="200" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold custom-label" for="thoigian_batdau">Bắt đầu</label>
                        <input type="datetime-local" id="thoigian_batdau" name="thoigian_batdau" class="form-control custom-input" min="<?= $minDateTime ?>" required>
                    </div>
                </div>

                <div class="row g-4 mb-3">
                    <div class="col-md-6">
                        <label class="fw-bold custom-label" for="phong_id">Phòng họp</label>
                        <select id="phong_id" name="phong_id" class="form-select custom-input" required>
                            <option value="">Chọn phòng...</option>
                            <?php if ($result_phong && $result_phong->num_rows > 0): ?>
                                <?php while ($row = $result_phong->fetch_assoc()): ?>
                                    <option value="<?= (int)$row['id'] ?>"><?= htmlspecialchars($row['tenphong']) ?></option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold custom-label" for="thoigian_ketthuc">Kết thúc</label>
                        <input type="datetime-local" id="thoigian_ketthuc" name="thoigian_ketthuc" class="form-control custom-input" min="<?= $minDateTime ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="fw-bold custom-label" for="nguoi_tham_gia">Người tham gia</label>
                    <select id="nguoi_tham_gia" name="nguoi_tham_gia[]" multiple required>
                        <?php if ($result_nv && $result_nv->num_rows > 0): ?>
                            <?php while ($row = $result_nv->fetch_assoc()): ?>
                                <option value="<?= (int)$row['id'] ?>">
                                    <?= htmlspecialchars($row['tennv']) ?> (<?= htmlspecialchars($row['email']) ?>)
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                    <small class="text-muted mt-1 d-block">
                        <i class="fa-solid fa-circle-info me-1"></i>Gõ tên hoặc email để tìm, sau đó bấm chọn. Không cần giữ Ctrl.
                    </small>
                </div>

                <div class="mb-5">
                    <label class="fw-bold custom-label" for="noidung">Mô tả</label>
                    <textarea id="noidung" name="noidung" class="form-control custom-input" rows="4" placeholder="Nhập mô tả nội dung cuộc họp"></textarea>
                </div>

                <div class="d-flex justify-content-end gap-3">
                    <a href="lich_hop.php" class="btn btn-cancel px-4 py-2 fw-bold">Hủy bỏ</a>
                    <button type="submit" class="btn btn-create px-4 py-2 fw-bold">Tạo cuộc họp</button>
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

<?php include_once 'footer.php'; ?>
