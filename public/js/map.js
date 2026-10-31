// ==========================================================================
// BẢN ĐỒ CẢNH BÁO ĐIỂM NGẬP VÀ KẸT XE - LOGIC GIAO DIỆN & BẢN ĐỒ (map.js)
// ==========================================================================

// 1. Khởi tạo bản đồ Leaflet (Ưu tiên vị trí gần nhất của người dùng)
const savedLat = parseFloat(localStorage.getItem('lastKnownLat')) || 12.2458;
const savedLng = parseFloat(localStorage.getItem('lastKnownLng')) || 109.1943;
const defaultZoom = localStorage.getItem('lastKnownLat') ? 16 : 14;

const map = L.map('map', {
    zoomControl: false // Sử dụng bộ nút điều khiển nổi riêng
}).setView([savedLat, savedLng], defaultZoom);

// 2. Tải lớp gạch bản đồ từ OpenStreetMap
L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

// Lớp chứa các điểm sự cố và lớp người dùng
const markersLayer = L.layerGroup().addTo(map);
let userMarker = null;
let selectedMarker = null; // Điểm ghim khi người dùng bấm chọn vị trí trên bản đồ
let userCircle = null; // Vòng tròn bán kính an toàn quanh người dùng
window.currentReports = [];
window.currentFilter = 'all'; // 'all', 'ngap_nuoc', 'ket_xe'
window.userLocation = { lat: savedLat, lng: savedLng };

// Cấu hình tính năng Cảnh báo theo bán kính GPS (Web Push Notifications)
let alertRadius = parseInt(localStorage.getItem('alertRadius')) || 1500; // Mặc định 1.5km
let enableProximityAlert = localStorage.getItem('enableProximityAlert') !== 'false';
let notifiedReportIds = new Set();
let closestAlertPos = null;

// Tự động định vị và zoom sát vào vị trí người dùng ngay khi vào trang
locateUser(true);
loadReports();
loadWeatherData(savedLat, savedLng); // Tự động lấy nhiệt độ thời tiết lúc mở trang

// 3. Các hàm điều khiển zoom bản đồ
function zoomInMap() {
    map.zoomIn();
}

function zoomOutMap() {
    map.zoomOut();
}

// 4. Thuật toán Haversine tính khoảng cách GPS chính xác (trả về mét)
function calcDistanceMeters(lat1, lon1, lat2, lon2) {
    const R = 6371e3; // Bán kính Trái Đất theo mét
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;

    const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ/2) * Math.sin(Δλ/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

    return Math.round(R * c);
}

// 5. Cập nhật Vòng tròn Radar bán kính cảnh báo an toàn quanh GPS
function updateUserRadiusCircle(lat, lng) {
    if (!enableProximityAlert) {
        if (userCircle) {
            map.removeLayer(userCircle);
            userCircle = null;
        }
        return;
    }

    if (userCircle) {
        userCircle.setLatLng([lat, lng]);
        userCircle.setRadius(alertRadius);
    } else {
        userCircle = L.circle([lat, lng], {
            radius: alertRadius,
            color: '#0284c7',
            fillColor: '#38bdf8',
            fillOpacity: 0.08,
            weight: 1.5,
            dashArray: '6, 6',
            interactive: false, // BẮT BUỘC: Cho phép click xuyên qua để người dùng chấm điểm báo cáo trên bản đồ
            className: 'radar-circle'
        }).addTo(map);
    }
}

