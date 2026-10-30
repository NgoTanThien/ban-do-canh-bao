    <!-- Cụm nút điều khiển bản đồ & Báo ngập ở góc phải dưới -->
    <div class="bottom-right-actions">
        <div class="map-controls">
            <button class="btn-control" onclick="zoomInMap()" title="Phóng to">
                <i class="ph-bold ph-plus"></i>
            </button>
            <button class="btn-control" onclick="zoomOutMap()" title="Thu nhỏ">
                <i class="ph-bold ph-minus"></i>
            </button>
            <button class="btn-control" onclick="locateUser(true, true)" title="Vị trí của bạn">
                <i class="ph-bold ph-crosshair"></i>
            </button>
            <button class="btn-control" onclick="openRadarSettings()" title="Radar cảnh báo GPS & Thông báo">
                <i class="ph-bold ph-radar" style="color: #38bdf8;"></i>
            </button>
        </div>
        <button class="btn-report" onclick="openReportSheet()">
            <i class="ph-bold ph-warning-circle" style="font-size: 1.25rem;"></i>
            <span>Báo sự cố</span>
        </button>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Trạng thái phiên đăng nhập của người dùng -->
    <script>
        const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        const currentUsername = <?= isset($_SESSION['username']) ? json_encode($_SESSION['username']) : 'null'; ?>;
    </script>

       <!-- API Client & JS Bản đồ -->
    <script src="public/js/api.js"></script>
    <script src="public/js/map.js"></script>
</body>
</html>
