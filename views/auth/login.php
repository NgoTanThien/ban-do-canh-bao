<?php
// File: views/auth/login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    $redirect = (($_SESSION['role'] ?? 'user') === 'admin') 
        ? 'index.php?route=admin_dashboard' 
        : 'index.php?route=home';
    header("Location: $redirect");
    exit;
}
require_once __DIR__ . '/../../config/google.php';
$isAdminRoute = (isset($_GET['route']) && $_GET['route'] === 'admin_login') || isset($_GET['admin']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-signin-client_id" content="<?= htmlspecialchars(GOOGLE_CLIENT_ID) ?>">
    <title>Đăng nhập - Bản đồ Cảnh Báo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
        window.GOOGLE_CLIENT_ID = "<?= htmlspecialchars(GOOGLE_CLIENT_ID) ?>";
    </script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #f0f2f5;
            color: #292929;
            padding: 24px 16px;
        }
        .auth-card {
            width: 100%;
            max-width: 520px;
            background: #ffffff;
            border-radius: 20px;
            padding: 40px 32px 32px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.06);
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        .auth-logo {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.28);
        }
        .auth-logo.admin {
            background: linear-gradient(135deg, #0f172a, #334155);
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.3);
        }
        .auth-title {
            font-size: 28px;
            font-weight: 700;
            color: #292929;
            margin-top: 18px;
            margin-bottom: 8px;
            text-align: center;
        }
        .auth-warning {
            font-size: 13.5px;
            color: #f33a58;
            max-width: 360px;
            margin: 0 auto 24px;
            text-align: center;
            line-height: 1.45;
        }
        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            padding: 4px 12px;
            border-radius: 9999px;
            margin-top: 10px;
        }
        .container-360 {
            width: 360px;
            max-width: 100%;
            margin: 0 auto;
        }
        .pill-btn {
            width: 360px;
            max-width: 100%;
            height: 44px;
            border-radius: 9999px;
            background: #ffffff;
            border: 1.5px solid #dce0e3;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            outline: none;
            padding: 0 16px;
        }
        .pill-btn:hover {
            background: #f8f9fa;
            border-color: #c9ced4;
        }
        .pill-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .pill-text {
            font-size: 14px;
            font-weight: 600;
            color: #35414c;
            text-align: center;
        }
        .form-input-pill {
            width: 100%;
            height: 44px;
            border-radius: 9999px;
            background: #ffffff;
            border: 1.5px solid #dce0e3;
            padding: 0 20px;
            font-size: 14px;
            color: #292929;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-input-pill:focus {
            border-color: #2563eb;
        }
        .submit-btn-pill {
            width: 100%;
            height: 44px;
            border-radius: 9999px;
            background: linear-gradient(to right, #2563eb, #3b82f6);
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: opacity 0.2s;
            margin-top: 14px;
        }
        .submit-btn-pill:hover {
            opacity: 0.92;
        }
        .submit-btn-admin {
            width: 100%;
            height: 44px;
            border-radius: 9999px;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: opacity 0.2s;
            margin-top: 14px;
        }
        .submit-btn-admin:hover {
            opacity: 0.92;
        }
        .btn-back-link {
            background: none;
            border: none;
            color: #64748b;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 0;
            margin-bottom: 16px;
        }
        .btn-back-link:hover {
            color: #111;
        }
        .auth-nav-links {
            margin-top: 24px;
            text-align: center;
            font-size: 14px;
            color: #35414c;
        }
        .auth-nav-links a {
            color: #2563eb;
            font-weight: 700;
            text-decoration: underline;
        }
        .admin-portal-box {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px dashed #dce0e3;
            width: 360px;
            max-width: 100%;
            text-align: center;
        }
        .admin-portal-btn {
            background: none;
            border: 1px solid #cbd5e1;
            padding: 8px 18px;
            border-radius: 9999px;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .admin-portal-btn:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
            color: #0f172a;
        }
        /* Modal chọn tài khoản Google */
        .google-modal-backdrop {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(3px);
            z-index: 999999;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .google-modal-card {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            animation: modalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes modalPop {
            0% { transform: scale(0.92); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
    <!-- Firebase Auth Web SDK (Google Sign-In) -->
    <script type="module" src="public/js/firebase-auth.js"></script>
</head>
<body>
    <div class="auth-card">
        <!-- Logo ứng dụng Bản đồ Cảnh Báo -->
        <a href="index.php?route=home" title="Về trang chủ" style="text-decoration:none;">
            <div class="auth-logo" id="mainAuthLogo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <path d="M12 8v4"/>
                    <path d="M12 16h.01"/>
                </svg>
            </div>
        </a>

        <!-- Tiêu đề & Cảnh báo phụ -->
        <h1 class="auth-title" id="mainAuthTitle">Đăng nhập</h1>
        <p class="auth-warning" id="mainAuthWarning">
            Mỗi người nên sử dụng riêng một tài khoản, tài khoản nhiều người sử dụng chung sẽ bị khóa.
        </p>

        <!-- ============================================================ -->
        <!-- 1. MÀN HÌNH CHÍNH (NGƯỜI DÙNG): 2 NÚT (GOOGLE + EMAIL/SĐT)    -->
        <!-- ============================================================ -->
        <div id="methodListSection" class="container-360" style="<?= $isAdminRoute ? 'display:none;' : 'display:block;' ?>">
            <div style="display: flex; flex-direction: column; gap: 12px; align-items: center;">
                <!-- 1. Nút Google (Chuẩn 4 màu) -->
                <button type="button" class="pill-btn" onclick="startGoogleSignIn()">
                    <span class="pill-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.66-5.17 3.66-9.17z"/>
                            <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.34 24 12 24z"/>
                            <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.17 0 9.98 0 12s.45 3.83 1.25 5.42l4.03-3.15z"/>
                            <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.34 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/>
                        </svg>
                    </span>
                    <span class="pill-text">Đăng nhập với Google</span>
                </button>

                <!-- 2. Nút Sử dụng email / số điện thoại -->
                <button type="button" class="pill-btn" onclick="toggleFormInput(true)">
                    <span class="pill-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#555555" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </span>
                    <span class="pill-text" style="color: #111111;">Sử dụng email / số điện thoại</span>
                </button>
            </div>

            <!-- Liên kết điều hướng bên dưới -->
            <div class="auth-nav-links">
                <div>
                    Bạn chưa có tài khoản? <a href="index.php?route=register">Đăng ký</a>
                </div>
                <div style="margin-top: 14px;">
                    <a href="index.php?route=forgot_password">Quên mật khẩu?</a>
                </div>
            </div>

            <!-- PHẦN ĐĂNG NHẬP DÀNH CHO ADMIN -->
            <div class="admin-portal-box">
                <button type="button" class="admin-portal-btn" onclick="toggleAdminLogin(true)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <path d="m9 12 2 2 4-4"/>
                    </svg>
                    Đăng nhập Quản trị viên (Admin) &rarr;
                </button>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- 2. FORM NHẬP EMAIL / SĐT (NGƯỜI DÙNG THƯỜNG)                -->
        <!-- ============================================================ -->
        <div id="formInputSection" class="container-360" style="display: none;">
            <button type="button" class="btn-back-link" onclick="toggleFormInput(false)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
                Quay lại
            </button>

            <form onsubmit="handleLoginFormSubmit(event)">
                <div style="margin-bottom: 14px;">
                    <label style="display:block; font-size:14px; font-weight:700; color:#292929; margin-bottom:6px;">Tên đăng nhập hoặc Email</label>
                    <input type="text" id="loginAccount" class="form-input-pill" placeholder="Email hoặc Số điện thoại" required autocomplete="username">
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display:block; font-size:14px; font-weight:700; color:#292929; margin-bottom:6px;">Mật khẩu</label>
                    <input type="password" id="loginPassword" class="form-input-pill" placeholder="Nhập mật khẩu" required autocomplete="current-password">
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; font-size:13.5px; margin-bottom:12px;">
                    <label style="display:flex; align-items:center; gap:6px; color:#4e5867; cursor:pointer;">
                        <input type="checkbox" id="loginRememberMe" checked style="width:16px; height:16px; accent-color:#2563eb;">
                        <span>Ghi nhớ 30 ngày</span>
                    </label>
                    <a href="index.php?route=forgot_password" style="color:#2563eb; text-decoration:none; font-weight:600;">Quên mật khẩu?</a>
                </div>

                <button type="submit" id="btnLoginSubmit" class="submit-btn-pill">
                    Đăng nhập
                </button>
            </form>

            <div class="auth-nav-links">
                <div>
                    Bạn chưa có tài khoản? <a href="index.php?route=register">Đăng ký</a>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- 3. PHẦN ĐĂNG NHẬP DÀNH RIÊNG CHO QUẢN TRỊ VIÊN (ADMIN)       -->
        <!-- ============================================================ -->
        <div id="adminLoginSection" class="container-360" style="<?= $isAdminRoute ? 'display:block;' : 'display:none;' ?>">
            <button type="button" class="btn-back-link" onclick="toggleAdminLogin(false)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
                Quay lại Đăng nhập người dùng
            </button>

            <div style="text-align:center; margin-bottom: 18px;">
                <span class="admin-badge">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    CỔNG QUẢN TRỊ VIÊN (ADMIN)
                </span>
                <p style="font-size: 13px; color: #64748b; margin-top: 8px;">
                    Dành riêng cho Quản trị viên quản lý bản đồ giao thông và người dùng.
                </p>
            </div>

            <form onsubmit="handleAdminLoginSubmit(event)">
                <div style="margin-bottom: 14px;">
                    <label style="display:block; font-size:14px; font-weight:700; color:#0f172a; margin-bottom:6px;">Tài khoản hoặc Email Admin</label>
                    <input type="text" id="adminAccount" class="form-input-pill" placeholder="admin@trafficmap.vn" required autocomplete="username" value="admin_super">
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display:block; font-size:14px; font-weight:700; color:#0f172a; margin-bottom:6px;">Mật khẩu Quản trị</label>
                    <input type="password" id="adminPassword" class="form-input-pill" placeholder="Nhập mật khẩu quản trị viên" required autocomplete="current-password">
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; font-size:13.5px; margin-bottom:12px;">
                    <label style="display:flex; align-items:center; gap:6px; color:#475569; cursor:pointer;">
                        <input type="checkbox" id="adminRememberMe" checked style="width:16px; height:16px; accent-color:#0f172a;">
                        <span>Duy trì phiên bảo mật</span>
                    </label>
                    <span style="color:#64748b; font-size:12px;">Cổng kiểm soát nội bộ</span>
                </div>

                <button type="submit" id="btnAdminSubmit" class="submit-btn-admin">
                    Đăng nhập Quản trị viên
                </button>
            </form>
        </div>

        <div style="margin-top: 24px; text-align: center;">
            <a href="index.php?route=home" style="color: #64748b; font-size: 13.5px; text-decoration: none;">&larr; Xem Bản đồ giao thông</a>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL ĐĂNG NHẬP / KẾT NỐI VỚI GOOGLE                         -->
    <!-- ============================================================ -->
    <div id="googleAuthModal" class="google-modal-backdrop" onclick="if(event.target===this) closeGoogleModal()">
        <div class="google-modal-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <svg width="24" height="24" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.66-5.17 3.66-9.17z"/>
                        <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.34 24 12 24z"/>
                        <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.17 0 9.98 0 12s.45 3.83 1.25 5.42l4.03-3.15z"/>
                        <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.34 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/>
                    </svg>
                    <span style="font-size:16px; font-weight:700; color:#1f2937;">Đăng nhập với Google</span>
                </div>
                <button type="button" onclick="closeGoogleModal()" style="background:none; border:none; font-size:20px; color:#9ca3af; cursor:pointer;">&times;</button>
            </div>

            <p style="font-size:13px; color:#6b7280; line-height:1.45; margin-bottom:16px;">
                Chọn tài khoản Google của bạn hoặc nhập email Google để đăng nhập / đăng ký nhanh chóng:
            </p>

            <form onsubmit="handleGoogleAuthSubmit(event)">
                <div style="margin-bottom:12px;">
                    <label style="display:block; font-size:12.5px; font-weight:700; color:#374151; margin-bottom:4px;">Địa chỉ Gmail / Google Account</label>
                    <input type="email" id="googleEmailInput" class="form-input-pill" placeholder="name@gmail.com" required style="font-size:13.5px;">
                </div>
                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:12.5px; font-weight:700; color:#374151; margin-bottom:4px;">Họ và tên hiển thị</label>
                    <input type="text" id="googleNameInput" class="form-input-pill" placeholder="Nguyễn Văn A" style="font-size:13.5px;">
                </div>

                <button type="submit" id="btnGoogleSubmit" class="submit-btn-pill" style="margin-top:0; background:linear-gradient(to right, #4285F4, #2563eb);">
                    Tiếp tục với tài khoản Google
                </button>
            </form>
        </div>
    </div>

    <script src="public/js/api.js"></script>
    <script>
        function showToast(msg, isSuccess = true) {
            const old = document.getElementById('toast');
            if (old) old.remove();
            const t = document.createElement('div');
            t.id = 'toast';
            t.style.cssText = 'position:fixed; top:24px; left:50%; transform:translateX(-50%); z-index:9999999; padding:12px 24px; border-radius:9999px; font-weight:600; font-size:14px; color:white; background:' + (isSuccess ? '#10b981' : '#f33a58') + '; box-shadow:0 4px 16px rgba(0,0,0,0.18);';
            t.innerText = msg;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 3500);
        }

        function toggleFormInput(show) {
            document.getElementById('methodListSection').style.display = show ? 'none' : 'block';
            document.getElementById('formInputSection').style.display = show ? 'block' : 'none';
            document.getElementById('adminLoginSection').style.display = 'none';
            updateHeaderTitles(false);
        }

        function toggleAdminLogin(show) {
            document.getElementById('methodListSection').style.display = show ? 'none' : 'block';
            document.getElementById('formInputSection').style.display = 'none';
            document.getElementById('adminLoginSection').style.display = show ? 'block' : 'none';
            updateHeaderTitles(show);
        }

        function updateHeaderTitles(isAdmin) {
            const title = document.getElementById('mainAuthTitle');
            const warn = document.getElementById('mainAuthWarning');
            const logo = document.getElementById('mainAuthLogo');

            if (isAdmin) {
                title.innerText = 'Đăng nhập Quản trị viên';
                warn.innerText = 'Cổng đăng nhập nội bộ. Chỉ tài khoản Quản trị viên mới có thể truy cập hệ thống.';
                logo.classList.add('admin');
            } else {
                title.innerText = 'Đăng nhập';
                warn.innerText = 'Mỗi người nên sử dụng riêng một tài khoản, tài khoản nhiều người sử dụng chung sẽ bị khóa.';
                logo.classList.remove('admin');
            }
        }

        // --- TÍCH HỢP GOOGLE IDENTITY SERVICES (GIS SDK) ---
        let googleTokenClient = null;

        function initGoogleGSI() {
            try {
                if (typeof google !== 'undefined' && google.accounts && google.accounts.oauth2) {
                    googleTokenClient = google.accounts.oauth2.initTokenClient({
                        client_id: window.GOOGLE_CLIENT_ID,
                        scope: 'email profile openid',
                        callback: async (tokenResponse) => {
                            if (tokenResponse && tokenResponse.access_token) {
                                await sendGoogleTokenToBackend(tokenResponse.access_token, 'access_token');
                            } else if (tokenResponse && tokenResponse.error) {
                                showToast('Hủy đăng nhập Google hoặc lỗi: ' + tokenResponse.error, false);
                            }
                        }
                    });
                }

                if (typeof google !== 'undefined' && google.accounts && google.accounts.id) {
                    google.accounts.id.initialize({
                        client_id: window.GOOGLE_CLIENT_ID,
                        callback: async (credentialResponse) => {
                            if (credentialResponse && credentialResponse.credential) {
                                await sendGoogleTokenToBackend(credentialResponse.credential, 'id_token');
                            }
                        }
                    });
                }
            } catch (e) {
                console.warn('[Google GIS]', e);
            }
        }
        window.addEventListener('load', initGoogleGSI);

        // Gửi token xác thực lên Backend
        async function sendGoogleTokenToBackend(token, type = 'access_token') {
            showToast('Đang xác thực chữ ký số từ Google...', true);
            try {
                const payload = (type === 'access_token') 
                    ? { access_token: token } 
                    : { credential: token };

                const res = await Api.googleLogin(payload);
                showToast('Đăng nhập với Google thành công!', true);
                setTimeout(() => {
                    if (res && res.role === 'admin') {
                        window.location.href = 'index.php?route=admin_dashboard';
                    } else {
                        window.location.href = 'index.php?route=home';
                    }
                }, 600);
            } catch (err) {
                showToast(err.message || 'Xác thực Google thất bại!', false);
            }
        }

        // Bấm nút Đăng nhập với Google
        // Bấm nút Đăng nhập với Google qua Firebase Auth
        async function startGoogleSignIn() {
            if (typeof window.loginWithFirebaseGoogle === 'function') {
                try {
                    await window.loginWithFirebaseGoogle('login');
                    return;
                } catch (e) {
                    console.warn('[Firebase Login]', e);
                    return;
                }
            }

            showToast('Đang kết nối cổng Google qua Firebase...', true);
            setTimeout(async () => {
                if (typeof window.loginWithFirebaseGoogle === 'function') {
                    try {
                        await window.loginWithFirebaseGoogle('login');
                        return;
                    } catch (e) {
                        return;
                    }
                }

                if (googleTokenClient && window.GOOGLE_CLIENT_ID && !window.GOOGLE_CLIENT_ID.includes('sampleclientid')) {
                    googleTokenClient.requestAccessToken({ prompt: 'select_account' });
                    return;
                }

                const modal = document.getElementById('googleAuthModal');
                if (modal) {
                    modal.style.display = 'flex';
                    const input = document.getElementById('googleEmailInput');
                    if (input && !input.value) input.focus();
                }
            }, 300);
        }

        function closeGoogleModal() {
            const modal = document.getElementById('googleAuthModal');
            if (modal) modal.style.display = 'none';
        }

        async function handleGoogleAuthSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('btnGoogleSubmit');
            const email = document.getElementById('googleEmailInput').value.trim();
            const name = document.getElementById('googleNameInput').value.trim();

            if (!email) {
                showToast('Vui lòng nhập địa chỉ email Google!', false);
                return;
            }

            btn.disabled = true;
            btn.innerText = 'Đang xác thực Google...';

            try {
                const res = await Api.googleLogin({ email, name });
                showToast('Đăng nhập Google thành công!', true);
                closeGoogleModal();
                setTimeout(() => {
                    if (res && res.role === 'admin') {
                        window.location.href = 'index.php?route=admin_dashboard';
                    } else {
                        window.location.href = 'index.php?route=home';
                    }
                }, 600);
            } catch (err) {
                showToast(err.message || 'Đăng nhập Google thất bại!', false);
                btn.disabled = false;
                btn.innerText = 'Tiếp tục với tài khoản Google';
            }
        }

        // --- ĐĂNG NHẬP NGƯỜI DÙNG THƯỜNG ---
        async function handleLoginFormSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('btnLoginSubmit');
            const account = document.getElementById('loginAccount').value.trim();
            const password = document.getElementById('loginPassword').value;
            const rememberMe = document.getElementById('loginRememberMe').checked;

            btn.disabled = true;
            btn.innerText = 'Đang đăng nhập...';

            try {
                const res = await Api.login(account, password, rememberMe);
                showToast('Đăng nhập thành công!', true);
                setTimeout(() => {
                    if (res && res.role === 'admin') {
                        window.location.href = 'index.php?route=admin_dashboard';
                    } else {
                        window.location.href = 'index.php?route=home';
                    }
                }, 600);
            } catch (err) {
                showToast(err.message || 'Đăng nhập thất bại!', false);
                btn.disabled = false;
                btn.innerText = 'Đăng nhập';
            }
        }

        // --- ĐĂNG NHẬP DÀNH CHO ADMIN ---
        async function handleAdminLoginSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('btnAdminSubmit');
            const account = document.getElementById('adminAccount').value.trim();
            const password = document.getElementById('adminPassword').value;
            const rememberMe = document.getElementById('adminRememberMe').checked;

            btn.disabled = true;
            btn.innerText = 'Đang xác thực Quản trị viên...';

            try {
                const res = await Api.login(account, password, rememberMe);
                if (res && res.role === 'admin') {
                    showToast('Xác thực Quản trị viên thành công!', true);
                    setTimeout(() => {
                        window.location.href = 'index.php?route=admin_dashboard';
                    }, 600);
                } else {
                    showToast('Tài khoản này không có quyền Quản trị viên! Vui lòng dùng cổng Người dùng.', false);
                    btn.disabled = false;
                    btn.innerText = 'Đăng nhập Quản trị viên';
                }
            } catch (err) {
                showToast(err.message || 'Đăng nhập Quản trị viên thất bại!', false);
                btn.disabled = false;
                btn.innerText = 'Đăng nhập Quản trị viên';
            }
        }

        <?php if ($isAdminRoute): ?>
        // Tự động mở form admin nếu truy cập theo route admin_login
        updateHeaderTitles(true);
        <?php endif; ?>
    </script>
</body>
</html>
