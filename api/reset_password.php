<?php
// POST /api/reset_password.php  { token, email, otp, password, confirm_password }
// Xác thực token hoặc OTP và đặt mật khẩu mới
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../controllers/AuthController.php';

require_method('POST');
respond((new AuthController($conn))->resetPassword(get_input()));

