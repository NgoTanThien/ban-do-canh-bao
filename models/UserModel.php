<?php
// File: models/UserModel.php
// Quản lý dữ liệu người dùng, tokens và OTP

class UserModel {
    const STATUS_ACTIVE  = 'active';
    const STATUS_BLOCKED = 'blocked';
    const DEFAULT_REPUTATION = 10;

    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Kiểm tra username hoặc email đã tồn tại chưa
    public function checkExists($username, $email)
    {
        $sql = "SELECT username, email FROM users WHERE username = ? OR email = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Kiểm tra riêng email đã tồn tại hay chưa (cho realtime check)
    public function checkEmailExists(string $email): bool
    {
        $sql = "SELECT id FROM users WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return (bool)$stmt->get_result()->fetch_assoc();
    }

    // Kiểm tra tên đăng nhập đã tồn tại chưa
    public function checkUsernameExists(string $username): bool
    {
        $sql = "SELECT id FROM users WHERE username = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        return (bool)$stmt->get_result()->fetch_assoc();
    }

    public function findById($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT id, username, full_name, avatar, email, role, status, is_verified, reputation_score, created_at FROM users WHERE id = ? LIMIT 1"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function findByEmail(string $email)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM users WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    // Tìm kiếm bằng Email, Username hoặc Số điện thoại
    public function findByAccount(string $account)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1"
        );
        $stmt->bind_param("ss", $account, $account);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function findByGoogleId(string $googleId)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM users WHERE google_id = ? LIMIT 1"
        );
        $stmt->bind_param("s", $googleId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function loginOrRegisterGoogle(string $email, string $fullName = '', ?string $googleId = null, ?string $avatar = null): ?array
    {
        $email = trim(strtolower($email));
        $user = null;

        if (!empty($googleId)) {
            $user = $this->findByGoogleId($googleId);
        }
        if (!$user) {
            $user = $this->findByEmail($email);
            if ($user) {
                // Đã có tài khoản: cập nhật google_id, avatar và đánh dấu is_verified = 1
                $updates = ["is_verified = 1"];
                $params = [];
                $types = "";
                if (!empty($googleId) && empty($user['google_id'])) {
                    $updates[] = "google_id = ?";
                    $params[] = $googleId;
                    $types .= "s";
                }
                if (!empty($avatar) && empty($user['avatar'])) {
                    $updates[] = "avatar = ?";
                    $params[] = $avatar;
                    $types .= "s";
                }
                $params[] = (int)$user['id'];
                $types .= "i";
                $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                return $this->findById($user['id']);
            }
        } else {
            // Đã tìm thấy qua google_id: cập nhật avatar nếu có
            if (!empty($avatar) && empty($user['avatar'])) {
                $stmt = $this->conn->prepare("UPDATE users SET avatar = ?, is_verified = 1 WHERE id = ?");
                $stmt->bind_param("si", $avatar, $user['id']);
                $stmt->execute();
                return $this->findById($user['id']);
            }
        }

        if ($user) {
            return $user;
        }

        // Người dùng chưa tồn tại: tự động tạo mới tài khoản với Họ tên, Email, Avatar và is_verified = 1
        $baseUser = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower(explode('@', $email)[0]));
        if (strlen($baseUser) < 3) $baseUser = 'user_' . substr(md5(uniqid()), 0, 6);
        $candidate = substr($baseUser, 0, 20);
        $counter = 1;
        while ($this->checkUsernameExists($candidate)) {
            $candidate = substr($baseUser, 0, 15) . '_' . $counter;
            $counter++;
        }
        $username = $candidate;
        $fullName = !empty($fullName) ? trim($fullName) : $username;
        $randomPass = bin2hex(random_bytes(16));
        $hashed = password_hash($randomPass, PASSWORD_BCRYPT);
        $avatarVal = !empty($avatar) ? trim($avatar) : null;

        $stmt = $this->conn->prepare(
            "INSERT INTO users(username, full_name, avatar, email, google_id, password, role, status, is_verified, reputation_score) VALUES (?, ?, ?, ?, ?, ?, 'user', 'active', 1, 10)"
        );
        $stmt->bind_param("ssssss", $username, $fullName, $avatarVal, $email, $googleId, $hashed);
        $stmt->execute();
        $newId = (int)$this->conn->insert_id;

        return $this->findById($newId);
    }

