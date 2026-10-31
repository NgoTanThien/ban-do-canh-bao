<?php
// GET  /api/reports.php[?type=all|ngap_nuoc|ket_xe]  -> danh sách báo cáo đang diễn ra (công khai)
// POST /api/reports.php (multipart/form-data)         -> tạo báo cáo (cần đăng nhập)
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../controllers/ReportController.php';

require_method('GET', 'POST');
$ctrl = new ReportController($conn);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!empty($_GET['my'])) {
        $user = require_login();
        respond($ctrl->myReports((int)$user['id']));
    }
    respond($ctrl->list($_GET['type'] ?? 'all'));
}

$user = require_login();
respond($ctrl->create((int)$user['id'], $_POST, $_FILES), 201);

