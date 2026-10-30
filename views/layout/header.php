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

    <!-- Style CSS -->
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>

    <!-- Cụm nút mở Menu góc trên trái -->
    <div class="top-left-actions">
        <button class="btn-menu" onclick="toggleSidebar()" title="Mở danh mục menu">
            <i class="ph-bold ph-list"></i>
        </button>
    </div>

    <!-- Thanh thông báo trạng thái & Bộ lọc ở giữa trên -->
    <div class="top-center-bar">
        <div class="notification-pill" onclick="openSidebarNews()" title="Xem danh sách tin mới">
            <span class="pulse-dot green" id="status-dot"></span>
            <span id="status-text">Đang tải trạng thái...</span>
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