    // Đăng ký tài khoản (hỗ trợ họ tên, sđt)
    public function registerWithProfile(string $username, string $fullName, string $email, string $password): int
    {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->conn->prepare(
            "INSERT INTO users(username, full_name, email, password) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("sssss", $username, $fullName, $email, $hashed);
        $stmt->execute();
        return (int)$this->conn->insert_id;
    }

    // Đăng ký cũ (tương thích ngược)
    public function register($username, $email, $password)
    {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->conn->prepare("INSERT INTO users(username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashed);
        return $stmt->execute();
    }

    // Đăng nhập: hỗ trợ đăng nhập bằng Email, Username hoặc Số điện thoại
    public function login($account, $password)
    {
        $user = $this->findByAccount($account);
        if (!$user) {
            return false;
        }

        if (password_verify($password, $user['password'])) {
            return $user;
        }

        // Hỗ trợ mật khẩu cũ chưa mã hóa
        if (hash_equals((string)$user['password'], (string)$password)) {
            $this->updatePassword((int)$user['id'], $password);
            return $user;
        }
        return false;
    }

    public function updatePassword($id, $plainPassword)
    {
        $hashed = password_hash($plainPassword, PASSWORD_BCRYPT);
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $id);
        return $stmt->execute();
    }

    public function listAll()
    {
        $result = $this->conn->query(
            "SELECT id, username, full_name, email, role, status, reputation_score, created_at FROM users ORDER BY id DESC"
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Đổi trạng thái: 'active' | 'blocked'. Khi mở khóa có thể khôi phục điểm uy tín.
    public function setStatus($id, $status, $resetReputation = false)
    {
        if ($status === self::STATUS_ACTIVE && $resetReputation) {
            $score = self::DEFAULT_REPUTATION;
            $stmt = $this->conn->prepare("UPDATE users SET status = ?, reputation_score = ? WHERE id = ?");
            $stmt->bind_param("sii", $status, $score, $id);
        } else {
            $stmt = $this->conn->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $id);
        }
        return $stmt->execute();
    }

    // Trừ điểm uy tín (không xuống dưới 0)
    public function deductReputation($user_id, $points = 1)
    {
         $sql = "UPDATE users 
                SET reputation_score = GREATEST(0, reputation_score - ?),
                    status = CASE WHEN GREATEST(0, reputation_score - ?) <= 0 THEN 'blocked' ELSE status END
                WHERE id = ? AND role != 'admin'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $points, $points, $user_id);
        return $stmt->execute();
    }

    // =========================================================================
    // QUẢN LÝ MÃ OTP (Bảng `email_otps`)
    // =========================================================================

    public function createEmailOtp(string $email, string $otpCode, string $action, ?array $data = null, int $expiresMinutes = 15): bool
    {
        // Xóa các OTP cũ cùng loại của email này
        $this->deleteEmailOtps($email, $action);

        $jsonData = $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : null;

        $stmt = $this->conn->prepare(
            "INSERT INTO email_otps (email, otp_code, action, data, expires_at) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))"
        );
        $stmt->bind_param("ssssi", $email, $otpCode, $action, $jsonData, $expiresMinutes);
        return $stmt->execute();
    }

    public function getValidEmailOtp(string $email, string $action, string $otpCode): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM email_otps WHERE email = ? AND action = ? AND otp_code = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1"
        );
        $stmt->bind_param("sss", $email, $action, $otpCode);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row && !empty($row['data'])) {
            $row['payload'] = json_decode($row['data'], true);
        }
        return $row ?: null;
    }

    public function deleteEmailOtps(string $email, string $action): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM email_otps WHERE email = ? AND action = ?");
        $stmt->bind_param("ss", $email, $action);
        return $stmt->execute();
    }

    // =========================================================================
    // QUẢN LÝ TOKEN (Bảng `user_tokens`) - Remember Me & Reset Password
    // =========================================================================

    public function createUserToken(int $userId, string $rawToken, string $type, int $expiresSeconds): bool
    {
        $tokenHash = hash('sha256', $rawToken);

        // Nếu là remember_me, xóa token cũ cùng loại của user
        if ($type === 'remember_me') {
            $stmtDel = $this->conn->prepare("DELETE FROM user_tokens WHERE user_id = ? AND type = 'remember_me'");
            $stmtDel->bind_param("i", $userId);
            $stmtDel->execute();
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO user_tokens (user_id, token_hash, type, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))"
        );
        $stmt->bind_param("issi", $userId, $tokenHash, $type, $expiresSeconds);
        return $stmt->execute();
    }

    public function findUserByToken(string $rawToken, string $type): ?array
    {
        $tokenHash = hash('sha256', $rawToken);
        $stmt = $this->conn->prepare(
            "SELECT u.*, t.id as token_id, t.expires_at as token_expires_at 
             FROM user_tokens t
             JOIN users u ON u.id = t.user_id
             WHERE t.token_hash = ? AND t.type = ? AND t.expires_at > NOW()
             LIMIT 1"
        );
        $stmt->bind_param("ss", $tokenHash, $type);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function deleteUserToken(string $rawToken, string $type): bool
    {
        $tokenHash = hash('sha256', $rawToken);
        $stmt = $this->conn->prepare("DELETE FROM user_tokens WHERE token_hash = ? AND type = ?");
        $stmt->bind_param("ss", $tokenHash, $type);
        return $stmt->execute();
    }

    public function deleteTokensByUserId(int $userId, ?string $type = null): bool
    {
        if ($type) {
            $stmt = $this->conn->prepare("DELETE FROM user_tokens WHERE user_id = ? AND type = ?");
            $stmt->bind_param("is", $userId, $type);
        } else {
            $stmt = $this->conn->prepare("DELETE FROM user_tokens WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
        }
        return $stmt->execute();
    }

    // =========================================================================
}
