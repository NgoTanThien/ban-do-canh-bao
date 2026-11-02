<?php
// GET /api/admin/reports.php[?status=all|dang_dien_ra|da_ket_thuc|bi_an]
// POST /api/admin/reports.php { report_id, action: 'verify'|'hide'|'active'|'end'|'delete' }
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/response.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/ReportModel.php';

require_admin();
$reportModel = new ReportModel($conn);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $status = $_GET['status'] ?? 'all';
    json_success($reportModel->getAllForAdmin($status));
}

$input = get_input();
$reportId = (int)($input['report_id'] ?? 0);
$action = (string)($input['action'] ?? '');

if ($reportId <= 0) {
    json_error('ID báo cáo không hợp lệ', 422);
}

if ($action === 'verify') {
    $stmt = $conn->prepare("UPDATE reports SET confirm_count = confirm_count + 5 WHERE id = ?");
    $stmt->bind_param("i", $reportId);
    $stmt->execute();
    json_success(null, 'Đã xác thực báo cáo chính thức!');
} elseif ($action === 'hide') {
    $reportModel->updateReportStatus($reportId, 'bi_an');
    json_success(null, 'Đã ẩn báo cáo vi phạm!');
} elseif ($action === 'active') {
    $reportModel->updateReportStatus($reportId, 'dang_dien_ra');
    json_success(null, 'Đã khôi phục báo cáo!');
} elseif ($action === 'end') {
    $reportModel->updateReportStatus($reportId, 'da_ket_thuc');
    json_success(null, 'Đã kết thúc sự cố!');
} elseif ($action === 'delete') {
    $reportModel->deleteReport($reportId);
    json_success(null, 'Đã xóa hoàn toàn báo cáo!');
} else {
    json_error('Thao tác không hợp lệ', 400);
}

