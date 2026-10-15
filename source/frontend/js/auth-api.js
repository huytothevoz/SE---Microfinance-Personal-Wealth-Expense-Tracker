let refreshRequest;

export async function authApi(route, data, retry = true) {
  let response;
  try {
    response = await fetch(`api/auth/${route}`, {
      method: data === undefined ? 'GET' : 'POST',
      credentials: 'same-origin',
      headers: data === undefined ? {} : { 'Content-Type': 'application/json', 'X-WealthFlow': '1' },
      body: data === undefined ? undefined : JSON.stringify(data),
      cache: 'no-store'
    });
  } catch {
    throw new Error('Không kết nối được máy chủ. Vui lòng kiểm tra kết nối và thử lại.');
  }
  if (response.status === 401 && retry && ['me', 'change-password'].includes(route)) {
    refreshRequest ??= authApi('refresh', {}, false).finally(() => { refreshRequest = null; });
    await refreshRequest;
    return authApi(route, data, false);
  }
  let payload;
  try { payload = await response.json(); }
  catch { throw new Error('Máy chủ chưa được cấu hình API. Vui lòng chạy script cài đặt XAMPP.'); }
  if (!response.ok || !payload.success) {
    const error = new Error(payload.error?.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
    error.fields = payload.error?.fieldErrors || {};
    error.status = response.status;
    throw error;
  }
  return payload.data;
}

export function errorMessage(error) {
  const fields = Object.values(error.fields || {});
  return fields.length ? fields.join(' ') : error.message;
}
