<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'traffic_map_db';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    // Nếu đang chạy trong API (đã nạp response.php) thì trả JSON, ngược lại in chuỗi lỗi
    if (function_exists('json_error')) {
        error_log('DB connect error: ' . $conn->connect_error);
        json_error('Không kết nối được cơ sở dữ liệu', 500);
    }
    die("Connection failed: " . $conn->connect_error);
}

// Bộ mã ký tự chuẩn UTF-8 tiếng Việt
$conn->set_charset("utf8mb4");

// Đồng bộ múi giờ Việt Nam
$conn->query("SET time_zone = '+07:00'");
?>
