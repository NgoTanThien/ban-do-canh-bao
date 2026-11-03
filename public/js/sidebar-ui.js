// ==========================================================================
// 3. QUẢN LÝ SIDEBAR, FORM BÁO CÁO & SUB-PANEL (public/js/sidebar-ui.js)
// ==========================================================================

// 1. Quản lý đóng/mở Sidebar & Form báo cáo
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const isOpen = sidebar.classList.toggle('open');

    if (!isOpen) {
        closeReportPanel();
        closeSubPanel();
    }

    setTimeout(() => map.invalidateSize(), 300);
}

function openSidebarNews() {
    const sidebar = document.getElementById('sidebar');
    const rep = document.getElementById('sidebar-report');
    if (rep) rep.style.display = 'none';

    if (sidebar) sidebar.classList.add('open');
    openSubPanel('Tin mới nhất', 'news');
}

function closeSubPanel() {
    const main = document.getElementById('sidebar-main');
    const sub = document.getElementById('sidebar-sub');
    const rep = document.getElementById('sidebar-report');

    if (sub) sub.style.display = 'none';
    if (rep) rep.style.display = 'none';
    if (main) main.style.display = 'flex';
}

function openReportPanel() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar) sidebar.classList.add('open');
    // KHÔNG bật lớp mờ overlay để người dùng thoải mái click lên bản đồ chọn/đổi vị trí
    if (overlay) overlay.classList.remove('active');

    const main = document.getElementById('sidebar-main');
    const sub = document.getElementById('sidebar-sub');
    const rep = document.getElementById('sidebar-report');

    if (main) main.style.display = 'none';
    if (sub) sub.style.display = 'none';
    if (rep) rep.style.display = 'flex';
}

function closeReportPanel() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const rep = document.getElementById('sidebar-report');
    const main = document.getElementById('sidebar-main');

    if (rep) rep.style.display = 'none';
    if (main) main.style.display = 'flex';
    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('active');

    // Xóa điểm ghim đã chọn nếu người dùng hủy đóng form
    if (selectedMarker) {
        map.removeLayer(selectedMarker);
        selectedMarker = null;
    }
}

function openReportSheet() {
    openReportPanel();
    // Nếu chưa có tọa độ, tự động định vị GPS gợi ý
    const latInput = document.getElementById('report_lat');
    if (!latInput || !latInput.value) {
        locateUser(false);
    }
}

// 2. Xử lý gửi báo cáo ngầm qua API (Hoàn toàn KHÔNG reload trang)
async function handleReportFormSubmit(e) {
    if (e) e.preventDefault();

    const form = document.getElementById('reportAddForm');
    if (!form) return;

    const lat = document.getElementById('report_lat').value;
    const lng = document.getElementById('report_lng').value;
    if (!lat || !lng) {
        showToast("Vui lòng chạm lên bản đồ để chọn vị trí sự cố!", false);
        return;
    }

    const formData = new FormData(form);

    try {
        await Api.addReport(formData);
        showToast("Gửi báo cáo sự cố thành công!", true);
        closeReportPanel();
        form.reset();
        // Cập nhật ngay điểm mới lên bản đồ
        await loadReports();
    } catch (err) {
        showToast(err.message || "Không thể gửi báo cáo!", false);
    }
}

