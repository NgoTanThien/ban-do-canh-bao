<?php
// POST /api/register_verify.php  { email, otp }
// Xác thực mã OTP và kích hoạt tài khoản
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../controllers/AuthController.php';

require_method('POST');
respond((new AuthController($conn))->verifyRegisterOtp(get_input()), 201);

