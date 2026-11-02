<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <?php
$googleConfigFile = __DIR__ . '/../../config/google.php';
if (file_exists($googleConfigFile)) {
    require_once $googleConfigFile;
}
?>
    <?php if (defined('GOOGLE_CLIENT_ID')): ?>
    <meta name="google-signin-client_id" content="<?= htmlspecialchars(GOOGLE_CLIENT_ID) ?>">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
        window.GOOGLE_CLIENT_ID = <?= json_encode(GOOGLE_CLIENT_ID) ?>;
    </script>
    <?php endif; ?>
    <title>Bản đồ Cảnh Báo Điểm Ngập & Kẹt Xe Công Cộng</title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Google Fonts & Phosphor Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css" />
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css" />
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/fill/style.css" />

    <!-- Style CSS (Có tham số version động để chống lưu cache trình duyệt cũ) -->
    <link rel="stylesheet" href="public/css/style.css?v=<?= file_exists(__DIR__ . '/../../public/css/style.css') ? filemtime(__DIR__ . '/../../public/css/style.css') : time() ?>">
    <!-- Firebase Auth Web SDK (Google Sign-In) -->
    <script type="module" src="public/js/firebase-auth.js?v=<?= time() ?>"></script>
</head>
<body>

    <!-- Cụm nút mở Menu góc trên trái & Thanh tìm kiếm địa điểm trên bản đồ -->
    <div class="top-left-actions" style="display: flex; align-items: center; gap: 8px;">
        <button class="btn-menu" onclick="toggleSidebar()" title="Mở danh mục menu">
            <i class="ph-bold ph-list"></i>
        </button>
        <div class="map-search-container" style="display: flex; align-items: center; background: rgba(24, 24, 27, 0.9); backdrop-filter: blur(8px); border: 1px solid #3f3f46; border-radius: 9999px; padding: 4px 10px 4px 14px; box-shadow: 0 4px 14px rgba(0,0,0,0.3); height: 40px;">
            <i class="ph-bold ph-magnifying-glass" style="color: #94a3b8; font-size: 1rem; margin-right: 6px;"></i>
            <input type="text" id="mapSearchInput" placeholder="Tìm đường, địa điểm..." style="background: transparent; border: none; outline: none; color: white; font-size: 0.85rem; width: 175px;" onkeydown="if(event.key==='Enter') searchLocationOnMap()">
            <button type="button" onclick="searchLocationOnMap()" style="background: #2563eb; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; cursor: pointer; margin-left: 4px; transition: transform 0.15s;" onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'" title="Tìm kiếm trên bản đồ">
                <i class="ph-bold ph-arrow-right" style="font-size: 0.85rem;"></i>
            </button>
        </div>
    </div>

    <!-- Thanh thông báo trạng thái & Bộ lọc ở giữa trên -->
    <div class="top-center-bar">
        <div class="notification-pill" onclick="openSidebarNews()" title="Xem danh sách tin mới">
            <span class="pulse-dot green" id="status-dot"></span>
            <span id="status-text">Đang tải trạng thái...</span>
        </div>
        
        <!-- Hiển thị nhiệt độ & icon thời tiết thời gian thực -->
        <div class="weather-pill" id="weather-pill" title="Thời tiết hiện tại">
            <span id="weather-icon"><i class="ph-bold ph-cloud-sun"></i></span>
            <span id="weather-temp">--°C</span>
        </div>

        <div class="filter-pill" onclick="toggleFilterMenu(event)">
            <span id="currentFilterText">Tất cả sự cố</span>
            <i class="ph-bold ph-caret-down"></i>
            
            <div id="filterMenu" class="filter-menu-dropdown">
                <div class="filter-option" onclick="applyFilter('all', event)">
                    <i class="ph ph-squares-four"></i> Tất cả sự cố
                </div>
                <div class="filter-option" onclick="applyFilter('ngap_nuoc', event)">
                    <i class="ph-bold ph-waves" style="color: #60a5fa;"></i> Chỉ báo ngập
                </div>
                <div class="filter-option" onclick="applyFilter('ket_xe', event)">
                    <i class="ph-bold ph-car" style="color: #fb923c;"></i> Chỉ kẹt xe
                </div>
            </div>
        </div>
    </div>

    <!-- Banner cảnh báo sự cố nguy hiểm trong bán kính GPS -->
    <div id="proximity-alert-banner" class="proximity-banner" style="display: none;">
        <div class="proximity-banner-content" onclick="focusClosestAlert()">
            <i class="ph-fill ph-warning-octagon proximity-icon"></i>
            <span id="proximity-banner-text">Đang quét sự cố quanh bạn...</span>
            <span class="btn-proximity-action">Xem vị trí &rarr;</span>
        </div>
        <button type="button" class="btn-close-banner" onclick="dismissProximityBanner(event)" title="Đóng cảnh báo">&times;</button>
    </div>

    <!-- Thông báo kết quả từ PHP (Flash Message) -->
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div style="position: fixed; top: 75px; left: 50%; transform: translateX(-50%); z-index: 9999; background: #059669; color: white; padding: 12px 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); display: flex; align-items: center; gap: 8px; font-size: 0.95rem; font-weight: 500; cursor: pointer;" onclick="this.remove()">
            <i class="ph-bold ph-check-circle" style="font-size: 1.2rem;"></i>
            <span><?= htmlspecialchars($_SESSION['flash_success']) ?></span>
            <i class="ph ph-x" style="margin-left: 10px; opacity: 0.7;"></i>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div style="position: fixed; top: 75px; left: 50%; transform: translateX(-50%); z-index: 9999; background: #dc2626; color: white; padding: 12px 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); display: flex; align-items: center; gap: 8px; font-size: 0.95rem; font-weight: 500; cursor: pointer;" onclick="this.remove()">
            <i class="ph-bold ph-warning-circle" style="font-size: 1.2rem;"></i>
            <span><?= htmlspecialchars($_SESSION['flash_error']) ?></span>
            <i class="ph ph-x" style="margin-left: 10px; opacity: 0.7;"></i>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
