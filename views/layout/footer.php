    <!-- Cụm nút điều khiển bản đồ & Báo ngập ở góc phải dưới -->
    <div class="bottom-right-actions">
        <div class="map-controls">
            <!-- Nút SOS khẩn cấp -->
            <button class="btn-control" onclick="openSosModal()" title="Cứu hộ khẩn cấp SOS">
                <i class="ph-bold ph-first-aid" style="color: #ef4444;"></i>
            </button>
            <!-- Nút Định tuyến né ngập -->
            <button class="btn-control" onclick="openRoutingModal()" title="Tìm đường né ngập thông minh">
                <i class="ph-bold ph-navigation-arrow" style="color: #10b981;"></i>
            </button>
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

    <!-- ========================================================================= -->
    <!-- 1. MODAL ĐỊNH TUYẾN NÉ NGẬP THEO LOẠI XE (VEHICLE ROUTING)                 -->
    <!-- ========================================================================= -->
    <div id="modal-routing" class="app-modal" style="display: none;">
        <div class="modal-card">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="ph-bold ph-navigation-arrow" style="color: #10b981;"></i>
                    <h3>Tìm Đường Né Ngập Thông Minh</h3>
                </div>
                <button type="button" class="btn-close-modal" onclick="closeRoutingModal()">&times;</button>
            </div>
            <div class="modal-body">
                <label class="modal-label">Loại phương tiện di chuyển:</label>
                <div class="vehicle-tabs">
                    <button type="button" class="vehicle-tab" data-vehicle="walk_bike" onclick="selectVehicle('walk_bike')">
                        <i class="ph-bold ph-person-simple-walk" style="font-size: 1.3rem;"></i>
                        <span>Đi bộ / Xe đạp</span>
                        <span style="font-size: 0.7rem; color: #94a3b8;">&lt; 10cm</span>
                    </button>
                    <button type="button" class="vehicle-tab active" data-vehicle="motorbike" onclick="selectVehicle('motorbike')">
                        <i class="ph-bold ph-moped" style="font-size: 1.3rem;"></i>
                        <span>Xe máy</span>
                        <span style="font-size: 0.7rem; color: #10b981;">&lt; 20cm</span>
                    </button>
                    <button type="button" class="vehicle-tab" data-vehicle="sedan" onclick="selectVehicle('sedan')">
                        <i class="ph-bold ph-car" style="font-size: 1.3rem;"></i>
                        <span>Sedan gầm thấp</span>
                        <span style="font-size: 0.7rem; color: #94a3b8;">&lt; 25cm</span>
                    </button>
                    <button type="button" class="vehicle-tab" data-vehicle="suv" onclick="selectVehicle('suv')">
                        <i class="ph-bold ph-jeep" style="font-size: 1.3rem;"></i>
                        <span>SUV / Bán tải</span>
                        <span style="font-size: 0.7rem; color: #94a3b8;">&lt; 45cm</span>
                    </button>
                </div>

                <!-- Điểm đi & Điểm đến -->
                <div class="route-inputs">
                    <div class="route-input-group">
                        <span class="route-pin-icon green">A</span>
                        <input type="text" id="route-from-input" class="route-input" placeholder="Điểm đi (Mặc định: Vị trí của tôi)...">
                        <button type="button" class="btn-route-action" onclick="useMyLocationForRoute('from')" title="Lấy GPS của tôi">
                            <i class="ph-bold ph-crosshair"></i>
                        </button>
                        <button type="button" class="btn-route-action" onclick="pickOnMapForRoute('from')" title="Chấm trên bản đồ">
                            <i class="ph-bold ph-map-pin"></i>
                        </button>
                    </div>

                    <div class="route-input-group">
                        <span class="route-pin-icon red">B</span>
                        <input type="text" id="route-to-input" class="route-input" placeholder="Điểm đến (Chấm trên bản đồ hoặc nhập)...">
                        <button type="button" class="btn-route-action" onclick="pickOnMapForRoute('to')" title="Chấm trên bản đồ">
                            <i class="ph-bold ph-map-pin"></i>
                        </button>
                    </div>
                </div>

                <button type="button" class="btn-primary-block" onclick="calculateSmartRoute()">
                    <i class="ph-bold ph-magnifying-glass"></i> Phân Tích Lộ Trình Né Ngập
                </button>

                <div id="routing-results" style="display: none; margin-top: 14px;">
                    <!-- Rendered by JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. MODAL DỰ BÁO NGẬP & THỦY TRIỀU (WEATHER & TIDE)                         -->
    <!-- ========================================================================= -->
    <div id="modal-weather" class="app-modal" style="display: none;">
        <div class="modal-card">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="ph-bold ph-cloud-rain" style="color: #38bdf8;"></i>
                    <h3>Dự Báo Mưa, Ngập & Thủy Triều</h3>
                </div>
                <button type="button" class="btn-close-modal" onclick="closeWeatherModal()">&times;</button>
            </div>
            <div class="modal-body" id="weather-modal-body">
                <div style="text-align: center; padding: 20px; color: #94a3b8;">
                    <i class="ph-bold ph-spinner-gap" style="font-size: 2rem; animation: spin 1s infinite linear;"></i>
                    <p style="margin-top: 8px;">Đang tải số liệu khí tượng thủy văn...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. MODAL CỨU HỘ KHẨN CẤP (SOS HOTLINE & YÊU CẦU CỨU TRỢ)                 -->
    <!-- ========================================================================= -->
    <div id="modal-sos" class="app-modal" style="display: none;">
        <div class="modal-card">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="ph-bold ph-first-aid" style="color: #ef4444;"></i>
                    <h3>Cứu Hộ Khẩn Cấp (SOS 24/7)</h3>
                </div>
                <button type="button" class="btn-close-modal" onclick="closeSosModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="sos-alert-box">
                    <i class="ph-fill ph-warning-octagon" style="font-size: 2rem; color: #ef4444; flex-shrink: 0;"></i>
                    <div>
                        <strong style="color: #ffffff; display: block; margin-bottom: 2px;">Bạn đang bị chết máy hoặc ngập xe giữa đường?</strong>
                        <p style="color: #cbd5e1; font-size: 0.82rem; margin: 0;">Hệ thống sẽ lấy tọa độ GPS của bạn để thông báo ngay đến Đội Điều Phối Cứu Hộ.</p>
                    </div>
                </div>

                <!-- Form gửi yêu cầu SOS -->
                <form id="sosSendForm" onsubmit="handleSendSosSignal(event)" style="display: flex; flex-direction: column; gap: 10px;">
                    <div>
                        <label class="modal-label">Số điện thoại liên hệ của bạn:</label>
                        <input type="tel" id="sos-phone" class="form-input" placeholder="Ví dụ: 0912345678..." required style="width: 100%; color: white;">
                    </div>
                    <div>
                        <label class="modal-label">Tình trạng phương tiện / Mô tả sự cố:</label>
                        <input type="text" id="sos-desc" class="form-input" placeholder="Ví dụ: Xe ga chết máy ngập nước ngang pô xe..." style="width: 100%; color: white;">
                    </div>
                    <button type="submit" id="btnSosSubmit" class="btn-danger-block">
                        <i class="ph-bold ph-broadcast"></i> Phát Tín Hiệu SOS Đến Đội Cứu Hộ
                    </button>
                </form>

                <div class="sidebar-divider" style="margin: 16px 0;"></div>

                <h4 style="font-size: 0.95rem; color: #cbd5e1; margin-bottom: 8px;">Đường dây nóng hỗ trợ khẩn cấp:</h4>
                <div class="hotline-grid">
                    <a href="tel:19001088" class="hotline-card">
                        <i class="ph-bold ph-phone-call" style="font-size: 1.4rem; color: #38bdf8;"></i>
                        <strong>1900 1088</strong>
                        <span>Cứu hộ giao thông</span>
                    </a>
                    <a href="tel:114" class="hotline-card">
                        <i class="ph-bold ph-lifebuoy" style="font-size: 1.4rem; color: #ef4444;"></i>
                        <strong>114</strong>
                        <span>Cứu nạn cứu hộ</span>
                    </a>
                    <a href="tel:115" class="hotline-card">
                        <i class="ph-bold ph-first-aid" style="font-size: 1.4rem; color: #10b981;"></i>
                        <strong>115</strong>
                        <span>Cấp cứu y tế</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. MODAL QUẢN LÝ LỚP BẢN ĐỒ GIS                                            -->
    <!-- ========================================================================= -->
    <div id="modal-layers" class="app-modal" style="display: none;">
        <div class="modal-card">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="ph-bold ph-stack" style="color: #38bdf8;"></i>
                    <h3>Quản Lý Lớp Bản Đồ GIS</h3>
                </div>
                <button type="button" class="btn-close-modal" onclick="closeLayerSwitcherModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="layer-toggle-list">
                    <label class="layer-toggle-item">
                        <input type="checkbox" id="layer-check-floods" checked onchange="toggleLayerVisibility('floods')">
                        <div class="layer-info">
                            <span class="layer-title"><i class="ph-bold ph-waves" style="color: #38bdf8;"></i> Lớp Điểm Ngập Nước</span>
                            <span class="layer-desc">Hiển thị các vị trí ngập sâu, mức nước và cảnh báo</span>
                        </div>
                    </label>

                    <label class="layer-toggle-item">
                        <input type="checkbox" id="layer-check-traffic" checked onchange="toggleLayerVisibility('traffic')">
                        <div class="layer-info">
                            <span class="layer-title"><i class="ph-bold ph-car" style="color: #fb923c;"></i> Lớp Kẹt Xe & Sự Cố Giao Thông</span>
                            <span class="layer-desc">Hiển thị các đoạn đường đang bị ùn tắc nghiêm trọng</span>
                        </div>
                    </label>

                    <label class="layer-toggle-item">
                        <input type="checkbox" id="layer-check-radar" onchange="toggleRadarFromModal(this.checked)">
                        <div class="layer-info">
                            <span class="layer-title"><i class="ph-bold ph-cloud-rain" style="color: #3b82f6;"></i> Lớp Radar Mây Mưa RainViewer</span>
                            <span class="layer-desc">Ảnh vệ tinh đám mây dông mưa thời gian thực</span>
                        </div>
                    </label>
                </div>
            </div>
        </div>
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
</body>
</html>
