<?php
// POST /api/sos.php - Gửi yêu cầu cứu hộ khẩn cấp
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/response.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Phương thức không được hỗ trợ', 405);
}

$input = get_input();
$phone = trim($input['phone'] ?? '');
$desc  = trim($input['description'] ?? '');
$vehicle = trim($input['vehicle_info'] ?? 'Xe máy');
$lat   = isset($input['latitude']) ? (float)$input['latitude'] : 0.0;
$lng   = isset($input['longitude']) ? (float)$input['longitude'] : 0.0;

if (empty($phone)) {
    json_error('Vui lòng cung cấp số điện thoại liên hệ khẩn cấp!', 422);
}

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$senderName = isset($_SESSION['username']) ? (string)$_SESSION['username'] : 'Người dân';

$stmt = $conn->prepare("INSERT INTO sos_requests (user_id, sender_name, phone, vehicle_info, latitude, longitude, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'cho_cuu_ho')");
$stmt->bind_param("isssdds", $userId, $senderName, $phone, $vehicle, $lat, $lng, $desc);

if ($stmt->execute()) {
    json_success(['id' => $conn->insert_id], 'Đã phát tín hiệu SOS thành công! Đội cứu hộ đang tiếp nhận.');
} else {
    json_error('Không thể gửi yêu cầu SOS, vui lòng gọi trực tiếp hotline 1900 1088!', 500);
}