// 3. Hiển thị thông báo kết quả mượt mà không làm gián đoạn bản đồ
function showToast(message, isSuccess = true) {
    const existing = document.getElementById('app-toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.id = 'app-toast';
    toast.style.cssText = `
        position: fixed;
        top: 75px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 99999;
        background: ${isSuccess ? '#059669' : '#dc2626'};
        color: white;
        padding: 12px 24px;
        border-radius: 10px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.35);
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.95rem;
        font-weight: 600;
        cursor: pointer;
    `;
    toast.innerHTML = `
        <i class="${isSuccess ? 'ph-bold ph-check-circle' : 'ph-bold ph-warning-circle'}" style="font-size: 1.25rem;"></i>
        <span>${message}</span>
    `;
    toast.onclick = () => toast.remove();
    document.body.appendChild(toast);

    setTimeout(() => {
        if (toast && toast.parentNode) toast.remove();
    }, 3500);
}

// 4. Chuyển đổi và Render nội dung Sub-panel
function openSubPanel(title, type, extraData = {}) {
    window.currentSubData = extraData || {};
    const sidebar = document.getElementById('sidebar');
    if (sidebar && !sidebar.classList.contains('open')) {
        sidebar.classList.add('open');
    }
    const overlay = document.getElementById('sidebarOverlay');
    if (overlay && window.innerWidth < 768) {
        overlay.classList.add('active');
    }
    const main = document.getElementById('sidebar-main');
    const sub = document.getElementById('sidebar-sub');
    const rep = document.getElementById('sidebar-report');

    if (main) main.style.display = 'none';
    if (rep) rep.style.display = 'none'; // Ẩn khung "Yêu cầu đăng nhập / Báo sự cố"
    if (sub) sub.style.display = 'flex';
    document.getElementById('sub-title').innerText = title;

    const contentDiv = document.getElementById('sub-content');

    // PANEL: CHÚ GIẢI
    if (type === 'legend') {
        contentDiv.innerHTML = `
            <div class="panel-card">
                <h4><i class="ph-bold ph-waves" style="color:#60a5fa; margin-right:6px;"></i> Cảnh báo Ngập nước</h4>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <div style="width:26px; height:26px; background:#60a5fa; border-radius:50%; display:flex; justify-content:center; align-items:center; color:white; font-size:12px;"><i class="ph-bold ph-waves"></i></div>
                        <span style="color:#d4d4d8; font-size:0.88rem;">Mắt cá (<20cm)</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <div style="width:26px; height:26px; background:#3b82f6; border-radius:50%; display:flex; justify-content:center; align-items:center; color:white; font-size:12px;"><i class="ph-bold ph-waves"></i></div>
                        <span style="color:#d4d4d8; font-size:0.88rem;">Gối (20-40cm)</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <div style="width:26px; height:26px; background:#2563eb; border-radius:50%; display:flex; justify-content:center; align-items:center; color:white; font-size:12px;"><i class="ph-bold ph-waves"></i></div>
                        <span style="color:#d4d4d8; font-size:0.88rem;">Bánh xe (>40cm)</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <div style="width:26px; height:26px; background:#10b981; border-radius:50%; display:flex; justify-content:center; align-items:center; color:white; font-size:12px;"><i class="ph-bold ph-check"></i></div>
                        <span style="color:#d4d4d8; font-size:0.88rem;">Đã rút nước</span>
                    </div>
                </div>
            </div>

            <div class="panel-card">
                <h4><i class="ph-bold ph-car" style="color:#fb923c; margin-right:6px;"></i> Cảnh báo Kẹt xe & Sự cố</h4>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <div style="width:26px; height:26px; background:#f97316; border-radius:50%; display:flex; justify-content:center; align-items:center; color:white; font-size:12px;"><i class="ph-bold ph-car"></i></div>
                        <span style="color:#d4d4d8; font-size:0.88rem;">Kẹt xe</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <div style="width:26px; height:26px; background:#ea580c; border-radius:50%; display:flex; justify-content:center; align-items:center; color:white; font-size:12px;"><i class="ph-bold ph-warning"></i></div>
                        <span style="color:#d4d4d8; font-size:0.88rem;">Va chạm</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <div style="width:26px; height:26px; background:#dc2626; border-radius:50%; display:flex; justify-content:center; align-items:center; color:white; font-size:12px;"><i class="ph-bold ph-minus"></i></div>
                        <span style="color:#d4d4d8; font-size:0.88rem;">Đóng đường</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <div style="width:26px; height:26px; background:#eab308; border-radius:50%; display:flex; justify-content:center; align-items:center; color:white; font-size:12px;"><i class="ph-bold ph-warning-circle"></i></div>
                        <span style="color:#d4d4d8; font-size:0.88rem;">Sự cố khác</span>
                    </div>
                </div>
            </div>
        `;
    }

    // PANEL: TIN MỚI NHẤT
    else if (type === 'news') {
        let newsHtml = `<div style="display:flex; flex-direction:column; gap:12px;">`;
        const reports = window.currentReports || [];

        if (reports.length === 0) {
            newsHtml += `
                <div style="text-align:center; padding:32px 16px; color:#a1a1aa;">
                    <i class="ph-light ph-check-circle" style="font-size:3rem; color:#22c55e; margin-bottom:8px;"></i>
                    <p style="font-size:0.95rem; font-weight:600; color:white;">Tuyến đường đang thông thoáng</p>
                    <p style="font-size:0.82rem; margin-top:4px;">Chưa có báo cáo điểm ngập hay kẹt xe nào gần đây.</p>
                </div>
            `;
        } else {
            const floodCount = reports.filter(r => (r.type === 'ngap_nuoc' || r.report_type === 'flood')).length;
            const trafficCount = reports.filter(r => (r.type === 'ket_xe' || r.report_type === 'traffic')).length;

            // Thẻ thống kê tổng quan các sự cố đang diễn ra
            newsHtml += `
                <div class="panel-card" style="background: linear-gradient(135deg, rgba(30, 41, 59, 0.9), rgba(15, 23, 42, 0.95)); border: 1px solid #3b82f6; padding: 14px; margin-bottom: 4px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                        <span style="color:white; font-weight:700; font-size:0.95rem;">
                            <i class="ph-bold ph-warning-circle" style="color:#f59e0b; margin-right:4px;"></i> Tổng sự cố đang diễn ra
                        </span>
                        <span style="background:#2563eb; color:white; font-size:0.85rem; font-weight:700; padding:3px 10px; border-radius:20px;">
                            ${reports.length} điểm
                        </span>
                    </div>
                    <div style="display:flex; gap:12px; font-size:0.85rem;">
                        <span style="background:rgba(59, 130, 246, 0.15); color:#60a5fa; padding:4px 8px; border-radius:6px; font-weight:600;">
                            🌊 ${floodCount} Ngập nước
                        </span>
                        <span style="background:rgba(249, 115, 22, 0.15); color:#fb923c; padding:4px 8px; border-radius:6px; font-weight:600;">
                            🚦 ${trafficCount} Kẹt xe
                        </span>
                    </div>
                </div>
            `;

            reports.forEach(r => {
                const isFlood = (r.type === 'ngap_nuoc' || r.report_type === 'flood');
                const iconHtml = isFlood 
                    ? `<div style="width:38px; height:38px; background:rgba(59, 130, 246, 0.2); border-radius:10px; display:flex; justify-content:center; align-items:center; color:#60a5fa; font-size:1.2rem; flex-shrink:0;"><i class="ph-fill ph-waves"></i></div>` 
                    : `<div style="width:38px; height:38px; background:rgba(249, 115, 22, 0.2); border-radius:10px; display:flex; justify-content:center; align-items:center; color:#fb923c; font-size:1.2rem; flex-shrink:0;"><i class="ph-fill ph-car"></i></div>`;

                const lat = parseFloat(r.latitude);
                const lng = parseFloat(r.longitude);
                const titleText = r.address_text || r.title || 'Điểm sự cố';
                const upCount = r.confirm_count || r.upvotes || 0;

                const sevMap = {
                    'nhe': '<span style="color:#34d399; font-size:0.75rem;">(Mức nhẹ)</span>',
                    'vua': '<span style="color:#f59e0b; font-size:0.75rem;">(Mức vừa)</span>',
                    'nghiem_trong': '<span style="color:#ef4444; font-size:0.75rem; font-weight:700;">(Nghiêm trọng)</span>'
                };
                const sevText = sevMap[r.severity] || '';

                newsHtml += `
                    <div class="panel-card" style="display:flex; gap:12px; cursor:pointer; transition:all 0.2s;" onmouseover="this.style.borderColor='#3b82f6'" onmouseout="this.style.borderColor='#3f3f46'" onclick="focusMarker(${lat}, ${lng})">
                        ${iconHtml}
                        <div style="flex:1;">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:4px;">
                                <h5 style="color:white; margin:0; font-size:0.92rem; font-weight:600; line-height:1.4;">${titleText}</h5>
                            </div>
                            <div style="margin-bottom:6px;">
                                <span style="font-size:0.78rem; font-weight:600; color:${isFlood ? '#60a5fa' : '#fb923c'};">${isFlood ? 'Điểm ngập' : 'Kẹt xe'}</span>
                                ${sevText}
                            </div>
                            <p style="color:#a1a1aa; font-size:0.82rem; margin:0 0 8px 0; line-height:1.4;">${r.description || 'Không có mô tả chi tiết'}</p>
                            <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.75rem; border-top:1px dashed #3f3f46; padding-top:6px;">
                                <span style="color:#71717a;"><i class="ph ph-clock"></i> ${r.created_at || 'Mới cập nhật'}</span>
                                <span style="color:#38bdf8; font-weight:600;"><i class="ph-bold ph-crosshair"></i> Xem vị trí</span>
                            </div>
                        </div>
                    </div>
                `;
            });
        }
        newsHtml += `</div>`;
        contentDiv.innerHTML = newsHtml;
    }

    // PANEL: BÁO CÁO SỰ CỐ
    else if (type === 'report' || type === 'report_traffic') {
        const isFlood = (type === 'report');
        const floodTabStyle = isFlood ? 'color:white; font-weight:bold; border-bottom:2px solid #3b82f6;' : 'color:#a1a1aa; border-bottom:2px solid transparent;';
        const trafficTabStyle = !isFlood ? 'color:white; font-weight:bold; border-bottom:2px solid #3b82f6;' : 'color:#a1a1aa; border-bottom:2px solid transparent;';

        const floodGrid = `
            <label class="road-pill">
                <input type="checkbox" name="is_whole_street" value="1" style="display:none;" checked>
                <i class="ph-bold ph-check check-icon"></i> Ngập cả đoạn đường
            </label>
            <p style="color:#f59e0b; font-size:0.78rem; margin-bottom:10px;">* Chọn mức độ ngập ước tính:</p>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <label class="sev-label" style="padding:10px 12px; gap:8px;">
                    <input type="radio" name="severity" value="nhe" style="display:none;" checked>
                    <i class="ph-fill ph-waves" style="color:#60a5fa; font-size:1.2rem;"></i> <span style="color:white; font-size:0.85rem;">Mắt cá (<20cm)</span>
                </label>
                <label class="sev-label" style="padding:10px 12px; gap:8px;">
                    <input type="radio" name="severity" value="vua" style="display:none;">
                    <i class="ph-fill ph-waves" style="color:#3b82f6; font-size:1.2rem;"></i> <span style="color:white; font-size:0.85rem;">Gối (20-40cm)</span>
                </label>
                <label class="sev-label" style="padding:10px 12px; gap:8px;">
                    <input type="radio" name="severity" value="nghiem_trong" style="display:none;">
                    <i class="ph-fill ph-waves" style="color:#2563eb; font-size:1.2rem;"></i> <span style="color:white; font-size:0.85rem;">Bánh xe (>40cm)</span>
                </label>
                <label class="sev-label" style="padding:10px 12px; gap:8px;">
                    <input type="radio" name="severity" value="da_ket_thuc" style="display:none;">
                    <i class="ph-fill ph-check-circle" style="color:#10b981; font-size:1.2rem;"></i> <span style="color:white; font-size:0.85rem;">Hết ngập</span>
                </label>
            </div>
        `;

        const trafficGrid = `
            <p style="color:#f59e0b; font-size:0.78rem; margin-bottom:10px;">* Chọn dạng sự cố giao thông:</p>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <label class="sev-label" style="padding:10px 12px; gap:8px;">
                    <input type="radio" name="severity" value="vua" style="display:none;" checked>
                    <i class="ph-fill ph-car" style="color:#fb923c; font-size:1.2rem;"></i> <span style="color:white; font-size:0.85rem;">Kẹt xe</span>
                </label>
                <label class="sev-label" style="padding:10px 12px; gap:8px;">
                    <input type="radio" name="severity" value="nghiem_trong" style="display:none;">
                    <i class="ph-fill ph-warning" style="color:#ea580c; font-size:1.2rem;"></i> <span style="color:white; font-size:0.85rem;">Va chạm</span>
                </label>
                <label class="sev-label" style="padding:10px 12px; gap:8px;">
                    <input type="radio" name="severity" value="nghiem_trong" style="display:none;">
                    <i class="ph-fill ph-minus-circle" style="color:#dc2626; font-size:1.2rem;"></i> <span style="color:white; font-size:0.85rem;">Đóng đường</span>
                </label>
                <label class="sev-label" style="padding:10px 12px; gap:8px;">
                    <input type="radio" name="severity" value="nhe" style="display:none;">
                    <i class="ph-fill ph-warning-circle" style="color:#eab308; font-size:1.2rem;"></i> <span style="color:white; font-size:0.85rem;">Sự cố khác</span>
                </label>
            </div>
        `;

        contentDiv.innerHTML = `
            <form id="reportForm" onsubmit="handleReportSubmit(event)" style="display:flex; flex-direction:column; gap:14px;">
                <div style="display:flex; gap:8px; border-bottom:1px solid #27272a;">
                    <button type="button" onclick="openSubPanel('Báo điểm ngập', 'report')" style="flex:1; background:transparent; border:none; padding-bottom:8px; font-size:0.92rem; cursor:pointer; ${floodTabStyle}">
                        <i class="ph-bold ph-waves"></i> Báo ngập
                    </button>
                    <button type="button" onclick="openSubPanel('Báo kẹt xe', 'report_traffic')" style="flex:1; background:transparent; border:none; padding-bottom:8px; font-size:0.92rem; cursor:pointer; ${trafficTabStyle}">
                        <i class="ph-bold ph-car"></i> Sự cố giao thông
                    </button>
                </div>

                <input type="text" id="report_address" name="address_text" placeholder="Địa chỉ hoặc chạm lên bản đồ..." class="form-input" required>

                <div style="display:flex; gap:8px; align-items:center;">
                    <button type="button" onclick="locateUser(true)" style="background:rgba(59, 130, 246, 0.15); border:1px solid rgba(59, 130, 246, 0.4); color:#60a5fa; padding:8px 14px; border-radius:20px; font-size:0.82rem; cursor:pointer; display:flex; align-items:center; gap:6px;">
                        <i class="ph-bold ph-crosshair"></i> Vị trí hiện tại
                    </button>
                    <span style="color:#a1a1aa; font-size:0.75rem;">(Hoặc chạm thẳng vào bản đồ)</span>
                </div>

                <div class="panel-card" style="margin-bottom:0;">
                    ${isFlood ? floodGrid : trafficGrid}
                </div>

                <div style="display:flex; align-items:center; gap:10px;">
                    <input type="file" id="reportImage" name="image" accept="image/*" style="display:none;" onchange="document.getElementById('imageLabel').innerText = this.files[0] ? this.files[0].name : 'Thêm ảnh thực tế'">
                    <button type="button" onclick="document.getElementById('reportImage').click()" style="background:#27272a; border:1px solid #3f3f46; border-radius:8px; padding:10px 14px; color:#d4d4d8; display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.85rem;">
                        <i class="ph-bold ph-camera"></i> Thêm ảnh
                    </button>
                    <span id="imageLabel" style="color:#60a5fa; font-size:0.8rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; flex:1;">
                        Thêm ảnh để tăng uy tín
                    </span>
                </div>

                <textarea id="report_desc" name="description" placeholder="Ghi chú chi tiết thêm..." class="form-input" style="height:70px; resize:none;"></textarea>

                <div style="display:flex; gap:10px; margin-top:6px;">
                    <button type="button" onclick="closeSubPanel()" style="flex:1; background:#27272a; border:none; padding:12px; color:white; border-radius:10px; font-weight:600; cursor:pointer;">
                        Huỷ
                    </button>
                    <button type="submit" style="flex:2; background:#2563eb; border:none; padding:12px; color:white; border-radius:10px; font-weight:bold; cursor:pointer;">
                        Gửi báo cáo
                    </button>
                </div>

                <input type="hidden" name="type" value="${isFlood ? 'ngap_nuoc' : 'ket_xe'}">
                <input type="hidden" id="report_lat" name="latitude">
                <input type="hidden" id="report_lng" name="longitude">
            </form>
        `;
    }

    // PANEL: ĐĂNG NHẬP (Google + Email/SĐT + Cổng Admin)
    else if (type === 'login') {
        contentDiv.innerHTML = `
            <div style="background:#ffffff; border-radius:18px; padding:28px 18px 24px; color:#292929; text-align:center; max-width:360px; margin:0 auto; box-shadow:0 8px 30px rgba(0,0,0,0.18);">
                <div style="width:48px; height:48px; background:linear-gradient(135deg, #2563eb, #3b82f6); border-radius:12px; display:inline-flex; align-items:center; justify-content:center; color:#ffffff; box-shadow:0 4px 14px rgba(37,99,235,0.28);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <path d="M12 8v4"/>
                        <path d="M12 16h.01"/>
                    </svg>
                </div>

                <h2 style="font-size:24px; font-weight:700; color:#292929; margin-top:16px; margin-bottom:8px;">Đăng nhập</h2>
            

                <!-- Danh sách nút chọn phương thức: Google + Email -->
                <div id="loginMethodsList" style="display:flex; flex-direction:column; gap:12px;">
                    <button type="button" class="f8-pill-btn" onclick="startGoogleAuthFromMap('login')">
                        <span class="f8-pill-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.66-5.17 3.66-9.17z"/>
                                <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.34 24 12 24z"/>
                                <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.17 0 9.98 0 12s.45 3.83 1.25 5.42l4.03-3.15z"/>
                                <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.34 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/>
                            </svg>
                        </span>
                        <span class="f8-pill-text">Đăng nhập với Google</span>
                    </button>

                    <button type="button" class="f8-pill-btn" onclick="document.getElementById('loginMethodsList').style.display='none'; document.getElementById('loginFormSection').style.display='block';">
                        <span class="f8-pill-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#555555" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>
                        <span class="f8-pill-text" style="color:#111111;">Sử dụng Tên đăng nhập/ Email</span>
                    </button>
                </div>

                <!-- Form đăng nhập khi bấm Sử dụng email / SĐT -->
                <div id="loginFormSection" style="display:none; text-align:left;">
                    <button type="button" class="f8-btn-back" onclick="document.getElementById('loginFormSection').style.display='none'; document.getElementById('loginMethodsList').style.display='flex';">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 18l-6-6 6-6"/>
                        </svg>
                        Quay lại
                    </button>

                    <form onsubmit="handleAuthLogin(event)" style="display:flex; flex-direction:column; gap:12px;">
                        <div>
                            <label style="font-size:13px; font-weight:700; color:#292929; margin-bottom:4px; display:block;">Tên đăng nhập hoặc Email</label>
                            <input type="text" id="loginUsername" placeholder="Nhập Email hoặc tên đăng nhập" class="f8-form-input" required autocomplete="username">
                        </div>
                        <div>
                            <label style="font-size:13px; font-weight:700; color:#292929; margin-bottom:4px; display:block;">Mật khẩu</label>
                            <input type="password" id="loginPassword" placeholder="Nhập mật khẩu" class="f8-form-input" required autocomplete="current-password">
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px; margin:2px 0;">
                            <label style="display:flex; align-items:center; gap:6px; color:#4e5867; cursor:pointer;">
                                <input type="checkbox" id="loginRememberMe" checked style="width:15px; height:15px; accent-color:#2563eb;">
                                <span>Ghi nhớ 30 ngày</span>
                            </label>
                            <a href="#" onclick="openSubPanel('Quên mật khẩu?', 'forgot_password'); return false;" style="color:#2563eb; font-weight:600; text-decoration:none;">Quên mật khẩu?</a>
                        </div>

                        <button type="submit" id="btnLoginSubmit" class="f8-submit-btn" style="background:linear-gradient(to right, #2563eb, #3b82f6);">
                            Đăng nhập
                        </button>
                    </form>
                </div>


                <!-- Liên kết điều hướng bên dưới -->
                <div style="margin-top:24px; text-align:center; font-size:13.5px; color:#35414c;">
                    <div>
                        Bạn chưa có tài khoản? <a href="#" onclick="openSubPanel('Đăng ký tài khoản', 'register'); return false;" style="color:#2563eb; font-weight:700; text-decoration:underline;">Đăng ký</a>
                    </div>
                    <div style="margin-top:12px;">
                        <a href="#" onclick="openSubPanel('Quên mật khẩu?', 'forgot_password'); return false;" style="color:#2563eb; font-weight:700; text-decoration:underline;">Quên mật khẩu?</a>
                    </div>
                </div>
            </div>`;
    }

        // PANEL: ĐĂNG KÝ TÀI KHOẢN (Mở thẳng form Họ tên + Username + Email + Mật khẩu)
    else if (type === 'register') {
        contentDiv.innerHTML = `
            <div style="background:#ffffff; border-radius:18px; padding:28px 18px 24px; color:#292929; text-align:center; max-width:360px; margin:0 auto; box-shadow:0 8px 30px rgba(0,0,0,0.18);">
                <div style="width:48px; height:48px; background:linear-gradient(135deg, #2563eb, #3b82f6); border-radius:12px; display:inline-flex; align-items:center; justify-content:center; color:#ffffff; box-shadow:0 4px 14px rgba(37,99,235,0.28);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <path d="M12 8v4"/>
                        <path d="M12 16h.01"/>
                    </svg>
                </div>

                <h2 style="font-size:24px; font-weight:700; color:#292929; margin-top:16px; margin-bottom:16px;">Đăng ký tài khoản</h2>

                <div id="regFormSection" style="text-align:left;">
                    <form onsubmit="handleAuthRegister(event)" style="display:flex; flex-direction:column; gap:10px;">
                        <div>
                            <label style="font-size:12.5px; font-weight:700; color:#292929; margin-bottom:3px; display:block;">Họ và tên <span style="color:#f33a58;">*</span></label>
                            <input type="text" id="regFullName" class="f8-form-input" placeholder="Ví dụ: Nguyễn Văn A" required>
                        </div>
                        <div>
                            <label style="font-size:12.5px; font-weight:700; color:#292929; margin-bottom:3px; display:block;">Tên đăng nhập (Username) <span style="color:#f33a58;">*</span></label>
                            <input type="text" id="regUsername" class="f8-form-input" placeholder="Ví dụ: nguyenvana (3-30 ký tự)" required minlength="3" maxlength="30">
                        </div>
                        <div>
                            <label style="font-size:12.5px; font-weight:700; color:#292929; margin-bottom:3px; display:block;">Email <span style="color:#f33a58;">*</span></label>
                            <input type="email" id="regEmail" class="f8-form-input" placeholder="email@domain.com" required oninput="handleEmailRealtimeCheck(this.value)" onblur="handleEmailRealtimeCheck(this.value)">
                            <div id="emailCheckStatus" style="font-size:11.5px; margin-top:3px; padding-left:8px;"></div>
                        </div>
                        <div>
                            <label style="font-size:12.5px; font-weight:700; color:#292929; margin-bottom:3px; display:block;">Mật khẩu <span style="color:#f33a58;">*</span></label>
                            <input type="password" id="regPassword" class="f8-form-input" placeholder="Ít nhất 6 ký tự" required minlength="6" autocomplete="new-password">
                        </div>
                        <div>
                            <label style="font-size:12.5px; font-weight:700; color:#292929; margin-bottom:3px; display:block;">Xác nhận mật khẩu <span style="color:#f33a58;">*</span></label>
                            <input type="password" id="regConfirmPassword" class="f8-form-input" placeholder="Nhập lại mật khẩu" required minlength="6" autocomplete="new-password">
                        </div>

                        <div style="display:flex; align-items:flex-start; gap:6px; margin:4px 0 8px; font-size:12px; color:#4e5867;">
                            <input type="checkbox" id="regTermsAgree" checked required style="width:15px; height:15px; accent-color:#2563eb; margin-top:2px;">
                            <label for="regTermsAgree" style="cursor:pointer; line-height:1.35;">
                                Đồng ý với Điều khoản dịch vụ & Chính sách bảo mật.
                            </label>
                        </div>

                        <button type="submit" id="btnRegisterSubmit" class="f8-submit-btn" style="background:linear-gradient(to right, #2563eb, #3b82f6);">
                            Đăng ký tài khoản
                        </button>
                    </form>
                </div>

                <!-- Liên kết điều hướng bên dưới -->
                <div style="margin-top:20px; text-align:center; font-size:13.5px; color:#35414c;">
                    <div>
                        Bạn đã có tài khoản? <a href="#" onclick="openSubPanel('Đăng nhập', 'login'); return false;" style="color:#2563eb; font-weight:700; text-decoration:underline;">Đăng nhập</a>
                    </div>
                </div>
            </div>`;
    }

    // PANEL: XÁC THỰC MÃ OTP ĐĂNG KÝ
    else if (type === 'verify_otp') {
        const targetEmail = extraData.email || '';
        const debugOtp = extraData.debugOtp || '';
        contentDiv.innerHTML = `
            <div style="padding: 8px 0; text-align:center;">
                <div style="width:56px; height:56px; background:#0284c7; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; color:white; font-size:1.8rem;">
                    <i class="ph-bold ph-envelope-simple"></i>
                </div>
                <h4 style="color:white; font-size:1.1rem; margin-bottom:6px;">Xác thực địa chỉ Email</h4>
                <p style="color:#a1a1aa; font-size:0.85rem; line-height:1.5; margin-bottom:16px;">
                    Mã xác thực gồm 6 chữ số đã được gửi tới email:<br>
                    <strong style="color:#60a5fa;">${targetEmail}</strong>
                </p>

                ${debugOtp ? `
                    <div style="background:#1e3a5f; border:1px dashed #3b82f6; color:#93c5fd; padding:10px 14px; border-radius:8px; font-size:0.83rem; margin-bottom:16px; text-align:left;">
                        💡 <b>Môi trường thử nghiệm:</b> Mã OTP của bạn là: <span style="font-size:1.15rem; font-weight:bold; color:white; letter-spacing:2px;">${debugOtp}</span>
                    </div>
                ` : ''}

                <form onsubmit="handleVerifyRegisterOtp(event)" style="display:flex; flex-direction:column; gap:14px;">
                    <input type="hidden" id="otpTargetEmail" value="${targetEmail}">
                    <div>
                        <label style="color:#a1a1aa; font-size:0.82rem; margin-bottom:6px; display:block;">Nhập mã OTP (6 số)</label>
                        <input type="text" id="regOtpInput" maxlength="6" placeholder="000000" class="form-input" style="text-align:center; font-size:1.5rem; letter-spacing:8px; font-weight:bold;" required autocomplete="one-time-code">
                    </div>
                    <button type="submit" id="btnVerifyOtpSubmit" style="width:100%; background:#0284c7; color:white; border:none; padding:12px; border-radius:10px; font-weight:bold; cursor:pointer; font-size:0.95rem;">
                        Xác nhận & Hoàn tất
                    </button>
                </form>

                <div style="margin-top:20px; font-size:0.85rem; color:#a1a1aa;">
                    Chưa nhận được mã? 
                    <a href="#" onclick="handleResendRegisterOtp('${targetEmail}'); return false;" style="color:#60a5fa; text-decoration:none; font-weight:600;">Gửi lại mã OTP</a>
                </div>
                <div style="margin-top:10px; font-size:0.83rem;">
                    <a href="#" onclick="openSubPanel('Đăng ký tài khoản', 'register'); return false;" style="color:#94a3b8; text-decoration:none;">&larr; Quay lại thay đổi thông tin</a>
                </div>
            </div>
        `;
    }

    // PANEL: QUÊN MẬT KHẨU
    else if (type === 'forgot_password') {
        contentDiv.innerHTML = `
            <div style="background:#ffffff; border-radius:18px; padding:28px 18px 24px; color:#292929; max-width:360px; margin:0 auto; box-shadow:0 8px 30px rgba(0,0,0,0.18); position:relative;">
                <button type="button" class="f8-btn-back" onclick="openSubPanel('Đăng nhập', 'login');" style="margin-bottom:8px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                    Quay lại
                </button>

                <div style="text-align:center; margin-bottom:12px;">
                    <div style="width:48px; height:48px; background:linear-gradient(135deg, #2563eb, #3b82f6); border-radius:12px; display:inline-flex; align-items:center; justify-content:center; color:#ffffff; box-shadow:0 4px 14px rgba(37,99,235,0.28);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            <path d="M12 8v4"/>
                            <path d="M12 16h.01"/>
                        </svg>
                    </div>
                </div>

                <h2 style="font-size:26px; font-weight:700; color:#000000; text-align:center; margin-bottom:6px;">Quên mật khẩu?</h2>
                <p style="font-size:13.5px; color:#4e5867; text-align:center; margin-bottom:20px; line-height:1.45;">
                    Nhập email hoặc tên đăng nhập để nhận mã đặt lại mật khẩu.
                </p>

                <div id="f8ForgotDebugBox" style="display:none; background:#f0fdfa; border:1px dashed #1dbfaf; color:#0f766e; padding:8px 12px; border-radius:10px; font-size:12.5px; margin-bottom:12px;">
                    💡 <b>Môi trường thử nghiệm:</b> Mã OTP là: <span id="f8DebugOtpCode" style="font-weight:bold; letter-spacing:2px; color:#111;"></span>
                </div>

                <form onsubmit="handleF8ResetPasswordSubmit(event)" style="display:flex; flex-direction:column;">
                    <label style="font-size:14px; font-weight:700; color:#292929; margin-bottom:8px; text-align:left;">Tên đăng nhập</label>

                    <input type="text" id="forgotEmail" class="f8-pill-input" placeholder="Email hoặc Username" required oninput="validateF8ForgotInput(this.value)">

                    <div class="f8-combo-input">
                        <input type="text" id="resetOtpCode" class="f8-combo-inner-input" placeholder="Nhập mã xác nhận" maxlength="6" autocomplete="one-time-code">
                        <button type="button" id="btnF8SendCode" class="f8-btn-send-code" onclick="sendF8ForgotCode()">
                            Gửi mã
                        </button>
                    </div>

                    <div id="f8NewPassSection" style="display:none; margin-top:12px; flex-direction:column; gap:10px;">
                        <input type="password" id="resetNewPassword" class="f8-pill-input" placeholder="Mật khẩu mới (ít nhất 6 ký tự)" minlength="6">
                        <input type="password" id="resetConfirmPassword" class="f8-pill-input" placeholder="Xác nhận mật khẩu mới" minlength="6">
                    </div>

                    <button type="submit" id="btnForgotSubmit" class="f8-btn-submit-reset">
                        Đặt lại mật khẩu
                    </button>
                </form>

                <div style="margin-top:20px; text-align:center; font-size:13.5px; color:#4e5867;">
                    Đã nhớ mật khẩu? <a href="#" onclick="openSubPanel('Đăng nhập', 'login'); return false;" style="color:#2563eb; font-weight:700; text-decoration:underline;">Đăng nhập</a>
                </div>
            </div>`;
    }

    // PANEL: ĐẶT LẠI MẬT KHẨU
    else if (type === 'reset_password') {
        const token = extraData.token || '';
        const email = extraData.email || '';
        const debugOtp = extraData.debugOtp || '';
        const hasToken = token.length > 0;

        contentDiv.innerHTML = `
            <div style="padding: 8px 0;">
                <h4 style="color:white; font-size:1.05rem; margin-bottom:8px;">Thiết lập mật khẩu mới</h4>
                <p style="color:#a1a1aa; font-size:0.84rem; line-height:1.5; margin-bottom:16px;">
                    ${hasToken 
                        ? 'Xác thực qua liên kết token hợp lệ (hiệu lực 15 phút).' 
                        : 'Nhập email, mã OTP nhận được và mật khẩu mới để hoàn tất (hiệu lực 15 phút).'}
                </p>

                ${debugOtp ? `
                    <div style="background:#1e3a5f; border:1px dashed #3b82f6; color:#93c5fd; padding:10px 14px; border-radius:8px; font-size:0.83rem; margin-bottom:16px;">
                        💡 <b>Môi trường thử nghiệm:</b> Mã OTP của bạn là: <span style="font-size:1.15rem; font-weight:bold; color:white; letter-spacing:2px;">${debugOtp}</span>
                    </div>
                ` : ''}

                <form onsubmit="handleResetPassword(event)" style="display:flex; flex-direction:column; gap:12px;">
                    <input type="hidden" id="resetToken" value="${token}">

                    ${!hasToken ? `
                        <div>
                            <label style="color:#a1a1aa; font-size:0.82rem; margin-bottom:4px; display:block;">Email tài khoản</label>
                            <input type="email" id="resetEmail" value="${email}" placeholder="email@domain.com" class="form-input" required>
                        </div>
                        <div>
                            <label style="color:#a1a1aa; font-size:0.82rem; margin-bottom:4px; display:block;">Mã OTP (6 số)</label>
                            <input type="text" id="resetOtpCode" maxlength="6" placeholder="000000" class="form-input" style="text-align:center; font-size:1.3rem; letter-spacing:6px; font-weight:bold;" required>
                        </div>
                    ` : ''}

                    <div>
                        <label style="color:#a1a1aa; font-size:0.82rem; margin-bottom:4px; display:block;">Mật khẩu mới</label>
                        <input type="password" id="resetNewPassword" placeholder="Ít nhất 6 ký tự" class="form-input" required minlength="6">
                    </div>
                    <div>
                        <label style="color:#a1a1aa; font-size:0.82rem; margin-bottom:4px; display:block;">Xác nhận mật khẩu mới</label>
                        <input type="password" id="resetConfirmPassword" placeholder="Nhập lại mật khẩu mới" class="form-input" required minlength="6">
                    </div>

                    <button type="submit" id="btnResetSubmit" style="width:100%; background:#10b981; color:white; border:none; padding:12px; border-radius:10px; font-weight:bold; cursor:pointer; font-size:0.95rem; margin-top:6px;">
                        Cập nhật mật khẩu mới
                    </button>
                </form>

                <div style="text-align:center; margin-top:18px; font-size:0.88rem; color:#a1a1aa;">
                    <a href="#" onclick="openSubPanel('Đăng nhập tài khoản', 'login'); return false;" style="color:#60a5fa; text-decoration:none; font-weight:600;">&larr; Quay lại Đăng nhập</a>
                </div>
            </div>
        `;
    }

    // PANEL: ĐIỀU KHOẢN DỊCH VỤ
    else if (type === 'terms') {
        contentDiv.innerHTML = `
            <div style="color:#d4d4d8; font-size:0.88rem; line-height:1.6;">
                <h4 style="color:white; margin-bottom:10px;">Điều khoản sử dụng dịch vụ</h4>
                <div class="panel-card" style="margin-bottom:12px;">
                    <b style="color:white; display:block; margin-bottom:4px;">1. Tính xác thực thông tin</b>
                    <p style="color:#a1a1aa; font-size:0.84rem;">Người dùng cam kết cung cấp thông tin sự cố chính xác, trung thực. Mọi hành vi cố tình đăng tải thông tin sai lệch sẽ bị trừ điểm uy tín và khóa tài khoản vĩnh viễn.</p>
                </div>
                <div class="panel-card" style="margin-bottom:12px;">
                    <b style="color:white; display:block; margin-bottom:4px;">2. Quyền và trách nhiệm cộng đồng</b>
                    <p style="color:#a1a1aa; font-size:0.84rem;">Cộng đồng cùng chung tay kiểm duyệt qua tính năng xác nhận để đảm bảo dữ liệu luôn hữu ích cho người tham gia giao thông.</p>
                </div>
                <button type="button" onclick="openSubPanel('Đăng ký tài khoản', 'register'); return false;" style="width:100%; background:#2563eb; color:white; border:none; padding:10px; border-radius:8px; font-weight:bold; cursor:pointer; margin-top:8px;">
                    &larr; Quay lại Đăng ký
                </button>
            </div>
        `;
    }

    // PANEL: CHÍNH SÁCH BẢO MẬT
    else if (type === 'privacy') {
        contentDiv.innerHTML = `
            <div style="color:#d4d4d8; font-size:0.88rem; line-height:1.6;">
                <h4 style="color:white; margin-bottom:10px;">Chính sách bảo mật thông tin</h4>
                <div class="panel-card" style="margin-bottom:12px;">
                    <b style="color:white; display:block; margin-bottom:4px;">1. Bảo mật mật khẩu & tài khoản</b>
                    <p style="color:#a1a1aa; font-size:0.84rem;">Mật khẩu của bạn được mã hóa an toàn bằng thuật toán BCRYPT một chiều. Chúng tôi cam kết không chia sẻ dữ liệu cá nhân cho bên thứ ba.</p>
                </div>
                <div class="panel-card" style="margin-bottom:12px;">
                    <b style="color:white; display:block; margin-bottom:4px;">2. Dữ liệu vị trí GPS</b>
                    <p style="color:#a1a1aa; font-size:0.84rem;">Tọa độ GPS chỉ được sử dụng phục vụ mục đích ghim điểm ngập, kẹt xe trên bản đồ và hiển thị thông báo phù hợp khu vực của bạn.</p>
                </div>
                <button type="button" onclick="openSubPanel('Đăng ký tài khoản', 'register'); return false;" style="width:100%; background:#2563eb; color:white; border:none; padding:10px; border-radius:8px; font-weight:bold; cursor:pointer; margin-top:8px;">
                    &larr; Quay lại Đăng ký
                </button>
            </div>
        `;
    }

    // PANEL: HƯỚNG DẪN
    else if (type === 'guide') {
        contentDiv.innerHTML = `
            <div style="color:#d4d4d8; font-size:0.9rem; line-height:1.6;">
                <div class="panel-card">
                    <b style="color:white; display:block; margin-bottom:6px;">1. Báo sự cố nhanh</b>
                    <p style="color:#a1a1aa; font-size:0.85rem;">Bấm nút "Báo sự cố" màu xanh ở góc phải màn hình, chọn mức độ và chạm vào bản đồ hoặc vị trí GPS của bạn.</p>
                </div>
                <div class="panel-card">
                    <b style="color:white; display:block; margin-bottom:6px;">2. Xem chi tiết điểm ngập</b>
                    <p style="color:#a1a1aa; font-size:0.85rem;">Bấm vào bất kỳ điểm sóng màu xanh (ngập nước) hoặc màu đỏ (kẹt xe) trên bản đồ để xem mô tả và thời gian.</p>
                </div>
                <div class="panel-card">
                    <b style="color:white; display:block; margin-bottom:6px;">3. Chung tay xác nhận</b>
                    <p style="color:#a1a1aa; font-size:0.85rem;">Bấm 👍 Xác nhận để giúp cộng đồng nhận biết tin tức chính xác, hoặc 👎 Đã hết nếu sự cố đã kết thúc.</p>
                </div>
            </div>
        `;
    }

    // PANEL: GÓP Ý
    else if (type === 'feedback') {
        contentDiv.innerHTML = `
            <div style="color:#d4d4d8; font-size:0.9rem;">
                <p style="margin-bottom:12px; color:#a1a1aa; font-size:0.85rem;">Chúng tôi trân trọng mọi ý kiến đóng góp của bạn nhằm hoàn thiện bản đồ cộng đồng tốt hơn.</p>
                <textarea placeholder="Nhập ý kiến góp ý của bạn..." class="form-input" style="height:110px; resize:none; margin-bottom:12px;"></textarea>
                <input type="text" placeholder="Số điện thoại hoặc Email liên hệ (tuỳ chọn)" class="form-input" style="margin-bottom:14px;">
                <button onclick="alert('Cảm ơn bạn đã gửi góp ý quý báu!'); closeSubPanel();" style="width:100%; background:#2563eb; color:white; border:none; padding:12px; border-radius:10px; font-weight:bold; cursor:pointer;">
                    Gửi Góp Ý
                </button>
            </div>
        `;
    }

    // PANEL: THEO DÕI KHU VỰC & CẢNH BÁO BÁN KÍNH GPS
    else if (type === 'follow') {
        const hasNotif = ("Notification" in window);
        const notifGranted = hasNotif && (Notification.permission === "granted");
        const fixedRadiusLabel = alertRadius < 500 ? `${alertRadius}m` : `${alertRadius / 1000}km`;

        let nearbyList = [];
        if (window.userLocation && Array.isArray(window.currentReports)) {
            window.currentReports.forEach(r => {
                const rLat = parseFloat(r.latitude);
                const rLng = parseFloat(r.longitude);
                if (isNaN(rLat) || isNaN(rLng)) return;
                const d = calcDistanceMeters(window.userLocation.lat, window.userLocation.lng, rLat, rLng);
                if (d <= alertRadius) {
                    nearbyList.push({ report: r, distance: d });
                }
            });
            nearbyList.sort((a, b) => a.distance - b.distance);
        }

        let listHtml = '';
        if (nearbyList.length === 0) {
            listHtml = `
                <div style="text-align:center; padding:16px; color:#a1a1aa; font-size:0.85rem;">
                    <i class="ph-bold ph-shield-check" style="color:#10b981; font-size:2rem; display:block; margin-bottom:6px;"></i>
                    Hiện không có sự cố nào trong bán kính <b>${alertRadius < 1000 ? alertRadius + 'm' : (alertRadius/1000) + 'km'}</b> quanh bạn.
                </div>
            `;
        } else {
            listHtml = nearbyList.map(item => {
                const r = item.report;
                const isFlood = (r.type === 'ngap_nuoc' || r.report_type === 'flood');
                const distText = item.distance < 1000 ? `${item.distance}m` : `${(item.distance/1000).toFixed(1)}km`;
                return `
                    <div class="panel-card" style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; margin-bottom:8px; cursor:pointer;" onclick="focusMarker(${parseFloat(r.latitude)}, ${parseFloat(r.longitude)})">
                        <div>
                            <span style="font-size:0.75rem; font-weight:700; color:${isFlood ? '#60a5fa' : '#fb923c'};">
                                ${isFlood ? '🌊 NGẬP NƯỚC' : '🚦 KẸT XE'}
                            </span>
                            <h5 style="color:white; margin:2px 0; font-size:0.88rem;">${r.address_text || 'Điểm sự cố'}</h5>
                            <span style="color:#a1a1aa; font-size:0.78rem;">Cách bạn: <strong style="color:#38bdf8;">${distText}</strong></span>
                        </div>
                        <i class="ph-bold ph-caret-right" style="color:#60a5fa; font-size:1.1rem;"></i>
                    </div>
                `;
            }).join('');
        }

        contentDiv.innerHTML = `
            <div style="display:flex; flex-direction:column; gap:14px;">
                <div class="panel-card" style="border-color:${notifGranted ? '#10b981' : '#f59e0b'};">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <span style="color:white; font-weight:600; font-size:0.9rem;">
                            <i class="ph-bold ph-bell" style="color:#fbbf24; margin-right:4px;"></i> Thông báo đẩy trình duyệt
                        </span>
                        ${notifGranted 
                            ? `<span style="background:rgba(16, 185, 129, 0.2); color:#34d399; font-size:0.75rem; padding:3px 8px; border-radius:12px; font-weight:700;">🟢 ĐÃ BẬT</span>` 
                            : `<span style="background:rgba(239, 68, 68, 0.2); color:#f87171; font-size:0.75rem; padding:3px 8px; border-radius:12px; font-weight:700;">🔴 CHƯA BẬT</span>`
                        }
                    </div>
                    <p style="color:#a1a1aa; font-size:0.8rem; margin-bottom:10px; line-height:1.4;">
                        Khi có điểm ngập mới lọt vào bán kính bạn đang đứng, trình duyệt sẽ tự động rung và gửi thông báo trực tiếp.
                    </p>
                    ${!notifGranted ? `
                        <button type="button" onclick="requestNotificationPermission()" style="width:100%; background:#2563eb; color:white; border:none; padding:10px; border-radius:8px; font-weight:bold; cursor:pointer; font-size:0.85rem; display:flex; align-items:center; justify-content:center; gap:6px;">
                            <i class="ph-bold ph-bell-ringing"></i> Cấp Quyền Thông Báo Ngay
                        </button>
                    ` : ''}
                </div>

                <div class="panel-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                        <span style="color:white; font-weight:600; font-size:0.9rem;">
                            <i class="ph-bold ph-broadcast" style="color:#38bdf8; margin-right:4px;"></i> Quét radar quanh GPS
                        </span>
                        <label style="cursor:pointer; display:flex; align-items:center; gap:6px;">
                            <input type="checkbox" ${enableProximityAlert ? 'checked' : ''} onchange="toggleProximityAlert(this.checked)" style="width:18px; height:18px; cursor:pointer;">
                            <span style="color:#cbd5e1; font-size:0.85rem;">Bật</span>
                        </label>
                    </div>

                    <p style="color:#a1a1aa; font-size:0.82rem; margin:0;">
                        Bán kính quét an toàn cố định: <strong style="color:#38bdf8;">${fixedRadiusLabel}</strong>
                    </p>
                </div>

                <div>
                    <h5 style="color:white; margin:0 0 8px 0; font-size:0.9rem; display:flex; justify-content:space-between; align-items:center;">
                        <span>⚠️ Sự cố trong bán kính</span>
                        <span style="color:#38bdf8; font-size:0.8rem;">${nearbyList.length} điểm</span>
                    </h5>
                    ${listHtml}
                </div>
            </div>
        `;
    }

    // PANEL: CHANGELOG
    else if (type === 'changelog') {
        contentDiv.innerHTML = `
            <div class="panel-card">
                <span style="background:#2563eb; color:white; font-size:0.75rem; padding:2px 8px; border-radius:4px; font-weight:600;">Phiên bản 2.0</span>
                <p style="color:#a1a1aa; font-size:0.85rem; margin-top:8px; line-height:1.5;">
                    • Giao diện bản đồ thời gian thực đa nền tảng.<br>
                    • Hỗ trợ phân loại điểm ngập & kẹt xe theo màu sắc sóng xung động.<br>
                    • Bộ lọc nhanh trên thanh trạng thái.<br>
                    • Hệ thống vote xác nhận tin tức cộng đồng.
                </p>
            </div>
        `;
    }
}