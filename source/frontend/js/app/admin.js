/**
 * WealthFlow Admin & System Settings Controller
 * Manages admin dashboard, user locks, default categories, broadcasts, notifications, and profile settings.
 */
import { loadView } from './view-loader.js';
import { adminUsers, adminOverview, categories, notifications } from '../data/live-data.js?v=wealth-ui-2';
import { icon, safe } from './ui-core.js';
import { reportBarChart } from './reports.js';

export async function renderAdminOverviewPage() {
  const rawHtml = await loadView('admin-overview');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  if (adminOverview && typeof adminOverview.totalUsers === 'number') {
    const totalEl = temp.querySelector('#admin-total-users');
    if (totalEl) totalEl.textContent = Number(adminOverview.totalUsers).toLocaleString('vi-VN');
    const activeEl = temp.querySelector('#admin-active-users');
    if (activeEl) activeEl.textContent = Number(adminOverview.activeUsers).toLocaleString('vi-VN');
    const newEl = temp.querySelector('#admin-new-users');
    if (newEl) newEl.textContent = Number(adminOverview.newUsersThisMonth).toLocaleString('vi-VN');
    const lockedEl = temp.querySelector('#admin-locked-users');
    if (lockedEl) lockedEl.textContent = Number(adminOverview.lockedUsers).toLocaleString('vi-VN');
  }

  const chartContainer = temp.querySelector('#admin-activity-chart');
  if (chartContainer) {
    chartContainer.innerHTML = reportBarChart();
  }

  return temp.innerHTML;
}

export async function renderAdminUsersPage() {
  const rawHtml = await loadView('admin-users');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const tbody = temp.querySelector('#admin-users-table-body');
  if (tbody) {
    if (!adminUsers.length) {
      tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:24px;color:#68778a;">Chưa có tài khoản người dùng nào hoặc đang tải dữ liệu…</td></tr>`;
    } else {
      tbody.innerHTML = adminUsers.map(u => {
        const isLocked = String(u.status).toUpperCase() === 'LOCKED';
        const idEnc = encodeURIComponent(String(u.id));
        return `
        <tr>
          <td>
            <div class="table-name">
              <span class="table-icon">${icon('user')}</span>
              <span>${safe(u.fullName || u.name)}<small>${safe(u.email)} · #${safe(u.id)}</small></span>
            </div>
          </td>
          <td>${safe(String(u.createdAt || '').slice(0, 10))}</td>
          <td>${safe(u.baseCurrency || 'VND')}</td>
          <td>
            <span class="badge ${isLocked ? 'danger' : 'success'}">${safe(u.status)}</span>
          </td>
          <td>
            <button class="text-action" data-action="live:${isLocked ? 'unlock' : 'lock'}:adminUsers:${idEnc}">
              ${isLocked ? 'Mở khóa' : 'Khóa tài khoản'}
            </button>
          </td>
        </tr>
      `;
      }).join('');
    }
  }

  return temp.innerHTML;
}

async function adminFetch(endpoint, options = {}) {
  const headers = {
    Accept: 'application/json',
    'X-WealthFlow': '1',
    ...(options.headers || {})
  };
  if (options.body && !headers['Content-Type']) {
    headers['Content-Type'] = 'application/json';
  }
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  if (csrf) headers['X-CSRF-Token'] = csrf;

  const res = await fetch(endpoint, {
    credentials: 'same-origin',
    ...options,
    headers
  });

  let json = null;
  try {
    json = await res.json();
  } catch {
    throw new Error(`Máy chủ phản hồi không hợp lệ (${res.status}).`);
  }

  if (!res.ok || json?.success === false) {
    const msg = json?.error?.message || json?.message || `Lỗi HTTP ${res.status}`;
    const err = new Error(msg);
    err.status = res.status;
    err.fields = json?.error?.fieldErrors || {};
    throw err;
  }
  return json;
}

