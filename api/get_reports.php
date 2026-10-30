<?php
// Xóa mọi bộ đệm để tránh khoảng trắng thừa lọt vào JSON
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/ReportModel.php';

try {
    $reportModel = new ReportModel($conn);
    // Lấy toàn bộ danh sách báo cáo đang diễn ra
    $reports = $reportModel->getActiveReports();

    echo json_encode([
        'status' => 'success',
        'data'   => is_array($reports) ? $reports : []
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'data'   => []
    ]);
}
exit;
