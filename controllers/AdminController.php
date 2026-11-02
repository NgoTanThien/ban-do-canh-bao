<?php
// File: controllers/AdminController.php
// Nghiệp vụ quản trị. KHÔNG echo / redirect / render view (view lấy dữ liệu qua API).
require_once __DIR__ . '/../models/UserModel.php';

class AdminController {
    private $users;

    public function __construct(mysqli $conn) {
        $this->users = new UserModel($conn);
    }

    public function listUsers(): array {
        return ['ok' => true, 'data' => $this->users->listAll()];
    }

    // action: 'block' | 'unblock' | 'set_role' | 'set_score' | 'delete'
    public function updateUserStatus(int $adminId, array $in): array {
        $userId = (int)($in['user_id'] ?? 0);
        $action = $in['action'] ?? '';

        if ($userId <= 0) {
            return ['ok' => false, 'error' => 'ID tài khoản không hợp lệ', 'code' => 422];
        }
        if ($userId === $adminId && in_array($action, ['block', 'delete', 'set_role'], true)) {
            return ['ok' => false, 'error' => 'Bạn không thể thao tác trên tài khoản Admin đang đăng nhập của chính mình!', 'code' => 403];
        }
        $target = $this->users->findById($userId);
        if (!$target) {
            return ['ok' => false, 'error' => 'Người dùng không tồn tại', 'code' => 404];
        }

        if ($action === 'block') {
            $this->users->setStatus($userId, UserModel::STATUS_BLOCKED);
            $message = 'Đã khóa tài khoản thành công!';
        } elseif ($action === 'unblock') {
            $this->users->setStatus($userId, UserModel::STATUS_ACTIVE, true);
            $message = 'Đã mở khóa và khôi phục điểm uy tín cho tài khoản!';
        } elseif ($action === 'set_role') {
            $newRole = ($target['role'] === 'admin') ? 'user' : 'admin';
            $this->users->setRole($userId, $newRole);
            $message = "Đã chuyển đổi quyền tài khoản thành {$newRole}!";
        } elseif ($action === 'set_score') {
            $score = max(0, min(100, (int)($in['score'] ?? 100)));
            $this->users->setReputationScore($userId, $score);
            $message = "Đã cập nhật điểm uy tín thành {$score} điểm!";
        } elseif ($action === 'delete') {
            $this->users->deleteUser($userId);
            return ['ok' => true, 'message' => 'Đã xóa tài khoản người dùng thành công!', 'data' => null];
        } else {
            return ['ok' => false, 'error' => 'Hành động không hợp lệ', 'code' => 400];
        }

        return ['ok' => true, 'message' => $message, 'data' => $this->users->findById($userId)];
    }
}