export async function renderAdminCategoriesPage() {
  const rawHtml = await loadView('admin-categories');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const cardList = temp.querySelector('#admin-category-card-list');
  if (!cardList) return temp.innerHTML;

  try {
    const res = await adminFetch('/api/admin/default-categories');
    const defaultCats = res?.data?.categories || [];

    if (!defaultCats.length) {
      cardList.innerHTML = `
        <div class="empty-state" style="grid-column: 1 / -1; text-align: center; padding: 32px;">
          <p>Hiện chưa có danh mục mặc định nào trong hệ thống.</p>
        </div>
      `;
    } else {
      cardList.innerHTML = defaultCats.map(c => `
        <div class="mini-panel" style="display:flex; flex-direction:column; justify-content:space-between;">
          <div>
            <div class="status-row" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
              <h3 style="margin:0; font-size:1.05rem;">${safe(c.name)}</h3>
              <div style="display:flex; gap:6px;">
                <span class="badge ${c.categoryType === 'INCOME' ? 'blue' : 'neutral'}">
                  ${c.categoryType === 'INCOME' ? 'Thu nhập' : 'Chi tiêu'}
                </span>
                <span class="badge ${c.isActive ? '' : 'danger'}">
                  ${c.isActive ? 'Áp dụng' : 'Tạm ngưng'}
                </span>
              </div>
            </div>
            <p style="color:var(--text-muted, #64748b); font-size:0.875rem; margin:0 0 16px;">
              ${c.isActive ? 'Khởi tạo mặc định cho người dùng mới khi đăng ký.' : 'Đang tạm ngưng: Không khởi tạo cho người dùng mới.'}
            </p>
          </div>
          <div style="display:flex; gap:8px; align-items:center; padding-top:12px; border-top:1px solid var(--border-color, #e2e8f0);">
            <button class="btn btn--outline btn-sm" data-action="admin-category-edit" data-id="${c.id}" data-name="${safe(c.name)}" data-type="${c.categoryType}" data-active="${c.isActive ? '1' : '0'}">
              Điều chỉnh
            </button>
            <button class="btn btn--outline btn-sm" data-action="admin-category-toggle" data-id="${c.id}" data-active="${c.isActive ? '1' : '0'}">
              ${c.isActive ? 'Tạm ngưng' : 'Kích hoạt'}
            </button>
            <button class="btn btn--outline btn-sm" data-action="admin-category-delete" data-id="${c.id}" data-name="${safe(c.name)}" style="color:var(--danger, #ef4444); margin-left:auto;">
              Xóa
            </button>
          </div>
        </div>
      `).join('');
    }
  } catch (err) {
    cardList.innerHTML = `
      <div class="empty-state" style="grid-column: 1 / -1; color: var(--danger, #ef4444); padding: 24px;">
        <p>Không thể tải danh mục hệ thống: ${safe(err.message)}</p>
      </div>
    `;
  }

  return temp.innerHTML;
}

