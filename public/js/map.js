// ==========================================================================
// 1. LÕI BẢN ĐỒ CẢNH BÁO ĐIỂM NGẬP VÀ KẸT XE (public/js/map.js)
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
window.currentReports = [];
window.floodHistoryReports = []; // Lưu lịch sử điểm ngập trong 30 ngày gần nhất
window.trafficHistoryReports = []; // Lưu lịch sử điểm kẹt xe trong 90 ngày gần nhất
window.forceRushHourMode = false;  // Cho phép bật thử nghiệm Giờ cao điểm bất kỳ lúc nào
window.currentFilter = 'all'; // 'all', 'ngap_nuoc', 'ket_xe'
window.userLocation = { lat: savedLat, lng: savedLng };

// Kiểm tra thời gian hiện tại có đang nằm trong Khung Giờ Cao Điểm hay không
function getRushHourStatus() {
    if (window.forceRushHourMode) {
        return { isRush: true, label: 'Giờ cao điểm (Chế độ mô phỏng)' };
    }
    const now = new Date();
    const totalMinutes = now.getHours() * 60 + now.getMinutes();
    // Cao điểm sáng: 06:30 (390p) -> 08:30 (510p)
    if (totalMinutes >= 390 && totalMinutes <= 510) {
        return { isRush: true, label: 'Cao điểm sáng (06:30 - 08:30)' };
    }
    // Cao điểm trưa: 11:00 (660p) -> 12:30 (750p)
    if (totalMinutes >= 660 && totalMinutes <= 750) {
        return { isRush: true, label: 'Cao điểm trưa (11:00 - 12:30)' };
    }
    // Cao điểm chiều tối: 16:30 (990p) -> 19:00 (1140p)
    if (totalMinutes >= 990 && totalMinutes <= 1140) {
        return { isRush: true, label: 'Cao điểm chiều (16:30 - 19:00)' };
    }
    return { isRush: false, label: 'Giờ bình thường' };
}
// 3. Các hàm điều khiển zoom bản đồ
function zoomInMap() {
    map.zoomIn();
}

function zoomOutMap() {
    map.zoomOut();
}

// 4. Định vị GPS người dùng
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
            if (typeof loadWeatherData === 'function') {
                loadWeatherData(lat, lng);
            }

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

            // Vẽ vòng radar an toàn quanh vị trí GPS & quét sự cố trong bán kính
            if (typeof updateUserRadiusCircle === 'function') {
                updateUserRadiusCircle(lat, lng);
            }
            if (typeof checkProximityAlerts === 'function') {
                checkProximityAlerts();
            }

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

// 5. Bắt sự kiện chạm/click trên bản đồ để chọn tọa độ báo cáo
map.on('click', function(e) {
    if (typeof handleMapClickForRoute === 'function' && handleMapClickForRoute(e.latlng.lat, e.latlng.lng)) {
        return;
    }
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

    // Mở khung form báo cáo
    if (typeof openReportPanel === 'function') {
        openReportPanel();
    }
});

// 6. Tải dữ liệu các điểm cảnh báo, lịch sử rốn ngập (30 ngày) & điểm đen kẹt xe (90 ngày)
async function loadReports() {
    try {
        const [resActive, resFloodHist, resTrafficHist] = await Promise.all([
            fetch('api/reports.php'),
            fetch('api/reports.php?flood_history=1&days=30'),
            fetch('api/reports.php?traffic_history=1&days=90')
        ]);
        const jsonActive = await resActive.json();
        const jsonFloodHist = await resFloodHist.json();
        const jsonTrafficHist = await resTrafficHist.json();

        if (jsonFloodHist.status === 'success' && Array.isArray(jsonFloodHist.data)) {
            window.floodHistoryReports = jsonFloodHist.data;
        }
        if (jsonTrafficHist.status === 'success' && Array.isArray(jsonTrafficHist.data)) {
            window.trafficHistoryReports = jsonTrafficHist.data;
        }

        if (jsonActive.status === 'success' && Array.isArray(jsonActive.data)) {
            window.currentReports = jsonActive.data;
            renderMarkers(jsonActive.data);
            // Quét sự cố trong bán kính GPS ngay khi nạp dữ liệu mới
            if (typeof checkProximityAlerts === 'function') {
                checkProximityAlerts();
            }
        } else {
            renderMarkers([]);
        }
    } catch (e) {
        console.warn("Lỗi tải API reports.php:", e);
        renderMarkers([]);
    }
}

