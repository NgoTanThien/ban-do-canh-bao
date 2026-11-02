<?php
// File: views/admin/dashboard.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Chặn truy cập nếu không phải Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php?route=admin_login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng Quản Trị Hệ Thống - UrbanGuard</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css" />
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css" />
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/fill/style.css" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #f8fafc; color: #1e293b; display: flex; min-height: 100vh; }
        .admin-sidebar { width: 270px; background: #0f172a; color: #fff; display: flex; flex-direction: column; padding: 20px 16px; flex-shrink: 0; }
        .admin-brand { font-size: 1.15rem; font-weight: 800; color: #38bdf8; margin-bottom: 24px; padding: 0 8px; display: flex; align-items: center; gap: 10px; }
        .admin-brand i { font-size: 1.5rem; }
        .admin-menu { display: flex; flex-direction: column; gap: 6px; flex: 1; }
        .admin-menu a { color: #94a3b8; text-decoration: none; padding: 12px 14px; border-radius: 10px; font-weight: 600; font-size: 0.92rem; display: flex; align-items: center; gap: 10px; transition: all 0.2s; cursor: pointer; }
        .admin-menu a:hover, .admin-menu a.active { background: #1e293b; color: #38bdf8; }
        .admin-menu a.active { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border-left: 3px solid #38bdf8; }
        .admin-main { flex: 1; display: flex; flex-direction: column; min-width: 0; overflow-x: hidden; }
        .admin-header { background: #fff; padding: 16px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 10; }
        .admin-content { padding: 24px 30px; overflow-y: auto; flex: 1; }
        
        /* Thẻ thống kê */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; border-left: 4px solid #3b82f6; display: flex; align-items: center; justify-content: space-between; }
        .stat-card.green { border-left-color: #10b981; }
        .stat-card.orange { border-left-color: #f97316; }
        .stat-card.red { border-left-color: #ef4444; }
        .stat-info h4 { color: #64748b; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; }
        .stat-info .number { font-size: 1.85rem; font-weight: 800; color: #0f172a; }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .stat-card .stat-icon { background: #eff6ff; color: #3b82f6; }
        .stat-card.green .stat-icon { background: #ecfdf5; color: #10b981; }
        .stat-card.orange .stat-icon { background: #fff7ed; color: #f97316; }
        .stat-card.red .stat-icon { background: #fef2f2; color: #ef4444; }

        /* Bảng dữ liệu */
        .card-table { background: #fff; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; padding: 22px; margin-bottom: 24px; }
        .card-table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 12px; }
        .card-table h3 { font-size: 1.15rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        
        /* Thanh tìm kiếm & bộ lọc */
        .table-toolbar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; width: 100%; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9; }
        .search-box { display: flex; align-items: center; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 6px 14px; flex: 1; min-width: 260px; }
        .search-box input { border: none; background: transparent; outline: none; font-size: 0.9rem; padding-left: 8px; width: 100%; color: #0f172a; }
        .filter-select { padding: 8px 12px; border-radius: 10px; border: 1px solid #cbd5e1; background: #f8fafc; font-size: 0.88rem; font-weight: 600; color: #475569; outline: none; cursor: pointer; }

        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; white-space: nowrap; }
        th, td { padding: 13px 16px; text-align: left; border-bottom: 1px solid #f1f5f9; font-size: 0.88rem; }
        th { background: #f8fafc; font-weight: 700; color: #475569; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.5px; }
        tbody tr:hover { background: #f8fafc; }
        
        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }
        .badge-admin { background: #fee2e2; color: #dc2626; }
        .badge-user { background: #e0f2fe; color: #0284c7; }
        .badge-active { background: #dcfce7; color: #16a34a; }
        .badge-blocked { background: #fef2f2; color: #dc2626; }
        .badge-flood { background: #e0f2fe; color: #0284c7; }
        .badge-traffic { background: #ffedd5; color: #ea580c; }
        
        .btn-action { padding: 5px 10px; border-radius: 8px; border: none; font-size: 0.8rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: opacity 0.2s; }
        .btn-action:hover { opacity: 0.85; }
        .btn-block { background: #fee2e2; color: #dc2626; }
        .btn-unblock { background: #dcfce7; color: #16a34a; }
        .btn-role { background: #eff6ff; color: #2563eb; }
        .btn-score { background: #fef3c7; color: #d97706; }
        .btn-delete { background: #f1f5f9; color: #64748b; }
        .btn-delete:hover { background: #dc2626; color: #fff; }
        .btn-export { background: #059669; color: #fff; padding: 8px 16px; font-size: 0.88rem; text-decoration: none; border-radius: 8px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }

        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>

    <!-- Sidebar Quản trị -->
    <div class="admin-sidebar">
        <div class="admin-brand">
            <i class="ph-bold ph-shield-check"></i>
            <div>
                <span>UrbanGuard</span>
                <span style="font-size:0.75rem; color:#94a3b8; display:block; font-weight:500;">Admin Management</span>
            </div>
        </div>
        <div class="admin-menu">
            <a id="tab-btn-users" class="active" onclick="switchTab('users')">
                <i class="ph-bold ph-users"></i> Tài khoản khách hàng
            </a>
            <a id="tab-btn-reports" onclick="switchTab('reports')">
                <i class="ph-bold ph-warning-circle"></i> Quản lý sự cố & Xuất Excel
            </a>
            <a id="tab-btn-sos" onclick="switchTab('sos')">
                <i class="ph-bold ph-first-aid"></i> Điều phối Cứu hộ SOS
            </a>
            <div style="height: 1px; background: #334155; margin: 12px 0;"></div>
            <a href="index.php?route=home">
                <i class="ph-bold ph-map-pin"></i> Xem Bản đồ giao thông
            </a>
            <!-- Đăng xuất sạch sẽ mọi phiên -->
            <a href="javascript:void(0)" onclick="executeAppLogout(event)" style="color: #f87171;">
                <i class="ph-bold ph-sign-out"></i> Đăng xuất hệ thống
            </a>
        </div>
    </div>

    <!-- Nội dung chính -->
    <div class="admin-main">
        <div class="admin-header">
            <div>
                <h2 style="font-size: 1.35rem; font-weight: 800; color: #0f172a;">Bảng Quản Trị Hệ Thống</h2>
                <p style="font-size: 0.85rem; color: #64748b;">Quản lý dữ liệu tài khoản thành viên, điểm sự cố giao thông và cứu hộ SOS</p>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 0.88rem; color: #64748b;">Quản trị viên: <strong style="color: #0f172a;"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong></span>
                <button type="button" onclick="executeAppLogout(event)" style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; font-size: 0.82rem; font-weight: 700; color: #dc2626; cursor: pointer;">
                    <i class="ph-bold ph-sign-out"></i> Đăng xuất
                </button>
            </div>
        </div>

        <div class="admin-content">
            <!-- Thống kê nhanh 4 thẻ -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <h4>Tổng tài khoản khách hàng</h4>
                        <div class="number" id="totalUsersCount">--</div>
                    </div>
                    <div class="stat-icon"><i class="ph-bold ph-users"></i></div>
                </div>
                <div class="stat-card green">
                    <div class="stat-info">
                        <h4>Khách hàng đang hoạt động</h4>
                        <div class="number" id="activeUsersCount">--</div>
                    </div>
                    <div class="stat-icon"><i class="ph-bold ph-user-check"></i></div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-info">
                        <h4>Sự cố đang diễn ra</h4>
                        <div class="number" id="activeReportsCount">--</div>
                    </div>
                    <div class="stat-icon"><i class="ph-bold ph-warning"></i></div>
                </div>
                <div class="stat-card red">
                    <div class="stat-info">
                        <h4>Yêu cầu Cứu hộ SOS</h4>
                        <div class="number" id="sosRequestsCount">--</div>
                    </div>
                    <div class="stat-icon"><i class="ph-bold ph-first-aid"></i></div>
                </div>
            </div>

            <!-- TAB 1: QUẢN LÝ TÀI KHOẢN KHÁCH HÀNG -->
            <div id="tab-users" class="tab-content active">
                <div class="card-table">
                    <div class="card-table-header">
                        <h3><i class="ph-bold ph-users" style="color: #2563eb;"></i> Danh Sách Tài Khoản Khách Hàng (Users)</h3>
                        <button type="button" onclick="loadUsers()" style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 6px 14px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer;">
                            <i class="ph-bold ph-arrows-clockwise"></i> Làm mới
                        </button>
                    </div>

                    <!-- Thanh tìm kiếm & Lọc dữ liệu khách hàng -->
                    <div class="table-toolbar">
                        <div class="search-box">
                            <i class="ph-bold ph-magnifying-glass" style="color: #94a3b8;"></i>
                            <input type="text" id="userSearchInput" placeholder="Tìm theo tên đăng nhập, họ tên, email, số điện thoại..." oninput="filterUsersTable()">
                        </div>
                        <select id="userRoleFilter" class="filter-select" onchange="filterUsersTable()">
                            <option value="all">Tất cả vai trò</option>
                            <option value="user">Chỉ khách hàng (User)</option>
                            <option value="admin">Quản trị viên (Admin)</option>
                        </select>
                        <select id="userStatusFilter" class="filter-select" onchange="filterUsersTable()">
                            <option value="all">Tất cả trạng thái</option>
                            <option value="active">Đang hoạt động</option>
                            <option value="blocked">Đã bị khóa</option>
                        </select>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Khách hàng / Thành viên</th>
                                    <th>Liên hệ (Email / SĐT)</th>
                                    <th>Vai trò</th>
                                    <th>Điểm uy tín</th>
                                    <th>Đóng góp</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày tham gia</th>
                                    <th>Thao tác quản trị</th>
                                </tr>
                            </thead>
                            <tbody id="userTableBody">
                                <tr><td colspan="9" style="text-align: center; color: #64748b; padding: 30px;">Đang tải dữ liệu khách hàng từ cơ sở dữ liệu...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: QUẢN LÝ SỰ CỐ & XUẤT EXCEL -->
            <div id="tab-reports" class="tab-content">
                <div class="card-table">
                    <div class="card-table-header">
                        <h3><i class="ph-bold ph-warning-circle" style="color: #f97316;"></i> Danh Sách Sự Cố Điểm Ngập & Kẹt Xe</h3>
                        <a href="api/admin/export_reports.php" class="btn-export" title="Tải file CSV tiếng Việt cho Excel">
                            <i class="ph-bold ph-file-csv"></i> 📥 Xuất file Excel / CSV
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Loại sự cố</th>
                                    <th>Mức độ</th>
                                    <th>Địa chỉ vị trí</th>
                                    <th>Người báo</th>
                                    <th>Bình chọn cộng đồng</th>
                                    <th>Trạng thái</th>
                                    <th>Thời hạn</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="reportsTableBody">
                                <tr><td colspan="9" style="text-align: center; color: #64748b; padding: 30px;">Đang tải danh sách sự cố...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 3: ĐIỀU PHỐI CỨU HỘ SOS -->
            <div id="tab-sos" class="tab-content">
                <div class="card-table">
                    <div class="card-table-header">
                        <h3><i class="ph-bold ph-first-aid" style="color: #ef4444;"></i> Danh Sách Tín Hiệu Cứu Hộ Khẩn Cấp SOS (24/7)</h3>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Người gặp nạn</th>
                                    <th>Số điện thoại</th>
                                    <th>Phương tiện / Tình trạng</th>
                                    <th>Vị trí GPS</th>
                                    <th>Thời gian gửi</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác điều phối</th>
                                </tr>
                            </thead>
                            <tbody id="sosTableBody">
                                <tr><td colspan="8" style="text-align: center; color: #64748b; padding: 30px;">Đang tải yêu cầu cứu hộ...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Nhúng Firebase Auth & API Client -->
    <script type="module" src="public/js/firebase-auth.js?v=<?= time() ?>"></script>
    <script src="public/js/api.js?v=<?= time() ?>"></script>
    <script>
        const currentAdminId = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
        let globalUsersList = [];

        function switchTab(tab) {
            document.querySelectorAll('.admin-menu a').forEach(a => a.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

            const btn = document.getElementById('tab-btn-' + tab);
            const content = document.getElementById('tab-' + tab);
            if (btn) btn.classList.add('active');
            if (content) content.classList.add('active');

            if (tab === 'users') loadUsers();
            if (tab === 'reports') loadReports();
            if (tab === 'sos') loadSos();
        }

        // TẢI DANH SÁCH TÀI KHOẢN KHÁCH HÀNG TỪ DATABASE
        async function loadUsers() {
            const tbody = document.getElementById('userTableBody');
            try {
                const res = await Api.adminUsers();
                const users = (res && res.data) ? res.data : (Array.isArray(res) ? res : []);
                globalUsersList = users;

                document.getElementById('totalUsersCount').innerText = users.length;
                const activeCount = users.filter(u => u.status === 'active').length;
                document.getElementById('activeUsersCount').innerText = activeCount;

                renderUsersTable(users);
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; color:red; padding:20px;">Lỗi tải dữ liệu khách hàng: ${err.message}</td></tr>`;
            }
        }

        // Lọc và tìm kiếm khách hàng
        function filterUsersTable() {
            const query = (document.getElementById('userSearchInput').value || '').toLowerCase().trim();
            const roleFilter = document.getElementById('userRoleFilter').value;
            const statusFilter = document.getElementById('userStatusFilter').value;

            const filtered = globalUsersList.filter(u => {
                const matchQuery = !query || 
                    (u.username && u.username.toLowerCase().includes(query)) ||
                    (u.full_name && u.full_name.toLowerCase().includes(query)) ||
                    (u.email && u.email.toLowerCase().includes(query)) ||
                    (u.phone && u.phone.toLowerCase().includes(query));

                const matchRole = (roleFilter === 'all') || (u.role === roleFilter);
                const matchStatus = (statusFilter === 'all') || (u.status === statusFilter);

                return matchQuery && matchRole && matchStatus;
            });

            renderUsersTable(filtered);
        }

        // Hiển thị danh sách khách hàng lên bảng
        function renderUsersTable(users) {
            const tbody = document.getElementById('userTableBody');
            if (!users || users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; color: #64748b; padding: 24px;">Không tìm thấy tài khoản khách hàng nào phù hợp.</td></tr>';
                return;
            }

            tbody.innerHTML = users.map(u => {
                const isSelf = parseInt(u.id) === currentAdminId;
                const isActive = u.status === 'active';
                const roleBadge = u.role === 'admin' 
                    ? '<span class="badge badge-admin"><i class="ph-bold ph-shield-check"></i> ADMIN</span>' 
                    : '<span class="badge badge-user"><i class="ph-bold ph-user"></i> USER</span>';

                const statusBadge = isActive 
                    ? '<span class="badge badge-active">🟢 Hoạt động</span>' 
                    : '<span class="badge badge-blocked">🔴 Đã khóa</span>';

                let actionBtns = '';
                if (isSelf) {
                    actionBtns = '<span style="color:#64748b; font-style:italic; font-size:0.82rem;">(Tài khoản của bạn)</span>';
                } else {
                    const blockBtn = isActive
                        ? `<button class="btn-action btn-block" onclick="handleUserAction(${u.id}, 'block')" title="Khóa tài khoản này"><i class="ph-bold ph-lock"></i> Khóa</button>`
                        : `<button class="btn-action btn-unblock" onclick="handleUserAction(${u.id}, 'unblock')" title="Mở khóa tài khoản"><i class="ph-bold ph-lock-open"></i> Mở khóa</button>`;

                    const roleBtn = `<button class="btn-action btn-role" onclick="handleUserAction(${u.id}, 'set_role')" title="Đổi quyền User <-> Admin"><i class="ph-bold ph-swap"></i> Đổi quyền</button>`;
                    const scoreBtn = `<button class="btn-action btn-score" onclick="handleUserScore(${u.id})" title="Đặt lại điểm uy tín"><i class="ph-bold ph-star"></i> Điểm</button>`;
                    const delBtn = `<button class="btn-action btn-delete" onclick="handleUserAction(${u.id}, 'delete')" title="Xóa tài khoản"><i class="ph-bold ph-trash"></i> Xóa</button>`;

                    actionBtns = `<div style="display:flex; gap:6px; flex-wrap:wrap;">${blockBtn} ${roleBtn} ${scoreBtn} ${delBtn}</div>`;
                }

                const contactInfo = `
                    <div><strong>${u.email || '<span style=\"color:#94a3b8;\">Chưa có email</span>'}</strong></div>
                    <div style="font-size:0.8rem; color:#64748b;">${u.phone ? ('📞 ' + u.phone) : ''}</div>
                `;

                const contribution = `
                    <span title="Số báo cáo sự cố đã gửi" style="color:#2563eb; font-weight:600;"><i class="ph-bold ph-broadcast"></i> ${u.report_count || 0}</span> | 
                    <span title="Số yêu cầu SOS" style="color:#ef4444; font-weight:600;"><i class="ph-bold ph-first-aid"></i> ${u.sos_count || 0}</span>
                `;

                return `
                    <tr>
                        <td><strong>#${u.id}</strong></td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="width:32px; height:32px; border-radius:50%; background:#e0f2fe; color:#0284c7; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.85rem;">
                                    ${(u.full_name || u.username || 'U').charAt(0).toUpperCase()}
                                </div>
                                <div>
                                    <strong style="color:#0f172a; display:block;">${u.username}</strong>
                                    <span style="font-size:0.8rem; color:#64748b;">${u.full_name || 'Khách vãng lai'}</span>
                                </div>
                            </div>
                        </td>
                        <td>${contactInfo}</td>
                        <td>${roleBadge}</td>
                        <td><strong style="color:#f59e0b;">⭐ ${u.reputation_score || 0}</strong></td>
                        <td>${contribution}</td>
                        <td>${statusBadge}</td>
                        <td><span style="font-size:0.8rem; color:#64748b;">${u.created_at ? u.created_at.substring(0, 10) : ''}</span></td>
                        <td>${actionBtns}</td>
                    </tr>
                `;
            }).join('');
        }

        // Xử lý hành động tài khoản khách hàng
        async function handleUserAction(userId, action) {
            let confirmMsg = '';
            if (action === 'block') confirmMsg = 'Bạn có chắc chắn muốn KHÓA tài khoản khách hàng này?';
            if (action === 'unblock') confirmMsg = 'Mở khóa và khôi phục điểm uy tín cho khách hàng này?';
            if (action === 'set_role') confirmMsg = 'Chuyển đổi vai trò quyền hạn (User <-> Admin) cho tài khoản này?';
            if (action === 'delete') confirmMsg = 'CẢNH BÁO: Bạn có chắc muốn XÓA VĨNH VIỄN tài khoản khách hàng này?';

            if (!confirm(confirmMsg)) return;

            try {
                await Api.adminUserStatus(userId, action);
                await loadUsers();
            } catch (err) {
                alert(err.message || 'Thao tác không thành công!');
            }
        }

        // Điều chỉnh điểm uy tín
        async function handleUserScore(userId) {
            const scoreStr = prompt("Nhập điểm uy tín mới cho khách hàng (0 - 100):", "100");
            if (scoreStr === null) return;
            const score = parseInt(scoreStr);
            if (isNaN(score) || score < 0 || score > 100) {
                alert("Điểm uy tín phải là số từ 0 đến 100!");
                return;
            }

            try {
                await Api.adminUserStatus(userId, 'set_score', { score: score });
                await loadUsers();
            } catch (err) {
                alert(err.message || 'Lỗi cập nhật điểm uy tín!');
            }
        }

        // TẢI DANH SÁCH SỰ CỐ
        async function loadReports() {
            const tbody = document.getElementById('reportsTableBody');
            try {
                const res = await Api.adminReports('all');
                const list = (res && res.data) ? res.data : (Array.isArray(res) ? res : []);
                const activeCount = list.filter(r => r.status === 'dang_dien_ra').length;
                document.getElementById('activeReportsCount').innerText = activeCount;

                if (!list || list.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 24px;">Chưa có sự cố nào được ghi nhận.</td></tr>';
                    return;
                }

                tbody.innerHTML = list.map(r => {
                    let typeBadge = '<span class="badge badge-flood"><i class="ph-bold ph-waves"></i> Ngập nước</span>';
                    if (r.type === 'ket_xe') typeBadge = '<span class="badge badge-traffic"><i class="ph-bold ph-car"></i> Kẹt xe</span>';
                    if (r.type === 'cay_do') typeBadge = '<span class="badge" style="background:#dcfce7; color:#16a34a;"><i class="ph-bold ph-tree"></i> Cây đổ</span>';
                    if (r.type === 'tai_nan') typeBadge = '<span class="badge" style="background:#fee2e2; color:#dc2626;"><i class="ph-bold ph-warning-octagon"></i> Tai nạn</span>';
                    if (r.type === 'rao_chan') typeBadge = '<span class="badge" style="background:#fef3c7; color:#d97706;"><i class="ph-bold ph-barrier"></i> Rào chắn</span>';
                    
                    let statusBadge = '<span class="badge badge-active">🟢 Đang diễn ra</span>';
                    if (r.status === 'da_ket_thuc') statusBadge = '<span class="badge badge-user">⚪ Đã kết thúc</span>';
                    if (r.status === 'bi_an') statusBadge = '<span class="badge badge-blocked">🔴 Bị ẩn vi phạm</span>';

                    return `
                        <tr>
                            <td><strong>#${r.id}</strong></td>
                            <td>${typeBadge}</td>
                            <td><strong>${r.severity || 'vua'}</strong></td>
                            <td>${r.address_text || 'Chưa xác định'}</td>
                            <td>${r.username || 'Ẩn danh'}</td>
                            <td>👍 ${r.confirm_count || 0} | 👎 ${r.reject_count || 0}</td>
                            <td>${statusBadge}</td>
                            <td><span style="font-size:0.8rem; color:#64748b;">${r.expires_at || 'Không có'}</span></td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    ${r.status !== 'bi_an' 
                                        ? `<button class="btn-action btn-block" onclick="handleReportAction(${r.id}, 'hide')">Ẩn</button>` 
                                        : `<button class="btn-action btn-unblock" onclick="handleReportAction(${r.id}, 'active')">Khôi phục</button>`}
                                    ${r.status === 'dang_dien_ra' 
                                        ? `<button class="btn-action btn-role" onclick="handleReportAction(${r.id}, 'end')">Đóng sự cố</button>` : ''}
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; color:red; padding:20px;">Lỗi tải sự cố: ${err.message}</td></tr>`;
            }
        }

        async function handleReportAction(reportId, action) {
            try {
                await Api.adminReportAction(reportId, action);
                await loadReports();
            } catch (err) {
                alert(err.message || 'Lỗi xử lý sự cố');
            }
        }

        // TẢI DANH SÁCH SOS
        async function loadSos() {
            const tbody = document.getElementById('sosTableBody');
            try {
                const res = await Api.adminSos();
                const list = (res && res.data) ? res.data : (Array.isArray(res) ? res : []);
                document.getElementById('sosRequestsCount').innerText = list.length;

                if (!list || list.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 24px;">Chưa có yêu cầu cứu hộ SOS nào.</td></tr>';
                    return;
                }

                tbody.innerHTML = list.map(s => {
                    let stBadge = '<span class="badge" style="background:#fef3c7; color:#d97706;">🟡 Chờ cứu hộ</span>';
                    if (s.status === 'dang_xu_ly') stBadge = '<span class="badge badge-user">🔵 Đang xử lý</span>';
                    if (s.status === 'da_hoan_thanh') stBadge = '<span class="badge badge-active">🟢 Đã xong</span>';
                    if (s.status === 'huy') stBadge = '<span class="badge badge-blocked">🔴 Đã hủy</span>';

                    return `
                        <tr>
                            <td><strong>#${s.id}</strong></td>
                            <td><strong>${s.sender_name || 'Khách gặp nạn'}</strong></td>
                            <td><a href="tel:${s.phone}" style="color:#2563eb; font-weight:700;"><i class="ph-bold ph-phone"></i> ${s.phone}</a></td>
                            <td>${s.vehicle_info || s.description || 'Chết máy ngập nước'}</td>
                            <td>[${parseFloat(s.latitude).toFixed(4)}, ${parseFloat(s.longitude).toFixed(4)}]</td>
                            <td>${s.created_at || ''}</td>
                            <td>${stBadge}</td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    ${s.status === 'cho_cuu_ho' ? `<button class="btn-action btn-role" onclick="changeSosStatus(${s.id}, 'dang_xu_ly')">Nhận cứu hộ</button>` : ''}
                                    ${s.status === 'dang_xu_ly' ? `<button class="btn-action btn-unblock" onclick="changeSosStatus(${s.id}, 'da_hoan_thanh')">Hoàn thành</button>` : ''}
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; color:red; padding:20px;">Lỗi tải dữ liệu SOS: ${err.message}</td></tr>`;
            }
        }

        async function changeSosStatus(sosId, status) {
            try {
                await Api.adminSosAction(sosId, status);
                await loadSos();
            } catch (err) {
                alert(err.message || 'Lỗi cập nhật SOS');
            }
        }

        // Đăng xuất sạch sẽ
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

            window.location.replace('index.php?route=logout&logged_out=1&_t=' + Date.now());
        }

        // Khởi chạy nạp dữ liệu ban đầu
        loadUsers();
        Api.adminReports('dang_dien_ra').then(r => {
            const list = (r && r.data) ? r.data : (Array.isArray(r) ? r : []);
            document.getElementById('activeReportsCount').innerText = list.length;
        }).catch(() => {});
        Api.adminSos().then(s => {
            const list = (s && s.data) ? s.data : (Array.isArray(s) ? s : []);
            document.getElementById('sosRequestsCount').innerText = list.length;
        }).catch(() => {});
    </script>
</body>
</html>