export function handleAdminAction(action, trigger, { dialog, toast, reload }) {
  if (!action.startsWith('admin-')) return false;

  if (action === 'admin-category-add') {
    document.querySelector('#modal-overline').textContent = 'KHỞI TẠO CHO NGƯỜI DÙNG MỚI';
    document.querySelector('#modal-title').textContent = 'Thêm danh mục mẫu hệ thống';
    document.querySelector('#modal-content').innerHTML = `
      <form id="admin-category-add-form">
        <p class="form-hint">Danh mục này sẽ tự động được khởi tạo cho mọi tài khoản người dùng mới khi đăng ký. Các tài khoản và dữ liệu giao dịch hiện tại không bị ảnh hưởng.</p>
        <div class="field">
          <label for="admin-cat-name">Tên danh mục *</label>
          <input id="admin-cat-name" name="name" type="text" maxlength="100" placeholder="Ví dụ: Lương thưởng, Du lịch..." required autofocus>
        </div>
        <div class="field">
          <label for="admin-cat-type">Loại danh mục *</label>
          <select id="admin-cat-type" name="categoryType" required>
            <option value="EXPENSE">Chi tiêu (EXPENSE)</option>
            <option value="INCOME">Thu nhập (INCOME)</option>
          </select>
        </div>
        <p id="admin-cat-feedback" role="status" aria-live="polite" style="color:var(--danger, #ef4444); font-size:0.875rem; margin-top:8px;"></p>
        <div class="modal-footer">
          <button type="button" class="btn btn--outline" data-close-modal>Hủy</button>
          <button type="submit" class="btn btn--primary">Thêm danh mục</button>
        </div>
      </form>
    `;
    const form = document.querySelector('#admin-category-add-form');
    form.addEventListener('submit', async e => {
      e.preventDefault();
      const submit = form.querySelector('[type=submit]');
      const feedback = form.querySelector('#admin-cat-feedback');
      const name = form.elements.namedItem('name').value.trim();
      const categoryType = form.elements.namedItem('categoryType').value;
      if (!name) { feedback.textContent = 'Vui lòng nhập tên danh mục.'; return; }

      submit.disabled = true;
      feedback.textContent = 'Đang lưu danh mục…';
      try {
        await adminFetch('/api/admin/default-categories', {
          method: 'POST',
          body: JSON.stringify({ name, categoryType })
        });
        dialog.close();
        toast('Đã thêm danh mục mẫu thành công.');
        reload();
      } catch (err) {
        feedback.textContent = err.message;
      } finally {
        submit.disabled = false;
      }
    });
    dialog.showModal();
    return true;
  }

  if (action === 'admin-category-edit') {
    const id = trigger.dataset.id;
    const currentName = trigger.dataset.name || '';
    const currentType = trigger.dataset.type || 'EXPENSE';
    const currentActive = trigger.dataset.active === '1';

    document.querySelector('#modal-overline').textContent = 'ĐIỀU CHỈNH DANH MỤC MẪU';
    document.querySelector('#modal-title').textContent = `Sửa danh mục: ${safe(currentName)}`;
    document.querySelector('#modal-content').innerHTML = `
      <form id="admin-category-edit-form">
        <p class="form-hint">Điều chỉnh thông tin danh mục mẫu. Lưu ý: Không làm ảnh hưởng tới các danh mục hoặc giao dịch cá nhân đã tạo trước đây.</p>
        <div class="field">
          <label for="admin-cat-name">Tên danh mục *</label>
          <input id="admin-cat-name" name="name" type="text" maxlength="100" value="${safe(currentName)}" required>
        </div>
        <div class="field">
          <label for="admin-cat-type">Loại danh mục *</label>
          <select id="admin-cat-type" name="categoryType" required>
            <option value="EXPENSE" ${currentType === 'EXPENSE' ? 'selected' : ''}>Chi tiêu (EXPENSE)</option>
            <option value="INCOME" ${currentType === 'INCOME' ? 'selected' : ''}>Thu nhập (INCOME)</option>
          </select>
        </div>
        <div class="field">
          <label for="admin-cat-active">Trạng thái áp dụng *</label>
          <select id="admin-cat-active" name="isActive" required>
            <option value="1" ${currentActive ? 'selected' : ''}>Đang áp dụng (Khởi tạo cho tài khoản mới)</option>
            <option value="0" ${!currentActive ? 'selected' : ''}>Tạm ngưng (Không khởi tạo cho tài khoản mới)</option>
          </select>
        </div>
        <p id="admin-cat-feedback" role="status" aria-live="polite" style="color:var(--danger, #ef4444); font-size:0.875rem; margin-top:8px;"></p>
        <div class="modal-footer">
          <button type="button" class="btn btn--outline" data-close-modal>Hủy</button>
          <button type="submit" class="btn btn--primary">Lưu thay đổi</button>
        </div>
      </form>
    `;
    const form = document.querySelector('#admin-category-edit-form');
    form.addEventListener('submit', async e => {
      e.preventDefault();
      const submit = form.querySelector('[type=submit]');
      const feedback = form.querySelector('#admin-cat-feedback');
      const name = form.elements.namedItem('name').value.trim();
      const categoryType = form.elements.namedItem('categoryType').value;
      const isActive = form.elements.namedItem('isActive').value === '1';
      if (!name) { feedback.textContent = 'Vui lòng nhập tên danh mục.'; return; }

      submit.disabled = true;
      feedback.textContent = 'Đang cập nhật…';
      try {
        await adminFetch(`/api/admin/default-categories/${encodeURIComponent(id)}`, {
          method: 'PUT',
          body: JSON.stringify({ name, categoryType, isActive })
        });
        dialog.close();
        toast('Đã cập nhật danh mục mẫu thành công.');
        reload();
      } catch (err) {
        feedback.textContent = err.message;
      } finally {
        submit.disabled = false;
      }
    });
    dialog.showModal();
    return true;
  }

  if (action === 'admin-category-toggle') {
    const id = trigger.dataset.id;
    const active = trigger.dataset.active === '1';
    void (async () => {
      try {
        await adminFetch(`/api/admin/default-categories/${encodeURIComponent(id)}`, {
          method: 'PUT',
          body: JSON.stringify({ isActive: !active })
        });
        toast(active ? 'Đã tạm ngưng danh mục mẫu.' : 'Đã kích hoạt áp dụng danh mục mẫu.');
        reload();
      } catch (err) {
        toast(`Lỗi khi đổi trạng thái: ${err.message}`, 'error');
      }
    })();
    return true;
  }

  if (action === 'admin-category-delete') {
    const id = trigger.dataset.id;
    const name = trigger.dataset.name || '';
    document.querySelector('#modal-overline').textContent = 'XÓA DANH MỤC MẪU';
    document.querySelector('#modal-title').textContent = `Xóa danh mục: ${safe(name)}`;
    document.querySelector('#modal-content').innerHTML = `
      <form id="admin-category-delete-form">
        <p class="form-hint" style="color:var(--danger, #ef4444);">
          Bạn có chắc chắn muốn xóa danh mục mẫu <strong>${safe(name)}</strong>?<br>
          Danh mục này sẽ bị xóa khỏi hệ thống khởi tạo mặc định. Các tài khoản người dùng đã có sẽ không bị ảnh hưởng.
        </p>
        <p id="admin-cat-feedback" role="status" aria-live="polite" style="color:var(--danger, #ef4444); font-size:0.875rem; margin-top:8px;"></p>
        <div class="modal-footer">
          <button type="button" class="btn btn--outline" data-close-modal>Hủy</button>
          <button type="submit" class="btn btn--primary btn--danger" style="background:var(--danger, #ef4444);">Xác nhận xóa</button>
        </div>
      </form>
    `;
    const form = document.querySelector('#admin-category-delete-form');
    form.addEventListener('submit', async e => {
      e.preventDefault();
      const submit = form.querySelector('[type=submit]');
      const feedback = form.querySelector('#admin-cat-feedback');
      submit.disabled = true;
      feedback.textContent = 'Đang xóa danh mục…';
      try {
        await adminFetch(`/api/admin/default-categories/${encodeURIComponent(id)}`, {
          method: 'DELETE'
        });
        dialog.close();
        toast('Đã xóa danh mục mẫu khỏi hệ thống.');
        reload();
      } catch (err) {
        feedback.textContent = err.message;
      } finally {
        submit.disabled = false;
      }
    });
    dialog.showModal();
    return true;
  }

  if (action === 'admin-config') {
    document.querySelector('#modal-overline').textContent = 'QUẢN TRỊ THAM SỐ VẬN HÀNH';
    document.querySelector('#modal-title').textContent = 'Điều chỉnh tham số hệ thống';
    document.querySelector('#modal-content').innerHTML = `
      <form id="admin-config-edit-form">
        <p class="form-hint">Cập nhật các tham số vận hành chung áp dụng trên toàn bộ ứng dụng WealthFlow.</p>
        
        <div class="field">
          <label for="admin-cfg-threshold">Ngưỡng cảnh báo ngân sách (%) *</label>
          <input id="admin-cfg-threshold" name="BUDGET_WARNING_THRESHOLD_PERCENT" type="number" min="50" max="95" step="1" required>
          <small class="form-hint" style="margin-top:2px; font-size:0.75rem;">Giới hạn: 50% - 95%. Kích hoạt cảnh báo Vàng khi người dùng chi tiêu đạt mức này.</small>
        </div>

        <div class="field">
          <label for="admin-cfg-jwt-ttl">Thời gian sống Token xác thực (Phút) *</label>
          <input id="admin-cfg-jwt-ttl" name="JWT_ACCESS_TOKEN_TTL_MINUTES" type="number" min="15" max="1440" step="1" required>
          <small class="form-hint" style="margin-top:2px; font-size:0.75rem;">Giới hạn: 15 - 1440 phút (24 giờ).</small>
        </div>

        <div class="field">
          <label for="admin-cfg-csv-rows">Giới hạn số dòng đối soát xuất CSV *</label>
          <input id="admin-cfg-csv-rows" name="CSV_EXPORT_MAX_ROWS" type="number" min="100" max="50000" step="100" required>
          <small class="form-hint" style="margin-top:2px; font-size:0.75rem;">Giới hạn: 100 - 50.000 dòng trên mỗi tệp xuất.</small>
        </div>

        <div class="field">
          <label for="admin-cfg-loan-days">Số ngày nhắc hạn khoản vay trước hạn *</label>
          <input id="admin-cfg-loan-days" name="LOAN_REMINDER_DAYS_BEFORE" type="number" min="1" max="30" step="1" required>
          <small class="form-hint" style="margin-top:2px; font-size:0.75rem;">Giới hạn: 1 - 30 ngày.</small>
        </div>

        <p id="admin-config-feedback" role="status" aria-live="polite" style="color:var(--danger, #ef4444); font-size:0.875rem; margin-top:8px;"></p>
        <div class="modal-footer">
          <button type="button" class="btn btn--outline" data-close-modal>Hủy</button>
          <button type="submit" class="btn btn--primary" id="btn-save-config">Lưu cấu hình</button>
        </div>
      </form>
    `;

    const form = document.querySelector('#admin-config-edit-form');
    const feedback = form.querySelector('#admin-config-feedback');

    // Nạp dữ liệu hiện tại vào các ô input
    adminFetch('/api/admin/system-config').then(res => {
      const cfg = res?.data?.configs || {};
      if (form.elements['BUDGET_WARNING_THRESHOLD_PERCENT']) form.elements['BUDGET_WARNING_THRESHOLD_PERCENT'].value = cfg.BUDGET_WARNING_THRESHOLD_PERCENT || 80;
      if (form.elements['JWT_ACCESS_TOKEN_TTL_MINUTES']) form.elements['JWT_ACCESS_TOKEN_TTL_MINUTES'].value = cfg.JWT_ACCESS_TOKEN_TTL_MINUTES || 60;
      if (form.elements['CSV_EXPORT_MAX_ROWS']) form.elements['CSV_EXPORT_MAX_ROWS'].value = cfg.CSV_EXPORT_MAX_ROWS || 5000;
      if (form.elements['LOAN_REMINDER_DAYS_BEFORE']) form.elements['LOAN_REMINDER_DAYS_BEFORE'].value = cfg.LOAN_REMINDER_DAYS_BEFORE || 3;
    }).catch(err => {
      feedback.textContent = `Không thể tải cấu hình: ${err.message}`;
    });

    form.addEventListener('submit', async e => {
      e.preventDefault();
      const submit = form.querySelector('[type=submit]');
      submit.disabled = true;
      feedback.textContent = 'Đang lưu cấu hình…';
      feedback.style.color = 'var(--text-muted, #64748b)';

      const payload = {
        BUDGET_WARNING_THRESHOLD_PERCENT: Number(form.elements['BUDGET_WARNING_THRESHOLD_PERCENT'].value),
        JWT_ACCESS_TOKEN_TTL_MINUTES: Number(form.elements['JWT_ACCESS_TOKEN_TTL_MINUTES'].value),
        CSV_EXPORT_MAX_ROWS: Number(form.elements['CSV_EXPORT_MAX_ROWS'].value),
        LOAN_REMINDER_DAYS_BEFORE: Number(form.elements['LOAN_REMINDER_DAYS_BEFORE'].value),
      };

      try {
        await adminFetch('/api/admin/system-config', {
          method: 'PUT',
          body: JSON.stringify(payload)
        });
        dialog.close();
        toast('Đã cập nhật tham số vận hành hệ thống thành công.');
        reload();
      } catch (err) {
        feedback.style.color = 'var(--danger, #ef4444)';
        feedback.textContent = err.message;
      } finally {
        submit.disabled = false;
      }
    });

    dialog.showModal();
    return true;
  }

  if (action === 'admin-broadcast-add' || action === 'broadcast-add') {
    document.querySelector('#modal-overline').textContent = 'PHÁT THÔNG BÁO TOÀN HỆ THỐNG';
    document.querySelector('#modal-title').textContent = 'Tạo thông báo phát thanh';
    document.querySelector('#modal-content').innerHTML = `
      <form id="admin-broadcast-add-form">
        <p class="form-hint">Thông báo này sẽ được phát thanh tức thời tới toàn bộ người dùng đang hoạt động (ACTIVE) trong hệ thống với thời gian $O(1)$.</p>
        
        <div class="field">
          <label for="admin-bc-title">Tiêu đề thông báo * <span style="font-size:0.75rem; color:var(--text-muted, #64748b);">(tối đa 255 ký tự)</span></label>
          <input id="admin-bc-title" name="title" type="text" maxlength="255" placeholder="Ví dụ: Bảo trì máy chủ định kỳ..." required autofocus>
        </div>

        <div class="field">
          <label for="admin-bc-priority">Mức độ ưu tiên *</label>
          <select id="admin-bc-priority" name="priority" required>
            <option value="INFO">INFO (Thông tin thường)</option>
            <option value="WARNING">WARNING (Cảnh báo chú ý)</option>
            <option value="URGENT">URGENT (Khẩn cấp)</option>
            <option value="MAINTENANCE">MAINTENANCE (Bảo trì hệ thống)</option>
            <option value="CRITICAL">CRITICAL (Nghiêm trọng)</option>
          </select>
        </div>

        <div class="field">
          <label for="admin-bc-body">Nội dung chi tiết * <span style="font-size:0.75rem; color:var(--text-muted, #64748b);">(tối đa 2000 ký tự)</span></label>
          <textarea id="admin-bc-body" name="body" rows="4" maxlength="2000" placeholder="Nhập nội dung thông báo gửi đến toàn thể người dùng..." required style="width:100%; border:1px solid var(--border-color, #cbd5e1); border-radius:6px; padding:8px 12px; font-family:inherit;"></textarea>
        </div>

        <p id="admin-bc-feedback" role="status" aria-live="polite" style="color:var(--danger, #ef4444); font-size:0.875rem; margin-top:8px;"></p>
        <div class="modal-footer">
          <button type="button" class="btn btn--outline" data-close-modal>Hủy</button>
          <button type="submit" class="btn btn--primary" id="btn-submit-broadcast">Phát thông báo ngay</button>
        </div>
      </form>
    `;

    const form = document.querySelector('#admin-broadcast-add-form');
    const feedback = form.querySelector('#admin-bc-feedback');

    form.addEventListener('submit', async e => {
      e.preventDefault();
      const submit = form.querySelector('[type=submit]');
      submit.disabled = true;
      feedback.textContent = 'Đang phát thanh thông báo…';
      feedback.style.color = 'var(--text-muted, #64748b)';

      const title = form.elements['title'].value.trim();
      const priority = form.elements['priority'].value;
      const body = form.elements['body'].value.trim();

      if (title.length > 255) {
        feedback.style.color = 'var(--danger, #ef4444)';
        feedback.textContent = 'Tiêu đề không được vượt quá 255 ký tự.';
        submit.disabled = false;
        return;
      }

      if (body.length > 2000) {
        feedback.style.color = 'var(--danger, #ef4444)';
        feedback.textContent = 'Nội dung không được vượt quá 2000 ký tự.';
        submit.disabled = false;
        return;
      }

      try {
        const res = await adminFetch('/api/admin/announcements', {
          method: 'POST',
          body: JSON.stringify({ title, priority, body })
        });
        dialog.close();
        const msg = res?.data?.message || 'Đã phát thông báo toàn hệ thống thành công.';
        toast(msg);
        reload();
      } catch (err) {
        feedback.style.color = 'var(--danger, #ef4444)';
        feedback.textContent = err.message;
      } finally {
        submit.disabled = false;
      }
    });

    dialog.showModal();
    return true;
  }

  return false;
}

