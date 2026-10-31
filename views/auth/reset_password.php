<?php
// File: views/auth/reset_password.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';
$otp   = $_GET['otp'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - Bản đồ Cảnh Báo</title>
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
            margin-bottom: 12px;
        }
        .f8-pill-input:focus {
            border-color: #1dbfaf;
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
            margin-top: 10px;
            transition: opacity 0.2s;
        }
        .f8-btn-submit-reset:hover {
            opacity: 0.93;
        }
    </style>
</head>
<body>
    <div class="f8-auth-card">
        <!-- Nút "< Quay lại" -->
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

        <h1 class="f8-title">Đặt lại mật khẩu</h1>
        <p class="f8-subtitle">
            <?= !empty($token) 
                ? 'Xác thực qua liên kết token hợp lệ (thời hạn 15 phút).' 
                : 'Nhập thông tin mã OTP nhận được trong email (thời hạn 15 phút).' ?>
        </p>

        <div class="f8-form-container">
            <form onsubmit="handleResetFormSubmit(event)">
                <input type="hidden" id="resetToken" value="<?= htmlspecialchars($token) ?>">

                <?php if (empty($token)): ?>
                    <input type="email" id="resetEmail" class="f8-pill-input" value="<?= htmlspecialchars($email) ?>" placeholder="Email tài khoản" required>
                    <input type="text" id="resetOtp" class="f8-pill-input" value="<?= htmlspecialchars($otp) ?>" maxlength="6" placeholder="Mã OTP gồm 6 số" style="text-align:center; font-size:16px; letter-spacing:4px; font-weight:bold;" required>
                <?php endif; ?>

                <input type="password" id="newPassword" class="f8-pill-input" placeholder="Mật khẩu mới (ít nhất 6 ký tự)" required minlength="6" autocomplete="new-password">
                <input type="password" id="confirmPassword" class="f8-pill-input" placeholder="Xác nhận mật khẩu mới" required minlength="6" autocomplete="new-password">

                <button type="submit" id="btnSubmitReset" class="f8-btn-submit-reset">
                    Cập nhật mật khẩu mới
                </button>
            </form>
        </div>

        <div style="margin-top: 28px; text-align: center; font-size: 14px; color: #4e5867;">
            Quay lại <a href="index.php?route=login" style="color: #f05123; font-weight: 700; text-decoration: underline;">Đăng nhập</a>
        </div>
    </div>

    <script src="public/js/api.js"></script>
    <script>
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

        async function handleResetFormSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitReset');
            const token = document.getElementById('resetToken') ? document.getElementById('resetToken').value.trim() : '';
            const emailEl = document.getElementById('resetEmail');
            const otpEl = document.getElementById('resetOtp');
            const email = emailEl ? emailEl.value.trim() : '';
            const otp = otpEl ? otpEl.value.trim() : '';
            const password = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;

            if (password.length < 6) {
                showToast('Mật khẩu mới phải có ít nhất 6 ký tự!', false);
                return;
            }
            if (password !== confirm) {
                showToast('Mật khẩu xác nhận không khớp!', false);
                return;
            }

            btn.disabled = true;
            btn.innerText = 'Đang cập nhật...';

            try {
                await Api.resetPassword({
                    token: token,
                    email: email,
                    otp: otp,
                    password: password,
                    confirm_password: confirm
                });
                showToast('Đặt lại mật khẩu thành công! Vui lòng đăng nhập.', true);
                setTimeout(() => window.location.href = 'index.php?route=login', 900);
            } catch (err) {
                showToast(err.message || 'Đặt lại mật khẩu thất bại!', false);
                btn.disabled = false;
                btn.innerText = 'Cập nhật mật khẩu mới';
            }
        }
    </script>
</body>
</html>
