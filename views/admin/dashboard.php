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
    <title>Trang Quản Trị - Hệ Thống Cảnh Báo Ngập Lụt</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: #f1f5f9; color: #1e293b; display: flex; min-height: 100vh; }
        .admin-sidebar { width: 260px; background: #0f172a; color: #fff; display: flex; flex-direction: column; padding: 20px; }
        .admin-brand { font-size: 1.15rem; font-weight: 700; color: #38bdf8; margin-bottom: 30px; display: flex; align-items: center; gap: 10px; }
        .admin-menu { display: flex; flex-direction: column; gap: 8px; flex: 1; }
        .admin-menu a { color: #94a3b8; text-decoration: none; padding: 12px 16px; border-radius: 8px; font-weight: 500; display: flex; align-items: center; gap: 10px; transition: 0.2s; }
        .admin-menu a:hover, .admin-menu a.active { background: #1e293b; color: #fff; }
        .admin-main { flex: 1; display: flex; flex-direction: column; }
        .admin-header { background: #fff; padding: 16px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; }
        .admin-content { padding: 30px; overflow-y: auto; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #3b82f6; }
        .stat-card h4 { color: #64748b; font-size: 0.875rem; margin-bottom: 8px; }
        .stat-card .number { font-size: 1.75rem; font-weight: 700; color: #0f172a; }
        .card-table { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 20px; }
        .card-table h3 { margin-bottom: 15px; font-size: 1.1rem; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; }
        th { background: #f8fafc; font-weight: 600; color: #475569; }
        .badge-admin { background: #fee2e2; color: #dc2626; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }
        .badge-user { background: #e0f2fe; color: #0284c7; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }
        .btn { padding: 6px 12px; border-radius: 6px; border: none; font-size: 0.85rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
        .btn-danger { background: #ef4444; color: #fff; }
        .btn-success { background: #22c55e; color: #fff; }
        .btn:hover { opacity: 0.9; }
    </style>
</head>
<body>

    <!-- Sidebar Quản trị -->
    <div class="admin-sidebar">
        <div class="admin-brand">
            <i class="ph-bold ph-shield-check" style="font-size: 1.3rem;"></i> Quản Trị Hệ Thống
        </div>
        <div class="admin-menu">
            <a href="index.php?route=admin_dashboard" class="active"><i class="ph ph-users"></i> Quản lý tài khoản</a>
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
            <!-- Thống kê nhanh -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Tổng số thành viên</h4>
                    <div class="number" id="totalUsersCount">--</div>
                </div>
            </div>

            <!-- Bảng danh sách người dùng -->
            <div class="card-table">
                <h3>Danh sách thành viên hệ thống</h3>
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
    </div>

    <!-- Nhúng API Client -->
    <script src="public/js/api.js"></script>
    <script>
        const currentAdminId = <?= (int)($_SESSION['user_id'] ?? 0) ?>;

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
                await loadUsers(); // Làm mới danh sách ngay lập tức
            } catch (err) {
                alert(err.message || 'Thao tác thất bại');
            }
        }

        // Tải danh sách khi mở trang
        loadUsers();
    </script>
</body>
</html>