// 6. Định vị GPS người dùng
function locateUser(zoom = true, showAlert = false) {
    if (!navigator.geolocation) {
        if (showAlert) alert("Trình duyệt của bạn không hỗ trợ định vị GPS.");
        return;
    }

    navigator.geolocation.getCurrentPosition(
        pos => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;

            // Lưu lại vị trí hiện tại
            window.userLocation = { lat, lng };
            localStorage.setItem('lastKnownLat', lat);
            localStorage.setItem('lastKnownLng', lng);
            loadWeatherData(lat, lng); // 👉 THÊM DÒNG NÀY: cập nhật thời tiết theo tọa độ GPS vừa lấy

            const latInput = document.getElementById('report_lat');
            const lngInput = document.getElementById('report_lng');
            const addressInput = document.getElementById('report_address');

            if (latInput) latInput.value = lat;
            if (lngInput) lngInput.value = lng;
            if (addressInput && !addressInput.value) {
                addressInput.value = "Vị trí hiện tại của bạn";
            }

            if (userMarker) map.removeLayer(userMarker);

            const pinIcon = L.divIcon({
                className: 'custom-pin',
                html: `
                    <div style="width:24px; height:24px; background:#2563eb; border-radius:50% 50% 50% 0; transform:rotate(-45deg); border:2.5px solid white; box-shadow:0 3px 8px rgba(0,0,0,0.35); position:relative;">
                        <div style="width:8px; height:8px; background:white; border-radius:50%; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%);"></div>
                    </div>
                `,
                iconSize: [24, 24],
                iconAnchor: [12, 24]
            });

            userMarker = L.marker([lat, lng], { icon: pinIcon })
                .addTo(map)
                .bindPopup('<b style="color:#2563eb;">📍 Bạn đang ở đây</b>')
                .openPopup();

            // Vẽ vòng radar an toàn quanh vị trí GPS
            updateUserRadiusCircle(lat, lng);

            // Quét sự cố trong bán kính
            checkProximityAlerts();

            // Zoom sát vào vị trí người dùng (mức zoom 16.5 cận cảnh)
            if (zoom) {
                map.flyTo([lat, lng], 16.5, { animate: true, duration: 1.2 });
            }
        },
        err => {
            console.warn("GPS Warning:", err.message);
            if (showAlert) {
                alert("Không thể lấy vị trí GPS! Hãy cấp quyền truy cập Vị trí cho trình duyệt.");
            }
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}

// 7. Bắt sự kiện chạm/click trên bản đồ để chọn tọa độ báo cáo
map.on('click', function(e) {
    const lat = e.latlng.lat.toFixed(6);
    const lng = e.latlng.lng.toFixed(6);

    const latInput = document.getElementById('report_lat');
    const lngInput = document.getElementById('report_lng');
    const addressInput = document.getElementById('report_address');

    if (latInput) latInput.value = lat;
    if (lngInput) lngInput.value = lng;
    if (addressInput) {
        addressInput.value = `Điểm chọn trên bản đồ [${lat}, ${lng}]`;
    }

    if (selectedMarker) map.removeLayer(selectedMarker);

    const pinIcon = L.divIcon({
        className: 'custom-pin-selected',
        html: `
            <div style="width:26px; height:26px; background:#ef4444; border-radius:50% 50% 50% 0; transform:rotate(-45deg); border:2.5px solid white; box-shadow:0 3px 10px rgba(0,0,0,0.45); position:relative;">
                <div style="width:8px; height:8px; background:white; border-radius:50%; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%);"></div>
            </div>
        `,
        iconSize: [26, 26],
        iconAnchor: [13, 26]
    });

    selectedMarker = L.marker([lat, lng], { icon: pinIcon })
        .addTo(map)
        .bindPopup("<b style='color:#ef4444;'>📍 Vị trí bạn đã chọn:</b><br>" + lat + ", " + lng)
        .openPopup();

    // Mở khung form báo cáo (xử lý bằng PHP thuần)
    openReportPanel();
});

// 8. Tải dữ liệu các điểm cảnh báo từ máy chủ
async function loadReports() {
    try {
        const res = await fetch('api/reports.php');
        const json = await res.json();
        if (json.status === 'success' && Array.isArray(json.data)) {
            window.currentReports = json.data;
            renderMarkers(json.data);
            // Quét sự cố trong bán kính GPS ngay khi nạp dữ liệu mới
            checkProximityAlerts();
        } else {
            renderMarkers([]);
        }
    } catch (e) {
        console.warn("Lỗi tải API reports.php:", e);
        renderMarkers([]);
    }
}
// 7. Render danh sách Markers lên bản đồ
function renderMarkers(reports) {
    markersLayer.clearLayers();

    // Lọc theo bộ lọc hiện tại
    let filteredReports = reports;
    if (window.currentFilter === 'ngap_nuoc') {
        filteredReports = reports.filter(r => (r.type === 'ngap_nuoc' || r.report_type === 'flood'));
    } else if (window.currentFilter === 'ket_xe') {
        filteredReports = reports.filter(r => (r.type === 'ket_xe' || r.report_type === 'traffic'));
    }

    const statusText = document.getElementById('status-text');
    const statusDot = document.getElementById('status-dot');

    if (filteredReports.length > 0) {
        if (statusText) statusText.innerText = `Đang có ${filteredReports.length} điểm cảnh báo`;
        if (statusDot) {
            statusDot.className = 'pulse-dot orange';
        }
    } else {
        if (statusText) statusText.innerText = `Hiện tại đường thông thoáng`;
        if (statusDot) {
            statusDot.className = 'pulse-dot green';
        }
    }

    filteredReports.forEach(r => {
        const isFlood = (r.type === 'ngap_nuoc' || r.report_type === 'flood');
        const pulseClass = isFlood ? 'pulse-marker-flood' : 'pulse-marker-traffic';

        const customIcon = L.divIcon({
            className: pulseClass,
            iconSize: [26, 26],
            iconAnchor: [13, 13],
            html: `<div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:12px; line-height:1;">${isFlood ? '🌊' : '🚦'}</div>`
        });

        const lat = parseFloat(r.latitude);
        const lng = parseFloat(r.longitude);
        if (isNaN(lat) || isNaN(lng)) return;

        const marker = L.marker([lat, lng], { icon: customIcon });

        // Tên hiển thị loại và tiêu đề
        const typeBadge = isFlood
            ? `<span style="background: rgba(2, 132, 199, 0.2); color: #38bdf8; padding: 2px 8px; border-radius: 6px; font-weight: 700; font-size: 0.75rem;">🌊 NGẬP NƯỚC</span>`
            : `<span style="background: rgba(239, 68, 68, 0.2); color: #f87171; padding: 2px 8px; border-radius: 6px; font-weight: 700; font-size: 0.75rem;">🚦 KẸT XE</span>`;

        const titleText = r.address_text || r.title || 'Điểm sự cố';
        const descText = r.description || 'Không có mô tả chi tiết.';
        const upCount = r.confirm_count || r.upvotes || 0;
        const downCount = r.reject_count || r.downvotes || 0;
        const timeText = r.created_at || 'Vừa xong';
        const reporter = r.username ? `Người báo: <b>${r.username}</b>` : '';

        const popupHtml = `
            <div style="font-size: 0.9rem; line-height: 1.5; min-width: 200px;">
                <div style="margin-bottom: 6px;">${typeBadge}</div>
                <b style="color: white; font-size: 0.98rem; display: block; margin-bottom: 4px;">${titleText}</b>
                <p style="margin: 4px 0 8px 0; color: #a1a1aa; font-size: 0.85rem;">${descText}</p>
                <div style="font-size: 0.75rem; color: #71717a; margin-bottom: 8px;">
                    ${reporter ? reporter + ' • ' : ''}${timeText}
                </div>
                <div style="display: flex; gap: 8px; border-top: 1px solid #3f3f46; padding-top: 8px;">
                    <button onclick="voteReport(${r.id}, 'confirm')" style="flex: 1; padding: 6px 10px; font-size: 0.78rem; border-radius: 8px; border: 1px solid #3f3f46; background: #27272a; color: #34d399; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                        👍 Đúng (${upCount})
                    </button>
                    <button onclick="voteReport(${r.id}, 'reject')" style="flex: 1; padding: 6px 10px; font-size: 0.78rem; border-radius: 8px; border: 1px solid #3f3f46; background: #27272a; color: #f87171; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                        👎 Đã hết (${downCount})
                    </button>
                </div>
            </div>
        `;

        marker.bindPopup(popupHtml);
        markersLayer.addLayer(marker);
    });
}

// 8. Quản lý Bộ lọc sự cố
function toggleFilterMenu(e) {
    if (e) e.stopPropagation();
    const menu = document.getElementById('filterMenu');
    if (menu) {
        menu.classList.toggle('show');
    }
}

document.addEventListener('click', function() {
    const menu = document.getElementById('filterMenu');
    if (menu) menu.classList.remove('show');
});

function applyFilter(filterType, e) {
    if (e) e.stopPropagation();
    window.currentFilter = filterType;
    const menu = document.getElementById('filterMenu');
    if (menu) menu.classList.remove('show');

    const textEl = document.getElementById('currentFilterText');
    if (filterType === 'all') textEl.innerText = 'Tất cả sự cố';
    else if (filterType === 'ngap_nuoc') textEl.innerText = 'Chỉ báo ngập';
    else if (filterType === 'ket_xe') textEl.innerText = 'Chỉ kẹt xe';

    if (window.currentReports) {
        renderMarkers(window.currentReports);
    }
}

// 9. Quản lý Sidebar & Sub-panel (Không dùng lớp phủ mờ)
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

// Xử lý gửi báo cáo ngầm qua API (Hoàn toàn KHÔNG reload trang)
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

// Hiển thị thông báo kết quả mượt mà không làm gián đoạn bản đồ
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

// 10. Chuyển đổi và Render nội dung Sub-panel
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
                <p style="font-size:13px; color:#f33a58; line-height:1.45; margin-bottom:20px;">
                    Mỗi người nên sử dụng riêng một tài khoản, tài khoản nhiều người sử dụng chung sẽ bị khóa.
                </p>

                <!-- Danh sách nút chọn phương thức: Google + Email/SĐT -->
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
                        <span class="f8-pill-text" style="color:#111111;">Sử dụng email / số điện thoại</span>
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
                            <input type="text" id="loginUsername" placeholder="Email hoặc Số điện thoại" class="f8-form-input" required autocomplete="username">
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

    // PANEL: ĐĂNG KÝ (Google + Email/SĐT)
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

                <h2 style="font-size:24px; font-weight:700; color:#292929; margin-top:16px; margin-bottom:8px;">Đăng ký tài khoản</h2>
                <p style="font-size:13px; color:#f33a58; line-height:1.45; margin-bottom:20px;">
                    Mỗi người nên sử dụng riêng một tài khoản, tài khoản nhiều người sử dụng chung sẽ bị khóa.
                </p>

                <!-- Danh sách nút bấm: Google + Email/SĐT -->
                <div id="regMethodsList" style="display:flex; flex-direction:column; gap:12px;">
                    <button type="button" class="f8-pill-btn" onclick="startGoogleAuthFromMap('register')">
                        <span class="f8-pill-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.66-5.17 3.66-9.17z"/>
                                <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.34 24 12 24z"/>
                                <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.17 0 9.98 0 12s.45 3.83 1.25 5.42l4.03-3.15z"/>
                                <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.34 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/>
                            </svg>
                        </span>
                        <span class="f8-pill-text">Đăng ký với Google</span>
                    </button>

                    <button type="button" class="f8-pill-btn" onclick="document.getElementById('regMethodsList').style.display='none'; document.getElementById('regFormSection').style.display='block';">
                        <span class="f8-pill-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#555555" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>
                        <span class="f8-pill-text" style="color:#111111;">Sử dụng email / số điện thoại</span>
                    </button>
                </div>

                <!-- Form đăng ký khi bấm Sử dụng email / SĐT -->
                <div id="regFormSection" style="display:none; text-align:left;">
                    <button type="button" class="f8-btn-back" onclick="document.getElementById('regFormSection').style.display='none'; document.getElementById('regMethodsList').style.display='flex';">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 18l-6-6 6-6"/>
                        </svg>
                        Quay lại
                    </button>

                    <form onsubmit="handleAuthRegister(event)" style="display:flex; flex-direction:column; gap:10px;">
                        <div>
                            <label style="font-size:12.5px; font-weight:700; color:#292929; margin-bottom:3px; display:block;">Họ và tên <span style="color:#f33a58;">*</span></label>
                            <input type="text" id="regFullName" class="f8-form-input" placeholder="Ví dụ: Nguyễn Văn A" required>
                        </div>
                        <div>
                            <label style="font-size:12.5px; font-weight:700; color:#292929; margin-bottom:3px; display:block;">Email <span style="color:#f33a58;">*</span></label>
                            <input type="email" id="regEmail" class="f8-form-input" placeholder="email@domain.com" required oninput="handleEmailRealtimeCheck(this.value)" onblur="handleEmailRealtimeCheck(this.value)">
                            <div id="emailCheckStatus" style="font-size:11.5px; margin-top:3px; padding-left:8px;"></div>
                        </div>
                        <div>
                            <label style="font-size:12.5px; font-weight:700; color:#292929; margin-bottom:3px; display:block;">Số điện thoại (tùy chọn)</label>
                            <input type="tel" id="regPhone" class="f8-form-input" placeholder="Ví dụ: 0912345678">
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
                <div style="margin-top:24px; text-align:center; font-size:13.5px; color:#35414c;">
                    <div>
                        Bạn đã có tài khoản? <a href="#" onclick="openSubPanel('Đăng nhập', 'login'); return false;" style="color:#2563eb; font-weight:700; text-decoration:underline;">Đăng nhập</a>
                    </div>
                    <div style="margin-top:12px;">
                        <a href="#" onclick="openSubPanel('Quên mật khẩu?', 'forgot_password'); return false;" style="color:#2563eb; font-weight:700; text-decoration:underline;">Quên mật khẩu?</a>
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
                <!-- Nút "< Quay lại" nhỏ gọn ở góc trên bên trái -->
                <button type="button" class="f8-btn-back" onclick="openSubPanel('Đăng nhập', 'login');" style="margin-bottom:8px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                    Quay lại
                </button>

                <!-- Logo ứng dụng -->
                <div style="text-align:center; margin-bottom:12px;">
                    <div style="width:48px; height:48px; background:linear-gradient(135deg, #2563eb, #3b82f6); border-radius:12px; display:inline-flex; align-items:center; justify-content:center; color:#ffffff; box-shadow:0 4px 14px rgba(37,99,235,0.28);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            <path d="M12 8v4"/>
                            <path d="M12 16h.01"/>
                        </svg>
                    </div>
                </div>

                <!-- Tiêu đề: "Quên mật khẩu?" & Mô tả phụ -->
                <h2 style="font-size:26px; font-weight:700; color:#000000; text-align:center; margin-bottom:6px;">Quên mật khẩu?</h2>
                <p style="font-size:13.5px; color:#4e5867; text-align:center; margin-bottom:20px; line-height:1.45;">
                    Nhập email hoặc tên đăng nhập để nhận mã đặt lại mật khẩu.
                </p>

                <!-- Khu vực Form (chiều rộng 360px, căn giữa) -->
                <div id="f8ForgotDebugBox" style="display:none; background:#f0fdfa; border:1px dashed #1dbfaf; color:#0f766e; padding:8px 12px; border-radius:10px; font-size:12.5px; margin-bottom:12px;">
                    💡 <b>Môi trường thử nghiệm:</b> Mã OTP là: <span id="f8DebugOtpCode" style="font-weight:bold; letter-spacing:2px; color:#111;"></span>
                </div>

                <form onsubmit="handleF8ResetPasswordSubmit(event)" style="display:flex; flex-direction:column;">
                    <label style="font-size:14px; font-weight:700; color:#292929; margin-bottom:8px; text-align:left;">Tên đăng nhập</label>

                    <!-- Ô Input 1: Hình viên thuốc -->
                    <input type="text" id="forgotEmail" class="f8-pill-input" placeholder="Email hoặc Username" required oninput="validateF8ForgotInput(this.value)">

                    <!-- Ô Input 2: Tổ hợp nhập mã + nút Gửi mã -->
                    <div class="f8-combo-input">
                        <input type="text" id="resetOtpCode" class="f8-combo-inner-input" placeholder="Nhập mã xác nhận" maxlength="6" autocomplete="one-time-code">
                        <button type="button" id="btnF8SendCode" class="f8-btn-send-code" onclick="sendF8ForgotCode()">
                            Gửi mã
                        </button>
                    </div>

                    <!-- Ô nhập mật khẩu mới (Hiện khi đã gửi mã) -->
                    <div id="f8NewPassSection" style="display:none; margin-top:12px; flex-direction:column; gap:10px;">
                        <input type="password" id="resetNewPassword" class="f8-pill-input" placeholder="Mật khẩu mới (ít nhất 6 ký tự)" minlength="6">
                        <input type="password" id="resetConfirmPassword" class="f8-pill-input" placeholder="Xác nhận mật khẩu mới" minlength="6">
                    </div>

                    <!-- Nút Submit: Nền gradient xanh cyan/ngọc nhạt -->
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

        // Tìm các sự cố đang nằm trong bán kính quanh user
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

        const radiusOptions = [500, 1000, 1500, 2000];
        const radiusButtons = radiusOptions.map(r => {
            const isActive = (alertRadius === r);
            const label = r < 1000 ? `${r}m` : `${r/1000}km`;
            const bg = isActive ? '#2563eb' : '#27272a';
            const border = isActive ? '#60a5fa' : '#3f3f46';
            return `<button type="button" onclick="changeAlertRadius(${r})" style="flex:1; background:${bg}; color:white; border:1px solid ${border}; padding:8px 4px; border-radius:8px; font-weight:600; font-size:0.85rem; cursor:pointer;">${label}</button>`;
        }).join('');

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
                <!-- Trạng thái Web Notification -->
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

                <!-- Bật/tắt quét bán kính & Tùy chọn cự ly -->
                <div class="panel-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                        <span style="color:white; font-weight:600; font-size:0.9rem;">
                            <i class="ph-bold ph-radar" style="color:#38bdf8; margin-right:4px;"></i> Quét radar quanh GPS
                        </span>
                        <label style="cursor:pointer; display:flex; align-items:center; gap:6px;">
                            <input type="checkbox" ${enableProximityAlert ? 'checked' : ''} onchange="toggleProximityAlert(this.checked)" style="width:18px; height:18px; cursor:pointer;">
                            <span style="color:#cbd5e1; font-size:0.85rem;">Bật</span>
                        </label>
                    </div>

                    <p style="color:#a1a1aa; font-size:0.8rem; margin-bottom:8px;">Chọn cự ly quét an toàn:</p>
                    <div style="display:flex; gap:8px;">
                        ${radiusButtons}
                    </div>
                </div>

                <!-- Danh sách sự cố trong bán kính -->
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

    // PANEL: BÁO CÁO CỦA TÔI
    else if (type === 'my_reports') {
        contentDiv.innerHTML = `
            <div style="text-align:center; padding:30px 10px; color:#a1a1aa;">
                <div class="pulse-dot green" style="display:inline-block; margin-bottom:12px;"></div>
                <p>Đang tải lịch sử báo cáo của bạn...</p>
            </div>
        `;
        (async () => {
            try {
                const reports = await Api.myReports();
                if (!Array.isArray(reports) || reports.length === 0) {
                    contentDiv.innerHTML = `
                        <div style="text-align:center; padding:40px 16px; color:#a1a1aa;">
                            <i class="ph-light ph-folder-open" style="font-size:3rem; color:#60a5fa; margin-bottom:10px;"></i>
                            <h4 style="color:white; margin-bottom:6px; font-size:1.05rem;">Chưa có báo cáo nào</h4>
                            <p style="font-size:0.85rem; line-height:1.5; margin-bottom:20px;">
                                Bạn chưa đóng góp báo cáo điểm ngập hoặc kẹt xe nào cho cộng đồng.
                            </p>
                            <button type="button" onclick="openReportSheet()" style="background:#2563eb; color:white; border:none; padding:10px 20px; border-radius:8px; font-weight:bold; cursor:pointer;">
                                <i class="ph-bold ph-plus-circle"></i> Báo sự cố ngay
                            </button>
                        </div>
                    `;
                    return;
                }

                let html = `
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <div class="panel-card" style="background:linear-gradient(135deg, rgba(37,99,235,0.15), rgba(15,23,42,0.9)); border:1px solid #3b82f6; padding:14px;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="color:white; font-weight:700; font-size:0.95rem;">
                                    <i class="ph-bold ph-trophy" style="color:#fbbf24; margin-right:4px;"></i> Đóng góp cộng đồng
                                </span>
                                <span style="background:#2563eb; color:white; font-size:0.85rem; font-weight:700; padding:2px 10px; border-radius:20px;">
                                    ${reports.length} báo cáo
                                </span>
                            </div>
                        </div>
                `;

                reports.forEach(r => {
                    const isFlood = (r.type === 'ngap_nuoc' || r.report_type === 'flood');
                    const lat = parseFloat(r.latitude);
                    const lng = parseFloat(r.longitude);
                    const titleText = r.address_text || 'Điểm sự cố';
                    const upCount = r.confirm_count || 0;
                    const downCount = r.reject_count || 0;

                    let statusBadge = '<span style="background:rgba(16,185,129,0.2); color:#34d399; font-size:0.75rem; padding:2px 8px; border-radius:12px; font-weight:700;">🟢 Đang hiển thị</span>';
                    if (r.status === 'hidden') {
                        statusBadge = '<span style="background:rgba(239,68,68,0.2); color:#f87171; font-size:0.75rem; padding:2px 8px; border-radius:12px; font-weight:700;">⚪ Đã ẩn</span>';
                    } else if (r.status === 'closed') {
                        statusBadge = '<span style="background:rgba(148,163,184,0.2); color:#94a3b8; font-size:0.75rem; padding:2px 8px; border-radius:12px; font-weight:700;">⚫ Đã kết thúc</span>';
                    }

                    html += `
                        <div class="panel-card" style="padding:12px 14px; margin-bottom:2px;">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:6px;">
                                <span style="font-size:0.78rem; font-weight:700; color:${isFlood ? '#60a5fa' : '#fb923c'};\>
                                    ${isFlood ? '🌊 NGẬP NƯỚC' : '🚦 KẸT XE'}
                                </span>
                                ${statusBadge}
                            </div>
                            <h5 style="color:white; margin:0 0 6px 0; font-size:0.92rem; line-height:1.4;">${titleText}</h5>
                            <p style="color:#a1a1aa; font-size:0.82rem; margin:0 0 8px 0;">${r.description || 'Không có mô tả chi tiết'}</p>
                            <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.78rem; border-top:1px dashed #3f3f46; padding-top:8px;">
                                <span style="color:#94a3b8;">
                                    👍 ${upCount} xác nhận &nbsp;|&nbsp; 👎 ${downCount} báo hết
                                </span>
                                <button type="button" onclick="focusMarker(${lat}, ${lng})" style="background:rgba(59,130,246,0.15); border:1px solid #3b82f6; color:#60a5fa; padding:4px 10px; border-radius:6px; font-size:0.75rem; font-weight:600; cursor:pointer;">
                                    <i class="ph-bold ph-crosshair"></i> Xem vị trí
                                </button>
                            </div>
                        </div>
                    `;
                });

                html += `</div>`;
                contentDiv.innerHTML = html;
            } catch (err) {
                contentDiv.innerHTML = `
                    <div style="text-align:center; padding:30px 10px; color:#f87171;">
                        <i class="ph-bold ph-warning-circle" style="font-size:2rem; margin-bottom:8px; display:block;"></i>
                        <p>${err.message || 'Không thể tải lịch sử báo cáo!'}</p>
                    </div>
                `;
            }
        })();
    }

    // PANEL: HỒ SƠ CÁ NHÂN & ĐỔI MẬT KHẨU
    else if (type === 'profile') {
        contentDiv.innerHTML = `
            <div style="text-align:center; padding:30px 10px; color:#a1a1aa;">
                <div class="pulse-dot green" style="display:inline-block; margin-bottom:12px;"></div>
                <p>Đang tải thông tin tài khoản...</p>
            </div>
        `;
        (async () => {
            try {
                const user = await Api.me();
                contentDiv.innerHTML = `
                    <div style="display:flex; flex-direction:column; gap:16px;">
                        <!-- Thẻ Tổng quan User -->
                        <div class="panel-card" style="text-align:center; padding:18px 14px; background:linear-gradient(135deg, rgba(30,41,59,0.8), rgba(15,23,42,0.95)); border:1px solid #3b82f6;">
                            <div style="width:56px; height:56px; background:linear-gradient(135deg, #2563eb, #3b82f6); border-radius:50%; display:inline-flex; align-items:center; justify-content:center; color:white; font-size:1.8rem; margin-bottom:8px; box-shadow:0 4px 12px rgba(37,99,235,0.35);">
                                <i class="ph-bold ph-user"></i>
                            </div>
                            <h4 style="color:white; margin:0 0 4px 0; font-size:1.1rem;">${user.full_name || user.username}</h4>
                            <p style="color:#94a3b8; font-size:0.82rem; margin:0 0 10px 0;">@${user.username} &bull; ${user.email || 'Chưa liên kết email'}</p>
                            <div style="display:inline-flex; align-items:center; gap:6px; background:rgba(245,158,11,0.15); border:1px solid rgba(245,158,11,0.4); color:#fbbf24; padding:4px 12px; border-radius:20px; font-size:0.82rem; font-weight:700;">
                                <i class="ph-bold ph-star"></i> Điểm uy tín: ${user.reputation_score ?? 10} điểm
                            </div>
                        </div>

                        <!-- Form 1: Cập nhật thông tin -->
                        <div class="panel-card">
                            <h5 style="color:white; margin:0 0 12px 0; font-size:0.92rem; font-weight:700; display:flex; align-items:center; gap:6px;">
                                <i class="ph-bold ph-identification-card" style="color:#60a5fa;"></i> Thông tin cá nhân
                            </h5>
                            <form onsubmit="handleUpdateProfileSubmit(event)" style="display:flex; flex-direction:column; gap:10px;">
                                <div>
                                    <label style="color:#a1a1aa; font-size:0.8rem; margin-bottom:4px; display:block;">Họ và tên:</label>
                                    <input type="text" id="profileFullName" class="form-input" value="${user.full_name || ''}" placeholder="Nhập họ và tên..." required>
                                </div>
                                <div>
                                    <label style="color:#a1a1aa; font-size:0.8rem; margin-bottom:4px; display:block;">Số điện thoại:</label>
                                    <input type="tel" id="profilePhone" class="form-input" value="${user.phone || ''}" placeholder="Nhập số điện thoại...">
                                </div>
                                <button type="submit" id="btnUpdateProfile" style="background:#2563eb; color:white; border:none; padding:10px; border-radius:8px; font-weight:600; font-size:0.88rem; cursor:pointer; margin-top:4px;">
                                    Lưu thông tin cá nhân
                                </button>
                            </form>
                        </div>

                        <!-- Form 2: Đổi mật khẩu -->
                        <div class="panel-card">
                            <h5 style="color:white; margin:0 0 12px 0; font-size:0.92rem; font-weight:700; display:flex; align-items:center; gap:6px;">
                                <i class="ph-bold ph-lock-key" style="color:#34d399;"></i> Đổi mật khẩu
                            </h5>
                            <form onsubmit="handleChangePasswordSubmit(event)" style="display:flex; flex-direction:column; gap:10px;">
                                <div>
                                    <label style="color:#a1a1aa; font-size:0.8rem; margin-bottom:4px; display:block;">Mật khẩu hiện tại:</label>
                                    <input type="password" id="pwOld" class="form-input" placeholder="Mật khẩu hiện tại" required>
                                </div>
                                <div>
                                    <label style="color:#a1a1aa; font-size:0.8rem; margin-bottom:4px; display:block;">Mật khẩu mới:</label>
                                    <input type="password" id="pwNew" class="form-input" placeholder="Ít nhất 6 ký tự" minlength="6" required>
                                </div>
                                <div>
                                    <label style="color:#a1a1aa; font-size:0.8rem; margin-bottom:4px; display:block;">Xác nhận mật khẩu mới:</label>
                                    <input type="password" id="pwConfirm" class="form-input" placeholder="Nhập lại mật khẩu mới" minlength="6" required>
                                </div>
                                <button type="submit" id="btnChangePassword" style="background:#10b981; color:white; border:none; padding:10px; border-radius:8px; font-weight:600; font-size:0.88rem; cursor:pointer; margin-top:4px;">
                                    Đổi mật khẩu
                                </button>
                            </form>
                        </div>
                    </div>
                `;
            } catch (err) {
                contentDiv.innerHTML = `
                    <div style="text-align:center; padding:30px 10px; color:#f87171;">
                        <i class="ph-bold ph-warning-circle" style="font-size:2rem; margin-bottom:8px; display:block;"></i>
                        <p>${err.message || 'Không thể tải thông tin hồ sơ!'}</p>
                    </div>
                `;
            }
        })();
    }
}

