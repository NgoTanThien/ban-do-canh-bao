<?php
// ==========================================================================
// Kiểm tra đăng nhập / phân quyền cho API & Web
// Luôn đối chiếu CSDL để tài khoản bị khóa có hiệu lực ngay lập tức.
// Hỗ trợ tự động khôi phục phiên từ Cookie Remember Me 30 ngày.
// ==========================================================================
require_once __DIR__ . '/../models/UserModel.php';

/**
 * Tự động khôi phục phiên đăng nhập từ Remember Me Cookie nếu phiên PHP Session đã hết hạn
 */
function restore_remember_me_session(): void
{
    global $conn;
    if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_me'])) {
        $parts = explode(':', $_COOKIE['remember_me'], 2);
        if (count($parts) === 2) {
            $userId = (int)$parts[0];
            $rawToken = $parts[1];
            $userModel = new UserModel($conn);
            $user = $userModel->findUserByToken($rawToken, 'remember_me');
            if ($user && (int)$user['id'] === $userId && $user['status'] === UserModel::STATUS_ACTIVE) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
            } else {
                setcookie('remember_me', '', time() - 3600, '/');
                unset($_COOKIE['remember_me']);
            }
        }
    }
}

/** Bắt buộc đăng nhập. Trả về dòng user từ DB, nếu không hợp lệ thì dừng với 401/403. */
function require_login(): array
{
    global $conn;

    // Thử khôi phục từ Remember Me cookie nếu có
    restore_remember_me_session();

    if (empty($_SESSION['user_id'])) {
        json_error('Vui lòng đăng nhập', 401);
    }

    $user = (new UserModel($conn))->findById((int)$_SESSION['user_id']);
    if (!$user) {
        $_SESSION = [];
        json_error('Phiên đăng nhập không còn hợp lệ', 401);
    }
    if ($user['status'] !== UserModel::STATUS_ACTIVE) {
        $_SESSION = [];
        json_error('Tài khoản của bạn đã bị khóa', 403);
    }
    return $user;
}

/** Bắt buộc quyền admin. */
function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        json_error('Bạn không có quyền thực hiện thao tác này', 403);
    }
    return $user;
}
