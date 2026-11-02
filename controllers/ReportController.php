<?php
// File: controllers/ReportController.php
// Nghiệp vụ báo cáo: validate, upload ảnh, bình chọn. KHÔNG echo / redirect.
require_once __DIR__ . '/../models/ReportModel.php';

class ReportController {
    const TYPES      = ['ngap_nuoc', 'ket_xe', 'cay_do', 'tai_nan', 'rao_chan'];
    const SEVERITIES = ['nhe', 'vua', 'nghiem_trong'];
    const VOTES      = ['confirm', 'reject'];
    const MAX_IMAGE_BYTES = 5242880; // 5MB

    private $model;

    public function __construct(mysqli $conn) {
        $this->model = new ReportModel($conn);
    }

    public function list(string $type = 'all'): array {
        if ($type !== 'all' && !in_array($type, self::TYPES, true)) {
            return $this->fail('Loại sự cố không hợp lệ', 400);
        }
        $reports = $this->model->getActiveReports($type === 'all' ? null : $type);
        return ['ok' => true, 'data' => $reports];
    }

    public function myReports(int $userId): array {
        $reports = $this->model->getByUserId($userId);
        return ['ok' => true, 'data' => $reports];
    }

    public function create(int $userId, array $in, array $files): array {
        $type     = $in['type'] ?? '';
        $severity = $in['severity'] ?? 'vua';
        $lat      = (float)($in['latitude'] ?? 0);
        $lng      = (float)($in['longitude'] ?? 0);
        $address  = trim($in['address_text'] ?? '');
        $desc     = trim($in['description'] ?? '');

        // Tính toán mức độ từ trực quan nếu có
        if (!empty($in['water_level_visual'])) {
            if ($in['water_level_visual'] === 'mat_ca') $severity = 'nhe';
            elseif ($in['water_level_visual'] === 'banh_xe') $severity = 'vua';
            elseif ($in['water_level_visual'] === 'yen_xe') $severity = 'nghiem_trong';
        } elseif (!empty($in['traffic_speed_visual'])) {
            if ($in['traffic_speed_visual'] === 'cham') $severity = 'nhe';
            elseif ($in['traffic_speed_visual'] === 'un_u') $severity = 'vua';
            elseif ($in['traffic_speed_visual'] === 'tac_nghen') $severity = 'nghiem_trong';
        }

        if (!in_array($type, self::TYPES, true))         return $this->fail('Loại sự cố không hợp lệ', 422);
        if (!in_array($severity, self::SEVERITIES, true)) return $this->fail('Mức độ nghiêm trọng không hợp lệ', 422);
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ($lat == 0 && $lng == 0)) {
            return $this->fail('Vui lòng chọn vị trí hợp lệ trên bản đồ!', 422);
        }
        if (mb_strlen($address) > 255) return $this->fail('Địa chỉ quá dài (tối đa 255 ký tự)', 422);

        $imageUrl = null;
        if (!empty($files['image']) && $files['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $saved = $this->saveImage($files['image']);
            if ($saved === false) {
                return $this->fail('Ảnh không hợp lệ (chỉ nhận jpg, png, webp, gif tối đa 5MB)', 422);
            }
            $imageUrl = $saved;
        }

        $id = $this->model->createReport($userId, $type, $severity, $lat, $lng, $address, $desc, $imageUrl);
        if (!$id) {
            return $this->fail('Có lỗi xảy ra khi lưu vào cơ sở dữ liệu!', 500);
        }
        return ['ok' => true, 'message' => 'Gửi báo cáo sự cố thành công!', 'data' => ['id' => $id, 'image_url' => $imageUrl]];
    }

    public function vote(int $userId, array $in): array {
        $reportId = (int)($in['report_id'] ?? 0);
        $voteType = $in['vote_type'] ?? '';
        if ($reportId <= 0 || !in_array($voteType, self::VOTES, true)) {
            return $this->fail('Dữ liệu bình chọn không hợp lệ', 422);
        }
        $result = $this->model->vote($reportId, $userId, $voteType);
        if ($result['ok']) {
            $result['message'] = 'Đã ghi nhận bình chọn của bạn';
            return $result;
        }
        return $this->fail($result['message'] ?? 'Bình chọn thất bại', 400);
    }

    private function saveImage(array $file) {
        if ($file['size'] > self::MAX_IMAGE_BYTES) return false;
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!isset($allowed[$mime])) return false;

        $dir = __DIR__ . '/../public/images/reports';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $ext = $allowed[$mime];
        $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) return false;
        return 'public/images/reports/' . $filename;
    }

    private function fail(string $msg, int $code = 400): array {
        return ['ok' => false, 'message' => $msg, 'code' => $code];
    }
}