export const handleAdminCategoryAction = handleAdminAction;

export async function renderAdminBroadcastPage() {
  const rawHtml = await loadView('admin-broadcasts');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const container = temp.querySelector('#admin-broadcast-history-body');
  if (container) {
    try {
      const res = await adminFetch('/api/admin/announcements');
      const announcements = res?.data?.announcements || [];
      if (!announcements.length) {
        container.innerHTML = '<div style="padding:24px; text-align:center; color:var(--text-muted, #64748b);">Chưa có thông báo phát thanh nào được gửi.</div>';
      } else {
        container.innerHTML = announcements.map(a => {
          const reason = a.reason || '';
          const isUrgent = reason.includes('URGENT') || reason.includes('CRITICAL');
          const isWarn = reason.includes('WARNING') || reason.includes('MAINTENANCE');
          const badgeClass = isUrgent ? 'danger' : isWarn ? 'warn' : 'blue';
          const iconChar = isUrgent ? '!' : isWarn ? '◷' : '✧';
          return `
            <div class="notification-item">
              <span class="notification-icon">${iconChar}</span>
              <div class="notification-copy">
                <strong>${safe(reason)}</strong>
                <small>${safe(a.created_at)} · Tác giả: ${safe(a.admin_email || ('Admin #' + a.admin_user_id))}</small>
              </div>
              <span class="badge ${badgeClass}">Đã phát thanh</span>
            </div>
          `;
        }).join('');
      }
    } catch (err) {
      container.innerHTML = `<div style="padding:16px; color:var(--danger, #ef4444); text-align:center;">Lỗi tải lịch sử phát thanh: ${safe(err.message)}</div>`;
    }
  }

  return temp.innerHTML;
}

