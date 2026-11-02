<?php
// File: index.php
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_GET['logged_out']) || !empty($_GET['_t']) || !empty($_GET['logout'])) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/AuthController.php';

// Lấy tham số điều hướng trên URL (mặc định là 'home' - Trang bản đồ)
$route = $_GET['route'] ?? 'home';

// Khôi phục phiên tự động nếu có cookie Remember Me 30 ngày hợp lệ (trừ khi đang yêu cầu đăng xuất)
if ($route !== 'logout') {
    restore_remember_me_session();
}

switch ($route) {
    // 1. ĐĂNG XUẤT (Xóa sạch session, token DB & cookie)
    case 'logout':
        (new AuthController($conn))->logout();
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header('Location: index.php?route=home&logged_out=1&_t=' . time());
        exit;

    // 2. TRANG QUẢN TRỊ VIÊN (ADMIN DASHBOARD)
    case 'admin_dashboard':
    case 'admin':
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
            header('Location: index.php?route=home');
            exit;
        }
        if (file_exists(__DIR__ . '/views/admin/dashboard.php')) {
            require_once __DIR__ . '/views/admin/dashboard.php';
        } else {
            echo "<h2>Lỗi: Không tìm thấy giao diện Admin Dashboard.</h2>";
        }
        exit;

    // 3. MẶC ĐỊNH: HIỂN THỊ BẢN ĐỒ CÔNG CỘNG
    case 'home':
    default:
        break;
}

// =========================================================================
// GIAO DIỆN BẢN ĐỒ CHÍNH (Chỉ chạy khi $route là 'home')
// =========================================================================
require_once __DIR__ . '/views/layout/header.php';
require_once __DIR__ . '/views/layout/sidebar.php';
?>

<!-- KHUNG HIỂN THỊ BẢN ĐỒ TOÀN MÀN HÌNH -->
<div class="main-content">
    <div id="map"></div>
</div>

<?php
require_once __DIR__ . '/views/layout/footer.php';
?>