// 7. Thuật toán gom nhóm các điểm sự cố gần nhau trong bán kính cố định (150m)
// Chỉ gom các chấm thực sự nằm trong bán kính tính từ tâm vùng, các chấm ở ngoài bán kính sẽ KHÔNG bị gộp dây chuyền.
const WARNING_ZONE_RADIUS = 150; // Bán kính cố định 150m cho mọi vùng cảnh báo

function getDistanceInMeters(lat1, lon1, lat2, lon2) {
    if (typeof calcDistanceMeters === 'function') {
        return calcDistanceMeters(lat1, lon1, lat2, lon2);
    }
    const R = 6371e3;
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return Math.round(R * c);
}

function groupReportsIntoZones(reports, maxRadiusMeters = WARNING_ZONE_RADIUS) {
    const validReports = reports.filter(r => !isNaN(parseFloat(r.latitude)) && !isNaN(parseFloat(r.longitude)));
    const assigned = new Array(validReports.length).fill(false);
    const clusters = [];

    while (true) {
        let bestCenterIdx = -1;
        let bestNeighbors = [];

        // Tìm điểm làm tâm có nhiều điểm chưa gán nhất nằm trong đúng bán kính maxRadiusMeters
        for (let i = 0; i < validReports.length; i++) {
            if (assigned[i]) continue;
            const cLat = parseFloat(validReports[i].latitude);
            const cLng = parseFloat(validReports[i].longitude);
            const neighbors = [];

            for (let j = 0; j < validReports.length; j++) {
                if (assigned[j]) continue;
                const nLat = parseFloat(validReports[j].latitude);
                const nLng = parseFloat(validReports[j].longitude);
                if (getDistanceInMeters(cLat, cLng, nLat, nLng) <= maxRadiusMeters) {
                    neighbors.push(j);
                }
            }

            if (neighbors.length > bestNeighbors.length) {
                bestNeighbors = neighbors;
                bestCenterIdx = i;
            }
        }

        if (bestCenterIdx === -1) break;

        // Nếu cụm đông nhất có > 5 điểm (tức từ 6 điểm trở lên) trong bán kính 160m -> khóa thành 1 Vùng cảnh báo
        // Ngược lại, từng điểm lẻ sẽ giữ nguyên, không bị gộp
        if (bestNeighbors.length > 5) {
            const cluster = bestNeighbors.map(idx => {
                assigned[idx] = true;
                return validReports[idx];
            });
            clusters.push(cluster);
        } else {
            assigned[bestCenterIdx] = true;
            clusters.push([validReports[bestCenterIdx]]);
        }
    }

    return clusters;
}

// Biến lưu danh sách vùng cảnh báo đang tạm thời bung ra để xem chi tiết từng chấm
window.expandedZoneIndex = null;

function expandWarningZone(zoneIdx, centerLat, centerLng) {
    window.expandedZoneIndex = zoneIdx;
    map.closePopup();
    renderMarkers(window.currentReports || []);
    map.flyTo([centerLat, centerLng], Math.max(map.getZoom(), 17), { animate: true, duration: 0.8 });
    if (typeof showToast === 'function') {
        showToast("Đã tách từng điểm trong vùng cảnh báo! Thu nhỏ bản đồ để gộp lại.", true);
    }
}

// Tự động gộp lại thành vùng tròn khi người dùng thu nhỏ bản đồ
map.on('zoomend', function() {
    if (window.expandedZoneIndex !== null && map.getZoom() <= 15) {
        window.expandedZoneIndex = null;
        renderMarkers(window.currentReports || []);
    }
});

// Render 1 chấm sự cố đơn lẻ lên bản đồ
function addSingleReportMarker(r) {
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
}

