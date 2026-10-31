// ==========================================================================
// API CLIENT - Nơi DUY NHẤT gọi fetch tới backend (thư mục api/)
// ==========================================================================

const API_BASE = 'api/';

class ApiError extends Error {
    constructor(message, status = 0, data = null) {
        super(message);
        this.name = 'ApiError';
        this.status = status; // Mã HTTP (401: chưa đăng nhập, 403: bị khóa,...)
        this.data = data;
    }
}

async function apiRequest(endpoint, { method = 'GET', body = null, query = null } = {}) {
    let url = API_BASE + endpoint;
    if (query) url += '?' + new URLSearchParams(query);

    const options = { 
        method, 
        credentials: 'same-origin', 
        headers: { 'Accept': 'application/json' } 
    };

    if (body instanceof FormData) {
        options.body = body; // Trình duyệt tự sinh multipart/form-data kèm boundary
    } else if (body !== null) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(body);
    }

    let res;
    try {
        res = await fetch(url, options);
    } catch (e) {
        throw new ApiError('Không thể kết nối máy chủ, vui lòng thử lại!', 0);
    }

    let json = null;
    try { 
        json = await res.json(); 
    } catch (e) {
        // Phản hồi không phải JSON
    }

    if (!json || json.status !== 'success') {
        throw new ApiError(
            (json && json.message) || `Lỗi máy chủ (${res.status})`, 
            res.status, 
            json ? json.data : null
        );
    }
    return json.data !== undefined ? json.data : json;
}

const Api = {
    // --- Xác thực tài khoản ---
    me:                ()                           => apiRequest('me.php'),
    login:             (account, pass, rememberMe)  => apiRequest('login.php', { method: 'POST', body: { username_or_email: account, password: pass, remember_me: !!rememberMe } }),
    googleLogin:       (data)                       => apiRequest('google_login.php', { method: 'POST', body: data }),
    register:          (data)                       => apiRequest('register.php', { method: 'POST', body: data }),
    verifyRegisterOtp: (email, otp)                 => apiRequest('register_verify.php', { method: 'POST', body: { email, otp } }),
    checkEmail:        (email)                      => apiRequest('check_email.php', { query: { email } }),
    forgotPassword:    (email)                      => apiRequest('forgot_password.php', { method: 'POST', body: { email } }),
    resetPassword:     (data)                       => apiRequest('reset_password.php', { method: 'POST', body: data }),
    logout:            ()                           => apiRequest('logout.php', { method: 'POST' }),

    // --- Báo cáo sự cố & Bản đồ ---
    reports:           (type = 'all')               => apiRequest('reports.php', { query: { type } }),
    addReport:         (formData)                   => apiRequest('reports.php', { method: 'POST', body: formData }),
    vote:              (id, voteType)               => apiRequest('vote.php', { method: 'POST', body: { report_id: id, vote_type: voteType } }),

    // --- Quản trị Admin ---
    adminUsers:        ()                           => apiRequest('admin/users.php'),
    adminUserStatus:   (userId, action)             => apiRequest('admin/user_status.php', { method: 'POST', body: { user_id: userId, action } }),
};
