    <!-- Cụm nút điều khiển bản đồ & Báo ngập ở góc phải dưới -->
    <div class="bottom-right-actions">
        <div class="map-controls">
            <button class="btn-control" onclick="zoomInMap()" title="Phóng to">
                <i class="ph-bold ph-plus"></i>
            </button>
            <button class="btn-control" onclick="zoomOutMap()" title="Thu nhỏ">
                <i class="ph-bold ph-minus"></i>
            </button>
            <button class="btn-control" onclick="locateUser(true, true)" title="Vị trí GPS của bạn">
                <i class="ph-bold ph-crosshair"></i>
            </button>
            <button class="btn-control" id="btn-radar" onclick="toggleRadarLayer()" title="Bật/Tắt Radar Mây Mưa RainViewer">
                <i class="ph-bold ph-cloud-rain"></i>
            </button>
        </div>
        
        <button class="btn-report" onclick="openReportSheet()">
            <i class="ph-bold ph-warning-circle" style="font-size: 1.25rem;"></i>
            <span>+ Báo sự cố</span>
        </button>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Trạng thái phiên đăng nhập của người dùng -->
    <script>
        const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        const currentUserId = <?= isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'null'; ?>;
        const currentUsername = <?= isset($_SESSION['username']) ? json_encode($_SESSION['username']) : 'null'; ?>;
        const currentUserRole = <?= isset($_SESSION['role']) ? json_encode($_SESSION['role']) : '"guest"'; ?>;
    </script>

       <!-- API Client & JS Bản đồ -->
    <script src="public/js/api.js"></script>
    <script src="public/js/map.js"></script>
    <script src="public/js/radar-weather.js"></script>
    <script src="public/js/sidebar-ui.js"></script>
    <script src="public/js/auth-handlers.js"></script>
</body>
</html>
