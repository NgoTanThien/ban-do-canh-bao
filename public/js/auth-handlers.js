// ==========================================================================
// 4. XỬ LÝ XÁC THỰC NGƯỜI DÙNG & OTP (public/js/auth-handlers.js)
// ==========================================================================

// 1. Kiểm tra trùng lặp email realtime khi người dùng nhập trên form Đăng ký
let emailRealtimeTimer = null;
function handleEmailRealtimeCheck(email) {
    const statusDiv = document.getElementById('emailCheckStatus');
    if (!statusDiv) return;
    clearTimeout(emailRealtimeTimer);

    email = (email || '').trim();
    if (!email) {
        statusDiv.innerHTML = '';
        return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        statusDiv.innerHTML = '<span style="color:#f87171;">Email không đúng định dạng</span>';
        return;
    }

    statusDiv.innerHTML = '<span style="color:#94a3b8;">Đang kiểm tra trùng lặp...</span>';
    emailRealtimeTimer = setTimeout(async () => {
        try {
            const res = await Api.checkEmail(email);
            if (res && res.exists) {
                statusDiv.innerHTML = '<span style="color:#f87171;"><i class="ph-bold ph-warning-circle"></i> Email này đã được đăng ký!</span>';
            } else {
                statusDiv.innerHTML = '<span style="color:#34d399;"><i class="ph-bold ph-check-circle"></i> Email hợp lệ có thể sử dụng</span>';
            }
        } catch (e) {
            statusDiv.innerHTML = '';
        }
    }, 400);
}

// 2. Xử lý Đăng nhập tài khoản qua API (Kèm Remember Me 30 ngày)
// 2. Xử lý Đăng nhập tài khoản qua API (Tự động phân quyền User / Admin)
async function handleAuthLogin(e) {
    e.preventDefault();
    const btn = document.getElementById('btnLoginSubmit');
    const u = document.getElementById('loginUsername').value.trim();
    const p = document.getElementById('loginPassword').value;
    const rememberMe = document.getElementById('loginRememberMe') ? document.getElementById('loginRememberMe').checked : false;

    if (btn) { btn.disabled = true; btn.innerText = 'Đang đăng nhập...'; }

    try {
        const res = await Api.login(u, p, rememberMe);
        if (res && res.role === 'admin') {
            showToast("Xin chào Quản trị viên! Đang vào trang quản trị...", true);
            setTimeout(() => {
                window.location.href = 'index.php?route=admin_dashboard';
            }, 600);
        } else {
            showToast("Đăng nhập thành công!", true);
            setTimeout(() => window.location.reload(), 600);
        }
    } catch (err) {
        showToast(err.message || "Đăng nhập thất bại!", false);
        if (btn) { btn.disabled = false; btn.innerText = 'Đăng nhập'; }
    }
}

// 3. Xử lý Đăng ký tài khoản (Bước 1: Kiểm tra thông tin & Gửi mã OTP)
async function handleAuthRegister(e) {
    e.preventDefault();
    const btn = document.getElementById('btnRegisterSubmit');
    const fullName = document.getElementById('regFullName').value.trim();
     const username = document.getElementById('regUsername').value.trim();
    const email = document.getElementById('regEmail').value.trim();
    const password = document.getElementById('regPassword').value;
    const confirmPassword = document.getElementById('regConfirmPassword').value;
    const terms = document.getElementById('regTermsAgree') ? document.getElementById('regTermsAgree').checked : false;

    if (!terms) {
        showToast("Bạn cần đồng ý với Điều khoản dịch vụ và Chính sách bảo mật!", false);
        return;
    }
    if (password.length < 6) {
        showToast("Mật khẩu phải có ít nhất 6 ký tự!", false);
        return;
    }
    if (password !== confirmPassword) {
        showToast("Mật khẩu xác nhận không khớp!", false);
        return;
    }

    if (btn) { btn.disabled = true; btn.innerText = 'Đang gửi mã OTP...'; }

    try {
        const payload = {
            full_name: fullName,
            email: email,
            username: username,
            password: password,
            confirm_password: confirmPassword,
            terms_agree: 1
        };
        const res = await Api.register(payload);
        showToast("Mã xác thực OTP đã được gửi đến email của bạn!", true);

        openSubPanel('Xác thực địa chỉ Email', 'verify_otp', {
            email: email,
            fullName: fullName,
            regData: payload
        });
    } catch (err) {
        showToast(err.message || "Đăng ký thất bại!", false);
        if (btn) { btn.disabled = false; btn.innerText = 'Đăng ký tài khoản'; }
    }
}

