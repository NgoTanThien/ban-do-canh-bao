<?php
// File: services/Mailer.php
// Quản lý việc gửi email thông báo mã OTP và Link đặt lại mật khẩu

// Nạp cấu hình SMTP
if (file_exists(__DIR__ . '/../config/mail.php')) {
    require_once __DIR__ . '/../config/mail.php';
}

// Nạp thư viện PHPMailer nếu có
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    if (file_exists(__DIR__ . '/../libs/PHPMailer/src/PHPMailer.php')) {
        require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';
    } elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    }
}

class Mailer {
    private static $logDir = __DIR__ . '/../storage';
    private static $logFile = __DIR__ . '/../storage/mail_logs.txt';

    /**
     * Ghi lại lịch sử email vào storage/mail_logs.txt (dùng cho localhost)
     */
    public static function logMail(string $toEmail, string $subject, string $body): void
    {
        if (!is_dir(self::$logDir)) {
            @mkdir(self::$logDir, 0777, true);
        }
        $plainText = strip_tags(str_replace(['<br>', '<br/>', '</p>', '</div>'], "\n", $body));
        $logEntry = sprintf(
            "[%s] To: %s | Subject: %s\n%s\n----------------------------------------\n",
            date('Y-m-d H:i:s'),
            $toEmail,
            $subject,
            trim($plainText)
        );
        @file_put_contents(self::$logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Gửi email chung (hỗ trợ PHPMailer SMTP và fallback mail_logs)
     */
    public static function send(string $toEmail, string $subject, string $htmlBody): bool
    {
        // 1. Luôn ghi log để dễ dàng kiểm tra trên Localhost
        self::logMail($toEmail, $subject, $htmlBody);

        // 2. Nếu đã cấu hình PHPMailer và thông tin SMTP thực tế (khác placeholder)
        if (class_exists('PHPMailer\PHPMailer\PHPMailer') && defined('SMTP_USERNAME') && SMTP_USERNAME !== 'your_system_email@gmail.com') {
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USERNAME;
                $mail->Password   = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
                $mail->SMTPSecure = (defined('SMTP_SECURE') && SMTP_SECURE === 'ssl') 
                    ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS 
                    : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 587;

                $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'no-reply@trafficmap.vn';
                $fromName  = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Traffic Map System';
                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress($toEmail);
                $mail->isHTML(true);
                $mail->CharSet = 'UTF-8';
                $mail->Subject = $subject;
                $mail->Body    = $htmlBody;

                return $mail->send();
            } catch (\Throwable $e) {
                error_log("PHPMailer SMTP Error: " . $e->getMessage());
                // Fallback xuống hàm mail() bên dưới
            }
        }

        // 3. Fallback gửi mail bằng hàm mail() nội tại của PHP
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: Ban Do Canh Bao <no-reply@trafficmap.vn>',
            'Reply-To: no-reply@trafficmap.vn',
            'X-Mailer: PHP/' . phpversion()
        ];
        return @mail($toEmail, $encodedSubject, $htmlBody, implode("\r\n", $headers));
    }

    /**
     * Hàm gửi OTP quên mật khẩu (theo chuẩn PHPMailer / Gmail SMTP)
     */
    public static function sendOtpMail(string $toEmail, string $otpCode): bool
    {
        $subject = "[TrafficMap] Ma xac thuc quen mat khau";
        $content = "Ma OTP cua ban la: " . $otpCode . ". Ma co hieu luc trong 15 phut.";

        // Nếu có PHPMailer và đã điền SMTP thực tế
        if (class_exists('PHPMailer\PHPMailer\PHPMailer') && defined('SMTP_USERNAME') && SMTP_USERNAME !== 'your_system_email@gmail.com') {
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USERNAME;
                $mail->Password   = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
                $mail->SMTPSecure = (defined('SMTP_SECURE') && SMTP_SECURE === 'ssl') 
                    ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS 
                    : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 587;

                $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'no-reply@trafficmap.vn';
                $fromName  = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Traffic Map System';
                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress($toEmail);
                $mail->CharSet = 'UTF-8';
                $mail->Subject = $subject;
                $mail->Body    = $content;

                return $mail->send();
            } catch (\Throwable $e) {
                // Fallback ghi log nếu chưa kết nối được mạng/SMTP trên Localhost
                self::logMail($toEmail, $subject, $content);
                return false;
            }
        }

        // Fallback ghi log nếu chưa cấu hình SMTP thực tế
        self::logMail($toEmail, $subject, $content);
        return true;
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

// Hàm global tương thích với đoạn code mẫu
if (!function_exists('sendOtpMail')) {
    function sendOtpMail($toEmail, $otpCode) {
        return Mailer::sendOtpMail($toEmail, $otpCode);
    }
}