export async function renderAdminSettingsPage() {
  const rawHtml = await loadView('admin-settings');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const thresholdEl = temp.querySelector('#cfg-budget-threshold');
  const jwtTtlEl = temp.querySelector('#cfg-jwt-ttl');
  const csvRowsEl = temp.querySelector('#cfg-csv-max-rows');
  const loanDaysEl = temp.querySelector('#cfg-loan-reminder');
  const auditBody = temp.querySelector('#admin-audit-table-body');

  try {
    const res = await adminFetch('/api/admin/system-config');
    const configs = res?.data?.configs || {};
    const auditLogs = res?.data?.auditLogs || [];

    if (thresholdEl) thresholdEl.textContent = `${configs.BUDGET_WARNING_THRESHOLD_PERCENT || 80}%`;
    if (jwtTtlEl) jwtTtlEl.textContent = `${configs.JWT_ACCESS_TOKEN_TTL_MINUTES || 60} phút`;
    if (csvRowsEl) csvRowsEl.textContent = `${Number(configs.CSV_EXPORT_MAX_ROWS || 5000).toLocaleString('vi-VN')} dòng`;
    if (loanDaysEl) loanDaysEl.textContent = `Trước hạn ${configs.LOAN_REMINDER_DAYS_BEFORE || 3} ngày`;

    if (auditBody) {
      if (!auditLogs.length) {
        auditBody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:16px; color:var(--text-muted, #64748b);">Chưa có hoạt động quản trị</td></tr>';
      } else {
        auditBody.innerHTML = auditLogs.map(l => `
          <tr>
            <td><small>${safe(l.created_at)}</small></td>
            <td><span class="badge ${l.action.startsWith('DELETE') ? 'danger' : l.action.startsWith('CREATE') ? 'blue' : 'neutral'}">${safe(l.action)}</span></td>
            <td><small>${safe(l.admin_email || ('Admin #' + l.admin_user_id))}</small></td>
            <td><small>${safe(l.reason || '-')}</small></td>
          </tr>
        `).join('');
      }
    }
  } catch (err) {
    if (auditBody) {
      auditBody.innerHTML = `<tr><td colspan="4" style="color:var(--danger, #ef4444); text-align:center; padding:16px;">Lỗi tải cấu hình: ${safe(err.message)}</td></tr>`;
    }
  }

  return temp.innerHTML;
}

export async function renderNotificationsPage() {
  const rawHtml = await loadView('notifications');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const inbox = temp.querySelector('#notification-inbox');
  if (inbox) {
    try {
      const res = await adminFetch('/api/notifications');
      const list = res?.data?.notifications || [];
      if (!list.length) {
        inbox.innerHTML = '<div style="padding:24px; text-align:center; color:var(--text-muted, #64748b);">Hộp thư thông báo trống.</div>';
      } else {
        inbox.innerHTML = list.map(n => {
          const isUrgent = n.priority === 'URGENT' || n.priority === 'CRITICAL';
          const isWarn = n.priority === 'WARNING' || n.priority === 'MAINTENANCE';
          const badgeClass = isUrgent ? 'danger' : isWarn ? 'warn' : 'blue';
          const iconChar = isUrgent ? '!' : isWarn ? '◷' : '✧';
          const unreadDot = !n.read_at ? '<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--primary, #2563eb); margin-left:6px;" title="Chưa đọc"></span>' : '';
          return `
            <div class="notification-item ${!n.read_at ? 'notification-unread' : ''}">
              <span class="notification-icon">${iconChar}</span>
              <div class="notification-copy">
                <strong>${safe(n.title)}${unreadDot}</strong>
                <p>${safe(n.body)}</p>
                <small>${safe(n.created_at || '')} · ${safe(n.notification_type || 'SYSTEM')}</small>
              </div>
              <span class="badge ${badgeClass}">${safe(n.priority || 'INFO')}</span>
            </div>
          `;
        }).join('');
      }
    } catch (err) {
      inbox.innerHTML = notifications.map(n => `
        <div class="notification-item">
          <span class="notification-icon">${n.type === 'danger' ? '!' : n.type === 'warn' ? '◷' : '✧'}</span>
          <div class="notification-copy">
            <strong>${safe(n.title)}</strong>
            <p>${safe(n.content)}</p>
            <small>${safe(n.time)}</small>
          </div>
          <span class="badge ${n.type === 'danger' ? 'danger' : n.type === 'warn' ? 'warn' : 'blue'}">
            ${n.type === 'danger' ? 'Khẩn' : n.type === 'warn' ? 'Cần chú ý' : 'Thông tin'}
          </span>
        </div>
      `).join('');
    }
  }

  return temp.innerHTML;
}

export async function renderSettingsPage({ currentUser }) {
  const rawHtml = await loadView('settings');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const fullnameEl = temp.querySelector('#settings-fullname');
  if (fullnameEl && currentUser) fullnameEl.textContent = safe(currentUser.fullName);

  const emailEl = temp.querySelector('#settings-email');
  if (emailEl && currentUser) emailEl.textContent = safe(currentUser.email);

  const currencyEl = temp.querySelector('#settings-currency');
  if (currencyEl && currentUser) currencyEl.textContent = safe(currentUser.baseCurrency);

  return temp.innerHTML;
}
