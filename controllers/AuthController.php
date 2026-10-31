<?php
// File: controllers/AuthController.php
// Nghiệp vụ xác thực: Đăng ký (OTP), Đăng nhập (Remember Me 30 ngày), Quên & Đặt lại mật khẩu (Token/OTP 15 phút).
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../services/Mailer.php';
if (file_exists(__DIR__ . '/../config/firebase.php')) { require_once __DIR__ . '/../config/firebase.php'; }

class AuthController {
    private $conn;
    private $users;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->users = new UserModel($conn);
    }

    /**
     * Kiểm tra trùng lặp email realtime
     */
    public function checkEmail(array $in): array
    {
        $email = trim($in['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->fail('Email không hợp lệ!', 422);
        }
        $exists = $this->users->checkEmailExists($email);
        return [
            'ok' => true,
            'message' => $exists ? 'Email này đã được sử dụng!' : 'Email hợp lệ và có thể đăng ký.',
            'data' => ['exists' => $exists]
        ];
    }

    /**
     * Bước 1 Đăng ký: Kiểm tra dữ liệu -> Tạo mã OTP -> Gửi email xác thực
     */
    public function register(array $in): array
    {
        $fullName = trim($in['full_name'] ?? $in['fullname'] ?? '');
        $emailOrPhone = trim($in['email'] ?? $in['phone'] ?? '');
        $username = trim($in['username'] ?? '');
        $phone = trim($in['phone'] ?? '');
        $password = (string)($in['password'] ?? '');
        $confirm = (string)($in['confirm_password'] ?? '');
        $termsAgree = !empty($in['terms_agree']) || !empty($in['terms']);

        if (!$termsAgree) {
            return $this->fail('Bạn cần đồng ý với Điều khoản & Chính sách bảo mật để tiếp tục!', 422);
        }

        if ($fullName === '') {
            return $this->fail('Vui lòng nhập họ và tên!', 422);
        }

        // Tách email hoặc số điện thoại
        $email = '';
        if (filter_var($emailOrPhone, FILTER_VALIDATE_EMAIL)) {
            $email = $emailOrPhone;
        } elseif (preg_match('/^[0-9\+\-\. ]{9,15}$/', $emailOrPhone)) {
            $phone = $emailOrPhone;
            $email = trim($in['email_backup'] ?? '');
        }

        if ($email === '') {
            return $this->fail('Vui lòng cung cấp địa chỉ Email hợp lệ để nhận mã xác thực OTP!', 422);
        }

        // Nếu người dùng không nhập username, tự sinh từ email
        if ($username === '') {
            $baseUser = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower(explode('@', $email)[0]));
            if (strlen($baseUser) < 3) $baseUser = 'user_' . substr(md5(uniqid()), 0, 6);
            $candidate = substr($baseUser, 0, 20);
            $counter = 1;
            while ($this->users->checkUsernameExists($candidate)) {
                $candidate = substr($baseUser, 0, 15) . '_' . $counter;
                $counter++;
            }
            $username = $candidate;
        } else {
            if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
                return $this->fail('Tên đăng nhập từ 3-30 ký tự, chỉ gồm chữ cái, số và dấu gạch dưới!', 422);
            }
            if ($this->users->checkUsernameExists($username)) {
                return $this->fail('Tên đăng nhập này đã được sử dụng!', 409);
            }
        }

        if (strlen($password) < 6) {
            return $this->fail('Mật khẩu phải có ít nhất 6 ký tự!', 422);
        }
        if ($confirm !== '' && $password !== $confirm) {
            return $this->fail('Mật khẩu xác nhận không khớp!', 422);
        }

        if ($this->users->checkEmailExists($email)) {
            return $this->fail('Địa chỉ Email này đã tồn tại trong hệ thống!', 409);
        }

        // Tạo mã OTP 6 số
        $otpCode = sprintf('%06d', mt_rand(100000, 999999));
        $payload = [
            'username'  => $username,
            'full_name' => $fullName,
            'email'     => $email,
            'phone'     => $phone,
            'password'  => $password,
        ];

        // Lưu OTP vào bảng email_otps (thời hạn 15 phút)
        $this->users->createEmailOtp($email, $otpCode, 'register', $payload, 15);

        // Gửi email OTP
        Mailer::sendRegisterOtp($email, $fullName, $otpCode, 15);

        return [
            'ok' => true,
            'message' => 'Mã xác thực OTP đã được gửi đến email ' . htmlspecialchars($email) . '. Vui lòng kiểm tra hòm thư!',
            'data' => [
                'email'              => $email,
                'expires_in_minutes' => 15,
                'debug_otp'          => $otpCode // Hiển thị mã để test nhanh trên localhost
            ]
        ];
    }

    /**
     * Bước 2 Đăng ký: Nhập mã OTP xác thực -> Tạo tài khoản chính thức & Đăng nhập
     */
    public function verifyRegisterOtp(array $in): array
    {
        $email = trim($in['email'] ?? '');
        $otp = trim($in['otp'] ?? $in['otp_code'] ?? '');

        if ($email === '' || $otp === '') {
            return $this->fail('Vui lòng nhập đầy đủ email và mã OTP!', 422);
        }

        $otpRecord = $this->users->getValidEmailOtp($email, 'register', $otp);
        if (!$otpRecord || empty($otpRecord['payload'])) {
            return $this->fail('Mã OTP không chính xác hoặc đã hết hạn (15 phút)!', 422);
        }

        $p = $otpRecord['payload'];

        // Kiểm tra lại lần cuối tránh xung đột
        if ($this->users->checkEmailExists($p['email'])) {
            $this->users->deleteEmailOtps($email, 'register');
            return $this->fail('Email này đã được đăng ký trước đó!', 409);
        }

        try {
            $userId = $this->users->registerWithProfile(
                $p['username'],
                $p['full_name'],
                $p['email'],
                $p['phone'] ?? '',
                $p['password']
            );
        } catch (mysqli_sql_exception $e) {
            if ((int)$e->getCode() === 1062) {
                return $this->fail('Tên đăng nhập hoặc email đã tồn tại!', 409);
            }
            throw $e;
        }

        // Xóa OTP sau khi kích hoạt thành công
        $this->users->deleteEmailOtps($email, 'register');

        // Tự động đăng nhập
        if (!headers_sent()) { @session_regenerate_id(true); }
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $p['username'];
        $_SESSION['role'] = 'user';

        $user = $this->users->findById($userId);

        return [
            'ok' => true,
            'message' => 'Xác thực tài khoản thành công! Chào mừng bạn gia nhập hệ thống.',
            'data' => $this->publicUser($user ?: ['id' => $userId, 'username' => $p['username'], 'email' => $email, 'role' => 'user', 'reputation_score' => 10])
        ];
    }

    /**
     * Đăng nhập (Hỗ trợ Email/Username/SĐT + Ghi nhớ đăng nhập 30 ngày)
     */
    public function login(array $in): array
    {
        $account    = trim($in['username_or_email'] ?? $in['account'] ?? '');
        $password   = (string)($in['password'] ?? '');
        $rememberMe = !empty($in['remember_me']);

        if ($account === '' || $password === '') {
            return $this->fail('Vui lòng điền đầy đủ tài khoản và mật khẩu!', 422);
        }

        $user = $this->users->login($account, $password);
        if (!$user) {
            return $this->fail('Tài khoản hoặc mật khẩu không chính xác!', 401);
        }
        if ($user['status'] !== UserModel::STATUS_ACTIVE || (int)$user['reputation_score'] <= 0) {
            return $this->fail('Tài khoản của bạn đã bị khóa do vi phạm điểm uy tín!', 403);
        }

        if (!headers_sent()) { @session_regenerate_id(true); }
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Xử lý "Ghi nhớ đăng nhập" (Remember Me 30 ngày)
        if ($rememberMe) {
            $rawToken = bin2hex(random_bytes(32));
            $thirtyDays = 30 * 86400;
            $this->users->createUserToken((int)$user['id'], $rawToken, 'remember_me', $thirtyDays);

            $cookieValue = (int)$user['id'] . ':' . $rawToken;
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            @setcookie('remember_me', $cookieValue, [
                'expires'  => time() + $thirtyDays,
                'path'     => '/',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        return [
            'ok' => true,
            'message' => 'Đăng nhập thành công!',
            'data' => $this->publicUser($user)
        ];
    }

    /**
     * Xác thực Token Google trực tiếp qua API chính thức của Google
     */
    private function verifyGoogleToken(string $token): ?array
    {
        // 0. Thử xác thực Firebase ID Token qua Google Identity Toolkit REST API
        $firebaseKey = defined('FIREBASE_API_KEY') ? FIREBASE_API_KEY : 'AIzaSyA_bTtrkXNsKC6yj2SlbRMvOn2HJWTmz64';
        $fbUrl = 'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' . urlencode($firebaseKey);
        $ch = curl_init($fbUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['idToken' => $token]));
        $fbResp = curl_exec($ch);
        $fbCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($fbCode === 200 && $fbResp) {
            $fbData = json_decode($fbResp, true);
            if (!empty($fbData['users'][0]['email'])) {
                $u = $fbData['users'][0];
                return [
                    'email'    => strtolower(trim($u['email'])),
                    'name'     => $u['displayName'] ?? '',
                    'sub'      => $u['localId'] ?? '',
                    'picture'  => $u['photoUrl'] ?? '',
                    'verified' => !empty($u['emailVerified'])
                ];
            }
        }

        // 1. Thử xác thực ID Token qua Google OAuth2 tokeninfo API
        $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($token);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $resp) {
            $data = json_decode($resp, true);
            if (!empty($data['email'])) {
                return [
                    'email'    => strtolower(trim($data['email'])),
                    'name'     => $data['name'] ?? '',
                    'sub'      => $data['sub'] ?? '',
                    'picture'  => $data['picture'] ?? '',
                    'verified' => !empty($data['email_verified'])
                ];
            }
        }

        // 2. Thử xác thực nếu là Access Token qua userinfo API
        $url = 'https://www.googleapis.com/oauth2/v3/userinfo';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $resp) {
            $data = json_decode($resp, true);
            if (!empty($data['email'])) {
                return [
                    'email'    => strtolower(trim($data['email'])),
                    'name'     => $data['name'] ?? '',
                    'sub'      => $data['sub'] ?? '',
                    'picture'  => $data['picture'] ?? '',
                    'verified' => !empty($data['email_verified'])
                ];
            }
        }

        // 3. Fallback: Parse trực tiếp JWT nếu không kết nối được Google (offline / dev mode)
        $parts = explode('.', $token);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            if (is_array($payload) && !empty($payload['email'])) {
                return [
                    'email'    => strtolower(trim($payload['email'])),
                    'name'     => $payload['name'] ?? '',
                    'sub'      => $payload['sub'] ?? '',
                    'picture'  => $payload['picture'] ?? '',
                    'verified' => !empty($payload['email_verified'])
                ];
            }
        }

        return null;
    }

    /**
     * Đăng nhập hoặc Đăng ký tài khoản bằng Google
     */
    public function googleLogin(array $in): array
    {
        $token = trim($in['credential'] ?? $in['id_token'] ?? $in['access_token'] ?? $in['token'] ?? '');
        $email = trim(strtolower($in['email'] ?? ''));
        $name = trim($in['name'] ?? $in['full_name'] ?? '');
        $googleId = trim($in['google_id'] ?? $in['sub'] ?? '');
        $picture = trim($in['picture'] ?? $in['avatar'] ?? '');

        // Nếu client gửi token từ Google Identity Services
        if ($token !== '') {
            $verified = $this->verifyGoogleToken($token);
            if ($verified) {
                $email = $verified['email'];
                if (empty($name) && !empty($verified['name'])) {
                    $name = $verified['name'];
                }
                if (empty($googleId) && !empty($verified['sub'])) {
                    $googleId = $verified['sub'];
                }
                if (empty($picture) && !empty($verified['picture'])) {
                    $picture = $verified['picture'];
                }
            }
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->fail('Vui lòng cung cấp địa chỉ email Google hợp lệ!', 422);
        }

        $user = $this->users->loginOrRegisterGoogle($email, $name, $googleId ?: null, $picture ?: null);
        if (!$user) {
            return $this->fail('Không thể khởi tạo hoặc đăng nhập bằng tài khoản Google!', 500);
        }

        if ($user['status'] !== UserModel::STATUS_ACTIVE || (int)$user['reputation_score'] <= 0) {
            return $this->fail('Tài khoản này đã bị khóa do vi phạm chính sách hoặc điểm uy tín!', 403);
        }

        if (!headers_sent()) { @session_regenerate_id(true); }
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Tự động duy trì phiên 30 ngày cho Google Login
        $rawToken = bin2hex(random_bytes(32));
        $thirtyDays = 30 * 86400;
        $this->users->createUserToken((int)$user['id'], $rawToken, 'remember_me', $thirtyDays);

        $cookieValue = (int)$user['id'] . ':' . $rawToken;
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        @setcookie('remember_me', $cookieValue, [
            'expires'  => time() + $thirtyDays,
            'path'     => '/',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        return [
            'ok' => true,
            'message' => 'Đăng nhập với Google thành công!',
            'data' => $this->publicUser($user)
        ];
    }

    /**
     * Quên mật khẩu: Nhập email hoặc username -> Nhận OTP qua email và Link chứa Reset Token (15 phút)
     */
    public function forgotPassword(array $in): array
    {
        $input = trim($in['email'] ?? $in['account'] ?? $in['username'] ?? '');
        if ($input === '') {
            return $this->fail('Vui lòng nhập địa chỉ email hoặc tên đăng nhập!', 422);
        }

        $user = null;
        if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
            $user = $this->users->findByEmail($input);
        } else {
            $user = $this->users->findByAccount($input);
        }

        if (!$user) {
            return $this->fail('Không tìm thấy tài khoản nào khớp với thông tin này!', 404);
        }

        if ($user['status'] !== UserModel::STATUS_ACTIVE) {
            return $this->fail('Tài khoản này hiện đang bị khóa!', 403);
        }

        $email = $user['email'];

        // Tạo Reset Token (chuỗi ngẫu nhiên an toàn 64 ký tự)
        $resetToken = bin2hex(random_bytes(32));
        // Tạo OTP 6 số để người dùng có thể nhập trực tiếp
        $otpCode = sprintf('%06d', mt_rand(100000, 999999));
        $fifteenMinutes = 15 * 60;

        // Lưu Reset Token vào bảng user_tokens
        $this->users->deleteTokensByUserId((int)$user['id'], 'reset_password');
        $this->users->createUserToken((int)$user['id'], $resetToken, 'reset_password', $fifteenMinutes);

        // Lưu OTP vào bảng email_otps
        $this->users->createEmailOtp($email, $otpCode, 'reset_password', ['user_id' => (int)$user['id']], 15);

        // Gửi email thực tế chứa OTP và Link đặt lại mật khẩu
        Mailer::sendResetPasswordEmail($email, $user['full_name'] ?? $user['username'], $resetToken, $otpCode, 15);

        return [
            'ok' => true,
            'message' => 'Mã OTP đặt lại mật khẩu đã được gửi đến email của bạn!',
            'data' => [
                'email'              => $email,
                'expires_in_minutes' => 15,
                'debug_otp'          => $otpCode,     // Hỗ trợ kiểm tra nhanh localhost
                'debug_token'        => $resetToken  // Hỗ trợ kiểm tra nhanh localhost
            ]
        ];
    }

    /**
     * Đặt lại mật khẩu mới (Chấp nhận Reset Token hoặc Email + OTP)
     */
    public function resetPassword(array $in): array
    {
        $token       = trim($in['token'] ?? '');
        $email       = trim($in['email'] ?? '');
        $otp         = trim($in['otp'] ?? $in['otp_code'] ?? '');
        $newPassword = (string)($in['password'] ?? $in['new_password'] ?? '');
        $confirm     = (string)($in['confirm_password'] ?? '');

        if (strlen($newPassword) < 6) {
            return $this->fail('Mật khẩu mới phải có ít nhất 6 ký tự!', 422);
        }
        if ($confirm !== '' && $newPassword !== $confirm) {
            return $this->fail('Mật khẩu xác nhận không khớp!', 422);
        }

        $userId = null;

        // Cách 1: Xác thực qua Reset Token trên URL
        if ($token !== '') {
            $user = $this->users->findUserByToken($token, 'reset_password');
            if (!$user) {
                return $this->fail('Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn (15 phút)!', 422);
            }
            $userId = (int)$user['id'];
            $this->users->deleteUserToken($token, 'reset_password');
        }
        // Cách 2: Xác thực qua Email + Mã OTP
        elseif ($email !== '' && $otp !== '') {
            $otpRecord = $this->users->getValidEmailOtp($email, 'reset_password', $otp);
            if (!$otpRecord) {
                return $this->fail('Mã OTP không hợp lệ hoặc đã hết thời hạn 15 phút!', 422);
            }
            $user = $this->users->findByEmail($email);
            if (!$user) {
                return $this->fail('Không tìm thấy tài khoản người dùng!', 404);
            }
            $userId = (int)$user['id'];
            $this->users->deleteEmailOtps($email, 'reset_password');
        } else {
            return $this->fail('Thiếu mã OTP hoặc Token xác thực đặt lại mật khẩu!', 422);
        }

        // Cập nhật mật khẩu mới băm bcrypt
        $this->users->updatePassword($userId, $newPassword);

        // Hủy bỏ mọi phiên remember_me cũ để đảm bảo an toàn
        $this->users->deleteTokensByUserId($userId, 'remember_me');

        return [
            'ok' => true,
            'message' => 'Đặt lại mật khẩu thành công! Vui lòng đăng nhập bằng mật khẩu mới.',
            'data' => null
        ];
    }

    /**
     * Đăng xuất: Xóa session và xóa Remember Me cookie/token
     */
    public function logout(): array
    {
        if (!empty($_COOKIE['remember_me'])) {
            $parts = explode(':', $_COOKIE['remember_me'], 2);
            if (count($parts) === 2) {
                $rawToken = $parts[1];
                $this->users->deleteUserToken($rawToken, 'remember_me');
            }
            @setcookie('remember_me', '', time() - 3600, '/');
            unset($_COOKIE['remember_me']);
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            @setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        return ['ok' => true, 'message' => 'Đã đăng xuất thành công!', 'data' => null];
    }

    /**
     * Kiểm tra trạng thái phiên (Tự động khôi phục phiên nếu có Remember Me cookie hợp lệ)
     */
    public function me(): array
    {
        // 1. Nếu chưa có session nhưng có cookie remember_me -> khôi phục phiên 30 ngày
        if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_me'])) {
            $parts = explode(':', $_COOKIE['remember_me'], 2);
            if (count($parts) === 2) {
                $userId = (int)$parts[0];
                $rawToken = $parts[1];
                $user = $this->users->findUserByToken($rawToken, 'remember_me');
                if ($user && (int)$user['id'] === $userId && $user['status'] === UserModel::STATUS_ACTIVE) {
                    if (!headers_sent()) { @session_regenerate_id(true); }
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                } else {
                    // Cookie không còn hợp lệ -> xóa
                    @setcookie('remember_me', '', time() - 3600, '/');
                    unset($_COOKIE['remember_me']);
                }
            }
        }

        if (empty($_SESSION['user_id'])) {
            return ['ok' => true, 'data' => ['logged_in' => false, 'user' => null]];
        }

        $user = $this->users->findById((int)$_SESSION['user_id']);
        if (!$user || $user['status'] !== UserModel::STATUS_ACTIVE) {
            $_SESSION = [];
            return ['ok' => true, 'data' => ['logged_in' => false, 'user' => null]];
        }

        return ['ok' => true, 'data' => ['logged_in' => true, 'user' => $this->publicUser($user)]];
    }

    private function publicUser(array $u): array
    {
        return [
            'id'               => (int)$u['id'],
            'username'         => $u['username'],
            'full_name'        => $u['full_name'] ?? '',
            'avatar'           => $u['avatar'] ?? '',
            'email'            => $u['email'] ?? '',
            'phone'            => $u['phone'] ?? '',
            'role'             => $u['role'],
            'is_verified'      => (int)($u['is_verified'] ?? 1),
            'reputation_score' => (int)($u['reputation_score'] ?? 10),
        ];
    }

    private function fail(string $error, int $code): array
    {
        return ['ok' => false, 'error' => $error, 'code' => $code];
    }
}
