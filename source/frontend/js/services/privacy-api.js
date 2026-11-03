export const PrivacyApi = {
  async downloadAccountData() {
    let response;
    try {
      response = await fetch('api/privacy/export', {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'X-WealthFlow': '1' },
        cache: 'no-store'
      });
    } catch {
      throw new Error('Không kết nối được máy chủ. Vui lòng kiểm tra kết nối và thử lại.');
    }

    if (!response.ok) {
      let message = `Không thể xuất dữ liệu (HTTP ${response.status}).`;
      try {
        const payload = await response.json();
        message = payload.error?.message || message;
      } catch {
        // Keep the explicit HTTP error when the server did not return JSON.
      }
      throw new Error(message);
    }

    const blob = await response.blob();
    const disposition = response.headers.get('Content-Disposition') || '';
    const match = disposition.match(/filename="?([^";]+)"?/);
    const filename = match ? match[1] : `wealthflow_account_${Date.now()}.json`;
    const downloadUrl = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);
    return { filename, size: blob.size };
  }
};
