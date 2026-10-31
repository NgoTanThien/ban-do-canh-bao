// =========================================================================
// File: public/js/firebase-auth.js
// Đăng nhập & Đăng ký bằng tài khoản Google qua Firebase Auth SDK
// =========================================================================

// Import các hàm cần thiết từ Firebase Web SDK v10 (Google CDN)
import { initializeApp } from "https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js";
import { getAnalytics, isSupported as isAnalyticsSupported } from "https://www.gstatic.com/firebasejs/10.12.0/firebase-analytics.js";
import { 
    getAuth, 
    GoogleAuthProvider, 
    signInWithPopup 
} from "https://www.gstatic.com/firebasejs/10.12.0/firebase-auth.js";

// Cấu hình Firebase Web App của dự án
const firebaseConfig = {
  apiKey: "AIzaSyA_bTtrkXNsKC6yj2SlbRMvOn2HJWTmz64",
  authDomain: "ban-do-canh-bao.firebaseapp.com",
  projectId: "ban-do-canh-bao",
  storageBucket: "ban-do-canh-bao.firebasestorage.app",
  messagingSenderId: "934944876968",
  appId: "1:934944876968:web:7f6c4cfda16bebd23bd59d",
  measurementId: "G-RBMWR912JY"
};

// Khởi tạo Firebase App
const app = initializeApp(firebaseConfig);

// Khởi tạo Analytics nếu môi trường hỗ trợ
isAnalyticsSupported().then(supported => {
    if (supported) {
        getAnalytics(app);
    }
}).catch(() => {});

// Khởi tạo Firebase Auth & Google Auth Provider
const auth = getAuth(app);
const provider = new GoogleAuthProvider();

// Luôn hiển thị hộp thoại chọn tài khoản Google (Select Account)
provider.setCustomParameters({
    prompt: 'select_account'
});

// Lưu vào window để dễ debug và gọi từ mọi script khác
window.firebaseApp = app;
window.firebaseAuth = auth;
window.firebaseGoogleProvider = provider;

/**
 * Hàm kích hoạt đăng nhập / đăng ký bằng Google qua Firebase
 * @param {'login'|'register'} mode 
 */
window.loginWithFirebaseGoogle = async function(mode = 'login') {
    const notify = (msg, isSuccess = true) => {
        if (typeof showToast === 'function') {
            showToast(msg, isSuccess);
        } else {
            console.log(isSuccess ? '[Firebase Success]' : '[Firebase Error]', msg);
        }
    };

    notify('Đang mở bảng chọn tài khoản Google...', true);

    try {
        // Mở popup chọn tài khoản Google qua Firebase Auth
        const result = await signInWithPopup(auth, provider);
        const user = result.user;

        // Lấy Firebase ID Token có chữ ký số của Google
        const idToken = await user.getIdToken();

        notify('Đang xác thực tài khoản ' + (user.displayName || user.email) + '...', true);

        // Gửi token và thông tin người dùng lên Backend API để tạo phiên và lưu DB
        const payload = {
            id_token: idToken,
            email: user.email || '',
            name: user.displayName || '',
            avatar: user.photoURL || '',
            google_id: user.uid || ''
        };

        let response = null;
        if (typeof Api !== 'undefined' && typeof Api.googleLogin === 'function') {
            response = await Api.googleLogin(payload);
        } else {
            const res = await fetch('api/google_login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status !== 'success') {
                throw new Error(data.message || 'Lỗi xác thực Google từ máy chủ!');
            }
            response = data.data;
        }

        notify(mode === 'register' ? 'Đăng ký bằng Google thành công!' : 'Đăng nhập Google thành công!', true);

        // Điều hướng thông minh: Admin vào admin_dashboard, còn lại về trang chủ
        setTimeout(() => {
            if (response && response.role === 'admin') {
                window.location.href = 'index.php?route=admin_dashboard';
            } else if (window.location.search.includes('route=login') || window.location.search.includes('route=register') || window.location.search.includes('route=admin_login')) {
                window.location.href = 'index.php?route=home';
            } else {
                window.location.reload();
            }
        }, 600);

        return response;
    } catch (error) {
        console.error('[Firebase Auth Error Details]', error);

        let errorMsg = 'Đăng nhập Google thất bại!';
        let isConfigIssue = false;

        switch (error.code) {
            case 'auth/popup-closed-by-user':
                errorMsg = 'Bạn đã đóng cửa sổ đăng nhập Google.';
                break;
            case 'auth/cancelled-popup-request':
                errorMsg = 'Yêu cầu đăng nhập trước đó đã bị hủy.';
                break;
            case 'auth/popup-blocked':
                errorMsg = 'Trình duyệt đã chặn cửa sổ bật lên. Vui lòng cho phép popup để tiếp tục!';
                break;
            case 'auth/unauthorized-domain':
                isConfigIssue = true;
                errorMsg = 'Tên miền chưa được cấp phép trong Firebase! Vui lòng vào Firebase Console -> Authentication -> Settings -> Thêm "localhost" vào Authorized domains.';
                break;
            case 'auth/operation-not-allowed':
                isConfigIssue = true;
                errorMsg = 'Google Sign-In chưa được bật trong Firebase Console -> Authentication -> Sign-in method!';
                break;
            default:
                if (error.message) {
                    errorMsg = error.message;
                }
                break;
        }

        notify(errorMsg, false);

        // Nếu gặp lỗi cấu hình Firebase Domain hoặc cần thử nghiệm dev -> Mở modal chọn tài khoản dự phòng
        if (isConfigIssue) {
            const modal = document.getElementById('googleAuthModal');
            if (modal) {
                setTimeout(() => {
                    modal.style.display = 'flex';
                }, 800);
            }
        }

        throw error;
    }
};