// 11. Di chuyển bản đồ đến điểm sự cố khi bấm trong danh sách Tin mới
function focusMarker(lat, lng) {
    if (isNaN(lat) || isNaN(lng)) return;
    map.flyTo([lat, lng], 17, { animate: true, duration: 1.2 });
    closeSubPanel();
    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.classList.remove('open');
    const overlay = document.getElementById('sidebarOverlay');
    if (overlay) overlay.classList.remove('active');

    setTimeout(() => {
        markersLayer.eachLayer(layer => {
            const pos = layer.getLatLng();
            if (pos && Math.abs(pos.lat - lat) < 0.0001 && Math.abs(pos.lng - lng) < 0.0001) {
                layer.openPopup();
            }
        });
    }, 600);
}

// ==========================================================================
// 12. CÁC HÀM XỬ LÝ CẢNH BÁO BÁN KÍNH GPS & WEB PUSH NOTIFICATIONS
// ==========================================================================

// Quét toàn bộ sự cố hiện tại xem có điểm nào lọt vào bán kính của user không
function checkProximityAlerts() {
    if (!enableProximityAlert || !window.userLocation) return;
    const uLat = window.userLocation.lat;
    const uLng = window.userLocation.lng;
    if (!uLat || !uLng) return;

    const nearbyList = [];

    if (Array.isArray(window.currentReports)) {
        window.currentReports.forEach(r => {
            const rLat = parseFloat(r.latitude);
            const rLng = parseFloat(r.longitude);
            if (isNaN(rLat) || isNaN(rLng)) return;

            const dist = calcDistanceMeters(uLat, uLng, rLat, rLng);
            if (dist <= alertRadius) {
                nearbyList.push({ report: r, distance: dist });

                // Bắn thông báo nếu chưa báo lần nào
                if (!notifiedReportIds.has(r.id)) {
                    notifiedReportIds.add(r.id);
                    sendBrowserNotification(r, dist);
                }
            }
        });
    }

    // Sắp xếp sự cố từ gần nhất đến xa nhất
    nearbyList.sort((a, b) => a.distance - b.distance);

    // Cập nhật Banner cảnh báo trên đỉnh màn hình
    updateProximityBanner(nearbyList);
}