// Render Vòng tròn Vùng Cảnh Báo khi khu vực có > 5 điểm sự cố (> 5 chấm, tức từ 6 chấm trở lên)
function addWarningZoneCluster(cluster, zoneIdx) {
    let sumLat = 0, sumLng = 0;
    let floodCount = 0, trafficCount = 0;

    cluster.forEach(r => {
        sumLat += parseFloat(r.latitude);
        sumLng += parseFloat(r.longitude);
        if (r.type === 'ngap_nuoc' || r.report_type === 'flood') floodCount++;
        else trafficCount++;
    });

    const centerLat = sumLat / cluster.length;
    const centerLng = sumLng / cluster.length;

    // Tất cả các vùng cảnh báo đều có cùng một kích thước bán kính cố định bằng nhau (150m)
    const zoneRadius = WARNING_ZONE_RADIUS;

    const isMostlyFlood = floodCount >= trafficCount;
    const strokeColor = isMostlyFlood ? '#0284c7' : '#ef4444';
    const fillColor = isMostlyFlood ? '#38bdf8' : '#f87171';

    // Nếu người dùng vừa bấm "Tách xem từng chấm", vẫn vẽ vòng tròn nền mờ và hiện các chấm lẻ
    if (window.expandedZoneIndex === zoneIdx) {
        const bgZone = L.circle([centerLat, centerLng], {
            radius: zoneRadius,
            color: strokeColor,
            fillColor: fillColor,
            fillOpacity: 0.14,
            weight: 2,
            dashArray: '6, 6',
            interactive: false
        });
        markersLayer.addLayer(bgZone);
        cluster.forEach(r => addSingleReportMarker(r));
        return;
    }

    // 1. Vẽ Vòng Tròn Cảnh Báo Khu Vực
    const zoneCircle = L.circle([centerLat, centerLng], {
        radius: zoneRadius,
        color: strokeColor,
        fillColor: fillColor,
        fillOpacity: 0.28,
        weight: 2.5,
        className: isMostlyFlood ? 'warning-zone-circle-flood' : 'warning-zone-circle-traffic'
    });

    // 2. Tạo Huy Hiệu Trung Tâm Vùng Cảnh Báo
    const zoneBadgeClass = isMostlyFlood ? 'warning-zone-badge-flood' : 'warning-zone-badge-traffic';
    const zoneIcon = L.divIcon({
        className: zoneBadgeClass,
        iconSize: [52, 52],
        iconAnchor: [26, 26],
        html: `
            <div class="zone-badge-inner">
                <span class="zone-badge-emoji">${isMostlyFlood ? '🌊' : '🚦'}</span>
                <span class="zone-badge-count">${cluster.length}</span>
            </div>
        `
    });

    const centerMarker = L.marker([centerLat, centerLng], { icon: zoneIcon });

    // 3. Nội dung Popup tổng hợp danh sách các báo cáo trong vùng
    const zoneTitle = isMostlyFlood ? '⚠️ VÙNG CẢNH BÁO NGẬP NẶNG' : '⚠️ VÙNG CẢNH BÁO KẸT XE';
    const badgeBg = isMostlyFlood ? 'rgba(2, 132, 199, 0.22)' : 'rgba(239, 68, 68, 0.22)';
    const badgeColor = isMostlyFlood ? '#38bdf8' : '#f87171';

    let itemsHtml = '';
    cluster.forEach((r, idx) => {
        const rFlood = (r.type === 'ngap_nuoc' || r.report_type === 'flood');
        const rTitle = r.address_text || r.title || `Điểm báo #${idx + 1}`;
        const rDesc = r.description || 'Không có mô tả chi tiết';
        const up = r.confirm_count || r.upvotes || 0;
        const down = r.reject_count || r.downvotes || 0;

        itemsHtml += `
            <div style="padding: 8px; background: #27272a; border-radius: 8px; margin-bottom: 6px; border: 1px solid #3f3f46;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3px;">
                    <b style="color:white; font-size:0.84rem;">${rFlood ? '🌊' : '🚦'} ${rTitle}</b>
                </div>
                <div style="color:#a1a1aa; font-size:0.78rem; margin-bottom:6px;">${rDesc}</div>
                <div style="display:flex; gap:6px;">
                    <button onclick="voteReport(${r.id}, 'confirm')" style="flex:1; padding:4px 6px; font-size:0.74rem; border-radius:6px; border:1px solid #3f3f46; background:#18181b; color:#34d399; cursor:pointer;">
                        👍 Đúng (${up})
                    </button>
                    <button onclick="voteReport(${r.id}, 'reject')" style="flex:1; padding:4px 6px; font-size:0.74rem; border-radius:6px; border:1px solid #3f3f46; background:#18181b; color:#f87171; cursor:pointer;">
                        👎 Đã hết (${down})
                    </button>
                </div>
            </div>
        `;
    });

    const popupHtml = `
        <div style="font-size: 0.9rem; line-height: 1.45; min-width: 255px; max-width: 290px;">
            <div style="margin-bottom: 6px; display:flex; align-items:center; justify-content:space-between; gap:6px;">
                <span style="background: ${badgeBg}; color: ${badgeColor}; padding: 3px 9px; border-radius: 6px; font-weight: 700; font-size: 0.74rem;">
                    ${zoneTitle}
                </span>
                <span style="color:#a1a1aa; font-size:0.75rem;">Bán kính ~${zoneRadius}m</span>
            </div>
            <b style="color: white; font-size: 0.96rem; display: block; margin-bottom: 4px;">
                Khu vực có ${cluster.length} cảnh báo cùng lúc
            </b>
            <p style="margin: 2px 0 8px 0; color: #a1a1aa; font-size: 0.8rem;">
                🌊 ${floodCount} ngập nước • 🚦 ${trafficCount} kẹt xe
            </p>
            <div style="max-height: 185px; overflow-y: auto; padding-right: 2px; margin-bottom: 8px;">
                ${itemsHtml}
            </div>
            <button onclick="expandWarningZone(${zoneIdx}, ${centerLat}, ${centerLng})" style="width:100%; padding:7px 10px; font-size:0.8rem; font-weight:600; border-radius:8px; border:1px solid #3b82f6; background:rgba(59, 130, 246, 0.18); color:#60a5fa; cursor:pointer;">
                🔍 Tách xem từng chấm trên bản đồ
            </button>
        </div>
    `;

    zoneCircle.bindPopup(popupHtml);
    centerMarker.bindPopup(popupHtml);

    markersLayer.addLayer(zoneCircle);
    markersLayer.addLayer(centerMarker);
}

