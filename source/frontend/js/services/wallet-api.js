import { authApi } from './auth-api.js';

async function request(path, options = {}, retry = true) {
  let response;
  try {
    response = await fetch(`api${path}`, {
      credentials: 'same-origin',
      cache: 'no-store',
      ...options,
      headers: {
        ...(options.body ? { 'Content-Type': 'application/json', 'X-WealthFlow': '1' } : {}),
        ...(options.headers || {})
      }
    });
  } catch {
    throw new Error('Không kết nối được máy chủ. Vui lòng thử lại.');
  }

  if (response.status === 401 && retry) {
    await authApi('refresh', {}, false);
    return request(path, options, false);
  }

  let payload;
  try {
    payload = await response.json();
  } catch {
    throw new Error('Máy chủ trả về dữ liệu không hợp lệ.');
  }
  if (!response.ok || !payload.success) {
    const error = new Error(payload.error?.message || 'Không thể thực hiện yêu cầu.');
    error.fields = payload.error?.fieldErrors || {};
    error.status = response.status;
    error.code = payload.error?.code;
    throw error;
  }
  return payload.data;
}

export const walletApi = {
  list(status = 'all') {
    return request(`/wallets?status=${encodeURIComponent(status)}`);
  },

  create(data) {
    return request('/wallets', {
      method: 'POST',
      headers: { 'Idempotency-Key': crypto.randomUUID() },
      body: JSON.stringify(data)
    });
  },

  update(id, data) {
    return request(`/wallets/${encodeURIComponent(id)}`, {
      method: 'PUT',
      body: JSON.stringify(data)
    });
  },

  adjust(id, data) {
    return request(`/wallets/${encodeURIComponent(id)}/adjustments`, {
      method: 'POST',
      headers: { 'Idempotency-Key': crypto.randomUUID() },
      body: JSON.stringify(data)
    });
  },

  archive(id) {
    return request(`/wallets/${encodeURIComponent(id)}/archive`, {
      method: 'POST',
      body: JSON.stringify({})
    });
  },

  deletePermanently(id) {
    return request(`/wallets/${encodeURIComponent(id)}`, {
      method: 'DELETE',
      headers: { 'X-WealthFlow': '1' }
    });
  },

  syncRates() {
    return request('/exchange-rates/sync', {
      method: 'POST',
      body: JSON.stringify({})
    });
  }
};
