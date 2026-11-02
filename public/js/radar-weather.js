// ==========================================================================
// 2. RADAR BÁN KÍNH GPS, WEB PUSH & THỜI TIẾT (public/js/radar-weather.js)
// ==========================================================================

let userCircle = null; // Vòng tròn bán kính an toàn quanh người dùng
const alertRadius = 500;
let enableProximityAlert = localStorage.getItem('enableProximityAlert') !== 'false';
let notifiedReportIds = new Set();
let closestAlertPos = null;

// 1. Thuật toán Haversine tính khoảng cách GPS chính xác (trả về mét)
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

// 2. Cập nhật Vòng tròn Radar bán kính cảnh báo an toàn quanh GPS
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
            interactive: false, // BẮT BUỘC: Cho phép click xuyên qua để chấm điểm báo cáo trên bản đồ
            className: 'radar-circle'
        }).addTo(map);
    }
}

// 3. Quét toàn bộ sự cố hiện tại xem có điểm nào lọt vào bán kính của user không
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

// 4. Gửi thông báo đẩy qua Web Notification API
function sendBrowserNotification(r, dist) {
    const isFlood = (r.type === 'ngap_nuoc' || r.report_type === 'flood');
    const typeText = isFlood ? '🌊 Điểm ngập nước' : '🚦 Điểm kẹt xe';
    const distText = dist < 1000 ? `${dist}m` : `${(dist / 1000).toFixed(1)}km`;
    const title = `⚠️ Cảnh báo: Có ${typeText} cách bạn ${distText}!`;
    const bodyText = `${r.address_text || r.title || 'Gần vị trí của bạn'}: ${r.description || 'Hãy cẩn thận lộ trình di chuyển.'}`;

    if (navigator.vibrate) {
        navigator.vibrate([200, 100, 200]);
    }

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

// 5. Cập nhật thanh Banner cảnh báo khẩn cấp nổi trên bản đồ
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

// 6. TÍCH HỢP THỜI TIẾT (OPEN-METEO) & RADAR MÂY MƯA (RAINVIEWER)
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

let rainRadarLayer = null;
let isRadarActive = false;

async function toggleRadarLayer() {
    const btn = document.getElementById('btn-radar');
    
    if (isRadarActive) {
        if (rainRadarLayer && map.hasLayer(rainRadarLayer)) {
            map.removeLayer(rainRadarLayer);
        }
        isRadarActive = false;
        if (btn) btn.classList.remove('active');
        showToast('Đã tắt radar mây mưa', true);
        return;
    }

    try {
        if (btn) btn.classList.add('active');
        showToast('Đang kết nối vệ tinh radar thời tiết...', true);

        const res = await fetch('https://api.rainviewer.com/public/weather-maps.json');
        const data = await res.json();
        
        if (!data || !data.radar || !data.radar.past || data.radar.past.length === 0) {
            throw new Error('Dữ liệu radar hiện chưa sẵn sàng');
        }

        const latestFrame = data.radar.past[data.radar.past.length - 1];
        const tileUrl = `${data.host}${latestFrame.path}/256/{z}/{x}/{y}/2/1_1.png`;

        if (rainRadarLayer && map.hasLayer(rainRadarLayer)) {
            map.removeLayer(rainRadarLayer);
        }

        rainRadarLayer = L.tileLayer(tileUrl, {
            tileSize: 256,
            opacity: 0.65,
            zIndex: 50,
            maxNativeZoom: 7,
            maxZoom: 19,
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