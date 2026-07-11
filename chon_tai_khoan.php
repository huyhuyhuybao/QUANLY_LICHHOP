<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_user_id = (int)($_POST['user_id'] ?? 0);

    $stmt = $conn->prepare("SELECT id FROM nhanvien WHERE id = ? AND role = 'employee' LIMIT 1");
    $stmt->bind_param('i', $selected_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $_SESSION['user_id'] = $selected_user_id;
        $stmt->close();
        header('Location: dashboard.php');
        exit;
    }

    $stmt->close();
    $error = 'Tài khoản không hợp lệ.';
}

$employees = $conn->query("SELECT id, tennv, email FROM nhanvien WHERE role = 'employee' ORDER BY tennv ASC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chọn tài khoản demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7fe; min-height: 100vh; display: grid; place-items: center; font-family: 'Segoe UI', sans-serif; }
        .login-card { width: min(520px, calc(100% - 32px)); border: 0; border-radius: 20px; box-shadow: 0 12px 35px rgba(25, 62, 130, .12); }
    </style>
</head>
<body>
<div class="card login-card p-4 p-md-5">
    <div class="text-center mb-4">
        <div class="fs-1 text-primary mb-2"><i class="fa-solid fa-calendar-check"></i></div>
        <h3 class="fw-bold mb-2">Chọn tài khoản nhân viên</h3>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="user_id" class="form-label fw-semibold">Nhân viên</label>
        <select id="user_id" name="user_id" class="form-select form-select-lg mb-4" required>
            <option value="">-- Chọn tài khoản --</option>
            <?php if ($employees): ?>
                <?php while ($employee = $employees->fetch_assoc()): ?>
                    <option value="<?= (int)$employee['id'] ?>">
                        <?= htmlspecialchars($employee['tennv']) ?> — <?= htmlspecialchars($employee['email']) ?>
                    </option>
                <?php endwhile; ?>
            <?php endif; ?>
        </select>

        <button class="btn btn-primary btn-lg w-100 fw-bold" type="submit">
            <i class="fa-solid fa-right-to-bracket me-2"></i>Vào hệ thống
        </button>
    </form>
</div>
</body>
</html>
