<!-- KHUNG THANH ĐIỀU HƯỚNG BÊN TRÁI -->
<div class="sidebar" id="sidebar">
    <!-- 1. Menu Chính của Sidebar -->
    <div id="sidebar-main" style="display: flex; flex-direction: column; height: 100%;">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <div class="logo-icon">
                    <i class="ph-bold ph-waves"></i>
                </div>
                <div class="brand-text">
                    <h2>Cảnh Báo</h2>
                    <p>Bản đồ giao thông & ngập lụt</p>
                </div>
            </div>
            <button class="btn-close-sidebar" onclick="toggleSidebar()">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="sidebar-menu">
            <a href="#" class="menu-item" onclick="openSubPanel('Chú giải biểu tượng', 'legend')">
                <i class="ph ph-list-dashes"></i>
                <span>Chú giải</span>
            </a>
            <a href="#" class="menu-item" onclick="openSubPanel('Tin mới nhất', 'news')">
                <i class="ph ph-article"></i>
                <span>Tin mới</span>
            </a>
            <a href="#" class="menu-item" onclick="openSubPanel('Theo dõi khu vực', 'follow')">
                <i class="ph-bold ph-radar" style="color: #38bdf8;"></i>
                <span>Radar GPS</span>
                <span style="background: #ef4444; color: white; font-size: 0.65rem; padding: 1px 6px; border-radius: 10px; margin-left: auto; font-weight: 700;">Mới</span>
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
            <a href="#" class="menu-item" onclick="openSubPanel('Báo cáo của tôi', 'my_reports')">
                <i class="ph-bold ph-clock-counter-clockwise" style="color: #60a5fa;"></i>
                <span>Báo cáo của tôi</span>
            </a>
            <a href="#" class="menu-item" onclick="openSubPanel('Hồ sơ & Đổi mật khẩu', 'profile')">
                <i class="ph-bold ph-user-gear" style="color: #34d399;"></i>
                <span>Hồ sơ & Bảo mật</span>
            </a>
            <?php endif; ?>
        </div>

        <div class="sidebar-divider"></div>

        <div class="sidebar-links">
            <a href="#" onclick="openSubPanel('Góp ý xây dựng', 'feedback')">
                <i class="ph ph-chat-teardrop-dots"></i> Góp ý
            </a>
            <a href="#" onclick="openSubPanel('Hướng dẫn sử dụng', 'guide')">
                <i class="ph ph-question"></i> Hướng dẫn
            </a>
            <a href="#" onclick="openSubPanel('Nhật ký cập nhật', 'changelog')">
                <i class="ph ph-sparkle"></i> Cập nhật mới
            </a>
        </div>
        <div class="sidebar-divider"></div>

        <!-- Phần thông tin tài khoản ở chân Sidebar -->
        <div class="sidebar-footer" style="display:flex; flex-direction:column; gap:12px;">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div style="color: #94a3b8; font-size: 0.9rem; padding: 4px 0; border-bottom: 1px solid #3f3f46; padding-bottom: 8px;">
                    <i class="ph-bold ph-user" style="margin-right: 6px; color: #60a5fa;"></i>
                    Tài khoản: <strong style="color: white;"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></strong>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <span style="background: #3b82f6; color: white; font-size: 0.75rem; padding: 2px 6px; border-radius: 4px; margin-left: 6px;">Admin</span>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="index.php?route=admin_dashboard" style="color: #60a5fa; font-size: 0.85rem; text-decoration: none; display: flex; align-items: center; gap: 6px; margin-top: 4px;">
                        <i class="ph-bold ph-shield-check" style="color: #3b82f6;"></i> Bảng Quản Trị Viên (Admin) &rarr;
                    </a>
                <?php endif; ?>
                
                <!-- Nút Đăng xuất trỏ thẳng qua Router index.php?route=logout để chuyển về trang login -->
                <a href="index.php?route=logout" onclick="localStorage.removeItem('currentUser');" style="color: #ef4444; font-weight: 600; font-size: 0.95rem; text-decoration: none; cursor: pointer;">
                    <i class="ph-bold ph-sign-out" style="margin-right: 8px;"></i> Đăng xuất
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

    <!-- 3. Khung Báo Cáo Sự Cố Bằng PHP Form Thuần (Gửi qua POST) -->
    <div id="sidebar-report" style="display: none; flex-direction: column; height: 100%;">
        <div class="sidebar-header" style="justify-content: flex-start; gap: 12px;">
            <button type="button" class="btn-close-sidebar" onclick="closeReportPanel()" title="Quay lại">
                <i class="ph ph-caret-left"></i>
            </button>
            <h3 style="color: white; font-size: 1.05rem; flex: 1;">Báo sự cố tại điểm này</h3>
            <button type="button" class="btn-close-sidebar" onclick="closeReportPanel()" title="Đóng sidebar">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="sidebar-sub-content" style="padding: 16px; overflow-y: auto;">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div style="text-align: center; padding: 40px 10px; color: #a1a1aa;">
                    <i class="ph-bold ph-lock-key" style="font-size: 2.8rem; color: #f59e0b; margin-bottom: 12px; display: block;"></i>
                    <p style="color: white; font-weight: 600; font-size: 1.05rem; margin-bottom: 8px;">Yêu cầu đăng nhập</p>
                    <p style="font-size: 0.88rem; margin-bottom: 20px; line-height: 1.5;">Vui lòng đăng nhập tài khoản để gửi thông tin báo cáo sự cố!</p>
                    <button type="button" onclick="openSubPanel('Đăng nhập tài khoản', 'login')" style="background: #2563eb; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; cursor: pointer;">
                        Đăng nhập ngay
                    </button>
                </div>
            <?php else: ?>
                <form id="reportAddForm" action="index.php?route=report_add" method="POST" enctype="multipart/form-data" onsubmit="handleReportFormSubmit(event)" style="display: flex; flex-direction: column; gap: 14px;">
                    <!-- Tọa độ ẩn được JavaScript tự điền khi click lên bản đồ -->
                    <input type="hidden" id="report_lat" name="latitude" required>
                    <input type="hidden" id="report_lng" name="longitude" required>

                    <div>
                        <label style="color: #a1a1aa; font-size: 0.85rem; margin-bottom: 6px; display: block; font-weight: 500;">Loại sự cố:</label>
                        <select name="type" class="form-input" style="color: white; background: #27272a; width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #3f3f46;" required>
                            <option value="ngap_nuoc">🌊 Điểm ngập nước</option>
                            <option value="ket_xe">🚦 Kẹt xe / Tai nạn giao thông</option>
                        </select>
                    </div>

                    <div>
                        <label style="color: #a1a1aa; font-size: 0.85rem; margin-bottom: 6px; display: block; font-weight: 500;">Mức độ nghiêm trọng:</label>
                        <select name="severity" class="form-input" style="color: white; background: #27272a; width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #3f3f46;" required>
                            <option value="nhe">Nhẹ </option>
                            <option value="vua" selected>Vừa </option>
                            <option value="nghiem_trong">Nghiêm trọng</option>
                        </select>
                    </div>

                    <div>
                        <label style="color: #a1a1aa; font-size: 0.85rem; margin-bottom: 6px; display: block; font-weight: 500;">Vị trí sự cố:</label>
                        <!-- Box hướng dẫn chọn điểm trên bản đồ -->
                        <div style="background: rgba(37, 99, 235, 0.15); border: 1px dashed #3b82f6; border-radius: 8px; padding: 8px 10px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                            <span style="color: #93c5fd; font-size: 0.8rem; line-height: 1.3;">
                                <i class="ph-bold ph-hand-tap"></i> Click vào bản đồ để chọn vị trí
                            </span>
                            <button type="button" onclick="locateUser(true, true)" style="background: #2563eb; color: white; border: none; padding: 5px 10px; border-radius: 6px; font-size: 0.78rem; font-weight: 600; cursor: pointer; white-space: nowrap; display: flex; align-items: center; gap: 4px;">
                                <i class="ph-bold ph-crosshair"></i> GPS tôi
                            </button>
                        </div>
                        <input type="text" id="report_address" name="address_text" placeholder="Chạm lên bản đồ hoặc nhập tên đường..." class="form-input" style="width: 100%;" required>
                    </div>

                    <div>
                        <label style="color: #a1a1aa; font-size: 0.85rem; margin-bottom: 6px; display: block; font-weight: 500;">Hình ảnh thực tế (tuỳ chọn):</label>
                        <input type="file" name="image" accept="image/*" class="form-input" style="width: 100%; color: #94a3b8; padding: 8px;">
                    </div>

                    <div>
                        <label style="color: #a1a1aa; font-size: 0.85rem; margin-bottom: 6px; display: block; font-weight: 500;">Mô tả chi tiết:</label>
                        <textarea name="description" placeholder="Ví dụ: Nước ngập sâu, nhiều xe máy chết máy đoạn qua ngã tư..." class="form-input" style="width: 100%; height: 80px; resize: none;"></textarea>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <button type="button" onclick="closeReportPanel()" style="flex: 1; background: #27272a; border: 1px solid #3f3f46; padding: 12px; color: white; border-radius: 8px; font-weight: 600; cursor: pointer;">
                            Huỷ
                        </button>
                        <button type="submit" style="flex: 2; background: #2563eb; border: none; padding: 12px; color: white; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 0.95rem;">
                            <i class="ph-bold ph-paper-plane-right"></i> Gửi Báo Cáo
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