// Gửi thông báo đẩy qua Web Notification API
function sendBrowserNotification(r, dist) {
    const isFlood = (r.type === 'ngap_nuoc' || r.report_type === 'flood');
    const typeText = isFlood ? '🌊 Điểm ngập nước' : '🚦 Điểm kẹt xe';
    const distText = dist < 1000 ? `${dist}m` : `${(dist / 1000).toFixed(1)}km`;
    const title = `⚠️ Cảnh báo: Có ${typeText} cách bạn ${distText}!`;
    const bodyText = `${r.address_text || r.title || 'Gần vị trí của bạn'}: ${r.description || 'Hãy cẩn thận lộ trình di chuyển.'}`;

    // Rung điện thoại (nếu thiết bị hỗ trợ)
    if (navigator.vibrate) {
        navigator.vibrate([200, 100, 200]);
    }

    // Hiện thông báo trình duyệt nếu đã được cấp quyền
    if ("Notification" in window && Notification.permission === "granted") {
        try {
            const notif = new Notification(title, {
                body: bodyText,
                icon: isFlood ? 'https://cdn-icons-png.flaticon.com/512/564/564619.png' : 'https://cdn-icons-png.flaticon.com/512/854/854878.png'
            });
            notif.onclick = function() {
                window.focus();
                focusMarker(parseFloat(r.latitude), parseFloat(r.longitude));
            };
        } catch (e) {
            console.warn("Lỗi gửi Web Notification:", e);
        }
    }
}

