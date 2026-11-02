<?php
// GET /api/admin/export_reports.php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/ReportModel.php';

require_admin();

$reportModel = new ReportModel($conn);
$reports = $reportModel->getAllForAdmin('all');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="danh_sach_su_co_' . date('Ymd_His') . '.csv"');

// Thêm UTF-8 BOM để Excel hiển thị tiếng Việt có dấu chuẩn
echo "\xEF\xBB\xBF";

$fp = fopen('php://output', 'w');
fputcsv($fp, ['ID', 'Loại sự cố', 'Mức độ', 'Địa chỉ', 'Kinh độ (Lng)', 'Vĩ độ (Lat)', 'Người báo', 'Lượt xác nhận', 'Lượt báo sai', 'Trạng thái', 'Thời gian']);

$typeMap = [
    'ngap_nuoc' => 'Ngập nước',
    'ket_xe'    => 'Kẹt xe',
    'cay_do'    => 'Cây đổ',
    'tai_nan'   => 'Tai nạn',
    'rao_chan'  => 'Rào chắn'
];

foreach ($reports as $r) {
    $typeText = $typeMap[$r['type']] ?? $r['type'];
    $statusText = ($r['status'] === 'dang_dien_ra') ? 'Đang diễn ra' : (($r['status'] === 'da_ket_thuc') ? 'Đã kết thúc' : 'Bị ẩn');
    fputcsv($fp, [
        $r['id'],
        $typeText,
        $r['severity'],
        $r['address_text'],
        $r['longitude'],
        $r['latitude'],
        $r['username'],
        $r['confirm_count'],
        $r['reject_count'],
        $statusText,
        $r['created_at']
    ]);
}

fclose($fp);
exit;

