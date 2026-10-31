<!-- KHUNG THANH ĐIỀU HƯỚNG BÊN TRÁI URBAN GUARD -->
<div class="sidebar" id="sidebar">
    <!-- 1. Menu Chính của Sidebar -->
    <div id="sidebar-main" style="display: flex; flex-direction: column; height: 100%;">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <div class="logo-icon">
                    <i class="ph-bold ph-shield-check"></i>
                </div>
                <div class="brand-text">
                    <h2>UrbanGuard</h2>
                    <p>Cảnh báo ngập & giao thông thời gian thực</p>
                </div>
            </div>
            <button class="btn-close-sidebar" onclick="toggleSidebar()" title="Đóng menu">
                <i class="ph ph-x"></i>
            </button>
        </div>
        
        <div class="sidebar-menu">
            <a href="#" class="menu-item" onclick="openRoutingModal(); toggleSidebar(); return false;">
                <i class="ph-bold ph-navigation-arrow" style="color: #10b981;"></i>
                <span>Tìm đường né ngập</span>
                <span class="badge-pill green">AI</span>
            </a>
            <a href="#" class="menu-item" onclick="openWeatherModal(); toggleSidebar(); return false;">
                <i class="ph-bold ph-cloud-rain" style="color: #38bdf8;"></i>
                <span>Dự báo ngập & Thủy triều</span>
                <span class="badge-pill blue">1-3h</span>
            </a>
            <a href="#" class="menu-item" onclick="openSosModal(); toggleSidebar(); return false;">
                <i class="ph-bold ph-first-aid" style="color: #ef4444;"></i>
                <span>Cứu hộ khẩn cấp SOS</span>
                <span class="badge-pill red">Hotline</span>
            </a>
            <a href="#" class="menu-item" onclick="openSubPanel('Chú giải chuẩn 4 cấp', 'legend'); return false;">
                <i class="ph ph-list-dashes"></i>
                <span>Chú giải chuẩn 4 cấp</span>
            </a>
            <a href="#" class="menu-item" onclick="openSubPanel('Điểm nóng giao thông', 'news'); return false;">
                <i class="ph ph-article"></i>
                <span>Điểm nóng giao thông</span>
            </a>
            <a href="#" class="menu-item" onclick="openSubPanel('Radar cảnh báo GPS', 'follow'); return false;">
                <i class="ph-bold ph-radar" style="color: #38bdf8;"></i>
                <span>Radar cảnh báo GPS</span>
                <span class="badge-pill red">Mới</span>
            </a>

            <?php if (isset($_SESSION['user_id'])): ?>
            <a href="#" class="menu-item" onclick="openSubPanel('Báo cáo của tôi', 'my_reports'); return false;">
                <i class="ph-bold ph-clock-counter-clockwise" style="color: #60a5fa;"></i>
                <span>Báo cáo của tôi</span>
            </a>
            <a href="#" class="menu-item" onclick="openSubPanel('Hồ sơ & Đổi mật khẩu', 'profile'); return false;">
                <i class="ph-bold ph-user-gear" style="color: #34d399;"></i>
                <span>Hồ sơ & Bảo mật</span>
            </a>
            <?php endif; ?>
        </div>

        <div class="sidebar-divider"></div>

        <div class="sidebar-links">
            <a href="#" onclick="openLayerSwitcherModal(); toggleSidebar(); return false;">
                <i class="ph ph-stack"></i> Quản lý lớp bản đồ GIS
            </a>
            <a href="#" onclick="openSubPanel('Góp ý xây dựng', 'feedback'); return false;">
                <i class="ph ph-chat-teardrop-dots"></i> Góp ý phát triển
            </a>
            <a href="#" onclick="openSubPanel('Hướng dẫn sử dụng', 'guide'); return false;">
                <i class="ph ph-question"></i> Hướng dẫn & An toàn
            </a>
            <a href="#" onclick="openSubPanel('Nhật ký cập nhật', 'changelog'); return false;">
                <i class="ph ph-sparkle"></i> Phiên bản UrbanGuard 2.0
            </a>
        </div>
        
        <div class="sidebar-divider"></div>

        <!-- Phần thông tin tài khoản ở chân Sidebar -->
        <div class="sidebar-footer" style="display:flex; flex-direction:column; gap:12px;">
            <?php if (isset($_SESSION['user_id'])): 
                $userRepScore = 10;
                if (isset($conn) && isset($_SESSION['user_id'])) {
                    $qUser = $conn->query("SELECT reputation_score, role FROM users WHERE id = " . (int)$_SESSION['user_id']);
                    if ($qUser && $rowU = $qUser->fetch_assoc()) {
                        $userRepScore = (int)($rowU['reputation_score'] ?? 10);
                    }
                }
            ?>
                <div style="background: rgba(39, 39, 42, 0.7); border: 1px solid #3f3f46; border-radius: 12px; padding: 10px 12px;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="ph-bold ph-user-circle" style="font-size: 1.4rem; color: #38bdf8;"></i>
                            <strong style="color: white; font-size: 0.95rem;"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></strong>
                        </div>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <span class="badge-admin">ADMIN</span>
                        <?php else: ?>
                            <span class="badge-trusted">⭐ Thành viên</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 0.8rem; color: #94a3b8; margin-top: 6px; display: flex; justify-content: space-between;">
                        <span>Điểm uy tín (Trust Score):</span>
                        <strong style="color: #fbbf24;"><?= $userRepScore ?> ⭐</strong>
                    </div>
                </div>
                
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="index.php?route=admin_dashboard" style="color: #38bdf8; font-size: 0.85rem; text-decoration: none; display: flex; align-items: center; gap: 6px;">
                        <i class="ph-bold ph-shield-check" style="color: #38bdf8;"></i> Bảng Quản Trị Viên (Admin) &rarr;
                    </a>
                <?php endif; ?>
                
                <!-- Nút Đăng xuất: Logout ra nhưng reload trang index (bản đồ chính) -->
                <a href="index.php?route=logout" onclick="localStorage.removeItem('currentUser');" style="color: #ef4444; font-weight: 600; font-size: 0.95rem; text-decoration: none; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <i class="ph-bold ph-sign-out"></i> Đăng xuất
                </a>
            <?php else: ?>
                <a href="#" onclick="openSubPanel('Đăng nhập tài khoản', 'login')" style="color: #60a5fa; font-weight: 600; font-size: 1rem; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                    <i class="ph-bold ph-user-circle" style="font-size: 1.3rem;"></i> Đăng nhập / Đăng ký
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. Khung Sub-Panel Trượt vào (Hiển thị chi tiết theo từng tính năng) -->
    <div id="sidebar-sub" style="display: none; flex-direction: column; height: 100%;">
        <div class="sidebar-header" style="justify-content: flex-start; gap: 12px;">
            <button class="btn-close-sidebar" onclick="closeSubPanel()" title="Quay lại">
                <i class="ph ph-caret-left"></i>
            </button>
            <h3 id="sub-title" style="color: white; font-size: 1.05rem; flex: 1;">Tiêu đề</h3>
            <button class="btn-close-sidebar" onclick="toggleSidebar()" title="Đóng sidebar">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="sidebar-sub-content" id="sub-content">
            <!-- Nội dung động được render bằng JavaScript -->
        </div>
    </div>

    <!-- 3. Khung Báo Cáo Sự Cố Nhanh (3 Chạm Sáng Rõ & Đầy Đủ Tương Phản) -->
    <div id="sidebar-report" style="display: none; flex-direction: column; height: 100%;">
        <div class="sidebar-header" style="justify-content: flex-start; gap: 12px;">
            <button type="button" class="btn-close-sidebar" onclick="closeReportPanel()" title="Quay lại">
                <i class="ph ph-caret-left"></i>
            </button>
            <h3 style="color: white; font-size: 1.05rem; flex: 1;">Báo Cáo Sự Cố Nhanh (3 Chạm)</h3>
            <button type="button" class="btn-close-sidebar" onclick="closeReportPanel()" title="Đóng sidebar">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="sidebar-sub-content" style="padding: 16px; overflow-y: auto;">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div style="text-align: center; padding: 40px 10px; color: #a1a1aa;">
                    <i class="ph-bold ph-lock-key" style="font-size: 2.8rem; color: #f59e0b; margin-bottom: 12px; display: block;"></i>
                    <p style="color: white; font-weight: 600; font-size: 1.05rem; margin-bottom: 8px;">Yêu cầu đăng nhập</p>
                    <p style="font-size: 0.88rem; margin-bottom: 20px; line-height: 1.5; color: #d4d4d8;">Vui lòng đăng nhập tài khoản để gửi thông tin báo cáo sự cố và tích lũy điểm uy tín!</p>
                    <button type="button" onclick="openSubPanel('Đăng nhập tài khoản', 'login')" class="btn-primary-block">
                        Đăng nhập ngay
                    </button>
                </div>
            <?php else: ?>
                <form id="reportAddForm" onsubmit="handleReportFormSubmit(event)" style="display: flex; flex-direction: column; gap: 14px;">
                    <!-- Tọa độ ẩn -->
                    <input type="hidden" id="report_lat" name="latitude" required>
                    <input type="hidden" id="report_lng" name="longitude" required>

                    <!-- BƯỚC 1: Chọn loại sự cố -->
                    <div>
                        <label class="report-form-label"><span class="step-num">1</span> Loại sự cố đô thị:</label>
                        <div class="incident-type-selector">
                            <label class="incident-radio-card active">
                                <input type="radio" name="type" value="ngap_nuoc" checked onchange="toggleReportTypeFields('ngap_nuoc')">
                                <i class="ph-bold ph-waves" style="color: #60a5fa; font-size: 1.4rem;"></i>
                                <span>Ngập lụt</span>
                            </label>
                            <label class="incident-radio-card">
                                <input type="radio" name="type" value="ket_xe" onchange="toggleReportTypeFields('ket_xe')">
                                <i class="ph-bold ph-car" style="color: #fb923c; font-size: 1.4rem;"></i>
                                <span>Kẹt xe</span>
                            </label>
                            <label class="incident-radio-card">
                                <input type="radio" name="type" value="cay_do" onchange="toggleReportTypeFields('cay_do')">
                                <i class="ph-bold ph-tree" style="color: #22c55e; font-size: 1.4rem;"></i>
                                <span>Cây đổ</span>
                            </label>
                            <label class="incident-radio-card">
                                <input type="radio" name="type" value="tai_nan" onchange="toggleReportTypeFields('tai_nan')">
                                <i class="ph-bold ph-warning-octagon" style="color: #ef4444; font-size: 1.4rem;"></i>
                                <span>Tai nạn</span>
                            </label>
                            <label class="incident-radio-card">
                                <input type="radio" name="type" value="rao_chan" onchange="toggleReportTypeFields('rao_chan')">
                                <i class="ph-bold ph-barrier" style="color: #eab308; font-size: 1.4rem;"></i>
                                <span>Rào chắn</span>
                            </label>
                        </div>
                    </div>

                    <!-- BƯỚC 2: Mốc ước lượng trực quan thân thuộc -->
                    <div id="field-flood-depth">
                        <label class="report-form-label"><span class="step-num">2</span> Ước lượng mức nước trực quan:</label>
                        <div class="visual-level-selector">
                            <label class="visual-level-card active">
                                <input type="radio" name="water_level_visual" value="mat_ca" checked>
                                <div>
                                    <strong style="color: #ffffff; font-size: 0.9rem; display: block;">Ngang mắt cá chân</strong>
                                    <span style="color: #94a3b8; font-size: 0.78rem;">Xe máy đi bình thường</span>
                                </div>
                                <span class="depth-tag yellow">~10 - 15cm (Ngập nhẹ)</span>
                            </label>
                            <label class="visual-level-card">
                                <input type="radio" name="water_level_visual" value="banh_xe">
                                <div>
                                    <strong style="color: #ffffff; font-size: 0.9rem; display: block;">Nửa bánh xe máy</strong>
                                    <span style="color: #94a3b8; font-size: 0.78rem;">Khuyên xe ga không nên đi</span>
                                </div>
                                <span class="depth-tag orange">~20 - 25cm (Nguy hiểm)</span>
                            </label>
                            <label class="visual-level-card">
                                <input type="radio" name="water_level_visual" value="yen_xe">
                                <div>
                                    <strong style="color: #ffffff; font-size: 0.9rem; display: block;">Ngập ống xả / Yên xe</strong>
                                    <span style="color: #94a3b8; font-size: 0.78rem;">Đóng đường hoàn toàn</span>
                                </div>
                                <span class="depth-tag red">>40 - 50cm (Chết máy)</span>
                            </label>
                        </div>
                    </div>

                    <div id="field-traffic-speed" style="display: none;">
                        <label class="report-form-label"><span class="step-num">2</span> Tình trạng lưu thông:</label>
                        <div class="visual-level-selector">
                            <label class="visual-level-card active">
                                <input type="radio" name="traffic_speed_visual" value="cham" checked>
                                <div>
                                    <strong style="color: #ffffff; font-size: 0.9rem; display: block;">Di chuyển chậm</strong>
                                    <span style="color: #94a3b8; font-size: 0.78rem;">Xe đông nối đuôi</span>
                                </div>
                                <span class="depth-tag yellow">15 - 25 km/h</span>
                            </label>
                            <label class="visual-level-card">
                                <input type="radio" name="traffic_speed_visual" value="un_u">
                                <div>
                                    <strong style="color: #ffffff; font-size: 0.9rem; display: block;">Ùn ứ nặng kéo dài</strong>
                                    <span style="color: #94a3b8; font-size: 0.78rem;">Di chuyển từng mét</span>
                                </div>
                                <span class="depth-tag orange">5 - 15 km/h</span>
                            </label>
                            <label class="visual-level-card">
                                <input type="radio" name="traffic_speed_visual" value="tac_nghen">
                                <div>
                                    <strong style="color: #ffffff; font-size: 0.9rem; display: block;">Tắc nghẽn bất động</strong>
                                    <span style="color: #94a3b8; font-size: 0.78rem;">Chôn chân tại chỗ</span>
                                </div>
                                <span class="depth-tag red"><5 km/h (Chôn chân)</span>
                            </label>
                        </div>
                    </div>

                    <input type="hidden" name="severity" id="computed_severity" value="vua">

                    <!-- BƯỚC 3: Vị trí & Ảnh hiện trường -->
                    <div>
                        <label class="report-form-label"><span class="step-num">3</span> Vị trí điểm sự cố:</label>
                        <div style="background: rgba(37, 99, 235, 0.15); border: 1px dashed #3b82f6; border-radius: 8px; padding: 8px 10px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                            <span style="color: #93c5fd; font-size: 0.8rem; line-height: 1.3;">
                                <i class="ph-bold ph-hand-tap"></i> Chạm lên bản đồ hoặc bấm GPS
                            </span>
                            <button type="button" onclick="locateUser(true, true)" class="btn-route-action" style="width: auto; padding: 4px 10px; font-size: 0.78rem; font-weight: 600; background: #2563eb; color: white; display: flex; align-items: center; gap: 4px;">
                                <i class="ph-bold ph-crosshair"></i> Vị trí của tôi
                            </button>
                        </div>
                        <input type="text" id="report_address" name="address_text" placeholder="Chạm lên bản đồ hoặc nhập tên đường..." class="form-input" style="width: 100%; color: white;" required>
                    </div>

                    <div>
                        <label class="report-form-label">Ảnh hiện trường thực tế (tuỳ chọn):</label>
                        <input type="file" id="report_image_input" name="image" accept="image/*" class="form-input" style="width: 100%; color: #94a3b8; padding: 8px;">
                    </div>

                    <div>
                        <label class="report-form-label">Mô tả thêm (tuỳ chọn):</label>
                        <textarea name="description" placeholder="Ví dụ: Nước tràn vào cửa hàng, nhiều xe chết máy tại giao lộ..." class="form-input" style="width: 100%; height: 75px; resize: none; color: white;"></textarea>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 6px;">
                        <button type="button" onclick="closeReportPanel()" class="btn-cancel">
                            Huỷ
                        </button>
                        <button type="submit" class="btn-submit-report">
                            <i class="ph-bold ph-paper-plane-right"></i> Phát Cảnh Báo
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