// Cập nhật thanh Banner cảnh báo khẩn cấp nổi trên bản đồ
function updateProximityBanner(nearbyList) {
    const banner = document.getElementById('proximity-alert-banner');
    const bannerText = document.getElementById('proximity-banner-text');
    if (!banner || !bannerText) return;

    if (nearbyList.length === 0) {
        banner.style.display = 'none';
        closestAlertPos = null;
        return;
    }

    const closest = nearbyList[0];
    closestAlertPos = {
        lat: parseFloat(closest.report.latitude),
        lng: parseFloat(closest.report.longitude)
    };

    const isFlood = (closest.report.type === 'ngap_nuoc' || closest.report.report_type === 'flood');
    const typeLabel = isFlood ? 'ngập nước' : 'kẹt xe';
    const distText = closest.distance < 1000 ? `${closest.distance}m` : `${(closest.distance / 1000).toFixed(1)}km`;

    bannerText.innerText = `Cách bạn ${distText} có điểm ${typeLabel}! (Tổng: ${nearbyList.length} sự cố gần bạn)`;
    banner.style.display = 'flex';
}

// Bấm vào Banner để bay đến sự cố gần nhất
function focusClosestAlert() {
    if (closestAlertPos) {
        focusMarker(closestAlertPos.lat, closestAlertPos.lng);
    }
}

// Đóng banner
function dismissProximityBanner(e) {
    if (e) e.stopPropagation();
    const banner = document.getElementById('proximity-alert-banner');
    if (banner) banner.style.display = 'none';
}

// Yêu cầu quyền gửi thông báo từ người dùng
async function requestNotificationPermission() {
    if (!("Notification" in window)) {
        alert("Trình duyệt của bạn không hỗ trợ Web Notifications.");
        return;
    }
    const perm = await Notification.requestPermission();
    if (perm === "granted") {
        showToast("Đã cấp quyền thông báo cảnh báo thành công!", true);
        openSubPanel('Theo dõi khu vực', 'follow');
        checkProximityAlerts();
    } else {
        showToast("Bạn đã từ chối quyền thông báo.", false);
    }
}

// Thay đổi bán kính cảnh báo (500m, 1000m, 1500m, 2000m)
function changeAlertRadius(radiusMeters) {
    alertRadius = parseInt(radiusMeters);
    localStorage.setItem('alertRadius', alertRadius);
    if (window.userLocation) {
        updateUserRadiusCircle(window.userLocation.lat, window.userLocation.lng);
        checkProximityAlerts();
    }
    openSubPanel('Theo dõi khu vực', 'follow');
    showToast(`Đã đổi bán kính cảnh báo sang ${alertRadius < 1000 ? alertRadius + 'm' : (alertRadius/1000) + 'km'}!`, true);
}

// Bật / Tắt tính năng cảnh báo lân cận
function toggleProximityAlert(enable) {
    enableProximityAlert = enable;
    localStorage.setItem('enableProximityAlert', enable ? 'true' : 'false');
    if (window.userLocation) {
        updateUserRadiusCircle(window.userLocation.lat, window.userLocation.lng);
    }
    if (!enable) {
        dismissProximityBanner();
    } else {
        checkProximityAlerts();
    }
    openSubPanel('Theo dõi khu vực', 'follow');
}

