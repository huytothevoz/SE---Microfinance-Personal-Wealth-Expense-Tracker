/**
 * WealthFlow HTTP Client Adapter
 * Standardized Fetch wrapper for RESTful API communication.
 * Automatically injects headers, Idempotency-Key for mutating requests,
 * unwraps standard response envelopes, and manages 401 token refresh.
 */

let refreshPromise = null;

export function generateUUID() {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID();
  }
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}

export async function httpClient(endpoint, options = {}) {
  const {
    method = 'GET',
    data,
    params,
    headers = {},
    retryOnAuth = true,
    requireIdempotency = ['POST', 'PUT', 'DELETE'].includes(method.toUpperCase())
  } = options;

  let url = endpoint.startsWith('/') ? endpoint.slice(1) : endpoint;
  if (!url.startsWith('api/')) {
    url = `api/${url}`;
  }

  if (params && Object.keys(params).length > 0) {
    const query = new URLSearchParams();
    for (const [key, value] of Object.entries(params)) {
      if (value !== undefined && value !== null && value !== '') {
        query.append(key, String(value));
      }
    }
    const queryString = query.toString();
    if (queryString) {
      url += (url.includes('?') ? '&' : '?') + queryString;
    }
  }

  const reqHeaders = {
    'X-WealthFlow': '1',
    ...headers
  };

  if (data !== undefined && data !== null) {
    reqHeaders['Content-Type'] = 'application/json';
  }

  if (requireIdempotency && !reqHeaders['Idempotency-Key']) {
    reqHeaders['Idempotency-Key'] = generateUUID();
  }

  let response;
  try {
    response = await fetch(url, {
      method,
      credentials: 'same-origin',
      headers: reqHeaders,
      body: data !== undefined && data !== null ? JSON.stringify(data) : undefined,
      cache: 'no-store'
    });
  } catch {
    throw new Error('Không kết nối được máy chủ. Vui lòng kiểm tra kết nối mạng và thử lại.');
  }

  // Handle 401 Unauthorized with automatic refresh token flow
  if (response.status === 401 && retryOnAuth && !url.includes('auth/login') && !url.includes('auth/refresh')) {
    if (!refreshPromise) {
      refreshPromise = httpClient('api/auth/refresh', {
        method: 'POST',
        data: {},
        retryOnAuth: false,
        requireIdempotency: false
      }).finally(() => {
        refreshPromise = null;
      });
    }

    try {
      await refreshPromise;
      return httpClient(endpoint, { ...options, retryOnAuth: false });
    } catch {
      if (typeof window !== 'undefined' && !window.location.pathname.endsWith('index.html')) {
        window.location.replace('index.html?auth=login');
      }
      const authErr = new Error('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
      authErr.status = 401;
      throw authErr;
    }
  }

  // Parse JSON response
  let payload;
  try {
    payload = await response.json();
  } catch {
    throw new Error('Phản hồi từ máy chủ không hợp lệ. Vui lòng kiểm tra lại dịch vụ.');
  }

  if (!response.ok || !payload.success) {
    const message = payload.error?.message || 'Có lỗi xảy ra khi xử lý yêu cầu.';
    const error = new Error(message);
    error.status = response.status;
    error.code = payload.error?.code || 'UNKNOWN_ERROR';
    error.fields = payload.error?.fieldErrors || {};
    throw error;
  }

  return payload.data;
}

export function formatErrorMessage(error) {
  if (!error) return 'Có lỗi không xác định xảy ra.';
  const fields = Object.values(error.fields || {});
  return fields.length ? fields.join(' ') : error.message;
}
