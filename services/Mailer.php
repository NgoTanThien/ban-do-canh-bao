<?php
// File: services/Mailer.php
// Quản lý việc gửi email thông báo mã OTP và Link đặt lại mật khẩu

class Mailer {
    private static $logDir = __DIR__ . '/../storage';
    private static $logFile = __DIR__ . '/../storage/mail_logs.txt';

    /**
     * Gửi email chung
     */
    public static function send(string $toEmail, string $subject, string $htmlBody): bool
    {
        // Đảm bảo thư mục lưu trữ log tồn tại
        if (!is_dir(self::$logDir)) {
            @mkdir(self::$logDir, 0777, true);
        }

        // Tiêu đề email UTF-8 chuẩn
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: Ban Do Canh Bao <no-reply@trafficmap.vn>',
            'Reply-To: no-reply@trafficmap.vn',
            'X-Mailer: PHP/' . phpversion()
        ];

        // Ghi lại log để thuận tiện kiểm tra trên localhost/XAMPP
        $logEntry = sprintf(
            "[%s] To: %s | Subject: %s\n%s\n----------------------------------------\n",
            date('Y-m-d H:i:s'),
            $toEmail,
            $subject,
            strip_tags(str_replace(['<br>', '<br/>', '</p>', '</div>'], "\n", $htmlBody))
        );
        @file_put_contents(self::$logFile, $logEntry, FILE_APPEND);

        // Gửi mail bằng hàm nội tại của PHP (suppress warning nếu SMTP localhost chưa bật)
        return @mail($toEmail, $encodedSubject, $htmlBody, implode("\r\n", $headers));
    }

    /**
     * Gửi mã OTP xác thực email khi đăng ký
     */
    public static function sendRegisterOtp(string $toEmail, string $fullName, string $otpCode, int $expiresMinutes = 15): bool
    {
        $subject = 'Mã xác thực đăng ký tài khoản Bản đồ Cảnh báo';
        $html = "
        <div style='font-family: Arial, sans-serif; max-width: 550px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;'>
            <div style='background: #0284c7; padding: 24px; text-align: center; color: #ffffff;'>
                <h2 style='margin: 0; font-size: 20px;'>BẢN ĐỒ CẢNH BÁO GIAO THÔNG & NGẬP LỤT</h2>
            </div>
            <div style='padding: 24px; color: #334155; line-height: 1.6;'>
                <p>Xin chào <strong>" . htmlspecialchars($fullName ?: $toEmail) . "</strong>,</p>
                <p>Cảm ơn bạn đã đăng ký tài khoản. Để hoàn tất đăng ký, vui lòng sử dụng mã xác thực OTP dưới đây:</p>
                <div style='text-align: center; margin: 28px 0;'>
                    <span style='display: inline-block; background: #f0f9ff; border: 2px dashed #0284c7; color: #0369a1; font-size: 32px; font-weight: bold; letter-spacing: 8px; padding: 12px 28px; border-radius: 8px;'>
                        {$otpCode}
                    </span>
                </div>
                <p style='color: #64748b; font-size: 14px;'>Mã xác thực có hiệu lực trong <strong>{$expiresMinutes} phút</strong>. Vui lòng không chia sẻ mã này cho bất kỳ ai.</p>
                <p style='margin-top: 24px; font-size: 13px; color: #94a3b8;'>Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email.</p>
            </div>
        </div>";

        return self::send($toEmail, $subject, $html);
    }

    /**
     * Gửi email đặt lại mật khẩu (gồm cả mã OTP và Link chứa Reset Token)
     */
    public static function sendResetPasswordEmail(string $toEmail, string $fullName, string $resetToken, string $otpCode, int $expiresMinutes = 15): bool
    {
        // Tạo link reset URL
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/ban-do-canh-bao'), '/\\');
        if (strpos($baseDir, '/api') !== false) {
            $baseDir = dirname($baseDir);
        }
        $resetLink = $protocol . $host . $baseDir . '/index.php?route=reset_password&token=' . urlencode($resetToken);

        $subject = 'Yêu cầu đặt lại mật khẩu - Bản đồ Cảnh báo';
        $html = "
        <div style='font-family: Arial, sans-serif; max-width: 550px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;'>
            <div style='background: #0284c7; padding: 24px; text-align: center; color: #ffffff;'>
                <h2 style='margin: 0; font-size: 20px;'>ĐẶT LẠI MẬT KHẨU TÀI KHOẢN</h2>
            </div>
            <div style='padding: 24px; color: #334155; line-height: 1.6;'>
                <p>Xin chào <strong>" . htmlspecialchars($fullName ?: $toEmail) . "</strong>,</p>
                <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn tại <strong>Bản đồ Cảnh Báo</strong>.</p>
                
                <p>Bạn có thể nhập mã OTP đặt lại mật khẩu dưới đây:</p>
                <div style='text-align: center; margin: 20px 0;'>
                    <span style='display: inline-block; background: #fef2f2; border: 2px dashed #ef4444; color: #b91c1c; font-size: 28px; font-weight: bold; letter-spacing: 6px; padding: 10px 24px; border-radius: 8px;'>
                        {$otpCode}
                    </span>
                </div>

                <p style='text-align: center; margin: 25px 0;'>
                    <a href='{$resetLink}' style='background: #0284c7; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; display: inline-block;'>
                        Hoặc bấm vào đây để Đặt lại mật khẩu
                    </a>
                </p>

                <p style='color: #64748b; font-size: 14px;'>Cả mã OTP và liên kết trên sẽ hết hạn sau <strong>{$expiresMinutes} phút</strong>.</p>
                <p style='margin-top: 24px; font-size: 13px; color: #94a3b8;'>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này hoặc liên hệ hỗ trợ.</p>
            </div>
        </div>";

        return self::send($toEmail, $subject, $html);
    }
}

