<?php
// File: views/auth/register.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    header('Location: index.php?route=home');
    exit;
}
require_once __DIR__ . '/../../config/google.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-signin-client_id" content="<?= htmlspecialchars(GOOGLE_CLIENT_ID) ?>">
    <title>Đăng ký tài khoản - Bản đồ Cảnh Báo</title>
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
        /* Modal Google */
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
        <!-- Logo ứng dụng -->
        <a href="index.php?route=home" title="Về trang chủ" style="text-decoration:none;">
            <div class="auth-logo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <path d="M12 8v4"/>
                    <path d="M12 16h.01"/>
                </svg>
            </div>
        </a>

        <!-- Tiêu đề & Cảnh báo phụ -->
        <h1 class="auth-title">Đăng ký tài khoản</h1>
        <p class="auth-warning">
            Mỗi người nên sử dụng riêng một tài khoản, tài khoản nhiều người sử dụng chung sẽ bị khóa.
        </p>

        <!-- 1. MÀN HÌNH CHÍNH: 2 NÚT (GOOGLE + EMAIL/SĐT) -->
        <div id="methodListSection" class="container-360">
            <div style="display: flex; flex-direction: column; gap: 12px; align-items: center;">
                <!-- 1. Nút Google (Chuẩn 4 màu) -->
                <button type="button" class="pill-btn" onclick="startGoogleSignUp()">
                    <span class="pill-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.66-5.17 3.66-9.17z"/>
                            <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.34 24 12 24z"/>
                            <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.17 0 9.98 0 12s.45 3.83 1.25 5.42l4.03-3.15z"/>
                            <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.34 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/>
                        </svg>
                    </span>
                    <span class="pill-text">Đăng ký với Google</span>
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
                    Bạn đã có tài khoản? <a href="index.php?route=login">Đăng nhập</a>
                </div>
                <div style="margin-top: 14px;">
                    <a href="index.php?route=forgot_password">Quên mật khẩu?</a>
                </div>
            </div>

            <div style="margin-top: 20px; padding-top: 16px; border-top: 1px dashed #dce0e3; text-align: center; font-size: 13px; color: #64748b;">
                Quản trị viên hệ thống? <a href="index.php?route=admin_login" style="color: #2563eb; font-weight: 700; text-decoration: none;">Đăng nhập Admin &rarr;</a>
            </div>
        </div>

        <!-- 2. FORM NHẬP EMAIL / SỐ ĐIỆN THOẠI (BƯỚC 1) -->
        <div id="formInputSection" class="container-360" style="display: none;">
            <button type="button" class="btn-back-link" onclick="toggleFormInput(false)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
                Quay lại
            </button>

            <!-- Form Đăng ký -->
            <form id="regForm" onsubmit="handleRegisterSubmit(event)">
                <div style="margin-bottom: 12px;">
                    <label style="display:block; font-size:13px; font-weight:700; color:#292929; margin-bottom:5px;">Họ và tên <span style="color:#f33a58;">*</span></label>
                    <input type="text" id="regFullName" class="form-input-pill" placeholder="Ví dụ: Nguyễn Văn A" required>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="display:block; font-size:13px; font-weight:700; color:#292929; margin-bottom:5px;">Email (nhận mã OTP) <span style="color:#f33a58;">*</span></label>
                    <input type="email" id="regEmail" class="form-input-pill" placeholder="email@domain.com" required oninput="checkEmailRealtime(this.value)" onblur="checkEmailRealtime(this.value)">
                    <div id="emailCheckStatus" style="font-size:12px; margin-top:4px; padding-left:10px;"></div>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="display:block; font-size:13px; font-weight:700; color:#292929; margin-bottom:5px;">Số điện thoại (tùy chọn)</label>
                    <input type="tel" id="regPhone" class="form-input-pill" placeholder="Ví dụ: 0912345678">
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="display:block; font-size:13px; font-weight:700; color:#292929; margin-bottom:5px;">Mật khẩu <span style="color:#f33a58;">*</span></label>
                    <input type="password" id="regPassword" class="form-input-pill" placeholder="Ít nhất 6 ký tự" required minlength="6" autocomplete="new-password">
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="display:block; font-size:13px; font-weight:700; color:#292929; margin-bottom:5px;">Xác nhận mật khẩu <span style="color:#f33a58;">*</span></label>
                    <input type="password" id="regConfirmPassword" class="form-input-pill" placeholder="Nhập lại mật khẩu" required minlength="6" autocomplete="new-password">
                </div>

                <div style="display:flex; align-items:flex-start; gap:8px; margin: 10px 0 14px; font-size:12.5px; color:#4e5867;">
                    <input type="checkbox" id="regTerms" checked required style="width:16px; height:16px; accent-color:#2563eb; margin-top:2px;">
                    <label for="regTerms" style="cursor:pointer; line-height:1.4;">
                        Tôi đồng ý với <a href="#" onclick="alert('Điều khoản dịch vụ: Tôn trọng quy định giao thông, chia sẻ thông tin ngập lụt trung thực và chính xác.'); return false;" style="color:#2563eb; text-decoration:underline;">Điều khoản dịch vụ</a> & <a href="#" onclick="alert('Chính sách bảo mật: Toàn bộ thông tin tài khoản được mã hóa và bảo mật an toàn.'); return false;" style="color:#2563eb; text-decoration:underline;">Chính sách bảo mật</a>.
                    </label>
                </div>

                <button type="submit" id="btnRegSubmit" class="submit-btn-pill">
                    Đăng ký tài khoản
                </button>
            </form>

            <!-- Form Nhập OTP (Bước 2) -->
            <div id="otpSection" style="display: none; text-align: center;">
                <p style="font-size: 13.5px; color: #4e5867; margin-bottom: 14px; line-height: 1.5;">
                    Mã OTP gồm 6 chữ số đã được gửi tới email:<br>
                    <strong id="displayOtpEmail" style="color: #2563eb;"></strong>
                </p>

                <div id="otpDebugBox" style="display:none; background:#fef2f2; border:1px dashed #f87171; color:#991b1b; padding:10px 14px; border-radius:12px; font-size:13px; margin-bottom:16px; text-align:left;">
                    💡 <b>Môi trường thử nghiệm:</b> Mã OTP là: <span id="debugOtpText" style="font-size:16px; font-weight:bold; letter-spacing:2px; color:#111;"></span>
                </div>

                <form onsubmit="handleVerifyOtpSubmit(event)">
                    <div style="margin-bottom: 14px;">
                        <input type="text" id="otpCode" class="form-input-pill" maxlength="6" placeholder="Nhập 6 số OTP" style="text-align:center; font-size:18px; letter-spacing:6px; font-weight:bold;" required autocomplete="one-time-code">
                    </div>
                    <button type="submit" id="btnOtpSubmit" class="submit-btn-pill" style="background:linear-gradient(to right, #10b981, #059669);">
                        Xác nhận & Hoàn tất
                    </button>
                </form>

                <div style="margin-top: 14px; font-size: 13px;">
                    Chưa nhận được mã? <a href="#" onclick="resendOtp(); return false;" style="color:#2563eb; font-weight:700; text-decoration:underline;">Gửi lại mã</a>
                </div>
            </div>
        </div>

        <div style="margin-top: 24px; text-align: center;">
            <a href="index.php?route=home" style="color: #64748b; font-size: 13.5px; text-decoration: none;">&larr; Xem Bản đồ giao thông</a>
        </div>
    </div>

    <!-- MODAL ĐĂNG KÝ VỚI GOOGLE -->
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
                    <span style="font-size:16px; font-weight:700; color:#1f2937;">Đăng ký bằng Google</span>
                </div>
                <button type="button" onclick="closeGoogleModal()" style="background:none; border:none; font-size:20px; color:#9ca3af; cursor:pointer;">&times;</button>
            </div>

            <p style="font-size:13px; color:#6b7280; line-height:1.45; margin-bottom:16px;">
                Nhập email Google của bạn để tạo tài khoản nhanh chóng và an toàn không cần nhập mật khẩu:
            </p>

            <form onsubmit="handleGoogleAuthSubmit(event)">
                <div style="margin-bottom:12px;">
                    <label style="display:block; font-size:12.5px; font-weight:700; color:#374151; margin-bottom:4px;">Địa chỉ Gmail / Google Account</label>
                    <input type="email" id="googleEmailInput" class="form-input-pill" placeholder="name@gmail.com" required style="font-size:13.5px;">
                </div>
                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:12.5px; font-weight:700; color:#374151; margin-bottom:4px;">Họ và tên của bạn</label>
                    <input type="text" id="googleNameInput" class="form-input-pill" placeholder="Nguyễn Văn A" style="font-size:13.5px;">
                </div>

                <button type="submit" id="btnGoogleSubmit" class="submit-btn-pill" style="margin-top:0; background:linear-gradient(to right, #4285F4, #2563eb);">
                    Tạo tài khoản với Google
                </button>
            </form>
        </div>
    </div>

    <script src="public/js/api.js"></script>
    <script>
        let regDataPayload = {};

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
                                showToast('Hủy đăng ký Google hoặc lỗi: ' + tokenResponse.error, false);
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
            showToast('Đang tạo và xác thực tài khoản Google...', true);
            try {
                const payload = (type === 'access_token') 
                    ? { access_token: token } 
                    : { credential: token };

                const res = await Api.googleLogin(payload);
                showToast('Đăng ký bằng Google thành công!', true);
                setTimeout(() => window.location.href = 'index.php?route=home', 600);
            } catch (err) {
                showToast(err.message || 'Xác thực Google thất bại!', false);
            }
        }

        // --- ĐĂNG KÝ VỚI GOOGLE ---
        // Bấm nút Đăng ký với Google qua Firebase Auth
        async function startGoogleSignUp() {
            if (typeof window.loginWithFirebaseGoogle === 'function') {
                try {
                    await window.loginWithFirebaseGoogle('register');
                    return;
                } catch (e) {
                    console.warn('[Firebase Register]', e);
                    return;
                }
            }

            showToast('Đang kết nối cổng Google qua Firebase...', true);
            setTimeout(async () => {
                if (typeof window.loginWithFirebaseGoogle === 'function') {
                    try {
                        await window.loginWithFirebaseGoogle('register');
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
            btn.innerText = 'Đang đăng ký Google...';

            try {
                const res = await Api.googleLogin({ email, name });
                showToast('Đăng ký bằng Google thành công!', true);
                closeGoogleModal();
                setTimeout(() => window.location.href = 'index.php?route=home', 600);
            } catch (err) {
                showToast(err.message || 'Đăng ký Google thất bại!', false);
                btn.disabled = false;
                btn.innerText = 'Tạo tài khoản với Google';
            }
        }

        // --- KIỂM TRA TRÙNG LẶP EMAIL REALTIME ---
        let emailTimer = null;
        function checkEmailRealtime(email) {
            const status = document.getElementById('emailCheckStatus');
            clearTimeout(emailTimer);
            email = (email || '').trim();
            if (!email) {
                status.innerHTML = '';
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                status.innerHTML = '<span style="color:#f33a58;">Email không đúng định dạng</span>';
                return;
            }
            status.innerHTML = '<span style="color:#64748b;">Đang kiểm tra trùng lặp...</span>';
            emailTimer = setTimeout(async () => {
                try {
                    const res = await Api.checkEmail(email);
                    if (res && res.exists) {
                        status.innerHTML = '<span style="color:#f33a58;">⚠ Email này đã được đăng ký!</span>';
                    } else {
                        status.innerHTML = '<span style="color:#10b981;">✓ Email có thể sử dụng</span>';
                    }
                } catch (e) {
                    status.innerHTML = '';
                }
            }, 400);
        }

        // --- GỬI ĐĂNG KÝ BƯỚC 1 (NHẬN OTP) ---
        async function handleRegisterSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('btnRegSubmit');
            const fullName = document.getElementById('regFullName').value.trim();
            const email = document.getElementById('regEmail').value.trim();
            const phone = document.getElementById('regPhone').value.trim();
            const password = document.getElementById('regPassword').value;
            const confirm = document.getElementById('regConfirmPassword').value;
            const terms = document.getElementById('regTerms').checked;

            if (!terms) {
                showToast('Vui lòng đồng ý với Điều khoản và Chính sách bảo mật!', false);
                return;
            }
            if (password.length < 6) {
                showToast('Mật khẩu phải có ít nhất 6 ký tự!', false);
                return;
            }
            if (password !== confirm) {
                showToast('Mật khẩu xác nhận không khớp!', false);
                return;
            }

            btn.disabled = true;
            btn.innerText = 'Đang gửi mã OTP...';

            regDataPayload = {
                full_name: fullName,
                email: email,
                phone: phone,
                password: password,
                confirm_password: confirm,
                terms_agree: 1
            };

            try {
                const res = await Api.register(regDataPayload);
                showToast('Mã OTP xác thực đã được gửi đến email!', true);

                // Chuyển sang form nhập OTP
                document.getElementById('regForm').style.display = 'none';
                document.getElementById('otpSection').style.display = 'block';
                document.getElementById('displayOtpEmail').innerText = email;

                if (res && res.debug_otp) {
                    document.getElementById('otpDebugBox').style.display = 'block';
                    document.getElementById('debugOtpText').innerText = res.debug_otp;
                }
            } catch (err) {
                showToast(err.message || 'Đăng ký thất bại!', false);
                btn.disabled = false;
                btn.innerText = 'Đăng ký tài khoản';
            }
        }

        // --- XÁC THỰC OTP BƯỚC 2 ---
        async function handleVerifyOtpSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('btnOtpSubmit');
            const otp = document.getElementById('otpCode').value.trim();

            if (!otp) {
                showToast('Vui lòng nhập mã OTP gồm 6 chữ số!', false);
                return;
            }

            btn.disabled = true;
            btn.innerText = 'Đang xác thực...';

            try {
                await Api.verifyRegisterOtp(regDataPayload.email, otp);
                showToast('Xác thực thành công! Đang chuyển hướng...', true);
                setTimeout(() => window.location.href = 'index.php?route=home', 600);
            } catch (err) {
                showToast(err.message || 'Xác thực OTP thất bại!', false);
                btn.disabled = false;
                btn.innerText = 'Xác nhận & Hoàn tất';
            }
        }

        async function resendOtp() {
            try {
                showToast('Đang gửi lại mã OTP mới...', true);
                const res = await Api.register(regDataPayload);
                showToast('Đã gửi lại mã OTP mới!', true);
                if (res && res.debug_otp) {
                    document.getElementById('otpDebugBox').style.display = 'block';
                    document.getElementById('debugOtpText').innerText = res.debug_otp;
                }
            } catch (err) {
                showToast(err.message || 'Gửi lại OTP thất bại!', false);
            }
        }
    </script>
</body>
</html>
