/**
 * Quản lý Bảng thông báo khẩn cấp & bảo trì tức thì (Modal Popup)
 * Hiển thị tức thời cho người dùng khi có thông báo URGENT, MAINTENANCE, CRITICAL chưa đọc.
 */

const URGENT_PRIORITIES = ['URGENT', 'MAINTENANCE', 'CRITICAL'];

export function checkUrgentBroadcasts({ state, notifications = [], renderNav = () => {} } = {}) {
  const urgentDialog = document.querySelector('#urgent-broadcast-dialog');
  if (!urgentDialog) return;

  // Không làm gián đoạn nếu người dùng đang mở hộp thoại thao tác khác
  const actionDialog = document.querySelector('#action-dialog');
  if (actionDialog && actionDialog.open) return;

  // Nếu người dùng đang chủ động xem trang Hộp thư thông báo, không chặn giao diện bằng popup
  if (state && state.page === 'notifications') {
    if (urgentDialog.open) urgentDialog.close();
    return;
  }

  const pendingUrgent = notifications.filter(n => {
    const isUnread = !n.isRead && !n.read_at && !n.readAt;
    const prio = String(n.priority || '').toUpperCase();
    const isUrgent = URGENT_PRIORITIES.includes(prio);
    const isDismissed = sessionStorage.getItem('dismissed_urgent_' + n.id) === '1';
    return isUnread && isUrgent && !isDismissed;
  });

  if (!pendingUrgent.length) {
    if (urgentDialog.open) urgentDialog.close();
    return;
  }

  // Chọn thông báo ưu tiên cao nhất / mới nhất
  const target = pendingUrgent[0];
  const prio = String(target.priority || 'URGENT').toUpperCase();

  urgentDialog.className = 'app-dialog urgent-dialog priority-' + prio.toLowerCase();

  const overlineEl = urgentDialog.querySelector('#urgent-modal-overline');
  if (overlineEl) {
    if (prio === 'MAINTENANCE') {
      overlineEl.textContent = 'THÔNG BÁO BẢO TRÌ HỆ THỐNG';
    } else if (prio === 'CRITICAL') {
      overlineEl.textContent = 'CẢNH BÁO HỆ THỐNG NGHIÊM TRỌNG';
    } else {
      overlineEl.textContent = 'THÔNG BÁO KHẨN CẤP';
    }
  }

  const titleEl = urgentDialog.querySelector('#urgent-broadcast-title');
  if (titleEl) titleEl.textContent = target.title || 'Thông báo hệ thống';

  const contentEl = urgentDialog.querySelector('#urgent-broadcast-content');
  if (contentEl) contentEl.textContent = target.body || target.message || '';

  const timeEl = urgentDialog.querySelector('#urgent-broadcast-time');
  if (timeEl) {
    const rawTime = target.createdAt || target.created_at || '';
    timeEl.textContent = rawTime ? `Thời gian: ${rawTime}` : '';
  }

  const badgeEl = urgentDialog.querySelector('#urgent-broadcast-badge');
  if (badgeEl) {
    badgeEl.textContent = prio;
    badgeEl.className = 'badge ' + (prio === 'MAINTENANCE' ? 'warn' : 'danger');
  }

  const dismissBtn = urgentDialog.querySelector('#btn-dismiss-urgent-broadcast');
  if (dismissBtn) {
    dismissBtn.onclick = async () => {
      sessionStorage.setItem('dismissed_urgent_' + target.id, '1');
      target.isRead = true;
      target.read_at = new Date().toISOString();
      target.readAt = target.read_at;
      target.status = 'Đã đọc';

      urgentDialog.close();
      renderNav();

      if (target.id) {
        try {
          await fetch(`/api/notifications/${encodeURIComponent(target.id)}/read`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({})
          });
        } catch (e) {
          console.warn('Failed to mark notification read:', e);
        }
      }

      setTimeout(() => checkUrgentBroadcasts({ state, notifications, renderNav }), 200);
    };
  }

  if (!urgentDialog.open) {
    urgentDialog.showModal();
  }
}
