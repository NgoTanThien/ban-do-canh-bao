<!-- KHUNG THANH ĐIỀU HƯỚNG BÊN TRÁI URBAN GUARD -->
<div class="sidebar" id="sidebar" style="position: fixed; top: 0; left: 0; bottom: 0; height: 100vh; max-height: 100vh; overflow: hidden; display: flex; flex-direction: column; width: 360px; max-width: 88vw; z-index: 2001;">
    <!-- 1. Menu Chính của Sidebar -->
    <div id="sidebar-main" style="display: flex; flex-direction: column; height: 100%; max-height: 100%; overflow: hidden; flex: 1; min-height: 0;">
        <div class="sidebar-header" style="flex-shrink: 0;">
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
        
        <!-- Thân Sidebar có thanh cuộn mượt mà (Scrollable Body) -->
        <div class="sidebar-body" style="flex: 1; overflow-y: auto; overflow-x: hidden; min-height: 0; display: flex; flex-direction: column;">
            <div class="sidebar-menu">
    
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
        </div>

        <!-- Phần thông tin tài khoản luôn ghim cố định ở chân Sidebar -->
        <div class="sidebar-footer" id="sidebarUserFooter" style="flex-shrink: 0; margin-top: auto; padding: 12px 16px 22px 16px; border-top: 1px solid #27272a; background: #18181b; display: flex; flex-direction: column; gap: 8px; z-index: 10; box-shadow: 0 -4px 14px rgba(0,0,0,0.4);">
            <?php if (isset($_SESSION['user_id'])): 
                $userRepScore = 10;
                if (isset($conn) && isset($_SESSION['user_id'])) {
                    $qUser = $conn->query("SELECT reputation_score, role FROM users WHERE id = " . (int)$_SESSION['user_id']);
                    if ($qUser && $rowU = $qUser->fetch_assoc()) {
                        $userRepScore = (int)($rowU['reputation_score'] ?? 10);
                    }
                }
            ?>
                <div style="background: rgba(39, 39, 42, 0.75); border: 1px solid #3f3f46; border-radius: 12px; padding: 10px 12px;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="ph-bold ph-user-circle" style="font-size: 1.4rem; color: #38bdf8;"></i>
                            <strong style="color: white; font-size: 0.95rem;"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></strong>
                        </div>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <span class="badge-admin" style="background:#fee2e2; color:#dc2626; padding:2px 8px; border-radius:6px; font-size:0.75rem; font-weight:700;">ADMIN</span>
                        <?php else: ?>
                            <span class="badge-trusted" style="color:#fbbf24; font-size:0.8rem; font-weight:600;">⭐ Thành viên</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 0.8rem; color: #94a3b8; margin-top: 6px; display: flex; justify-content: space-between;">
                        <span>Điểm uy tín (Trust Score):</span>
                        <strong style="color: #fbbf24;"><?= $userRepScore ?> ⭐</strong>
                    </div>
                </div>
                
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="index.php?route=admin_dashboard" style="color: #38bdf8; font-size: 0.85rem; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 6px; padding: 4px 0;">
                        <i class="ph-bold ph-shield-check" style="color: #38bdf8;"></i> Bảng Quản Trị Viên (Admin) &rarr;
                    </a>
                <?php endif; ?>
                
                <!-- Nút Đăng xuất an toàn: Thoát triệt để phiên và chuyển về màn hình chính chưa đăng nhập -->
                <a href="javascript:void(0)" onclick="executeAppLogout(event)" style="color: #ef4444; font-weight: 600; font-size: 0.95rem; text-decoration: none; cursor: pointer; display: flex; align-items: center; gap: 8px; padding: 4px 0;">
                    <i class="ph-bold ph-sign-out"></i> Đăng xuất
                </a>
            <?php else: ?>
                <a href="#" onclick="openSubPanel('Đăng nhập tài khoản', 'login')" style="color: #60a5fa; font-weight: 600; font-size: 1rem; text-decoration: none; display: flex; align-items: center; gap: 8px; padding: 6px 0;">
                    <i class="ph-bold ph-user-circle" style="font-size: 1.35rem;"></i> Đăng nhập / Đăng ký
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. Khung Sub-Panel Trượt vào (Hiển thị chi tiết theo từng tính năng) -->
    <div id="sidebar-sub" style="display: none; flex-direction: column; height: 100%; overflow: hidden;">
        <div class="sidebar-header" style="justify-content: flex-start; gap: 12px; flex-shrink: 0;">
            <button type="button" class="btn-close-sidebar" onclick="closeSubPanel()" title="Quay lại">
                <i class="ph ph-caret-left"></i>
            </button>
            <h3 id="sub-title" style="color: white; font-size: 1.05rem; flex: 1;">Tiêu đề</h3>
            <button class="btn-close-sidebar" onclick="toggleSidebar()" title="Đóng sidebar">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="sidebar-sub-content" id="sub-content" style="flex: 1; overflow-y: auto; padding: 16px;">
            <!-- Nội dung động được render bằng JavaScript -->
        </div>
    </div>

    <!-- 3. Khung Báo Cáo Sự Cố Nhanh (3 Chạm Sáng Rõ & Đầy Đủ Tương Phản) -->
    <div id="sidebar-report" style="display: none; flex-direction: column; height: 100%; overflow: hidden;">
        <div class="sidebar-header" style="justify-content: flex-start; gap: 12px; flex-shrink: 0;">
            <button type="button" class="btn-close-sidebar" onclick="closeReportPanel()" title="Quay lại">
                <i class="ph ph-caret-left"></i>
            </button>
            <h3 style="color: white; font-size: 1.05rem; flex: 1; font-weight: 700;">Báo Cáo Sự Cố</h3>
            <button type="button" class="btn-close-sidebar" onclick="closeReportPanel()" title="Đóng sidebar">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="sidebar-sub-content" style="flex: 1; padding: 16px; overflow-y: auto;">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div style="text-align: center; padding: 40px 10px; color: #a1a1aa;">
                    <i class="ph-bold ph-lock-key" style="font-size: 2.8rem; color: #f59e0b; margin-bottom: 12px; display: block;"></i>
                    <p style="color: white; font-weight: 600; font-size: 1.05rem; margin-bottom: 8px;">Yêu cầu đăng nhập</p>
                    <p style="font-size: 0.88rem; margin-bottom: 20px; line-height: 1.5; color: #d4d4d8;">Vui lòng đăng nhập tài khoản để gửi thông tin báo cáo sự cố và tích lũy điểm uy tín!</p>
                    <button type="button" onclick="openSubPanel('Đăng nhập tài khoản', 'login')" class="btn-primary-block" style="background: #2563eb; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 700; width: 100%; cursor: pointer;">
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
                        <label style="display: flex; align-items: center; gap: 6px; color: #f1f5f9; font-size: 0.88rem; font-weight: 700; margin-bottom: 8px;">
                            <span style="background: #2563eb; color: white; width: 22px; height: 22px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem;">1</span> 
                            Loại sự cố đô thị:
                        </label>
                        <div class="incident-type-selector" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
                            <label class="incident-radio-card active" onclick="selectIncidentType('ngap_nuoc', this)" style="background: rgba(56, 189, 248, 0.2); border: 2px solid #38bdf8; border-radius: 10px; padding: 10px 4px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; cursor: pointer; color: #ffffff !important; text-align: center; font-size: 0.82rem; font-weight: 600; transition: all 0.2s;">
                                <input type="radio" name="type" value="ngap_nuoc" checked style="display:none;">
                                <i class="ph-bold ph-waves" style="color: #38bdf8; font-size: 1.5rem;"></i>
                                <span style="color: #ffffff !important; font-weight: 700;">Ngập lụt</span>
                            </label>
                            <label class="incident-radio-card" onclick="selectIncidentType('ket_xe', this)" style="background: #27272a; border: 1.5px solid #3f3f46; border-radius: 10px; padding: 10px 4px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; cursor: pointer; color: #ffffff !important; text-align: center; font-size: 0.82rem; font-weight: 600; transition: all 0.2s;">
                                <input type="radio" name="type" value="ket_xe" style="display:none;">
                                <i class="ph-bold ph-car" style="color: #fb923c; font-size: 1.5rem;"></i>
                                <span style="color: #ffffff !important; font-weight: 700;">Kẹt xe</span>
                            </label>
    
                        </div>
                    </div>

                    <!-- BƯỚC 2: Mốc ước lượng trực quan thân thuộc -->
                    <div id="field-flood-depth">
                        <label style="display: flex; align-items: center; gap: 6px; color: #f1f5f9; font-size: 0.88rem; font-weight: 700; margin-bottom: 8px;">
                            <span style="background: #2563eb; color: white; width: 22px; height: 22px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem;">2</span> 
                            Ước lượng mức nước trực quan:
                        </label>
                        <div class="visual-level-selector" style="display: flex; flex-direction: column; gap: 8px;">
                            <label class="visual-level-card active" onclick="selectVisualDepth('mat_ca', this)" style="background: rgba(56, 189, 248, 0.2); border: 2px solid #38bdf8; border-radius: 10px; padding: 10px 12px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; color: #ffffff !important; transition: all 0.2s;">
                                <input type="radio" name="water_level_visual" value="mat_ca" checked style="display:none;">
                                <div>
                                    <strong style="color: #ffffff !important; font-size: 0.92rem; display: block;">Ngang mắt cá chân</strong>
                                    <span style="color: #cbd5e1; font-size: 0.78rem;">Xe máy đi lại bình thường</span>
                                </div>
                                <span style="background: rgba(245, 158, 11, 0.25); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.5); font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 12px;">~10 - 15cm (Ngập nhẹ)</span>
                            </label>
                            <label class="visual-level-card" onclick="selectVisualDepth('banh_xe', this)" style="background: #27272a; border: 1.5px solid #3f3f46; border-radius: 10px; padding: 10px 12px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; color: #ffffff !important; transition: all 0.2s;">
                                <input type="radio" name="water_level_visual" value="banh_xe" style="display:none;">
                                <div>
                                    <strong style="color: #ffffff !important; font-size: 0.92rem; display: block;">Nửa bánh xe máy</strong>
                                    <span style="color: #cbd5e1; font-size: 0.78rem;">Khuyên xe ga không nên đi qua</span>
                                </div>
                                <span style="background: rgba(249, 115, 22, 0.25); color: #fb923c; border: 1px solid rgba(249, 115, 22, 0.5); font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 12px;">~20 - 25cm (Nguy hiểm)</span>
                            </label>
                            <label class="visual-level-card" onclick="selectVisualDepth('yen_xe', this)" style="background: #27272a; border: 1.5px solid #3f3f46; border-radius: 10px; padding: 10px 12px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; color: #ffffff !important; transition: all 0.2s;">
                                <input type="radio" name="water_level_visual" value="yen_xe" style="display:none;">
                                <div>
                                    <strong style="color: #ffffff !important; font-size: 0.92rem; display: block;">Ngập ống xả / Yên xe</strong>
                                    <span style="color: #cbd5e1; font-size: 0.78rem;">Đóng đường hoàn toàn - Xe chết máy</span>
                                </div>
                                <span style="background: rgba(239, 68, 68, 0.25); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.5); font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 12px;">&gt;40 - 50cm (Chết máy)</span>
                            </label>
                        </div>
                    </div>

                    <div id="field-traffic-speed" style="display: none;">
                        <label style="display: flex; align-items: center; gap: 6px; color: #f1f5f9; font-size: 0.88rem; font-weight: 700; margin-bottom: 8px;">
                            <span style="background: #2563eb; color: white; width: 22px; height: 22px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem;">2</span> 
                            Tình trạng lưu thông:
                        </label>
                        <div class="visual-level-selector" style="display: flex; flex-direction: column; gap: 8px;">
                            <label class="visual-level-card active" onclick="selectTrafficSpeed('cham', this)" style="background: rgba(56, 189, 248, 0.2); border: 2px solid #38bdf8; border-radius: 10px; padding: 10px 12px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; color: #ffffff !important; transition: all 0.2s;">
                                <input type="radio" name="traffic_speed_visual" value="cham" checked style="display:none;">
                                <div>
                                    <strong style="color: #ffffff !important; font-size: 0.92rem; display: block;">Di chuyển chậm</strong>
                                    <span style="color: #cbd5e1; font-size: 0.78rem;">Xe đông nối đuôi</span>
                                </div>
                                <span style="background: rgba(245, 158, 11, 0.25); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.5); font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 12px;">15 - 25 km/h</span>
                            </label>
                            <label class="visual-level-card" onclick="selectTrafficSpeed('un_u', this)" style="background: #27272a; border: 1.5px solid #3f3f46; border-radius: 10px; padding: 10px 12px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; color: #ffffff !important; transition: all 0.2s;">
                                <input type="radio" name="traffic_speed_visual" value="un_u" style="display:none;">
                                <div>
                                    <strong style="color: #ffffff !important; font-size: 0.92rem; display: block;">Ùn ứ nặng kéo dài</strong>
                                    <span style="color: #cbd5e1; font-size: 0.78rem;">Di chuyển từng mét một</span>
                                </div>
                                <span style="background: rgba(249, 115, 22, 0.25); color: #fb923c; border: 1px solid rgba(249, 115, 22, 0.5); font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 12px;">5 - 15 km/h</span>
                            </label>
                            <label class="visual-level-card" onclick="selectTrafficSpeed('tac_nghen', this)" style="background: #27272a; border: 1.5px solid #3f3f46; border-radius: 10px; padding: 10px 12px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; color: #ffffff !important; transition: all 0.2s;">
                                <input type="radio" name="traffic_speed_visual" value="tac_nghen" style="display:none;">
                                <div>
                                    <strong style="color: #ffffff !important; font-size: 0.92rem; display: block;">Tắc nghẽn bất động</strong>
                                    <span style="color: #cbd5e1; font-size: 0.78rem;">Chôn chân tại chỗ</span>
                                </div>
                                <span style="background: rgba(239, 68, 68, 0.25); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.5); font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 12px;">&lt;5 km/h (Chôn chân)</span>
                            </label>
                        </div>
                    </div>

                    <input type="hidden" name="severity" id="computed_severity" value="vua">

                    <!-- BƯỚC 3: Vị trí & Ảnh hiện trường -->
                    <div>
                        <label style="display: flex; align-items: center; gap: 6px; color: #f1f5f9; font-size: 0.88rem; font-weight: 700; margin-bottom: 8px;">
                            <span style="background: #2563eb; color: white; width: 22px; height: 22px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem;">3</span> 
                            Vị trí điểm sự cố:
                        </label>
                        <div style="background: rgba(37, 99, 235, 0.15); border: 1px dashed #3b82f6; border-radius: 8px; padding: 8px 10px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                            <span style="color: #93c5fd; font-size: 0.8rem; line-height: 1.3;">
                                <i class="ph-bold ph-hand-tap"></i> Chạm lên bản đồ hoặc bấm GPS
                            </span>
                            <button type="button" onclick="locateUser(true, true)" class="btn-route-action" style="width: auto; padding: 5px 12px; font-size: 0.8rem; font-weight: 700; background: #2563eb; color: white; border: none; border-radius: 6px; display: flex; align-items: center; gap: 4px; cursor: pointer;">
                                <i class="ph-bold ph-crosshair"></i> Vị trí của tôi
                            </button>
                        </div>
                        <input type="text" id="report_address" name="address_text" placeholder="Chạm lên bản đồ hoặc nhập tên đường..." style="width: 100%; background: #27272a; border: 1px solid #3f3f46; border-radius: 8px; padding: 10px 12px; color: #ffffff !important; font-size: 0.88rem; outline: none;" required>
                    </div>

                    <div>
                        <label style="color: #cbd5e1; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; display: block;">Ảnh hiện trường thực tế (tuỳ chọn):</label>
                        <input type="file" id="report_image_input" name="image" accept="image/*" style="width: 100%; background: #27272a; border: 1px solid #3f3f46; border-radius: 8px; padding: 8px; color: #cbd5e1; font-size: 0.85rem;">
                    </div>

                    <div>
                        <label style="color: #cbd5e1; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; display: block;">Mô tả thêm (tuỳ chọn):</label>
                        <textarea name="description" placeholder="Ví dụ: Nước tràn vào cửa hàng, nhiều xe chết máy tại giao lộ..." style="width: 100%; height: 75px; resize: none; background: #27272a; border: 1px solid #3f3f46; border-radius: 8px; padding: 10px 12px; color: #ffffff !important; font-size: 0.88rem; outline: none;"></textarea>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 6px;">
                        <button type="button" onclick="closeReportPanel()" style="flex: 1; background: #27272a; border: 1px solid #3f3f46; padding: 12px; color: #ffffff !important; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.9rem; text-align: center;">
                            Huỷ
                        </button>
                        <button type="submit" style="flex: 2; background: linear-gradient(135deg, #2563eb, #1d4ed8); border: none; padding: 12px; color: #ffffff !important; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 14px rgba(37,99,235,0.4);">
                            <i class="ph-bold ph-paper-plane-right"></i> Phát Cảnh Báo
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Điều khiển chọn loại sự cố trực quan
function selectIncidentType(type, cardEl) {
    document.querySelectorAll('.incident-type-selector .incident-radio-card').forEach(c => {
        c.classList.remove('active');
        c.style.background = '#27272a';
        c.style.border = '1.5px solid #3f3f46';
        const inp = c.querySelector('input');
        if (inp) inp.checked = false;
    });

    if (cardEl) {
        cardEl.classList.add('active');
        cardEl.style.background = 'rgba(56, 189, 248, 0.2)';
        cardEl.style.border = '2px solid #38bdf8';
        const inp = cardEl.querySelector('input');
        if (inp) inp.checked = true;
    }

    const floodDepth = document.getElementById('field-flood-depth');
    const trafficSpeed = document.getElementById('field-traffic-speed');

    if (type === 'ngap_nuoc') {
        if (floodDepth) floodDepth.style.display = 'block';
        if (trafficSpeed) trafficSpeed.style.display = 'none';
    } else if (type === 'ket_xe') {
        if (floodDepth) floodDepth.style.display = 'none';
        if (trafficSpeed) trafficSpeed.style.display = 'block';
    } else {
        if (floodDepth) floodDepth.style.display = 'none';
        if (trafficSpeed) trafficSpeed.style.display = 'none';
    }
}