// Render danh sách Markers & Vùng Cảnh Báo lên bản đồ
// Render các Vùng Rốn Ngập Lịch Sử (trong 30 ngày gần nhất) khi trời đang mưa
function renderHistoricalFloodZones(historyReports, activeClusters = []) {
    if (!window.isCurrentlyRaining || !Array.isArray(historyReports) || historyReports.length === 0) {
        return 0;
    }

    // Gom cụm lịch sử ngập trong bán kính 150m bằng chính thuật toán chống nối đuôi
    const historyClusters = groupReportsIntoZones(historyReports, WARNING_ZONE_RADIUS);
    let historyZoneCount = 0;

    historyClusters.forEach((cluster) => {
        // Chỉ những khu vực có > 5 báo cáo ngập (>= 6) trong 30 ngày qua mới được nhớ là "Rốn ngập"
        if (cluster.length <= 5) return;

        let sumLat = 0, sumLng = 0;
        let minDaysAgo = 999;

        cluster.forEach(r => {
            sumLat += parseFloat(r.latitude);
            sumLng += parseFloat(r.longitude);
            const d = parseInt(r.days_ago, 10);
            if (!isNaN(d) && d < minDaysAgo) minDaysAgo = d;
        });

        const centerLat = sumLat / cluster.length;
        const centerLng = sumLng / cluster.length;

        // Quy tắc kiểm chứng gần nhất: Nếu điểm ngập mới nhất trong cụm đã cách đây > 20 ngày
        // (tức gần 3 tuần qua không còn ai báo ngập), khả năng đường đã sửa xong -> bỏ qua
        if (minDaysAgo > 20 && minDaysAgo !== 999) return;

        // Không vẽ đè nếu vị trí này đang có sẵn 1 Vùng Cảnh Báo Trực Tiếp (> 5 điểm đang diễn ra)
        const isOverlappingActiveZone = activeClusters.some(actCluster => {
            if (actCluster.length <= 5) return false;
            const aLat = actCluster.reduce((s, item) => s + parseFloat(item.latitude), 0) / actCluster.length;
            const aLng = actCluster.reduce((s, item) => s + parseFloat(item.longitude), 0) / actCluster.length;
            return getDistanceInMeters(centerLat, centerLng, aLat, aLng) < 120;
        });
        if (isOverlappingActiveZone) return;

        historyZoneCount++;

        // 1. Vẽ Vòng tròn Vùng Rốn Ngập Dự Báo (Nét đứt màu xanh lam đậm)
        const zoneCircle = L.circle([centerLat, centerLng], {
            radius: WARNING_ZONE_RADIUS,
            color: '#0369a1',
            fillColor: '#0ea5e9',
            fillOpacity: 0.25,
            weight: 2.5,
            dashArray: '8, 6',
            className: 'warning-zone-circle-flood'
        });

        // 2. Huy hiệu trung tâm Vùng Rốn Ngập
        const zoneIcon = L.divIcon({
            className: 'warning-zone-badge-flood',
            iconSize: [54, 54],
            iconAnchor: [27, 27],
            html: `
                <div class="zone-badge-inner" style="border: 2px dashed #bae6fd;">
                    <span class="zone-badge-emoji">🌧️</span>
                    <span class="zone-badge-count">${cluster.length}</span>
                </div>
            `
        });

        const centerMarker = L.marker([centerLat, centerLng], { icon: zoneIcon });

        const lastTimeText = (minDaysAgo === 0 || minDaysAgo === 999)
            ? 'hôm nay'
            : `${minDaysAgo} ngày trước`;

        const sampleAddress = cluster[0].address_text || 'Khu vực trũng thấp thường xuyên ngập';

        const popupHtml = `
            <div style="font-size: 0.9rem; line-height: 1.45; min-width: 250px; max-width: 285px;">
                <div style="margin-bottom: 6px; display:flex; align-items:center; justify-content:space-between; gap:6px;">
                    <span style="background: rgba(14, 165, 233, 0.22); color: #38bdf8; padding: 3px 9px; border-radius: 6px; font-weight: 700; font-size: 0.74rem;">
                        🌧️ CẢNH BÁO RỐN NGẬP LỊCH SỬ
                    </span>
                    <span style="color:#a1a1aa; font-size:0.75rem;">Bán kính ${WARNING_ZONE_RADIUS}m</span>
                </div>
                <b style="color: white; font-size: 0.95rem; display: block; margin-bottom: 4px;">
                    ${sampleAddress}
                </b>
                <p style="margin: 4px 0 8px 0; color: #d4d4d8; font-size: 0.82rem;">
                    Khu vực này đã ghi nhận <b style="color:#38bdf8;">${cluster.length} lượt báo cáo ngập</b> trong <b>30 ngày qua</b> (lần gần nhất: <b>${lastTimeText}</b>).
                </p>
                <div style="padding: 8px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 8px; color: #fca5a5; font-size: 0.79rem;">
                    ⚠️ <b>Dự báo tự động:</b> Hiện đang có mưa, khu vực này có nguy cơ tái ngập rất cao dù chưa cần người dân báo mới. Hãy chủ động tránh tuyến đường này!
                </div>
            </div>
        `;

        zoneCircle.bindPopup(popupHtml);
        centerMarker.bindPopup(popupHtml);

        markersLayer.addLayer(zoneCircle);
        markersLayer.addLayer(centerMarker);
    });

    return historyZoneCount;
}

