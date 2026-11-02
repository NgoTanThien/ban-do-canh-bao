<?php
// File: services/SmsService.php
// Quản lý việc gửi mã xác thực OTP qua tin nhắn SMS

if (file_exists(__DIR__ . '/../config/sms.php')) {
    require_once __DIR__ . '/../config/sms.php';
}

class SmsService {
    private static $logDir = __DIR__ . '/../storage';
    private static $logFile = __DIR__ . '/../storage/sms_logs.txt';

    /**
     * Ghi lại nhật ký tin nhắn SMS vào storage/sms_logs.txt (dùng cho localhost)
     */
    public static function logSms(string $phone, string $message): void
    {
        if (!is_dir(self::$logDir)) {
            @mkdir(self::$logDir, 0777, true);
        }
        $logEntry = sprintf(
            "[%s] To Phone: %s | Message: %s\n----------------------------------------\n",
            date('Y-m-d H:i:s'),
            $phone,
            $message
        );
        @file_put_contents(self::$logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Gửi mã OTP xác thực qua tin nhắn SMS
     */
    public static function sendOtpSms(string $phone, string $otpCode): bool
    {
        $message = "[TrafficMap] Ma OTP dat lai mat khau cua ban la: " . $otpCode . ". Ma co hieu luc trong 15 phut.";

        // Luôn ghi log để lập trình viên kiểm tra trên localhost/XAMPP
        self::logSms($phone, $message);

        // 1. Tích hợp eSMS.vn (nếu có cấu hình API thực tế)
        if (defined('ESMS_API_KEY') && ESMS_API_KEY !== 'your_esms_api_key') {
            try {
                $url = "http://rest.esms.vn/MainService.svc/json/SendMultipleMessage_subId/?" . http_build_query([
                    'Phone' => $phone,
                    'Content' => $message,
                    'ApiKey' => ESMS_API_KEY,
                    'SecretKey' => ESMS_SECRET_KEY,
                    'IsUnicode' => 0,
                    'Brandname' => defined('ESMS_BRANDNAME') ? ESMS_BRANDNAME : 'Baodocanhbao',
                    'SmsType' => 2 // OTP SMS
                ]);
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_exec($ch);
                curl_close($ch);
                return true;
            } catch (\Throwable $e) {
                error_log("eSMS Error: " . $e->getMessage());
            }
        }

        // 2. Tích hợp Twilio (nếu có cấu hình)
        if (defined('TWILIO_SID') && TWILIO_SID !== 'your_twilio_sid') {
            try {
                $url = "https://api.twilio.com/2010-04-01/Accounts/" . TWILIO_SID . "/Messages.json";
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_USERPWD, TWILIO_SID . ":" . TWILIO_TOKEN);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                    'From' => TWILIO_FROM,
                    'To' => $phone,
                    'Body' => $message
                ]));
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_exec($ch);
                curl_close($ch);
                return true;
            } catch (\Throwable $e) {
                error_log("Twilio SMS Error: " . $e->getMessage());
            }
        }

        return true;
    }
}

// Hàm global thuận tiện gọi trực tiếp
if (!function_exists('sendOtpSms')) {
    function sendOtpSms($toPhone, $otpCode) {
        return SmsService::sendOtpSms($toPhone, $otpCode);
    }
}

