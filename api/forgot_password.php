<?php
// POST /api/forgot_password.php  { email }
// Tạo và gửi link reset token + mã OTP 15 phút tới email
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../controllers/AuthController.php';

require_method('POST');
respond((new AuthController($conn))->forgotPassword(get_input()));

