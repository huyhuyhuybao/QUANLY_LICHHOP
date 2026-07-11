<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

$servername = 'localhost';
$username = 'root';
$password = '';
$dbname = 'quanly_lichhop';

$conn = @new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die('Không thể kết nối cơ sở dữ liệu. Hãy kiểm tra MySQL và file db_connect.php.');
}

$conn->set_charset('utf8mb4');

// Đồng bộ NOW() của MySQL với múi giờ Việt Nam để kiểm tra lịch chính xác.
$conn->query("SET time_zone = '+07:00'");
?>
