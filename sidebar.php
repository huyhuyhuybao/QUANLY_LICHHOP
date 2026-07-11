<?php
$current_page = basename($_SERVER['PHP_SELF']);

function sidebar_active(array $pages, string $current_page): string
{
    return in_array($current_page, $pages, true) ? 'active' : '';
}
?>
<div class="col-md-3 col-lg-2 sidebar d-flex flex-column p-0">
    <div class="text-center mb-5 mt-3">
        <h2 class="brand-text m-0"><i class="fa-solid fa-calendar me-2"></i>Meeting</h2>
    </div>

    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?= sidebar_active(['dashboard.php'], $current_page) ?>">
                <i class="fa-solid fa-border-all"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="tao_lich.php" class="nav-link <?= sidebar_active(['tao_lich.php', 'xuly_taolich.php'], $current_page) ?>">
                <i class="fa-solid fa-calendar-plus"></i> Tạo cuộc họp
            </a>
        </li>
        <li>
            <a href="lich_hop.php" class="nav-link <?= sidebar_active(['lich_hop.php', 'chitiet_cuochop.php', 'sua_lich.php'], $current_page) ?>">
                <i class="fa-solid fa-calendar-days"></i> Quản lý cuộc họp
            </a>
        </li>

        <li class="mt-4">
            <a href="chon_tai_khoan.php" class="nav-link">
                <i class="fa-solid fa-user-group"></i> Đổi tài khoản
            </a>
        </li>
        <li>
            <a href="dang_xuat.php" class="nav-link">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng xuất
            </a>
        </li>
    </ul>

    <?php
    $avatar_name = urlencode($current_user['tennv'] ?? 'Employee');
    ?>
    <div class="p-4 border-top border-light border-opacity-25 mt-auto d-flex align-items-center">
        <img src="https://ui-avatars.com/api/?name=<?= $avatar_name ?>&background=ffffff&color=0d6efd"
             alt="User" class="rounded-circle me-3" width="40" height="40">
        <div class="overflow-hidden">
            <h6 class="m-0 fw-bold text-white text-truncate"><?= htmlspecialchars($current_user['tennv'] ?? 'Nhân viên') ?></h6>
            <small class="text-white-50">Employee</small>
        </div>
    </div>
</div>

<div class="col-md-9 col-lg-10 main-content">
