<?php
// File: config/mail.php
// Cấu hình dịch vụ gửi Email qua giao thức SMTP (Gmail / Mail Server)

// 1. Máy chủ SMTP và Cổng (Gmail mặc định: smtp.gmail.com, cổng 587 hoặc 465)
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 587);
if (!defined('SMTP_SECURE')) define('SMTP_SECURE', 'tls'); // 'tls' (587) hoặc 'ssl' (465)

// 2. Tài khoản Email gửi và Mật khẩu ứng dụng (App Password 16 ký tự của Google)
// Hướng dẫn tạo: Tài khoản Google -> Bảo mật -> Xác minh 2 bước -> Mật khẩu ứng dụng
if (!defined('SMTP_USERNAME')) define('SMTP_USERNAME', 'coco83868386@gmail.com');
if (!defined('SMTP_PASSWORD')) define('SMTP_PASSWORD', 'ldba yuyr qgwd ncpe');

// 3. Tên và địa chỉ hiển thị người gửi
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', 'coco83868386@gmail.com');
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', 'Traffic Map System');

