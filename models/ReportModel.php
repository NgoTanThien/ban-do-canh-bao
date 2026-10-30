<?php
// File: models/ReportModel.php
// Chỉ làm việc với bảng `reports` và `report_votes`.
class ReportModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // // Danh sách báo cáo đang diễn ra (kèm tên người báo + điểm uy tín)
    // public function getActiveReports(?string $type = null): array {
    //     $sql = "SELECT r.*,
    //                    COALESCE(u.username, 'Ẩn danh') AS username,
    //                    COALESCE(u.reputation_score, 0) AS reputation_score
    //             FROM reports r
    //             LEFT JOIN users u ON r.user_id = u.id
    //             WHERE r.status = 'dang_dien_ra'";
    //     if ($type !== null) {
    //         $sql .= " AND r.type = ?";
    //     }
    //     $sql .= " ORDER BY r.created_at DESC";

    //     $stmt = $this->conn->prepare($sql);
    //     if ($type !== null) {
    //         $stmt->bind_param("s", $type);
    //     }
    //     $stmt->execute();
    //     return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    // }
        // Tự động quét và ẩn các điểm đã quá hạn
    // - Ngập lụt: quá 4 tiếng không có ai xác nhận thêm
    // - Kẹt xe: quá 2 tiếng không có ai xác nhận thêm
    public function autoExpireReports(): void {
        $sql = "UPDATE reports 
                SET status = 'da_ket_thuc' 
                WHERE status = 'dang_dien_ra' 
                  AND (
                      (expires_at IS NOT NULL AND expires_at <= NOW())
                      OR (expires_at IS NULL AND type = 'ngap_nuoc' AND updated_at <= DATE_SUB(NOW(), INTERVAL 4 HOUR))
                      OR (expires_at IS NULL AND type = 'ket_xe' AND updated_at <= DATE_SUB(NOW(), INTERVAL 2 HOUR))
                  )";
        $this->conn->query($sql);
    }

    // Danh sách báo cáo đang diễn ra (kèm tên người báo + điểm uy tín)
    public function getActiveReports(?string $type = null): array {
        // Tự động ẩn các tin quá hạn trước khi lấy dữ liệu trả về cho bản đồ
        $this->autoExpireReports();

        $sql = "SELECT r.*,
                       COALESCE(u.username, 'Ẩn danh') AS username,
                       COALESCE(u.reputation_score, 0) AS reputation_score
                FROM reports r
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.status = 'dang_dien_ra'";
        if ($type !== null) {
            $sql .= " AND r.type = ?";
        }
        $sql .= " ORDER BY r.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        if ($type !== null) {
            $stmt->bind_param("s", $type);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

        // Trả về id mới (int) hoặc false
    public function createReport($userId, $type, $severity, $latitude, $longitude, $addressText, $description, $imageUrl = null) {
        // Ngập nước: 4 tiếng | Kẹt xe: 2 tiếng
        $expireHours = ($type === 'ngap_nuoc') ? 4 : 2;

        $sql = "INSERT INTO reports (user_id, type, severity, latitude, longitude, address_text, description, image_url, confirm_count, reject_count, status, expires_at, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 'dang_dien_ra', DATE_ADD(NOW(), INTERVAL ? HOUR), NOW(), NOW())";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("issddsssi", $userId, $type, $severity, $latitude, $longitude, $addressText, $description, $imageUrl, $expireHours);
        return $stmt->execute() ? $this->conn->insert_id : false;
    }

    // Bình chọn / đổi bình chọn. Trả: ['ok'=>true,'data'=>[confirm_count,reject_count,my_vote]] hoặc ['ok'=>false,'error','code']
    public function vote(int $reportId, int $userId, string $voteType): array {
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("SELECT user_id, status FROM reports WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $reportId);
            $stmt->execute();
            $report = $stmt->get_result()->fetch_assoc();

            if (!$report) {
                $this->conn->rollback();
                return ['ok' => false, 'error' => 'Báo cáo không tồn tại', 'code' => 404];
            }
            if ($report['status'] !== 'dang_dien_ra') {
                $this->conn->rollback();
                return ['ok' => false, 'error' => 'Báo cáo này đã kết thúc hoặc bị ẩn', 'code' => 409];
            }
            if ((int)$report['user_id'] === $userId) {
                $this->conn->rollback();
                return ['ok' => false, 'error' => 'Bạn không thể bình chọn báo cáo của chính mình', 'code' => 403];
            }

            $stmt = $this->conn->prepare("SELECT vote_type FROM report_votes WHERE report_id = ? AND user_id = ? FOR UPDATE");
            $stmt->bind_param("ii", $reportId, $userId);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();

            if ($existing && $existing['vote_type'] === $voteType) {
                $this->conn->rollback();
                return ['ok' => false, 'error' => 'Bạn đã bình chọn lựa chọn này rồi', 'code' => 409];
            }

                        if ($existing) {
                // Đổi ý: chuyển 1 phiếu từ loại cũ sang loại mới
                $stmt = $this->conn->prepare("UPDATE report_votes SET vote_type = ? WHERE report_id = ? AND user_id = ?");
                $stmt->bind_param("sii", $voteType, $reportId, $userId);
                $stmt->execute();
                
                // Nếu đổi sang Xác nhận (confirm) -> Tính lại thời gian từ đầu (ngập +4h, kẹt xe +2h)
                $sql = ($voteType === 'confirm')
                    ? "UPDATE reports SET confirm_count = confirm_count + 1, reject_count = GREATEST(reject_count - 1, 0), expires_at = DATE_ADD(NOW(), INTERVAL (CASE WHEN type = 'ngap_nuoc' THEN 4 ELSE 2 END) HOUR) WHERE id = ?"
                    : "UPDATE reports SET reject_count = reject_count + 1, confirm_count = GREATEST(confirm_count - 1, 0) WHERE id = ?";
            } else {
                $stmt = $this->conn->prepare("INSERT INTO report_votes (report_id, user_id, vote_type) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $reportId, $userId, $voteType);
                $stmt->execute();
                
                // Nếu bấm Xác nhận (confirm) lần đầu -> Tính lại thời gian từ đầu
                $sql = ($voteType === 'confirm')
                    ? "UPDATE reports SET confirm_count = confirm_count + 1, expires_at = DATE_ADD(NOW(), INTERVAL (CASE WHEN type = 'ngap_nuoc' THEN 4 ELSE 2 END) HOUR) WHERE id = ?"
                    : "UPDATE reports SET reject_count = reject_count + 1 WHERE id = ?";
            }
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $reportId);
            $stmt->execute();

            $stmt = $this->conn->prepare("SELECT confirm_count, reject_count FROM reports WHERE id = ?");
            $stmt->bind_param("i", $reportId);
            $stmt->execute();
            $counts = $stmt->get_result()->fetch_assoc();

            $confirmCount = (int)$counts['confirm_count'];
            $rejectCount  = (int)$counts['reject_count'];

            if ($voteType === 'reject' && ($rejectCount - $confirmCount) >= 3) {
                $stmt = $this->conn->prepare("UPDATE reports SET status = 'bi_an' WHERE id = ?");
                $stmt->bind_param("i", $reportId);
                $stmt->execute();
                require_once __DIR__ . '/UserModel.php';
                $userModel = new UserModel($this->conn);
                $userModel->deductReputation((int)$report['user_id'], 1);
            }

            $this->conn->commit();
            return ['ok' => true, 'data' => [
                'report_id'     => $reportId,
                'confirm_count' => $confirmCount,
                'reject_count'  => $rejectCount,
                'my_vote'       => $voteType,
            ]];
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}
