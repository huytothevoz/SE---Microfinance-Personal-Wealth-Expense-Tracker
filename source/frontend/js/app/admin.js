/**
 * WealthFlow Admin & System Settings Controller
 * Manages admin dashboard, user locks, default categories, broadcasts, notifications, and profile settings.
 */
import { loadView } from './view-loader.js';
import { adminUsers, categories, notifications } from '../data/live-data.js?v=wealth-ui-2';
import { icon, safe } from './ui-core.js';
import { reportBarChart } from './reports.js';

export async function renderAdminOverviewPage() {
  const rawHtml = await loadView('admin-overview');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

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
    tbody.innerHTML = adminUsers.map(u => `
      <tr>
        <td>
          <div class="table-name">
            <span class="table-icon">${icon('user')}</span>
            <span>${u.name}<small>${u.email} · ${u.id}</small></span>
          </div>
        </td>
        <td>${u.created}</td>
        <td>${u.currency}</td>
        <td>
          <span class="badge ${u.status === 'Active' ? '' : 'danger'}">${u.status}</span>
        </td>
        <td>
          <button class="text-action" data-action="admin-user-${u.status === 'Active' ? 'lock' : 'unlock'}">
            ${u.status === 'Active' ? 'Khóa tài khoản' : 'Mở khóa'}
          </button>
        </td>
      </tr>
    `).join('');
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

export function handleAdminCategoryAction(action, trigger, { dialog, toast, reload }) {
  if (!action.startsWith('admin-category-')) return false;

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

  return false;
}

export async function renderAdminBroadcastPage() {
  return loadView('admin-broadcasts');
}

export async function renderAdminSettingsPage() {
  return loadView('admin-settings');
}

export async function renderNotificationsPage() {
  const rawHtml = await loadView('notifications');
  const temp = document.createElement('div');
  temp.innerHTML = rawHtml;

  const inbox = temp.querySelector('#notification-inbox');
  if (inbox) {
    inbox.innerHTML = notifications.map(n => `
      <div class="notification-item">
        <span class="notification-icon">${n.type === 'danger' ? '!' : n.type === 'warn' ? '◷' : '✧'}</span>
        <div class="notification-copy">
          <strong>${n.title}</strong>
          <p>${n.content}</p>
          <small>${n.time}</small>
        </div>
        <span class="badge ${n.type === 'danger' ? 'danger' : n.type === 'warn' ? 'warn' : 'blue'}">
          ${n.type === 'danger' ? 'Khẩn' : n.type === 'warn' ? 'Cần chú ý' : 'Thông tin'}
        </span>
      </div>
    `).join('');
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