// Điều khiển chọn mức nước
function selectVisualDepth(val, cardEl) {
    document.querySelectorAll('#field-flood-depth .visual-level-card').forEach(c => {
        c.classList.remove('active');
        c.style.background = '#27272a';
        c.style.border = '1.5px solid #3f3f46';
        const inp = c.querySelector('input');
        if (inp) inp.checked = false;
    });
    if (cardEl) {
        cardEl.classList.add('active');
        cardEl.style.background = 'rgba(56, 189, 248, 0.2)';
        cardEl.style.border = '2px solid #38bdf8';
        const inp = cardEl.querySelector('input');
        if (inp) inp.checked = true;
    }
    const sevInput = document.getElementById('computed_severity');
    if (sevInput) {
        sevInput.value = (val === 'mat_ca') ? 'nhe' : (val === 'yen_xe' ? 'nghiem_trong' : 'vua');
    }
}

// Điều khiển chọn tốc độ giao thông
function selectTrafficSpeed(val, cardEl) {
    document.querySelectorAll('#field-traffic-speed .visual-level-card').forEach(c => {
        c.classList.remove('active');
        c.style.background = '#27272a';
        c.style.border = '1.5px solid #3f3f46';
        const inp = c.querySelector('input');
        if (inp) inp.checked = false;
    });
    if (cardEl) {
        cardEl.classList.add('active');
        cardEl.style.background = 'rgba(56, 189, 248, 0.2)';
        cardEl.style.border = '2px solid #38bdf8';
        const inp = cardEl.querySelector('input');
        if (inp) inp.checked = true;
    }
    const sevInput = document.getElementById('computed_severity');
    if (sevInput) {
        sevInput.value = (val === 'cham') ? 'nhe' : (val === 'tac_nghen' ? 'nghiem_trong' : 'vua');
    }
}

// Đăng xuất sạch sẽ mọi dữ liệu phiên cả PHP lẫn Firebase Auth client
async function executeAppLogout(e) {
    if (e) e.preventDefault();
    try {
        localStorage.clear();
        sessionStorage.clear();
    } catch(err) {}

    try {
        if (typeof window.logoutFirebaseGoogle === 'function') {
            await window.logoutFirebaseGoogle();
        }
    } catch(err) {}

    try {
        await fetch('api/logout.php', { method: 'POST', credentials: 'same-origin' });
    } catch(err) {}

    // Điều hướng sạch về trang index màn hình chính ở trạng thái chưa đăng nhập
    window.location.replace('index.php?route=logout&logged_out=1&_t=' + Date.now());
}
</script>