// Mở nhanh cài đặt Radar cảnh báo và thông báo GPS
function openRadarSettings() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar && !sidebar.classList.contains('open')) {
        toggleSidebar();
    }
    openSubPanel('Theo dõi khu vực', 'follow');
}

// 13. Bình chọn / Xác nhận điểm sự cố qua API
async function voteReport(id, type) {
    if (!isLoggedIn) {
        alert("Vui lòng đăng nhập để bình chọn xác nhận thông tin!");
        const sidebar = document.getElementById('sidebar');
        if (!sidebar.classList.contains('open')) {
            toggleSidebar();
        }
        openSubPanel('Đăng nhập tài khoản', 'login');
        return;
    }

    const fd = new FormData();
    fd.append('report_id', id);
    fd.append('vote_type', type);

    try {
        const res = await fetch('api/vote.php', { method: 'POST', body: fd });
        const json = await res.json();
        if (json.status === 'success') {
            showToast(json.message || "Đã ghi nhận bình chọn của bạn!", true);
            loadReports();
        } else {
            showToast(json.message || "Lỗi gửi bình chọn!", false);
        }
    } catch (err) {
        showToast("Đã gửi bình chọn!", true);
        loadReports();
    }
}

// =========================================================================
// CÁC HÀM XỬ LÝ XÁC THỰC (ĐĂNG KÝ OTP, REALTIME EMAIL, ĐĂNG NHẬP REMEMBER ME, RESET PASS)
// =========================================================================

// Kiểm tra trùng lặp email realtime khi người dùng nhập trên form Đăng ký
let emailRealtimeTimer = null;
function handleEmailRealtimeCheck(email) {
    const statusDiv = document.getElementById('emailCheckStatus');
    if (!statusDiv) return;
    clearTimeout(emailRealtimeTimer);

    email = (email || '').trim();
    if (!email) {
        statusDiv.innerHTML = '';
        return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        statusDiv.innerHTML = '<span style="color:#f87171;">Email không đúng định dạng</span>';
        return;
    }

    statusDiv.innerHTML = '<span style="color:#94a3b8;">Đang kiểm tra trùng lặp...</span>';
    emailRealtimeTimer = setTimeout(async () => {
        try {
            const res = await Api.checkEmail(email);
            if (res && res.exists) {
                statusDiv.innerHTML = '<span style="color:#f87171;"><i class="ph-bold ph-warning-circle"></i> Email này đã được đăng ký!</span>';
            } else {
                statusDiv.innerHTML = '<span style="color:#34d399;"><i class="ph-bold ph-check-circle"></i> Email hợp lệ có thể sử dụng</span>';
            }
        } catch (e) {
            statusDiv.innerHTML = '';
        }
    }, 400);
}

// 14. Xử lý Đăng nhập tài khoản qua API (Kèm Remember Me 30 ngày)
async function handleAuthLogin(e) {
    e.preventDefault();
    const btn = document.getElementById('btnLoginSubmit');
    const u = document.getElementById('loginUsername').value.trim();
    const p = document.getElementById('loginPassword').value;
    const rememberMe = document.getElementById('loginRememberMe') ? document.getElementById('loginRememberMe').checked : false;

    if (btn) { btn.disabled = true; btn.innerText = 'Đang đăng nhập...'; }

    const fd = new FormData();
    fd.append('username_or_email', u);
    fd.append('password', p);

    try {
        const res = await Api.login(u, p, rememberMe);
        showToast("Đăng nhập thành công!", true);
        setTimeout(() => window.location.reload(), 600);
    } catch (err) {
        showToast(err.message || "Đăng nhập thất bại!", false);
        if (btn) { btn.disabled = false; btn.innerText = 'Đăng nhập'; }
    }
}

// 15. Xử lý Đăng ký tài khoản (Bước 1: Kiểm tra thông tin & Gửi mã OTP)
async function handleAuthRegister(e) {
    e.preventDefault();
    const btn = document.getElementById('btnRegisterSubmit');
    const fullName = document.getElementById('regFullName').value.trim();
    const email = document.getElementById('regEmail').value.trim();
    const phone = document.getElementById('regPhone') ? document.getElementById('regPhone').value.trim() : '';
    const username = document.getElementById('regUsername') ? document.getElementById('regUsername').value.trim() : '';
    const password = document.getElementById('regPassword').value;
    const confirmPassword = document.getElementById('regConfirmPassword').value;
    const terms = document.getElementById('regTermsAgree') ? document.getElementById('regTermsAgree').checked : false;

    if (!terms) {
        showToast("Bạn cần đồng ý với Điều khoản dịch vụ và Chính sách bảo mật!", false);
        return;
    }
    if (password.length < 6) {
        showToast("Mật khẩu phải có ít nhất 6 ký tự!", false);
        return;
    }
    if (password !== confirmPassword) {
        showToast("Mật khẩu xác nhận không khớp!", false);
        return;
    }

    if (btn) { btn.disabled = true; btn.innerText = 'Đang gửi mã OTP...'; }

    try {
        const payload = {
            full_name: fullName,
            email: email,
            phone: phone,
            username: username,
            password: password,
            confirm_password: confirmPassword,
            terms_agree: 1
        };
        const res = await Api.register(payload);
        showToast("Mã xác thực OTP đã được gửi đến email của bạn!", true);

        // Chuyển sang bước 2: Nhập OTP
        openSubPanel('Xác thực địa chỉ Email', 'verify_otp', {
            email: email,
            fullName: fullName,
            debugOtp: (res && res.debug_otp) ? res.debug_otp : null,
            regData: payload
        });
    } catch (err) {
        showToast(err.message || "Đăng ký thất bại!", false);
        if (btn) { btn.disabled = false; btn.innerText = 'Tiếp tục (Nhận mã OTP)'; }
    }
}

// Bước 2: Xác thực mã OTP và kích hoạt tài khoản
async function handleVerifyRegisterOtp(e) {
    e.preventDefault();
    const btn = document.getElementById('btnVerifyOtpSubmit');
    const email = document.getElementById('otpTargetEmail').value;
    const otp = document.getElementById('regOtpInput').value.trim();

    if (!otp) {
        showToast("Vui lòng nhập mã OTP gồm 6 số!", false);
        return;
    }

    if (btn) { btn.disabled = true; btn.innerText = 'Đang xác thực...'; }

    try {
        await Api.verifyRegisterOtp(email, otp);
        showToast("Xác thực tài khoản thành công! Đang chuyển hướng...", true);
        setTimeout(() => window.location.reload(), 700);
    } catch (err) {
        showToast(err.message || "Xác thực OTP thất bại!", false);
        if (btn) { btn.disabled = false; btn.innerText = 'Xác nhận & Hoàn tất'; }
    }
}

// Gửi lại mã OTP đăng ký
async function handleResendRegisterOtp(email) {
    try {
        showToast("Đang gửi lại mã OTP mới...", true);
        const data = (window.currentSubData && window.currentSubData.regData) || { email: email };
        const res = await Api.register(data);
        showToast("Đã gửi lại mã OTP mới đến email!", true);
        if (res && res.debug_otp) {
            openSubPanel('Xác thực địa chỉ Email', 'verify_otp', {
                email: email,
                debugOtp: res.debug_otp,
                regData: data
            });
        }
    } catch (err) {
        showToast(err.message || "Gửi lại OTP thất bại!", false);
    }
}

// Xử lý gửi yêu cầu Quên mật khẩu
async function handleForgotPassword(e) {
    e.preventDefault();
    const btn = document.getElementById('btnForgotSubmit');
    const email = document.getElementById('forgotEmail').value.trim();

    if (!email) {
        showToast("Vui lòng nhập địa chỉ email!", false);
        return;
    }

    if (btn) { btn.disabled = true; btn.innerText = 'Đang xử lý...'; }

    try {
        const res = await Api.forgotPassword(email);
        showToast("Đã gửi liên kết và mã OTP đặt lại mật khẩu!", true);
        openSubPanel('Đặt lại mật khẩu', 'reset_password', {
            email: email,
            token: res ? res.debug_token : '',
            debugOtp: res ? res.debug_otp : ''
        });
    } catch (err) {
        showToast(err.message || "Không thể gửi yêu cầu đặt lại mật khẩu!", false);
        if (btn) { btn.disabled = false; btn.innerText = 'Gửi liên kết & Mã OTP'; }
    }
}