// Render danh sách Markers & Vùng Cảnh Báo lên bản đồ
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

    // Gom nhóm các điểm đang diễn ra gần nhau trong bán kính 150m
    const clusters = groupReportsIntoZones(filteredReports, WARNING_ZONE_RADIUS);
    let zoneCount = 0;

    clusters.forEach((cluster, idx) => {
        // Khi có nhiều hơn 5 cảnh báo (> 5, tức từ 6 cảnh báo trở lên) ở cùng khu vực -> chuyển thành Vùng vòng tròn cảnh báo
        if (cluster.length > 5) {
            zoneCount++;
            addWarningZoneCluster(cluster, idx);
        } else {
            cluster.forEach(r => addSingleReportMarker(r));
        }
    });

    // Tự động hiển thị thêm Vùng Rốn Ngập Lịch Sử (30 ngày) nếu trời đang mưa
    let historyZoneCount = 0;
    if (window.currentFilter === 'all' || window.currentFilter === 'ngap_nuoc') {
        historyZoneCount = renderHistoricalFloodZones(window.floodHistoryReports || [], clusters);
    }

    const totalZones = zoneCount + historyZoneCount;

    if (filteredReports.length > 0 || historyZoneCount > 0) {
        if (statusText) {
            statusText.innerText = totalZones > 0
                ? `Đang có ${filteredReports.length} cảnh báo (${totalZones} vùng nguy hiểm)`
                : `Đang có ${filteredReports.length} điểm cảnh báo`;
        }
        if (statusDot) {
            statusDot.className = 'pulse-dot orange';
        }
    } else {
        if (statusText) statusText.innerText = `Hiện tại đường thông thoáng`;
        if (statusDot) {
            statusDot.className = 'pulse-dot green';
        }
    }
}