// 4. Bước 2: Xác thực mã OTP và kích hoạt tài khoản
async function handleVerifyRegisterOtp(e) {
    e.preventDefault();
    const btn = document.getElementById('btnVerifyOtpSubmit');
    const email = document.getElementById('otpTargetEmail').value;
    const otp = document.getElementById('regOtpInput').value.trim();

    if (!otp) {
        showToast("Vui lòng nhập mã OTP gồm 6 số!", false);
        return;
    }

    if (btn) { btn.disabled = true; btn.innerText = 'Đang xác thực...'; }

    try {
        await Api.verifyRegisterOtp(email, otp);
        showToast("Xác thực tài khoản thành công! Đang chuyển hướng...", true);
        setTimeout(() => window.location.reload(), 700);
    } catch (err) {
        showToast(err.message || "Xác thực OTP thất bại!", false);
        if (btn) { btn.disabled = false; btn.innerText = 'Xác nhận & Hoàn tất'; }
    }
}

// 5. Gửi lại mã OTP đăng ký
async function handleResendRegisterOtp(email) {
    try {
        showToast("Đang gửi lại mã OTP mới...", true);
        const data = (window.currentSubData && window.currentSubData.regData) || { email: email };
        const res = await Api.register(data);
        showToast("Đã gửi lại mã OTP mới đến email!", true);
        openSubPanel('Xác thực địa chỉ Email', 'verify_otp', {
            email: email,
            regData: data
        });
    } catch (err) {
        showToast(err.message || "Gửi lại OTP thất bại!", false);
    }
}

// 6. Xử lý gửi yêu cầu Quên mật khẩu
async function handleForgotPassword(e) {
    e.preventDefault();
    const btn = document.getElementById('btnForgotSubmit');
    const email = document.getElementById('forgotEmail').value.trim();

    if (!email) {
        showToast("Vui lòng nhập địa chỉ email!", false);
        return;
    }

    if (btn) { btn.disabled = true; btn.innerText = 'Đang xử lý...'; }

    try {
        const res = await Api.forgotPassword(email);
        showToast("Đã gửi liên kết và mã OTP đặt lại mật khẩu!", true);
        openSubPanel('Đặt lại mật khẩu', 'reset_password', {
            email: email
        });
    } catch (err) {
        showToast(err.message || "Không thể gửi yêu cầu đặt lại mật khẩu!", false);
        if (btn) { btn.disabled = false; btn.innerText = 'Gửi liên kết & Mã OTP'; }
    }
}

// 7. Xử lý Đặt lại mật khẩu mới
async function handleResetPassword(e) {
    e.preventDefault();
    const btn = document.getElementById('btnResetSubmit');
    const token = document.getElementById('resetToken') ? document.getElementById('resetToken').value.trim() : '';
    const email = document.getElementById('resetEmail') ? document.getElementById('resetEmail').value.trim() : '';
    const otp = document.getElementById('resetOtpCode') ? document.getElementById('resetOtpCode').value.trim() : '';
    const newPassword = document.getElementById('resetNewPassword').value;
    const confirmPassword = document.getElementById('resetConfirmPassword').value;

    if (newPassword.length < 6) {
        showToast("Mật khẩu mới phải có ít nhất 6 ký tự!", false);
        return;
    }
    if (newPassword !== confirmPassword) {
        showToast("Mật khẩu xác nhận không khớp!", false);
        return;
    }

    if (btn) { btn.disabled = true; btn.innerText = 'Đang cập nhật...'; }

    try {
        await Api.resetPassword({
            token: token,
            email: email,
            otp: otp,
            password: newPassword,
            confirm_password: confirmPassword
        });
        showToast("Đặt lại mật khẩu thành công! Vui lòng đăng nhập bằng mật khẩu mới.", true);
        openSubPanel('Đăng nhập tài khoản', 'login');
    } catch (err) {
        showToast(err.message || "Đặt lại mật khẩu thất bại!", false);
        if (btn) { btn.disabled = false; btn.innerText = 'Cập nhật mật khẩu mới'; }
    }
}

// 8. Các hàm tiện ích Quên mật khẩu F8
function validateF8ForgotInput(val) {
    const btn = document.getElementById('btnF8SendCode');
    if (!btn) return;
    val = (val || '').trim();
    if (val.length >= 3) {
        btn.classList.add('active');
    } else {
        btn.classList.remove('active');
    }
}