// Xử lý Đặt lại mật khẩu mới
async function handleResetPassword(e) {
    e.preventDefault();
    const btn = document.getElementById('btnResetSubmit');
    const token = document.getElementById('resetToken') ? document.getElementById('resetToken').value.trim() : '';
    const email = document.getElementById('resetEmail') ? document.getElementById('resetEmail').value.trim() : '';
    const otp = document.getElementById('resetOtpCode') ? document.getElementById('resetOtpCode').value.trim() : '';
    const newPassword = document.getElementById('resetNewPassword').value;
    const confirmPassword = document.getElementById('resetConfirmPassword').value;

    if (newPassword.length < 6) {
        showToast("Mật khẩu mới phải có ít nhất 6 ký tự!", false);
        return;
    }
    if (newPassword !== confirmPassword) {
        showToast("Mật khẩu xác nhận không khớp!", false);
        return;
    }

    if (btn) { btn.disabled = true; btn.innerText = 'Đang cập nhật...'; }

    try {
        await Api.resetPassword({
            token: token,
            email: email,
            otp: otp,
            password: newPassword,
            confirm_password: confirmPassword
        });
        showToast("Đặt lại mật khẩu thành công! Vui lòng đăng nhập bằng mật khẩu mới.", true);
        openSubPanel('Đăng nhập tài khoản', 'login');
    } catch (err) {
        showToast(err.message || "Đặt lại mật khẩu thất bại!", false);
        if (btn) { btn.disabled = false; btn.innerText = 'Cập nhật mật khẩu mới'; }
    }
}

// Tự động kiểm tra URL query parameters để mở đúng sub-panel (login, register, reset_password,...)
document.addEventListener('DOMContentLoaded', () => {
    try {
        const urlParams = new URLSearchParams(window.location.search);
        const route = urlParams.get('route');
        const token = urlParams.get('token') || '';

        if (route === 'reset_password') {
            openSubPanel('Đặt lại mật khẩu', 'reset_password', { token: token });
        } else if (route === 'forgot_password') {
            openSubPanel('Quên mật khẩu', 'forgot_password');
        } else if (route === 'login') {
            openSubPanel('Đăng nhập tài khoản', 'login');
        } else if (route === 'register') {
            openSubPanel('Đăng ký tài khoản', 'register');
        }
    } catch (e) {}
});

// Tải dữ liệu lần đầu và tự động làm mới mỗi 25 giây
loadReports();
setInterval(loadReports, 25000);


// TÍCH HỢP THIRD-PARTY APIS: THỜI TIẾT (OPEN-METEO) & RADAR MÂY MƯA (RAINVIEWER)
// 1. Lấy nhiệt độ và tình trạng thời tiết thời gian thực
async function loadWeatherData(lat = 12.2458, lng = 109.1943) {
    const tempEl = document.getElementById('weather-temp');
    const iconEl = document.getElementById('weather-icon');
    const weatherPill = document.getElementById('weather-pill');
    if (!tempEl) return;

    try {
        const url = `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lng}&current_weather=true`;
        const res = await fetch(url);
        const data = await res.json();
        
        if (data && data.current_weather) {
            const temp = Math.round(data.current_weather.temperature);
            const code = data.current_weather.weathercode;
            
            let iconClass = 'ph-bold ph-sun';
            let desc = 'Trời quang';

            // Phân loại mã thời tiết theo chuẩn WMO
            if (code === 0) {
                iconClass = 'ph-bold ph-sun';
                desc = 'Trời nắng';
            } else if ([1, 2, 3].includes(code)) {
                iconClass = 'ph-bold ph-cloud-sun';
                desc = 'Có mây';
            } else if ([45, 48].includes(code)) {
                iconClass = 'ph-bold ph-cloud-fog';
                desc = 'Sương mù';
            } else if ([51, 53, 55, 61, 63, 65, 80, 81, 82].includes(code)) {
                iconClass = 'ph-bold ph-cloud-rain';
                desc = 'Đang có mưa';
            } else if ([95, 96, 99].includes(code)) {
                iconClass = 'ph-bold ph-cloud-lightning';
                desc = 'Giông bão nguy hiểm';
            }

            tempEl.innerText = `${temp}°C`;
            if (iconEl) {
                iconEl.innerHTML = `<i class="${iconClass}" style="font-size: 1.15rem;"></i>`;
            }
            if (weatherPill) {
                weatherPill.title = `Thời tiết hiện tại: ${desc} (${temp}°C)`;
            }
        }
    } catch (err) {
        console.warn('Lỗi lấy dữ liệu thời tiết:', err);
    }
}

// 2. Quản lý lớp phủ Radar Mây Mưa từ RainViewer API
let rainRadarLayer = null;
let isRadarActive = false;

async function toggleRadarLayer() {
    const btn = document.getElementById('btn-radar');
    
    // Nếu đang bật -> Bấm lần nữa để tắt
    if (isRadarActive) {
        if (rainRadarLayer && map.hasLayer(rainRadarLayer)) {
            map.removeLayer(rainRadarLayer);
        }
        isRadarActive = false;
        if (btn) btn.classList.remove('active');
        showToast('Đã tắt radar mây mưa', true);
        return;
    }

    // Bật lớp radar mây mưa
    try {
        if (btn) btn.classList.add('active');
        showToast('Đang kết nối vệ tinh radar thời tiết...', true);

        const res = await fetch('https://api.rainviewer.com/public/weather-maps.json');
        const data = await res.json();
        
        if (!data || !data.radar || !data.radar.past || data.radar.past.length === 0) {
            throw new Error('Dữ liệu radar hiện chưa sẵn sàng');
        }

        // Lấy khung ảnh vệ tinh radar mới nhất vừa chụp
        const latestFrame = data.radar.past[data.radar.past.length - 1];
        const tileUrl = `${data.host}${latestFrame.path}/256/{z}/{x}/{y}/2/1_1.png`;

        if (rainRadarLayer && map.hasLayer(rainRadarLayer)) {
            map.removeLayer(rainRadarLayer);
        }

        // Tạo lớp TileLayer phủ lên bản đồ Leaflet với độ trong suốt 0.65
        rainRadarLayer = L.tileLayer(tileUrl, {
            tileSize: 256,
            opacity: 0.65,
            zIndex: 50,
            maxNativeZoom: 7, // 👉 THÊM DÒNG NÀY: Mức zoom tối đa RainViewer hỗ trợ
            maxZoom: 19,      // 👉 THÊM DÒNG NÀY: Cho phép Leaflet tự phóng to mây khi zoom cận cảnh
            attribution: '&copy; <a href="https://www.rainviewer.com/" target="_blank">RainViewer</a>'
        });

        rainRadarLayer.addTo(map);
        isRadarActive = true;
        showToast('Đã bật radar mây mưa trực tiếp! 🌧️', true);
    } catch (err) {
        console.error('Lỗi tải radar mây mưa:', err);
        showToast('Không thể tải radar mây mưa, vui lòng thử lại sau!', false);
        if (btn) btn.classList.remove('active');
        isRadarActive = false;
    }
}
// =========================================================================
// CÁC HÀM TIỆN ÍCH F8 AUTH
// =========================================================================
function validateF8ForgotInput(val) {
    const btn = document.getElementById('btnF8SendCode');
    if (!btn) return;
    val = (val || '').trim();
    if (val.length >= 3) {
        btn.classList.add('active');
    } else {
        btn.classList.remove('active');
    }
}

