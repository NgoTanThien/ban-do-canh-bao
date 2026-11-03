<?php
// File: config/sms.php
// Cấu hình dịch vụ gửi tin nhắn SMS OTP

// Chế độ gửi: 'log' (mặc định thử nghiệm trên localhost), 'esms' (nhà mạng VN), 'twilio' (quốc tế)
if (!defined('SMS_PROVIDER')) define('SMS_PROVIDER', 'log');

// Cấu hình eSMS.vn (nếu dùng dịch vụ tại Việt Nam)
if (!defined('ESMS_API_KEY')) define('ESMS_API_KEY', 'your_esms_api_key');
if (!defined('ESMS_SECRET_KEY')) define('ESMS_SECRET_KEY', 'your_esms_secret_key');
if (!defined('ESMS_BRANDNAME')) define('ESMS_BRANDNAME', 'Baodocanhbao');

// Cấu hình Twilio (nếu dùng dịch vụ quốc tế)
if (!defined('TWILIO_SID')) define('TWILIO_SID', 'your_twilio_sid');
if (!defined('TWILIO_TOKEN')) define('TWILIO_TOKEN', 'your_twilio_token');
if (!defined('TWILIO_FROM')) define('TWILIO_FROM', '+1234567890');

