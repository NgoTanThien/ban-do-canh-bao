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
    <title>Trang Quản Trị - Hệ Thống Cảnh Báo Ngập Lụt & Giao Thông</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: #f1f5f9; color: #1e293b; display: flex; min-height: 100vh; }
        .admin-sidebar { width: 260px; background: #0f172a; color: #fff; display: flex; flex-direction: column; padding: 20px; }
        .admin-brand { font-size: 1.15rem; font-weight: 700; color: #38bdf8; margin-bottom: 30px; display: flex; align-items: center; gap: 10px; }
        .admin-menu { display: flex; flex-direction: column; gap: 8px; flex: 1; }
        .admin-menu a { color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 8px; font-weight: 500; display: flex; align-items: center; gap: 10px; transition: 0.2s; cursor: pointer; }
        .admin-menu a:hover, .admin-menu a.active { background: #1e293b; color: #38bdf8; }
        .admin-main { flex: 1; display: flex; flex-direction: column; }
        .admin-header { background: #fff; padding: 16px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; }
        .admin-content { padding: 30px; overflow-y: auto; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #3b82f6; }
        .stat-card.orange { border-left-color: #f97316; }
        .stat-card.red { border-left-color: #ef4444; }
        .stat-card h4 { color: #64748b; font-size: 0.875rem; margin-bottom: 8px; }
        .stat-card .number { font-size: 1.75rem; font-weight: 700; color: #0f172a; }
        .card-table { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 24px; }
        .card-table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
        .card-table h3 { font-size: 1.1rem; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; }
        th { background: #f8fafc; font-weight: 600; color: #475569; }
        .badge-admin { background: #fee2e2; color: #dc2626; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }
        .badge-user { background: #e0f2fe; color: #0284c7; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }
        .badge-flood { background: #e0f2fe; color: #0284c7; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }
        .badge-traffic { background: #ffedd5; color: #ea580c; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }
        .badge-danger { background: #fee2e2; color: #dc2626; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }
        .badge-success { background: #dcfce7; color: #16a34a; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }
        .badge-pending { background: #fef3c7; color: #d97706; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }
        .btn { padding: 6px 12px; border-radius: 6px; border: none; font-size: 0.85rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
        .btn-danger { background: #ef4444; color: #fff; }
        .btn-success { background: #22c55e; color: #fff; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-export { background: #059669; color: #fff; padding: 8px 16px; font-size: 0.88rem; }
        .btn:hover { opacity: 0.9; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>

    <!-- Sidebar Quản trị -->
    <div class="admin-sidebar">
        <div class="admin-brand">
            <i class="ph-bold ph-shield-check" style="font-size: 1.3rem;"></i> Quản Trị Hệ Thống
        </div>
        <div class="admin-menu">
            <a id="tab-btn-users" class="active" onclick="switchTab('users')"><i class="ph ph-users"></i> Quản lý tài khoản</a>
            <a id="tab-btn-reports" onclick="switchTab('reports')"><i class="ph ph-warning-circle"></i> Quản lý sự cố & Xuất Excel</a>
            <a id="tab-btn-sos" onclick="switchTab('sos')"><i class="ph ph-first-aid"></i> Điều phối Cứu hộ SOS</a>
            <div style="height: 1px; background: #334155; margin: 12px 0;"></div>
            <a href="index.php?route=home"><i class="ph ph-map-pin"></i> Xem bản đồ chính</a>
            <a href="index.php?route=logout"><i class="ph ph-sign-out"></i> Đăng xuất</a>
        </div>
    </div>

    <!-- Nội dung chính -->
    <div class="admin-main">
        <div class="admin-header">
            <h2>Bảng điều khiển quản trị (REST API)</h2>
            <span>Xin chào, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong></span>
        </div>

        <div class="admin-content">
            <!-- Thống kê nhanh 3 thẻ -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Tổng số thành viên</h4>
                    <div class="number" id="totalUsersCount">--</div>
                </div>
                <div class="stat-card orange">
                    <h4>Sự cố đang diễn ra</h4>
                    <div class="number" id="activeReportsCount">--</div>
                </div>
                <div class="stat-card red">
                    <h4>Yêu cầu Cứu hộ SOS</h4>
                    <div class="number" id="sosRequestsCount">--</div>
                </div>
            </div>

            <!-- TAB 1: QUẢN LÝ TÀI KHOẢN -->
            <div id="tab-users" class="tab-content active">
                <div class="card-table">
                    <div class="card-table-header">
                        <h3><i class="ph-bold ph-users"></i> Danh sách thành viên hệ thống</h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên đăng nhập</th>
                                <th>Email</th>
                                <th>Vai trò</th>
                                <th>Điểm uy tín</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="userTableBody">
                            <tr><td colspan="7" style="text-align: center; color: #64748b;">Đang tải dữ liệu từ API...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: QUẢN LÝ SỰ CỐ & XUẤT EXCEL -->
            <div id="tab-reports" class="tab-content">
                <div class="card-table">
                    <div class="card-table-header">
                        <h3><i class="ph-bold ph-warning-circle"></i> Danh sách sự cố điểm ngập & kẹt xe</h3>
                        <a href="api/admin/export_reports.php" class="btn btn-export" title="Tải file CSV cho Excel">
                            <i class="ph-bold ph-file-csv"></i> 📥 Xuất file Excel / CSV
                        </a>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Loại</th>
                                <th>Mức độ</th>
                                <th>Địa chỉ</th>
                                <th>Người báo</th>
                                <th>Bình chọn</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="reportsTableBody">
                            <tr><td colspan="8" style="text-align: center; color: #64748b;">Đang tải danh sách sự cố...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 3: ĐIỀU PHỐI CỨU HỘ SOS -->
            <div id="tab-sos" class="tab-content">
                <div class="card-table">
                    <div class="card-table-header">
                        <h3><i class="ph-bold ph-first-aid" style="color: #ef4444;"></i> Danh sách tín hiệu Cứu hộ Khẩn cấp SOS</h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Người gặp nạn</th>
                                <th>Số điện thoại</th>
                                <th>Phương tiện / Tình trạng</th>
                                <th>Vị trí GPS</th>
                                <th>Thời gian</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="sosTableBody">
                            <tr><td colspan="8" style="text-align: center; color: #64748b;">Đang tải yêu cầu cứu hộ...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Nhúng API Client -->
    <script src="public/js/api.js"></script>
    <script>
        const currentAdminId = <?= (int)($_SESSION['user_id'] ?? 0) ?>;

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

        async function loadUsers() {
            const tbody = document.getElementById('userTableBody');
            try {
                const users = await Api.adminUsers();
                document.getElementById('totalUsersCount').innerText = users.length;

                if (!users || users.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Chưa có thành viên nào.</td></tr>';
                    return;
                }

                tbody.innerHTML = users.map(u => {
                    const isSelf = u.id === currentAdminId;
                    const isActive = u.status === 'active';
                    const roleBadge = u.role === 'admin' 
                        ? '<span class="badge-admin">ADMIN</span>' 
                        : '<span class="badge-user">USER</span>';

                    let actionBtn = '<em>(Tài khoản của bạn)</em>';
                    if (!isSelf) {
                        actionBtn = isActive
                            ? `<button class="btn btn-danger" onclick="changeStatus(${u.id}, 'block')">Khóa</button>`
                            : `<button class="btn btn-success" onclick="changeStatus(${u.id}, 'unblock')">Mở khóa</button>`;
                    }

                    return `
                        <tr>
                            <td>${u.id}</td>
                            <td><strong>${u.username}</strong></td>
                            <td>${u.email || ''}</td>
                            <td>${roleBadge}</td>
                            <td>⭐ ${u.reputation_score}</td>
                            <td>${isActive ? '🟢 Hoạt động' : '🔴 Đã khóa'}</td>
                            <td>${actionBtn}</td>
                        </tr>
                    `;
                }).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:red;">Lỗi tải dữ liệu: ${err.message}</td></tr>`;
            }
        }

        async function changeStatus(userId, action) {
            const confirmMsg = action === 'block' 
                ? 'Bạn có chắc chắn muốn khóa tài khoản này?' 
                : 'Mở khóa và khôi phục điểm uy tín cho tài khoản này?';

            if (!confirm(confirmMsg)) return;

            try {
                await Api.adminUserStatus(userId, action);
                await loadUsers();
            } catch (err) {
                alert(err.message || 'Thao tác thất bại');
            }
        }

        async function loadReports() {
            const tbody = document.getElementById('reportsTableBody');
            try {
                const list = await Api.adminReports('all');
                const activeCount = list.filter(r => r.status === 'dang_dien_ra').length;
                document.getElementById('activeReportsCount').innerText = activeCount;

                if (!list || list.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Chưa có sự cố nào.</td></tr>';
                    return;
                }

                tbody.innerHTML = list.map(r => {
                    const typeBadge = r.type === 'ngap_nuoc'
                        ? '<span class="badge-flood">Ngập nước</span>'
                        : '<span class="badge-traffic">Kẹt xe</span>';
                    
                    let statusBadge = '<span class="badge-success">Đang diễn ra</span>';
                    if (r.status === 'da_ket_thuc') statusBadge = '<span class="badge-user">Đã kết thúc</span>';
                    if (r.status === 'bi_an') statusBadge = '<span class="badge-danger">Bị ẩn / Vi phạm</span>';

                    return `
                        <tr>
                            <td>${r.id}</td>
                            <td>${typeBadge}</td>
                            <td>${r.severity}</td>
                            <td>${r.address_text || 'Chưa có địa chỉ'}</td>
                            <td>${r.username || 'Ẩn danh'}</td>
                            <td>👍 ${r.confirm_count} | 👎 ${r.reject_count}</td>
                            <td>${statusBadge}</td>
                            <td>
                                ${r.status !== 'bi_an' 
                                    ? `<button class="btn btn-danger" onclick="handleReportAction(${r.id}, 'hide')">Ẩn</button>` 
                                    : `<button class="btn btn-success" onclick="handleReportAction(${r.id}, 'active')">Khôi phục</button>`}
                                ${r.status === 'dang_dien_ra' 
                                    ? `<button class="btn btn-primary" onclick="handleReportAction(${r.id}, 'end')">Đóng sự cố</button>` : ''}
                            </td>
                        </tr>
                    `;
                }).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; color:red;">Lỗi tải danh sách sự cố: ${err.message}</td></tr>`;
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

        async function loadSos() {
            const tbody = document.getElementById('sosTableBody');
            try {
                const list = await Api.adminSos();
                document.getElementById('sosRequestsCount').innerText = list.length;

                if (!list || list.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Chưa có tín hiệu cứu hộ SOS nào.</td></tr>';
                    return;
                }

                tbody.innerHTML = list.map(s => {
                    let stBadge = '<span class="badge-pending">Chờ cứu hộ</span>';
                    if (s.status === 'dang_xu_ly') stBadge = '<span class="badge-user">Đang xử lý</span>';
                    if (s.status === 'da_hoan_thanh') stBadge = '<span class="badge-success">Đã hoàn thành</span>';
                    if (s.status === 'huy') stBadge = '<span class="badge-danger">Đã hủy</span>';

                    return `
                        <tr>
                            <td>${s.id}</td>
                            <td><strong>${s.sender_name}</strong></td>
                            <td><a href="tel:${s.phone}" style="color:#2563eb; font-weight:700;"><i class="ph-bold ph-phone"></i> ${s.phone}</a></td>
                            <td>${s.vehicle_info || s.description || 'Chết máy ngập nước'}</td>
                            <td>[${parseFloat(s.latitude).toFixed(4)}, ${parseFloat(s.longitude).toFixed(4)}]</td>
                            <td>${s.created_at || ''}</td>
                            <td>${stBadge}</td>
                            <td>
                                ${s.status === 'cho_cuu_ho' ? `<button class="btn btn-primary" onclick="changeSosStatus(${s.id}, 'dang_xu_ly')">Nhận cứu hộ</button>` : ''}
                                ${s.status === 'dang_xu_ly' ? `<button class="btn btn-success" onclick="changeSosStatus(${s.id}, 'da_hoan_thanh')">Xong</button>` : ''}
                            </td>
                        </tr>
                    `;
                }).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; color:red;">Lỗi tải dữ liệu SOS: ${err.message}</td></tr>`;
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

        // Tải dữ liệu ban đầu
        loadUsers();
        // Cập nhật ngầm số liệu đếm
        Api.adminReports('dang_dien_ra').then(r => document.getElementById('activeReportsCount').innerText = r.length).catch(() => {});
        Api.adminSos().then(s => document.getElementById('sosRequestsCount').innerText = s.length).catch(() => {});
    </script>
</body>
</html>