// 8. Quản lý Bộ lọc sự cố
function toggleFilterMenu(e) {
    if (e) e.stopPropagation();
    const menu = document.getElementById('filterMenu');
    if (menu) {
        menu.classList.toggle('show');
    }
}

// Render các Vùng Điểm Đen Kẹt Xe Lịch Sử (trong 90 ngày gần nhất) khi vào Giờ Cao Điểm
function renderHistoricalTrafficZones(historyReports, activeClusters = []) {
    const rushStatus = getRushHourStatus();
    if (!rushStatus.isRush || !Array.isArray(historyReports) || historyReports.length === 0) {
        return 0;
    }

    // Gom cụm lịch sử kẹt xe trong bán kính 150m
    const historyClusters = groupReportsIntoZones(historyReports, WARNING_ZONE_RADIUS);
    let trafficZoneCount = 0;

    historyClusters.forEach((cluster) => {
        // Chỉ những khu vực có > 5 báo cáo kẹt xe (>= 6) trong 90 ngày qua mới được nhớ là "Điểm đen kẹt xe"
        if (cluster.length <= 5) return;

        let sumLat = 0, sumLng = 0;
        let minDaysAgo = 999;

        cluster.forEach(r => {
            sumLat += parseFloat(r.latitude);
            sumLng += parseFloat(r.longitude);
            const d = parseInt(r.days_ago, 10);
            if (!isNaN(d) && d < minDaysAgo) minDaysAgo = d;
        });

        const centerLat = sumLat / cluster.length;
        const centerLng = sumLng / cluster.length;

        // Quy tắc kiểm chứng hạ tầng: Nếu suốt 45 ngày (1.5 tháng) qua không còn ai báo kẹt xe tại đây,
        // khả năng đường đã được mở rộng / phân luồng lại -> bỏ qua không cảnh báo nữa
        if (minDaysAgo > 45 && minDaysAgo !== 999) return;

        // Không vẽ đè nếu vị trí này đang có sẵn 1 Vùng Cảnh Báo Trực Tiếp (> 5 điểm đang diễn ra)
        const isOverlappingActiveZone = activeClusters.some(actCluster => {
            if (actCluster.length <= 5) return false;
            const aLat = actCluster.reduce((s, item) => s + parseFloat(item.latitude), 0) / actCluster.length;
            const aLng = actCluster.reduce((s, item) => s + parseFloat(item.longitude), 0) / actCluster.length;
            return getDistanceInMeters(centerLat, centerLng, aLat, aLng) < 120;
        });
        if (isOverlappingActiveZone) return;

        trafficZoneCount++;

        // 1. Vẽ Vòng tròn Vùng Điểm Đen Kẹt Xe Giờ Cao Điểm (Nét đứt màu cam đỏ)
        const zoneCircle = L.circle([centerLat, centerLng], {
            radius: WARNING_ZONE_RADIUS,
            color: '#ea580c',
            fillColor: '#f97316',
            fillOpacity: 0.25,
            weight: 2.5,
            dashArray: '8, 6',
            className: 'warning-zone-circle-traffic'
        });

        // 2. Huy hiệu trung tâm Vùng Điểm Đen Kẹt Xe
        const zoneIcon = L.divIcon({
            className: 'warning-zone-badge-traffic',
            iconSize: [54, 54],
            iconAnchor: [27, 27],
            html: `
                <div class="zone-badge-inner" style="border: 2px dashed #fed7aa;">
                    <span class="zone-badge-emoji">⏰</span>
                    <span class="zone-badge-count">${cluster.length}</span>
                </div>
            `
        });

        const centerMarker = L.marker([centerLat, centerLng], { icon: zoneIcon });

        const lastTimeText = (minDaysAgo === 0 || minDaysAgo === 999)
            ? 'hôm nay'
            : `${minDaysAgo} ngày trước`;

        const sampleAddress = cluster[0].address_text || 'Nút giao thường xuyên ùn tắc';

        const popupHtml = `
            <div style="font-size: 0.9rem; line-height: 1.45; min-width: 250px; max-width: 285px;">
                <div style="margin-bottom: 6px; display:flex; align-items:center; justify-content:space-between; gap:6px;">
                    <span style="background: rgba(249, 115, 22, 0.22); color: #fb923c; padding: 3px 9px; border-radius: 6px; font-weight: 700; font-size: 0.74rem;">
                        ⏰ ĐIỂM ĐEN KẸT XE GIỜ CAO ĐIỂM
                    </span>
                    <span style="color:#a1a1aa; font-size:0.75rem;">Bán kính ${WARNING_ZONE_RADIUS}m</span>
                </div>
                <b style="color: white; font-size: 0.95rem; display: block; margin-bottom: 4px;">
                    ${sampleAddress}
                </b>
                <p style="margin: 4px 0 8px 0; color: #d4d4d8; font-size: 0.82rem;">
                    Khu vực này đã ghi nhận <b style="color:#fb923c;">${cluster.length} lượt báo cáo ùn tắc</b> trong <b>90 ngày qua</b> (lần gần nhất: <b>${lastTimeText}</b>).
                </p>
                <div style="padding: 8px; background: rgba(249, 115, 22, 0.15); border: 1px solid rgba(249, 115, 22, 0.4); border-radius: 8px; color: #fdba74; font-size: 0.79rem;">
                    ⚠️ <b>Cảnh báo ${rushStatus.label}:</b> Đây là điểm đen thường xuyên kẹt xe vào khung giờ này. Khuyến cáo chọn lộ trình thay thế!
                </div>
            </div>
        `;

        zoneCircle.bindPopup(popupHtml);
        centerMarker.bindPopup(popupHtml);

        markersLayer.addLayer(zoneCircle);
        markersLayer.addLayer(centerMarker);
    });

    return trafficZoneCount;
}

