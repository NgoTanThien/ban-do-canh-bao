<?php
// GET /api/admin/sos.php  -> danh sách yêu cầu cứu hộ
// POST /api/admin/sos.php { id, status: 'cho_cuu_ho'|'dang_xu_ly'|'da_hoan_thanh'|'huy' }
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/response.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $res = $conn->query("SELECT * FROM sos_requests ORDER BY id DESC LIMIT 50");
    $list = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    json_success($list);
}

$input = get_input();
$id = (int)($input['id'] ?? 0);
$status = (string)($input['status'] ?? '');

if ($id <= 0 || !in_array($status, ['cho_cuu_ho', 'dang_xu_ly', 'da_hoan_thanh', 'huy'], true)) {
    json_error('Dữ liệu không hợp lệ', 422);
}

$stmt = $conn->prepare("UPDATE sos_requests SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);
$stmt->execute();

json_success(null, 'Đã cập nhật trạng thái cứu hộ!');