async function sendF8ForgotCode() {
    const btn = document.getElementById('btnF8SendCode');
    if (!btn || !btn.classList.contains('active')) return;
    const account = document.getElementById('forgotEmail').value.trim();
    btn.innerText = 'Đang gửi...';
    btn.disabled = true;

    try {
        const res = await Api.forgotPassword(account);
        showToast('Đã gửi mã xác nhận đến email tài khoản!', true);
        window.currentF8TargetEmail = (res && res.email) ? res.email : account;
        window.currentF8ResetToken = (res && res.debug_token) ? res.debug_token : '';

        const newPassSec = document.getElementById('f8NewPassSection');
        if (newPassSec) newPassSec.style.display = 'flex';
        const codeInput = document.getElementById('resetOtpCode');
        if (codeInput) codeInput.focus();



        btn.innerText = 'Gửi lại';
        btn.disabled = false;
    } catch (err) {
        showToast(err.message || 'Không thể gửi mã xác nhận!', false);
        btn.innerText = 'Gửi mã';
        btn.disabled = false;
    }
}

async function handleF8ResetPasswordSubmit(e) {
    e.preventDefault();
    const btn = document.getElementById('btnForgotSubmit');
    const account = document.getElementById('forgotEmail').value.trim();
    const otp = document.getElementById('resetOtpCode').value.trim();
    const newPassSection = document.getElementById('f8NewPassSection');

    if (!account) {
        showToast('Vui lòng nhập Email hoặc Username!', false);
        document.getElementById('forgotEmail').focus();
        return;
    }

    if (!otp) {
        showToast('Vui lòng bấm "Gửi mã" và nhập mã xác nhận gồm 6 số!', false);
        document.getElementById('resetOtpCode').focus();
        return;
    }

    if (!newPassSection || newPassSection.style.display === 'none') {
        if (newPassSection) newPassSection.style.display = 'flex';
        showToast('Vui lòng nhập mật khẩu mới!', false);
        const passInput = document.getElementById('resetNewPassword');
        if (passInput) passInput.focus();
        return;
    }

    const newPass = document.getElementById('resetNewPassword').value;
    const confirmPass = document.getElementById('resetConfirmPassword').value;

    if (newPass.length < 6) {
        showToast('Mật khẩu mới phải có ít nhất 6 ký tự!', false);
        return;
    }
    if (newPass !== confirmPass) {
        showToast('Mật khẩu xác nhận không khớp!', false);
        return;
    }

    if (btn) {
        btn.disabled = true;
        btn.innerText = 'Đang cập nhật...';
    }

    try {
        await Api.resetPassword({
            token: window.currentF8ResetToken || '',
            email: window.currentF8TargetEmail || account,
            otp: otp,
            password: newPass,
            confirm_password: confirmPass
        });

        showToast('Đặt lại mật khẩu thành công! Vui lòng đăng nhập.', true);
        setTimeout(() => {
            openSubPanel('Đăng nhập tài khoản', 'login');
        }, 900);
    } catch (err) {
        showToast(err.message || 'Đặt lại mật khẩu thất bại!', false);
        if (btn) {
            btn.disabled = false;
            btn.innerText = 'Đặt lại mật khẩu';
        }
    }
}

// 9. Tiện ích Google Auth & Đăng nhập Admin trên bản đồ
async function startGoogleAuthFromMap(mode = 'login') {
    if (typeof window.loginWithFirebaseGoogle === 'function') {
        try {
            await window.loginWithFirebaseGoogle(mode);
            return;
        } catch (e) {
            console.warn('[Firebase Map Auth]', e);
            return;
        }
    }

    showToast('Đang kết nối cổng Google qua Firebase...', true);
    setTimeout(async () => {
        if (typeof window.loginWithFirebaseGoogle === 'function') {
            try {
                await window.loginWithFirebaseGoogle(mode);
                return;
            } catch (e) {
                return;
            }
        }
        window.location.href = (mode === 'register') ? 'index.php?route=register' : 'index.php?route=login';
    }, 400);
}

// 10. Tự động kiểm tra URL query parameters để mở đúng sub-panel
document.addEventListener('DOMContentLoaded', () => {
    try {
        const urlParams = new URLSearchParams(window.location.search);
        const route = urlParams.get('route');
        const token = urlParams.get('token') || '';

        if (route === 'reset_password') {
            openSubPanel('Đặt lại mật khẩu', 'reset_password', { token: token });
        } else if (route === 'forgot_password') {
            openSubPanel('Quên mật khẩu', 'forgot_password');
        } else if (route === 'login') {
            openSubPanel('Đăng nhập tài khoản', 'login');
        } else if (route === 'register') {
            openSubPanel('Đăng ký tài khoản', 'register');
        }
    } catch (e) {}
});