// Render danh sách Markers & Vùng Cảnh Báo lên bản đồ
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

    // Gom nhóm các điểm đang diễn ra gần nhau trong bán kính 150m
    const clusters = groupReportsIntoZones(filteredReports, WARNING_ZONE_RADIUS);
    let zoneCount = 0;

    clusters.forEach((cluster, idx) => {
        // Khi có nhiều hơn 5 cảnh báo (> 5, tức từ 6 cảnh báo trở lên) ở cùng khu vực -> chuyển thành Vùng vòng tròn cảnh báo
        if (cluster.length > 5) {
            zoneCount++;
            addWarningZoneCluster(cluster, idx);
        } else {
            cluster.forEach(r => addSingleReportMarker(r));
        }
    });

    // 1. Tự động hiển thị Vùng Rốn Ngập Lịch Sử (30 ngày) nếu trời đang mưa
    let historyFloodZoneCount = 0;
    if (window.currentFilter === 'all' || window.currentFilter === 'ngap_nuoc') {
        historyFloodZoneCount = renderHistoricalFloodZones(window.floodHistoryReports || [], clusters);
    }

    // 2. Tự động hiển thị Vùng Điểm Đen Kẹt Xe (90 ngày) nếu đang trong Giờ Cao Điểm
    let historyTrafficZoneCount = 0;
    if (window.currentFilter === 'all' || window.currentFilter === 'ket_xe') {
        historyTrafficZoneCount = renderHistoricalTrafficZones(window.trafficHistoryReports || [], clusters);
    }

    const totalZones = zoneCount + historyFloodZoneCount + historyTrafficZoneCount;

    if (filteredReports.length > 0 || totalZones > 0) {
        if (statusText) {
            statusText.innerText = totalZones > 0
                ? `Đang có ${filteredReports.length} cảnh báo (${totalZones} vùng nguy hiểm)`
                : `Đang có ${filteredReports.length} điểm cảnh báo`;
        }
        if (statusDot) {
            statusDot.className = 'pulse-dot orange';
        }
    } else {
        if (statusText) statusText.innerText = `Hiện tại đường thông thoáng`;
        if (statusDot) {
            statusDot.className = 'pulse-dot green';
        }
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

// 9. Di chuyển bản đồ đến điểm sự cố khi bấm trong danh sách Tin mới
function focusMarker(lat, lng) {
    if (isNaN(lat) || isNaN(lng)) return;

    // Nếu điểm này đang nằm trong một vùng cảnh báo gộp (> 5 điểm), tự động tách vùng đó ra để mở đúng popup
    if (window.currentReports) {
        let filteredReports = window.currentReports;
        if (window.currentFilter === 'ngap_nuoc') {
            filteredReports = window.currentReports.filter(r => (r.type === 'ngap_nuoc' || r.report_type === 'flood'));
        } else if (window.currentFilter === 'ket_xe') {
            filteredReports = window.currentReports.filter(r => (r.type === 'ket_xe' || r.report_type === 'traffic'));
        }
        const clusters = groupReportsIntoZones(filteredReports, WARNING_ZONE_RADIUS);
        clusters.forEach((cluster, idx) => {
            if (cluster.length > 5) {
                const found = cluster.some(r => Math.abs(parseFloat(r.latitude) - lat) < 0.0001 && Math.abs(parseFloat(r.longitude) - lng) < 0.0001);
                if (found && window.expandedZoneIndex !== idx) {
                    window.expandedZoneIndex = idx;
                    renderMarkers(window.currentReports);
                }
            }
        });
    }

    map.flyTo([lat, lng], 17, { animate: true, duration: 1.2 });
    if (typeof closeSubPanel === 'function') closeSubPanel();
    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.classList.remove('open');
    const overlay = document.getElementById('sidebarOverlay');
    if (overlay) overlay.classList.remove('active');

    setTimeout(() => {
        markersLayer.eachLayer(layer => {
            if (typeof layer.getLatLng === 'function') {
                const pos = layer.getLatLng();
                if (pos && Math.abs(pos.lat - lat) < 0.0001 && Math.abs(pos.lng - lng) < 0.0001) {
                    layer.openPopup();
                }
            }
        });
    }, 600);
}

// 10. Bình chọn / Xác nhận điểm sự cố qua API
async function voteReport(id, type) {
    if (!isLoggedIn) {
        alert("Vui lòng đăng nhập để bình chọn xác nhận thông tin!");
        const sidebar = document.getElementById('sidebar');
        if (sidebar && !sidebar.classList.contains('open')) {
            if (typeof toggleSidebar === 'function') toggleSidebar();
        }
        if (typeof openSubPanel === 'function') {
            openSubPanel('Đăng nhập tài khoản', 'login');
        }
        return;
    }

    const fd = new FormData();
    fd.append('report_id', id);
    fd.append('vote_type', type);

    try {
        const res = await fetch('api/vote.php', { method: 'POST', body: fd });
        const json = await res.json();
        if (json.status === 'success') {
            if (typeof showToast === 'function') showToast(json.message || "Đã ghi nhận bình chọn của bạn!", true);
            loadReports();
        } else {
            if (typeof showToast === 'function') showToast(json.message || "Lỗi gửi bình chọn!", false);
        }
    } catch (err) {
        if (typeof showToast === 'function') showToast("Đã gửi bình chọn!", true);
        loadReports();
    }
}

// 11. Khởi chạy khi toàn bộ các file JS đã sẵn sàng
document.addEventListener('DOMContentLoaded', () => {
    locateUser(true);
    loadReports();
    if (typeof loadWeatherData === 'function') {
        loadWeatherData(savedLat, savedLng);
    }
    setInterval(loadReports, 25000);
});