async function sendF8ForgotCode() {
    const btn = document.getElementById('btnF8SendCode');
    if (!btn || !btn.classList.contains('active')) return;
    const account = document.getElementById('forgotEmail').value.trim();
    btn.innerText = 'Đang gửi...';
    btn.disabled = true;

    try {
        const res = await Api.forgotPassword(account);
        showToast('Đã gửi mã xác nhận đến email tài khoản!', true);
        window.currentF8TargetEmail = (res && res.email) ? res.email : account;
        window.currentF8ResetToken = (res && res.debug_token) ? res.debug_token : '';

        const newPassSec = document.getElementById('f8NewPassSection');
        if (newPassSec) newPassSec.style.display = 'flex';
        const codeInput = document.getElementById('resetOtpCode');
        if (codeInput) codeInput.focus();

        if (res && res.debug_otp) {
            const debugBox = document.getElementById('f8ForgotDebugBox');
            const debugText = document.getElementById('f8DebugOtpCode');
            if (debugBox && debugText) {
                debugBox.style.display = 'block';
                debugText.innerText = res.debug_otp;
            }
        }

        btn.innerText = 'Gửi lại';
        btn.disabled = false;
    } catch (err) {
        showToast(err.message || 'Không thể gửi mã xác nhận!', false);
        btn.innerText = 'Gửi mã';
        btn.disabled = false;
    }
}

async function handleF8ResetPasswordSubmit(e) {
    e.preventDefault();
    const btn = document.getElementById('btnForgotSubmit');
    const account = document.getElementById('forgotEmail').value.trim();
    const otp = document.getElementById('resetOtpCode').value.trim();
    const newPassSection = document.getElementById('f8NewPassSection');

    if (!account) {
        showToast('Vui lòng nhập Email hoặc Username!', false);
        document.getElementById('forgotEmail').focus();
        return;
    }

    if (!otp) {
        showToast('Vui lòng bấm "Gửi mã" và nhập mã xác nhận gồm 6 số!', false);
        document.getElementById('resetOtpCode').focus();
        return;
    }

    if (!newPassSection || newPassSection.style.display === 'none') {
        if (newPassSection) newPassSection.style.display = 'flex';
        showToast('Vui lòng nhập mật khẩu mới!', false);
        const passInput = document.getElementById('resetNewPassword');
        if (passInput) passInput.focus();
        return;
    }

    const newPass = document.getElementById('resetNewPassword').value;
    const confirmPass = document.getElementById('resetConfirmPassword').value;

    if (newPass.length < 6) {
        showToast('Mật khẩu mới phải có ít nhất 6 ký tự!', false);
        return;
    }
    if (newPass !== confirmPass) {
        showToast('Mật khẩu xác nhận không khớp!', false);
        return;
    }

    if (btn) {
        btn.disabled = true;
        btn.innerText = 'Đang cập nhật...';
    }

    try {
        await Api.resetPassword({
            token: window.currentF8ResetToken || '',
            email: window.currentF8TargetEmail || account,
            otp: otp,
            password: newPass,
            confirm_password: confirmPass
        });

        showToast('Đặt lại mật khẩu thành công! Vui lòng đăng nhập.', true);
        setTimeout(() => {
            openSubPanel('Đăng nhập vào F8', 'login');
        }, 900);
    } catch (err) {
        showToast(err.message || 'Đặt lại mật khẩu thất bại!', false);
        if (btn) {
            btn.disabled = false;
            btn.innerText = 'Đặt lại mật khẩu';
        }
    }
}

// =========================================================================
// TIỆN ÍCH GOOGLE AUTH & ĐĂNG NHẬP ADMIN TRÊN BẢN ĐỒ
// =========================================================================
async function startGoogleAuthFromMap(mode = 'login') {
    if (typeof window.loginWithFirebaseGoogle === 'function') {
        try {
            await window.loginWithFirebaseGoogle(mode);
            return;
        } catch (e) {
            console.warn('[Firebase Map Auth]', e);
            return;
        }
    }

    showToast('Đang kết nối cổng Google qua Firebase...', true);
    setTimeout(async () => {
        if (typeof window.loginWithFirebaseGoogle === 'function') {
            try {
                await window.loginWithFirebaseGoogle(mode);
                return;
            } catch (e) {
                return;
            }
        }
        window.location.href = (mode === 'register') ? 'index.php?route=register' : 'index.php?route=login';
    }, 400);
}

// Cập nhật thông tin cá nhân
async function handleUpdateProfileSubmit(e) {
    e.preventDefault();
    const btn = document.getElementById('btnUpdateProfile');
    const fullName = document.getElementById('profileFullName').value.trim();
    const phone = document.getElementById('profilePhone').value.trim();

    if (btn) { btn.disabled = true; btn.innerText = 'Đang lưu...'; }

    try {
        await Api.updateProfile({ full_name: fullName, phone: phone });
        showToast('Cập nhật thông tin cá nhân thành công!', true);
        if (btn) { btn.disabled = false; btn.innerText = 'Lưu thông tin cá nhân'; }
    } catch (err) {
        showToast(err.message || 'Cập nhật thông tin thất bại!', false);
        if (btn) { btn.disabled = false; btn.innerText = 'Lưu thông tin cá nhân'; }
    }
}

// Đổi mật khẩu
async function handleChangePasswordSubmit(e) {
    e.preventDefault();
    const btn = document.getElementById('btnChangePassword');
    const oldPass = document.getElementById('pwOld').value;
    const newPass = document.getElementById('pwNew').value;
    const confirmPass = document.getElementById('pwConfirm').value;

    if (newPass !== confirmPass) {
        showToast('Mật khẩu xác nhận không khớp!', false);
        return;
    }

    if (btn) { btn.disabled = true; btn.innerText = 'Đang đổi mật khẩu...'; }

    try {
        await Api.changePassword(oldPass, newPass, confirmPass);
        showToast('Đổi mật khẩu thành công!', true);
        document.getElementById('pwOld').value = '';
        document.getElementById('pwNew').value = '';
        document.getElementById('pwConfirm').value = '';
        if (btn) { btn.disabled = false; btn.innerText = 'Đổi mật khẩu'; }
    } catch (err) {
        showToast(err.message || 'Đổi mật khẩu thất bại!', false);
        if (btn) { btn.disabled = false; btn.innerText = 'Đổi mật khẩu'; }
    }
}

// Tìm kiếm địa điểm trên bản đồ (OpenStreetMap Nominatim)
let searchMarker = null;
async function searchLocationOnMap() {
    const input = document.getElementById('mapSearchInput');
    if (!input) return;
    const query = input.value.trim();
    if (!query) {
        showToast('Vui lòng nhập địa điểm hoặc tên đường cần tìm!', false);
        return;
    }
    showToast('Đang tìm kiếm: ' + query + '...', true);
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=vn&limit=1`);
        const data = await res.json();
        if (data && data.length > 0) {
            const lat = parseFloat(data[0].lat);
            const lon = parseFloat(data[0].lon);
            const displayName = data[0].display_name;

            if (searchMarker) {
                map.removeLayer(searchMarker);
            }

            const searchIcon = L.divIcon({
                className: 'custom-search-marker',
                html: `<div style="background:#2563eb; color:white; width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 0 15px rgba(37,99,235,0.8); border:2px solid white;"><i class="ph-bold ph-map-pin" style="font-size:1.1rem;"></i></div>`,
                iconSize: [34, 34],
                iconAnchor: [17, 34]
            });

            searchMarker = L.marker([lat, lon], { icon: searchIcon }).addTo(map);
            searchMarker.bindPopup(`
                <div style="font-family:'Plus Jakarta Sans',sans-serif; padding:4px;">
                    <div style="font-weight:700; color:#2563eb; margin-bottom:4px;"><i class="ph-bold ph-magnifying-glass"></i> Địa điểm tìm kiếm</div>
                    <div style="font-size:0.85rem; color:#292929; line-height:1.4;">${displayName}</div>
                </div>
            `).openPopup();

            map.flyTo([lat, lon], 16, { animate: true, duration: 1.2 });
            showToast('Đã định vị đến địa điểm!', true);
        } else {
            showToast('Không tìm thấy địa điểm phù hợp! Thử nhập chi tiết hơn.', false);
        }
    } catch (e) {
        showToast('Lỗi kết nối khi tìm kiếm địa điểm!', false);
    }
}
