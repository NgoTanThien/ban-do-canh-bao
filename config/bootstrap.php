<?php
// ==========================================================================
// Khởi động chung cho mọi API: session, lỗi -> JSON, kết nối DB, xác thực
// Dùng: require_once __DIR__ . '/../config/bootstrap.php';
// ==========================================================================

date_default_timezone_set('Asia/Ho_Chi_Minh');

// Không để PHP in lỗi HTML ra làm hỏng JSON
ini_set('display_errors', '0');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/response.php';

// Mọi lỗi không bắt được -> JSON 500 (chi tiết chỉ ghi vào log)
set_exception_handler(function (Throwable $e) {
    error_log('[API] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    json_error('Lỗi hệ thống, vui lòng thử lại sau', 500);
});

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
