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
    // 1. TRANG ĐĂNG NHẬP
    case 'login':
        if (isset($_SESSION['user_id'])) {
            $redirect = (($_SESSION['role'] ?? 'user') === 'admin') 
                ? 'index.php?route=admin_dashboard' 
                : 'index.php?route=home';
            header("Location: $redirect");
            exit;
        }
        if (file_exists(__DIR__ . '/views/auth/login.php')) {
            require_once __DIR__ . '/views/auth/login.php';
        } else {
            header('Location: index.php?route=home');
        }
        exit;

    case 'admin_login':
        // Nếu đã đăng nhập với vai trò Admin thì chuyển thẳng vào Dashboard
        if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin') {
            header('Location: index.php?route=admin_dashboard');
            exit;
        }
        // Nếu đang đăng nhập bằng tài khoản người dùng thường, tự động giải phóng phiên để mở cổng Admin
        if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') !== 'admin') {
            (new AuthController($conn))->logout();
        }
        if (file_exists(__DIR__ . '/views/auth/login.php')) {
            require_once __DIR__ . '/views/auth/login.php';
        } else {
            header('Location: index.php?route=home');
        }
        exit;

    // 2. TRANG ĐĂNG KÝ
    case 'register':
        if (isset($_SESSION['user_id'])) {
            header('Location: index.php?route=home');
            exit;
        }
        if (file_exists(__DIR__ . '/views/auth/register.php')) {
            require_once __DIR__ . '/views/auth/register.php';
        } else {
            header('Location: index.php?route=home');
        }
        exit;

    // 3. QUÊN MẬT KHẨU
    case 'forgot_password':
        if (isset($_SESSION['user_id'])) {
            header('Location: index.php?route=home');
            exit;
        }
        if (file_exists(__DIR__ . '/views/auth/forgot_password.php')) {
            require_once __DIR__ . '/views/auth/forgot_password.php';
        } else {
            header('Location: index.php?route=home');
        }
        exit;

    // 4. ĐẶT LẠI MẬT KHẨU
    case 'reset_password':
        if (file_exists(__DIR__ . '/views/auth/reset_password.php')) {
            require_once __DIR__ . '/views/auth/reset_password.php';
        } else {
            header('Location: index.php?route=home');
        }
        exit;

    // 5. ĐĂNG XUẤT (Xóa sạch session, token DB & cookie -> Giữ nguyên trên trang index/home)
    case 'logout':
        (new AuthController($conn))->logout();
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header('Location: index.php?route=home&logged_out=1&_t=' . time());
        exit;

    // 6. TRANG QUẢN TRỊ VIÊN (ADMIN DASHBOARD)
    case 'admin_dashboard':
    case 'admin':
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
            header('Location: index.php?route=admin_login');
            exit;
        }
        
        if (file_exists(__DIR__ . '/views/admin/dashboard.php')) {
            require_once __DIR__ . '/views/admin/dashboard.php';
        } else {
            echo "<h2>Lỗi: Không tìm thấy giao diện Admin Dashboard.</h2>";
        }
        exit;

    // 7. MẶC ĐỊNH: HIỂN THỊ BẢN ĐỒ CÔNG CỘNG
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
