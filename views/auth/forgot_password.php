<?php
// File: views/auth/forgot_password.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    header('Location: index.php?route=home');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu? - Bản đồ Cảnh Báo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
        .f8-auth-card {
            width: 100%;
            max-width: 520px;
            background: #ffffff;
            border-radius: 20px;
            padding: 36px 32px 32px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.06);
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        .f8-btn-back-top {
            position: absolute;
            left: 28px;
            top: 28px;
            background: none;
            border: none;
            color: #4e5867;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            padding: 4px;
            transition: color 0.2s;
        }
        .f8-btn-back-top:hover {
            color: #000;
        }
        .f8-logo {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #f05123, #fe7147);
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(240, 81, 35, 0.25);
            margin-top: 10px;
        }
        .f8-logo span {
            color: #ffffff;
            font-weight: 800;
            font-size: 21px;
            letter-spacing: -0.5px;
        }
        .f8-title {
            font-size: 28px;
            font-weight: 700;
            color: #000000;
            margin-top: 16px;
            margin-bottom: 6px;
            text-align: center;
        }
        .f8-subtitle {
            font-size: 14px;
            color: #4e5867;
            text-align: center;
            margin-bottom: 24px;
        }
        .f8-form-container {
            width: 360px;
            max-width: 100%;
            margin: 0 auto;
        }
        .f8-form-label {
            display: block;
            text-align: left;
            font-size: 14px;
            font-weight: 700;
            color: #292929;
            margin-bottom: 8px;
        }
        .f8-pill-input {
            width: 100%;
            height: 44px;
            border-radius: 9999px;
            background: #ffffff;
            border: 1.5px solid #dce0e3;
            padding: 0 20px;
            font-size: 14px;
            color: #111;
            outline: none;
            transition: all 0.2s;
        }
        .f8-pill-input:focus {
            border-color: #1dbfaf;
        }
        .f8-combo-input {
            width: 100%;
            height: 44px;
            border-radius: 9999px;
            background: #d0d4d9;
            display: flex;
            align-items: center;
            padding: 2px 3px 2px 20px;
            margin-top: 12px;
            box-sizing: border-box;
            transition: background 0.2s;
        }
        .f8-combo-inner-input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            font-size: 14px;
            color: #111;
        }
        .f8-combo-inner-input::placeholder {
            color: #666666;
        }
        .f8-btn-send-code {
            height: 40px;
            padding: 0 24px;
            border-radius: 9999px;
            border: none;
            background: #c4c8cc;
            color: #555555;
            font-size: 14px;
            font-weight: 700;
            cursor: not-allowed;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .f8-btn-send-code.active {
            background: #1dbfaf;
            color: #ffffff;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(29, 191, 175, 0.3);
        }
        .f8-btn-send-code.active:hover {
            background: #17a092;
        }
        .f8-btn-submit-reset {
            width: 100%;
            height: 44px;
            border-radius: 9999px;
            background: linear-gradient(to right, #6ae3df, #8be9dd);
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            margin-top: 20px;
            transition: opacity 0.2s;
        }
        .f8-btn-submit-reset:hover {
            opacity: 0.93;
        }
    </style>
</head>
<body>
    <div class="f8-auth-card">
        <!-- Nút "< Quay lại" nhỏ gọn ở góc trên bên trái -->
        <a href="index.php?route=login" class="f8-btn-back-top">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 18l-6-6 6-6"/>
            </svg>
            Quay lại
        </a>

        <!-- Logo ứng dụng -->
        <a href="index.php?route=home" title="Về trang chủ" style="text-decoration:none;">
            <div class="f8-logo" style="background: linear-gradient(135deg, #2563eb, #3b82f6); color: white;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <path d="M12 8v4"/>
                    <path d="M12 16h.01"/>
                </svg>
            </div>
        </a>

        <!-- Tiêu đề: "Quên mật khẩu?" & Mô tả phụ -->
        <h1 class="f8-title">Quên mật khẩu?</h1>
        <p class="f8-subtitle">Nhập email hoặc tên đăng nhập để nhận mã đặt lại mật khẩu.</p>

        <!-- Khu vực Form (chiều rộng 360px, căn giữa) -->
        <div class="f8-form-container">
            <div id="debugNotice" style="display:none; background:#f0fdfa; border:1px dashed #1dbfaf; color:#0f766e; padding:10px 14px; border-radius:12px; font-size:13px; margin-bottom:14px; text-align:left;">
                💡 <b>Môi trường thử nghiệm:</b> Mã OTP là: <span id="debugOtpValue" style="font-size:16px; font-weight:bold; letter-spacing:2px; color:#111;"></span>
            </div>

            <form onsubmit="handleResetPasswordSubmit(event)">
                <!-- Label: "Tên đăng nhập" -->
                <label class="f8-form-label" for="accountInput">Tên đăng nhập</label>

                <!-- Ô Input 1: Hình viên thuốc -->
                <input type="text" id="accountInput" class="f8-pill-input" placeholder="Email hoặc Username" required oninput="validateInputFormat(this.value)">

                <!-- Ô Input 2: Tổ hợp nhập mã + nút Gửi mã -->
                <div class="f8-combo-input">
                    <input type="text" id="confirmCodeInput" class="f8-combo-inner-input" placeholder="Nhập mã xác nhận" maxlength="6" autocomplete="one-time-code">
                    <button type="button" id="btnSendCode" class="f8-btn-send-code" onclick="sendResetCode()">
                        Gửi mã
                    </button>
                </div>

                <!-- Ô nhập Mật khẩu mới & Xác nhận mật khẩu mới (Hiện khi đã gửi mã) -->
                <div id="newPasswordSection" style="display:none; margin-top:12px;">
                    <input type="password" id="newPasswordInput" class="f8-pill-input" placeholder="Nhập mật khẩu mới (ít nhất 6 ký tự)" style="margin-bottom:12px;" minlength="6">
                    <input type="password" id="confirmPasswordInput" class="f8-pill-input" placeholder="Xác nhận mật khẩu mới" minlength="6">
                </div>

                <!-- Nút Submit: Nền gradient xanh cyan/ngọc nhạt -->
                <button type="submit" id="btnSubmitReset" class="f8-btn-submit-reset">
                    Đặt lại mật khẩu
                </button>
            </form>
        </div>

        <div style="margin-top: 28px; text-align: center; font-size: 14px; color: #4e5867;">
            Bạn đã nhớ mật khẩu? <a href="index.php?route=login" style="color: #f05123; font-weight: 700; text-decoration: underline;">Đăng nhập</a>
        </div>
    </div>

    <script src="public/js/api.js"></script>
    <script>
        let currentTargetEmail = '';
        let currentResetToken = '';

        function showToast(msg, isSuccess = true) {
            const old = document.getElementById('toast');
            if (old) old.remove();
            const t = document.createElement('div');
            t.id = 'toast';
            t.style.cssText = 'position:fixed; top:24px; left:50%; transform:translateX(-50%); z-index:99999; padding:12px 24px; border-radius:9999px; font-weight:600; font-size:14px; color:white; background:' + (isSuccess ? '#10b981' : '#f33a58') + '; box-shadow:0 4px 16px rgba(0,0,0,0.18);';
            t.innerText = msg;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 3500);
        }

        // Khi người dùng nhập đúng định dạng vào ô 1 -> nút "Gửi mã" sáng lên
        function validateInputFormat(val) {
            const btn = document.getElementById('btnSendCode');
            val = (val || '').trim();
            // Hợp lệ nếu là email hoặc username từ 3 ký tự trở lên
            const isEmail = /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/.test(val);
            const isUsername = /^[a-zA-Z0-9_]{3,30}$/.test(val);

            if (isEmail || isUsername) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        }

        // Gửi mã OTP xác nhận
        async function sendResetCode() {
            const btn = document.getElementById('btnSendCode');
            if (!btn.classList.contains('active')) return;

            const account = document.getElementById('accountInput').value.trim();
            btn.innerText = 'Đang gửi...';
            btn.disabled = true;

            try {
                const res = await Api.forgotPassword(account);
                showToast('Đã gửi mã xác nhận đến email tài khoản!', true);
                currentTargetEmail = (res && res.email) ? res.email : account;
                currentResetToken = (res && res.debug_token) ? res.debug_token : '';

                document.getElementById('newPasswordSection').style.display = 'block';
                document.getElementById('confirmCodeInput').focus();

                if (res && res.debug_otp) {
                    document.getElementById('debugNotice').style.display = 'block';
                    document.getElementById('debugOtpValue').innerText = res.debug_otp;
                }

                btn.innerText = 'Gửi lại';
                btn.disabled = false;
            } catch (err) {
                showToast(err.message || 'Không thể gửi mã!', false);
                btn.innerText = 'Gửi mã';
                btn.disabled = false;
            }
        }

        // Đặt lại mật khẩu
        async function handleResetPasswordSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitReset');
            const code = document.getElementById('confirmCodeInput').value.trim();
            const newPass = document.getElementById('newPasswordInput').value;
            const confirmPass = document.getElementById('confirmPasswordInput').value;
            const account = document.getElementById('accountInput').value.trim();

            if (!code) {
                showToast('Vui lòng bấm "Gửi mã" và nhập mã xác nhận!', false);
                document.getElementById('confirmCodeInput').focus();
                return;
            }

            if (document.getElementById('newPasswordSection').style.display === 'none') {
                document.getElementById('newPasswordSection').style.display = 'block';
                showToast('Vui lòng nhập mật khẩu mới!', false);
                return;
            }

            if (newPass.length < 6) {
                showToast('Mật khẩu mới phải có ít nhất 6 ký tự!', false);
                return;
            }
            if (newPass !== confirmPass) {
                showToast('Mật khẩu xác nhận không khớp!', false);
                return;
            }

            btn.disabled = true;
            btn.innerText = 'Đang cập nhật...';

            try {
                await Api.resetPassword({
                    token: currentResetToken,
                    email: currentTargetEmail || account,
                    otp: code,
                    password: newPass,
                    confirm_password: confirmPass
                });

                showToast('Đặt lại mật khẩu thành công! Vui lòng đăng nhập.', true);
                setTimeout(() => window.location.href = 'index.php?route=login', 900);
            } catch (err) {
                showToast(err.message || 'Đặt lại mật khẩu thất bại!', false);
                btn.disabled = false;
                btn.innerText = 'Đặt lại mật khẩu';
            }
        }
    </script>
</body>
</html>
