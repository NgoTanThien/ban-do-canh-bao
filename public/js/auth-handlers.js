
function renderAuthSubPanel(type, contentDiv, extraData = {}){
    // PANEL: ĐĂNG NHẬP (Google + Email/SĐT + Cổng Admin)
    if (type === 'login') {
        contentDiv.innerHTML = `
            <div style="background:#ffffff; border-radius:18px; padding:28px 18px 24px; color:#292929; text-align:center; max-width:360px; margin:0 auto; box-shadow:0 8px 30px rgba(0,0,0,0.18);">
                <div style="width:48px; height:48px; background:linear-gradient(135deg, #2563eb, #3b82f6); border-radius:12px; display:inline-flex; align-items:center; justify-content:center; color:#ffffff; box-shadow:0 4px 14px rgba(37,99,235,0.28);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <path d="M12 8v4"/>
                        <path d="M12 16h.01"/>
                    </svg>
                </div>

                <h2 style="font-size:24px; font-weight:700; color:#292929; margin-top:16px; margin-bottom:8px;">Đăng nhập</h2>
            

                <!-- Danh sách nút chọn phương thức: Google + Email -->
                <div id="loginMethodsList" style="display:flex; flex-direction:column; gap:12px;">
                    <button type="button" class="f8-pill-btn" onclick="startGoogleAuthFromMap('login')">
                        <span class="f8-pill-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.66-5.17 3.66-9.17z"/>
                                <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.34 24 12 24z"/>
                                <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.17 0 9.98 0 12s.45 3.83 1.25 5.42l4.03-3.15z"/>
                                <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.34 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/>
                            </svg>
                        </span>
                        <span class="f8-pill-text">Đăng nhập với Google</span>
                    </button>

                    <button type="button" class="f8-pill-btn" onclick="document.getElementById('loginMethodsList').style.display='none'; document.getElementById('loginFormSection').style.display='block';">
                        <span class="f8-pill-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#555555" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>
                        <span class="f8-pill-text" style="color:#111111;">Sử dụng Tên đăng nhập/ Email</span>
                    </button>
                </div>

                <!-- Form đăng nhập khi bấm Sử dụng email / SĐT -->
                <div id="loginFormSection" style="display:none; text-align:left;">
                    <button type="button" class="f8-btn-back" onclick="document.getElementById('loginFormSection').style.display='none'; document.getElementById('loginMethodsList').style.display='flex';">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 18l-6-6 6-6"/>
                        </svg>
                        Quay lại
                    </button>

                    <form onsubmit="handleAuthLogin(event)" style="display:flex; flex-direction:column; gap:12px;">
                        <div>
                            <label style="font-size:13px; font-weight:700; color:#292929; margin-bottom:4px; display:block;">Tên đăng nhập hoặc Email</label>
                            <input type="text" id="loginUsername" placeholder="Nhập Email hoặc tên đăng nhập" class="f8-form-input" required autocomplete="username">
                        </div>
                        <div>
                            <label style="font-size:13px; font-weight:700; color:#292929; margin-bottom:4px; display:block;">Mật khẩu</label>
                            <div style="position:relative; width:100%;">
                                <input type="password" id="loginPassword" placeholder="Nhập mật khẩu" class="f8-form-input" required autocomplete="current-password" style="padding-right:42px;">
                                <button type="button" onclick="togglePasswordVisibility('loginPassword', this)" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#64748b; padding:4px; display:flex; align-items:center; justify-content:center;" title="Hiện/Ẩn mật khẩu" aria-label="Hiện mật khẩu">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>
                            </div>
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px; margin:2px 0;">
                            <label style="display:flex; align-items:center; gap:6px; color:#4e5867; cursor:pointer;">
                                <input type="checkbox" id="loginRememberMe" checked style="width:15px; height:15px; accent-color:#2563eb;">
                                <span>Ghi nhớ 30 ngày</span>
                            </label>
                            <a href="#" onclick="openSubPanel('Quên mật khẩu?', 'forgot_password'); return false;" style="color:#2563eb; font-weight:600; text-decoration:none;">Quên mật khẩu?</a>
                        </div>

                        <button type="submit" id="btnLoginSubmit" class="f8-submit-btn" style="background:linear-gradient(to right, #2563eb, #3b82f6);">
                            Đăng nhập
                        </button>
                    </form>
                </div>


                <!-- Liên kết điều hướng bên dưới -->
                <div style="margin-top:24px; text-align:center; font-size:13.5px; color:#35414c;">
                    <div>
                        Bạn chưa có tài khoản? <a href="#" onclick="openSubPanel('Đăng ký tài khoản', 'register'); return false;" style="color:#2563eb; font-weight:700; text-decoration:underline;">Đăng ký</a>
                    </div>
                    <div style="margin-top:12px;">
                        <a href="#" onclick="openSubPanel('Quên mật khẩu?', 'forgot_password'); return false;" style="color:#2563eb; font-weight:700; text-decoration:underline;">Quên mật khẩu?</a>
                    </div>
                </div>
            </div>`;
    }

        // PANEL: ĐĂNG KÝ TÀI KHOẢN (Mở thẳng form Họ tên + Username + Email + Mật khẩu)
    else if (type === 'register') {
        contentDiv.innerHTML = `
            <div style="background:#ffffff; border-radius:18px; padding:28px 18px 24px; color:#292929; text-align:center; max-width:360px; margin:0 auto; box-shadow:0 8px 30px rgba(0,0,0,0.18);">
                <div style="width:48px; height:48px; background:linear-gradient(135deg, #2563eb, #3b82f6); border-radius:12px; display:inline-flex; align-items:center; justify-content:center; color:#ffffff; box-shadow:0 4px 14px rgba(37,99,235,0.28);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <path d="M12 8v4"/>
                        <path d="M12 16h.01"/>
                    </svg>
                </div>

                <h2 style="font-size:24px; font-weight:700; color:#292929; margin-top:16px; margin-bottom:16px;">Đăng ký tài khoản</h2>

                <div id="regFormSection" style="text-align:left;">
                    <form onsubmit="handleAuthRegister(event)" style="display:flex; flex-direction:column; gap:10px;">
                        <div>
                            <label style="font-size:12.5px; font-weight:700; color:#292929; margin-bottom:3px; display:block;">Họ và tên <span style="color:#f33a58;">*</span></label>
                            <input type="text" id="regFullName" class="f8-form-input" placeholder="Ví dụ: Nguyễn Văn A" required>
                        </div>
                        <div>
                            <label style="font-size:12.5px; font-weight:700; color:#292929; margin-bottom:3px; display:block;">Tên đăng nhập (Username) <span style="color:#f33a58;">*</span></label>
                            <input type="text" id="regUsername" class="f8-form-input" placeholder="Ví dụ: nguyenvana (3-30 ký tự)" required minlength="3" maxlength="30">
                        </div>
                        <div>
                            <label style="font-size:12.5px; font-weight:700; color:#292929; margin-bottom:3px; display:block;">Email <span style="color:#f33a58;">*</span></label>
                            <input type="email" id="regEmail" class="f8-form-input" placeholder="email@domain.com" required oninput="handleEmailRealtimeCheck(this.value)" onblur="handleEmailRealtimeCheck(this.value)">
                            <div id="emailCheckStatus" style="font-size:11.5px; margin-top:3px; padding-left:8px;"></div>
                        </div>
                        <div>
                            <label style="font-size:12.5px; font-weight:700; color:#292929; margin-bottom:3px; display:block;">Mật khẩu <span style="color:#f33a58;">*</span></label>
                            <div style="position:relative; width:100%;">
                                <input type="password" id="regPassword" class="f8-form-input" placeholder="Ít nhất 6 ký tự" required minlength="6" autocomplete="new-password" style="padding-right:42px;">
                                <button type="button" onclick="togglePasswordVisibility('regPassword', this)" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#64748b; padding:4px; display:flex; align-items:center; justify-content:center;" title="Hiện/Ẩn mật khẩu" aria-label="Hiện mật khẩu">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label style="font-size:12.5px; font-weight:700; color:#292929; margin-bottom:3px; display:block;">Xác nhận mật khẩu <span style="color:#f33a58;">*</span></label>
                            <div style="position:relative; width:100%;">
                                <input type="password" id="regConfirmPassword" class="f8-form-input" placeholder="Nhập lại mật khẩu" required minlength="6" autocomplete="new-password" style="padding-right:42px;">
                                <button type="button" onclick="togglePasswordVisibility('regConfirmPassword', this)" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#64748b; padding:4px; display:flex; align-items:center; justify-content:center;" title="Hiện/Ẩn mật khẩu" aria-label="Hiện mật khẩu">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>
                            </div>
                        </div>

                        <div style="display:flex; align-items:flex-start; gap:6px; margin:4px 0 8px; font-size:12px; color:#4e5867;">
                            <input type="checkbox" id="regTermsAgree" checked required style="width:15px; height:15px; accent-color:#2563eb; margin-top:2px;">
                            <label for="regTermsAgree" style="cursor:pointer; line-height:1.35;">
                                Đồng ý với Điều khoản dịch vụ & Chính sách bảo mật.
                            </label>
                        </div>

                        <button type="submit" id="btnRegisterSubmit" class="f8-submit-btn" style="background:linear-gradient(to right, #2563eb, #3b82f6);">
                            Đăng ký tài khoản
                        </button>
                    </form>
                </div>

                <!-- Liên kết điều hướng bên dưới -->
                <div style="margin-top:20px; text-align:center; font-size:13.5px; color:#35414c;">
                    <div>
                        Bạn đã có tài khoản? <a href="#" onclick="openSubPanel('Đăng nhập', 'login'); return false;" style="color:#2563eb; font-weight:700; text-decoration:underline;">Đăng nhập</a>
                    </div>
                </div>
            </div>`;
    }

    // PANEL: XÁC THỰC MÃ OTP ĐĂNG KÝ
    else if (type === 'verify_otp') {
        const targetEmail = extraData.email || '';
        contentDiv.innerHTML = `
            <div style="padding: 8px 0; text-align:center;">
                <div style="width:56px; height:56px; background:#0284c7; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; color:white; font-size:1.8rem;">
                    <i class="ph-bold ph-envelope-simple"></i>
                </div>
                <h4 style="color:white; font-size:1.1rem; margin-bottom:6px;">Xác thực địa chỉ Email</h4>
                <p style="color:#a1a1aa; font-size:0.85rem; line-height:1.5; margin-bottom:16px;">
                    Mã xác thực gồm 6 chữ số đã được gửi tới email:<br>
                    <strong style="color:#60a5fa;">${targetEmail}</strong>
                </p>



                <form onsubmit="handleVerifyRegisterOtp(event)" style="display:flex; flex-direction:column; gap:14px;">
                    <input type="hidden" id="otpTargetEmail" value="${targetEmail}">
                    <div>
                        <label style="color:#a1a1aa; font-size:0.82rem; margin-bottom:6px; display:block;">Nhập mã OTP (6 số)</label>
                        <input type="text" id="regOtpInput" maxlength="6" placeholder="000000" class="form-input" style="text-align:center; font-size:1.5rem; letter-spacing:8px; font-weight:bold;" required autocomplete="one-time-code">
                    </div>
                    <button type="submit" id="btnVerifyOtpSubmit" style="width:100%; background:#0284c7; color:white; border:none; padding:12px; border-radius:10px; font-weight:bold; cursor:pointer; font-size:0.95rem;">
                        Xác nhận & Hoàn tất
                    </button>
                </form>

                <div style="margin-top:20px; font-size:0.85rem; color:#a1a1aa;">
                    Chưa nhận được mã? 
                    <a href="#" onclick="handleResendRegisterOtp('${targetEmail}'); return false;" style="color:#60a5fa; text-decoration:none; font-weight:600;">Gửi lại mã OTP</a>
                </div>
                <div style="margin-top:10px; font-size:0.83rem;">
                    <a href="#" onclick="openSubPanel('Đăng ký tài khoản', 'register'); return false;" style="color:#94a3b8; text-decoration:none;">&larr; Quay lại thay đổi thông tin</a>
                </div>
            </div>
        `;
    }

    // PANEL: QUÊN MẬT KHẨU
    else if (type === 'forgot_password') {
        contentDiv.innerHTML = `
            <div style="background:#ffffff; border-radius:18px; padding:28px 18px 24px; color:#292929; max-width:360px; margin:0 auto; box-shadow:0 8px 30px rgba(0,0,0,0.18); position:relative;">
                <button type="button" class="f8-btn-back" onclick="openSubPanel('Đăng nhập', 'login');" style="margin-bottom:8px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                    Quay lại
                </button>

                <div style="text-align:center; margin-bottom:12px;">
                    <div style="width:48px; height:48px; background:linear-gradient(135deg, #2563eb, #3b82f6); border-radius:12px; display:inline-flex; align-items:center; justify-content:center; color:#ffffff; box-shadow:0 4px 14px rgba(37,99,235,0.28);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            <path d="M12 8v4"/>
                            <path d="M12 16h.01"/>
                        </svg>
                    </div>
                </div>

                <h2 style="font-size:26px; font-weight:700; color:#000000; text-align:center; margin-bottom:6px;">Quên mật khẩu?</h2>
                <p style="font-size:13.5px; color:#4e5867; text-align:center; margin-bottom:20px; line-height:1.45;">
                    Nhập email hoặc tên đăng nhập để nhận mã đặt lại mật khẩu.
                </p>

                

                <form onsubmit="handleF8ResetPasswordSubmit(event)" style="display:flex; flex-direction:column;">
                    <label style="font-size:14px; font-weight:700; color:#292929; margin-bottom:8px; text-align:left;">Tên đăng nhập</label>

                    <input type="text" id="forgotEmail" class="f8-pill-input" placeholder="Email hoặc Username" required oninput="validateF8ForgotInput(this.value)">

                    <div class="f8-combo-input">
                        <input type="text" id="resetOtpCode" class="f8-combo-inner-input" placeholder="Nhập mã xác nhận" maxlength="6" autocomplete="one-time-code">
                        <button type="button" id="btnF8SendCode" class="f8-btn-send-code" onclick="sendF8ForgotCode()">
                            Gửi mã
                        </button>
                    </div>

                    <div id="f8NewPassSection" style="display:none; margin-top:12px; flex-direction:column; gap:10px;">
                        <div style="position:relative; width:100%;">
                            <input type="password" id="resetNewPassword" class="f8-pill-input" placeholder="Mật khẩu mới (ít nhất 6 ký tự)" minlength="6" style="padding-right:42px;">
                            <button type="button" onclick="togglePasswordVisibility('resetNewPassword', this)" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#64748b; padding:4px; display:flex; align-items:center; justify-content:center;" title="Hiện/Ẩn mật khẩu" aria-label="Hiện mật khẩu">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                        <div style="position:relative; width:100%;">
                            <input type="password" id="resetConfirmPassword" class="f8-pill-input" placeholder="Xác nhận mật khẩu mới" minlength="6" style="padding-right:42px;">
                            <button type="button" onclick="togglePasswordVisibility('resetConfirmPassword', this)" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#64748b; padding:4px; display:flex; align-items:center; justify-content:center;" title="Hiện/Ẩn mật khẩu" aria-label="Hiện mật khẩu">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" id="btnForgotSubmit" class="f8-btn-submit-reset">
                        Đặt lại mật khẩu
                    </button>
                </form>

                <div style="margin-top:20px; text-align:center; font-size:13.5px; color:#4e5867;">
                    Đã nhớ mật khẩu? <a href="#" onclick="openSubPanel('Đăng nhập', 'login'); return false;" style="color:#2563eb; font-weight:700; text-decoration:underline;">Đăng nhập</a>
                </div>
            </div>`;
    }

    // PANEL: ĐẶT LẠI MẬT KHẨU
    else if (type === 'reset_password') {
        const token = extraData.token || '';
        const email = extraData.email || '';
                const hasToken = token.length > 0;

        contentDiv.innerHTML = `
            <div style="padding: 8px 0;">
                <h4 style="color:white; font-size:1.05rem; margin-bottom:8px;">Thiết lập mật khẩu mới</h4>
                <p style="color:#a1a1aa; font-size:0.84rem; line-height:1.5; margin-bottom:16px;">
                    ${hasToken 
                        ? 'Xác thực qua liên kết token hợp lệ (hiệu lực 15 phút).' 
                        : 'Nhập email, mã OTP nhận được và mật khẩu mới để hoàn tất (hiệu lực 15 phút).'}
                </p>



                <form onsubmit="handleResetPassword(event)" style="display:flex; flex-direction:column; gap:12px;">
                    <input type="hidden" id="resetToken" value="${token}">

                    ${!hasToken ? `
                        <div>
                            <label style="color:#a1a1aa; font-size:0.82rem; margin-bottom:4px; display:block;">Email tài khoản</label>
                            <input type="email" id="resetEmail" value="${email}" placeholder="email@domain.com" class="form-input" required>
                        </div>
                        <div>
                            <label style="color:#a1a1aa; font-size:0.82rem; margin-bottom:4px; display:block;">Mã OTP (6 số)</label>
                            <input type="text" id="resetOtpCode" maxlength="6" placeholder="000000" class="form-input" style="text-align:center; font-size:1.3rem; letter-spacing:6px; font-weight:bold;" required>
                        </div>
                    ` : ''}

                    <div>
                        <label style="color:#a1a1aa; font-size:0.82rem; margin-bottom:4px; display:block;">Mật khẩu mới</label>
                        <div style="position:relative; width:100%;">
                            <input type="password" id="resetNewPassword" placeholder="Ít nhất 6 ký tự" class="form-input" required minlength="6" style="padding-right:42px;">
                            <button type="button" onclick="togglePasswordVisibility('resetNewPassword', this)" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#94a3b8; padding:4px; display:flex; align-items:center; justify-content:center;" title="Hiện/Ẩn mật khẩu" aria-label="Hiện mật khẩu">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label style="color:#a1a1aa; font-size:0.82rem; margin-bottom:4px; display:block;">Xác nhận mật khẩu mới</label>
                        <div style="position:relative; width:100%;">
                            <input type="password" id="resetConfirmPassword" placeholder="Nhập lại mật khẩu mới" class="form-input" required minlength="6" style="padding-right:42px;">
                            <button type="button" onclick="togglePasswordVisibility('resetConfirmPassword', this)" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#94a3b8; padding:4px; display:flex; align-items:center; justify-content:center;" title="Hiện/Ẩn mật khẩu" aria-label="Hiện mật khẩu">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" id="btnResetSubmit" style="width:100%; background:#10b981; color:white; border:none; padding:12px; border-radius:10px; font-weight:bold; cursor:pointer; font-size:0.95rem; margin-top:6px;">
                        Cập nhật mật khẩu mới
                    </button>
                </form>

                <div style="text-align:center; margin-top:18px; font-size:0.88rem; color:#a1a1aa;">
                    <a href="#" onclick="openSubPanel('Đăng nhập tài khoản', 'login'); return false;" style="color:#60a5fa; text-decoration:none; font-weight:600;">&larr; Quay lại Đăng nhập</a>
                </div>
            </div>
        `;
    }

}

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