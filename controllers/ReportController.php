<?php
// File: controllers/ReportController.php
// Nghiệp vụ báo cáo: validate, upload ảnh, bình chọn. KHÔNG echo / redirect.
require_once __DIR__ . '/../models/ReportModel.php';

class ReportController {
    const TYPES      = ['ngap_nuoc', 'ket_xe'];
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

    public function create(int $userId, array $in, array $files): array {
        $type     = $in['type'] ?? '';
        $severity = $in['severity'] ?? 'vua';
        $lat      = (float)($in['latitude'] ?? 0);
        $lng      = (float)($in['longitude'] ?? 0);
        $address  = trim($in['address_text'] ?? '');
        $desc     = trim($in['description'] ?? '');

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
        }
        return $result;
    }

    // Trả đường dẫn tương đối nếu lưu thành công, false nếu ảnh không hợp lệ
    private function saveImage(array $f) {
        if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > self::MAX_IMAGE_BYTES) return false;

        $info = @getimagesize($f['tmp_name']); // xác minh đúng là ảnh thật
        $map  = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
        if (!$info || !isset($map[$info[2]])) return false;

        $dir = __DIR__ . '/../public/images/reports/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) return false;

        $name = time() . '_' . uniqid() . '.' . $map[$info[2]];
        return move_uploaded_file($f['tmp_name'], $dir . $name) ? 'public/images/reports/' . $name : false;
    }

    public function myReports(int $userId): array {
        $items = $this->model->getByUserId($userId);
        return [
            'ok' => true, 'message' => 'Lấy danh sách báo cáo của bạn thành công', 'data' => $items
        ];
    }

    private function fail(string $error, int $code): array {
        return ['ok' => false, 'error' => $error, 'code' => $code];
    }
}
