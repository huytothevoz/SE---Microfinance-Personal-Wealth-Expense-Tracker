import { icon, safe, USER_NAV, ADMIN_NAV, META } from './ui-core.js';
import { createPages } from './pages.js?v=wallet-refactor-20261018-5';
import { FIELDS } from './forms.js';
import { authApi, errorMessage } from '../services/auth-api.js';
import { preloadViews } from './view-loader.js';
import {
  handleWalletAction,
  handleWalletSubmit,
  isWalletAction,
  isWalletForm
} from './wallets.js?v=wallet-refactor-20261018-5';
import {
  handleTransactionAction,
  handleTransactionSubmit,
  isTransactionAction,
  isTransactionForm,
  showTransactionDetails,
  updateTransactionTable
} from './transactions.js?v=wallet-refactor-20261018-5';

let currentUser;
try {
  ({ user: currentUser } = await authApi('me'));
} catch (error) {
  if (error.status === 401) location.replace('index.html?auth=login');
  document.querySelector('#app-content').textContent = error.message;
  throw error;
}
document.querySelector('#user-menu strong').textContent = currentUser.fullName;
document.querySelector('#user-menu small').textContent = currentUser.email;
window.addEventListener('pageshow', event => { if (event.persisted) location.reload(); });
window.addEventListener('focus', async () => {
  try { await authApi('me'); }
  catch (error) { if (error.status === 401) location.replace('index.html?auth=login'); }
});
/** Application shell. Backend authorizes all protected operations. */

const state = {
  page: 'overview',
  admin: false,
  txFilter: 'all',
  search: '',
  categoryFilter: 'all',
  onlyActiveWallets: true,
  reportRange: '6m'

};
const app = document.querySelector('#app-content'); const nav = document.querySelector('#sidebar-nav'); const sidebar = document.querySelector('#sidebar'); const dialog = document.querySelector('#action-dialog');
function heading(id, buttons = '') {
  const [name, description] = META[id];
  return `<div class="page-heading">\
<div>\
<h1>\
${safe(name)}\
</h1>\
<p>\
${safe(description)}\
</p>\
</div>\
<div class="page-actions">\
${buttons}</div></div>`;

}
const btn = (label, action, style = 'primary', ico = 'plus') => `<button type="button" class="btn btn--${style} btn-sm" data-action="${action}">${icon(ico)} ${label}</button>`;
const panel = (title, subtitle, content, right = '') => `<section class="panel">\
<div class="panel-title">\
<div>\
<h2>\
${title}</h2>${subtitle ? `<p>\
${subtitle}</p>` : ''}\
</div>\
<div class="panel-tools">\
${right}</div></div>${content}</section>`;
function renderNav() {
  let groups = state.admin ? ADMIN_NAV : USER_NAV;
  nav.innerHTML = groups.map(group => `<div class="sidebar-label">\
${group.group}\
</div>\
<div class="sidebar-group">\
${group.items.map(([id, label, ico]) => `<button type="button" data-page="${id}" class="nav-link ${state.page === id ? 'is-active' : ''}" ${state.page === id ? 'aria-current="page"' : ''}>${icon(ico)}\
<span>\
${label}</span>${id === 'notifications' ? '<span class="nav-count">4</span>' : ''}</button>`).join('')}</div>`).join('') + (currentUser.role === 'ADMIN' ? `<div class="sidebar-label">QUẢN TRỊ</div>\
<div class="sidebar-group">\
<button class="nav-link" data-action="switch-role">\
${icon(state.admin ? 'user' : 'lock')}\
<span>\
${state.admin ? 'Quay về người dùng' : 'Khu vực quản trị'}\
</span>\
</button>\
</div>` : '');
  document.body.classList.toggle('admin-mode', state.admin);

}
async function navigate(page) {
  if (page.startsWith('admin-') && currentUser.role !== 'ADMIN') page = 'overview';
  state.admin = page.startsWith('admin-') && currentUser.role === 'ADMIN';
  if (!META[page]) return;
  state.page = page;
  renderNav();
  document.querySelector('#breadcrumb-name').textContent = META[page][0];
  app.setAttribute('aria-busy', 'true');
  try {
    const content = await PAGES[page]();
    if (typeof content !== 'string' || content.trim() === '') {
      throw new Error('Module trang không trả về nội dung hiển thị.');
    }
    app.innerHTML = content;
  } catch (error) {
    console.error(`Không thể hiển thị trang ${page}:`, error);
    app.innerHTML = `<section class="panel page-load-error" role="alert">
      <div class="panel-title"><div><h2>Không thể tải trang</h2>
      <p>${safe(error?.message || 'Đã xảy ra lỗi khi khởi tạo giao diện.')}</p></div></div>
      <button type="button" class="btn btn--outline btn-sm" data-page="${safe(page)}">Thử tải lại</button>
    </section>`;
  } finally {
    app.removeAttribute('aria-busy');
    closeSidebar();
    const hash = '#' + page;
    if (location.hash !== hash) history.replaceState(null, '', hash);
    window.scrollTo({ top: 0, behavior: 'instant' });
  }
}
function closeSidebar() {
  sidebar.classList.remove('is-open');
  document.querySelector('#mobile-overlay').classList.remove('is-open');
  document.querySelector('#sidebar-toggle').setAttribute('aria-expanded', 'false');

}
const { PAGES } = createPages({ state, heading, btn, panel, currentUser });
function toast(message) {
  const stack = document.querySelector('#toast-stack');
  const item = document.createElement('div');
  item.className = 'toast';
  const glyph = document.createElement('span');
  glyph.className = 'toast-symbol';
  glyph.textContent = 'ⓘ';
  const txt = document.createElement('span');
  txt.textContent = message;
  item.append(glyph, txt);
  stack.append(item);
  setTimeout(() => item.remove(), 4700);

}
function fieldHTML([name, label, type, options]) {
  const required = !['note', 'receive', 'interest'].includes(name);
  const constraint = required ? 'required' : '';
  let control;
  if (type === 'select') {
    control = `<select id="field-${safe(name)}" name="${safe(name)}" ${constraint}>${options.map(o => `<option>${safe(o)}</option>`).join('')}</select>`;
  } else if (type === 'textarea') {
    control = `<textarea id="field-${safe(name)}" name="${safe(name)}" rows="3" ${constraint} placeholder="Nhập ${safe(label.toLowerCase())}..."></textarea>`;
  } else {
    control = `<input id="field-${safe(name)}" name="${safe(name)}" type="${type}" ${type === 'number' ? 'min="0" step="any"' : ''} ${constraint} placeholder="${type === 'number' ? '0' : safe(label)}">`;
  }
  return `
    <div class="field">
      <label for="field-${safe(name)}">${safe(label)}${required ? ' *' : ''}</label>
      ${control}
    </div>
  `;
}

function openAction(action) {
  const def = FIELDS[action];
  if (!def) {
    toast('Tính năng này đang được phát triển.');
    return;
  }
  document.querySelector('#modal-overline').textContent = def.desc;
  document.querySelector('#modal-title').textContent = def.title;
  const content = document.querySelector('#modal-content');
  content.innerHTML = `
    <p class="form-hint">
      ${safe(def.note || 'Chức năng lưu đang được phát triển. Dữ liệu nhập sẽ không được ghi nhận.')}
    </p>
    ${def.fields.length ? `
      <form id="action-form">
        <div class="form-grid">
          ${def.fields.map(fieldHTML).join('')}
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn--outline btn-sm" data-close-modal>Hủy</button>
          <button type="submit" class="btn btn--primary btn-sm">Kiểm tra biểu mẫu</button>
        </div>
      </form>
    ` : `
      <div class="modal-footer">
        <button type="button" class="btn btn--primary btn-sm" data-close-modal>Đóng</button>
      </div>
    `}
  `;
  dialog.showModal();
}

function handleAction(action, actionElement) {
  if (action === 'password') { openPasswordForm(); return; }
  if (action === 'logout') {
    authApi('logout', {}).then(() => location.replace('index.html?auth=login')).catch(error => toast(errorMessage(error)));
    return;
  }
  if (action === 'switch-role') {
    if (currentUser.role !== 'ADMIN') return;
    state.admin = !state.admin;
    navigate(state.admin ? 'admin-overview' : 'overview');
    toast(state.admin ? 'Đã chuyển đến khu vực quản trị.' : 'Đã chuyển về trang cá nhân.');
    return;

  }
  if (action === 'wallet-toggle') {
    state.onlyActiveWallets = !state.onlyActiveWallets;
    navigate('wallets');
    return;

  }
  if (isWalletAction(action)) {
    handleWalletAction(action, actionElement, { state, dialog, toast, navigate });
    return;
  }
  if (isTransactionAction(action)) {
    handleTransactionAction(action, actionElement, { state, dialog, toast, navigate });
    return;
  }
  if (action === 'reconcile') {
    toast('Tính năng đối soát đang được phát triển. Chưa có kết quả đối soát được xác nhận.');
    return;

  }
  openAction(action);

}
function openPasswordForm() {
  document.querySelector('#modal-overline').textContent = 'BẢO MẬT TÀI KHOẢN';
  document.querySelector('#modal-title').textContent = 'Đổi mật khẩu';
  document.querySelector('#modal-content').innerHTML = `<form id="password-form">
    <p class="form-hint">Mật khẩu mới cần ít nhất 8 ký tự, gồm chữ hoa, chữ thường và số; tối đa 72 byte. Các phiên cũ trên mọi thiết bị sẽ được đăng xuất.</p>
    <div class="field"><label for="current-password">Mật khẩu hiện tại</label><input id="current-password" name="currentPassword" type="password" autocomplete="current-password" required></div>
    <div class="field"><label for="new-password">Mật khẩu mới</label><input id="new-password" name="newPassword" type="password" autocomplete="new-password" minlength="8" required></div>
    <div class="field"><label for="confirm-password">Nhập lại mật khẩu mới</label><input id="confirm-password" name="passwordConfirmation" type="password" autocomplete="new-password" required></div>
    <p id="password-feedback" role="status" aria-live="polite"></p>
    <div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">Đổi mật khẩu</button></div>
  </form>`;
  document.querySelector('#password-form').addEventListener('submit', async event => {
    event.preventDefault();
    const form = event.currentTarget;
    const submit = form.querySelector('[type="submit"]');
    const feedback = form.querySelector('#password-feedback');
    submit.disabled = true;
    feedback.textContent = 'Đang đổi mật khẩu…';
    try {
      const result = await authApi('change-password', Object.fromEntries(new FormData(form)));
      form.reset();
      dialog.close();
      toast(result.message);
    } catch (error) {
      if (error.status === 401) location.replace('index.html?auth=login');
      feedback.textContent = errorMessage(error);
    } finally { submit.disabled = false; }
  });
  dialog.showModal();
}
document.addEventListener('click', event => {
  const page = event.target.closest('[data-page]');
  if (page) {
    navigate(page.dataset.page);
    return;

  }
  const detail = event.target.closest('[data-transaction-detail]');
  if (detail) {
    showTransactionDetails(detail.dataset.transactionDetail, { dialog, toast });
    return;

  }
  const filter = event.target.closest('[data-tx-filter]');
  if (filter) {
    state.txFilter = filter.dataset.txFilter;
    navigate('transactions');
    return;

  }
  const categoryFilter = event.target.closest('[data-category-filter]');
  if (categoryFilter) {
    state.categoryFilter = categoryFilter.dataset.categoryFilter;
    navigate('categories');
    return;
  }
  const action = event.target.closest('[data-action]');
  if (action) {
    handleAction(action.dataset.action, action);
    return;

  }
  if (event.target.closest('[data-close-modal]')) dialog.close();

});
document.addEventListener('input', event => {
  if (event.target.id === 'transaction-search') {
    state.search = event.target.value;
    updateTransactionTable(state);

  }

});
document.addEventListener('submit', event => {
  if (isWalletForm(event.target)) {
    event.preventDefault();
    handleWalletSubmit(event.target, { dialog, toast, navigate });
    return;
  }
  if (isTransactionForm(event.target)) {
    event.preventDefault();
    handleTransactionSubmit(event.target, { dialog, toast, navigate });
    return;
  }
  if (event.target.id !== 'action-form') return;
  event.preventDefault();
  dialog.close();
  toast('Tính năng lưu chưa khả dụng. Không có thay đổi nào được thực hiện.');

});
dialog.addEventListener('click', event => {
  if (event.target === dialog) dialog.close();

});
document.querySelector('#modal-close').addEventListener('click', () => dialog.close());
document.querySelector('#sidebar-toggle').addEventListener('click', () => {
  const open = !sidebar.classList.contains('is-open');
  sidebar.classList.toggle('is-open', open);
  document.querySelector('#mobile-overlay').classList.toggle('is-open', open);
  document.querySelector('#sidebar-toggle').setAttribute('aria-expanded', String(open));

});
document.querySelector('#mobile-overlay').addEventListener('click', closeSidebar);
document.querySelector('#top-notifications').addEventListener('click', () => {
  if (state.admin) {
    toast('Thông báo tài chính cá nhân chỉ dành cho chủ tài khoản.');
    return;

  }
  navigate('notifications');

});
document.querySelector('#user-menu').addEventListener('click', () => navigate(state.admin ? 'admin-settings' : 'settings'));
const globalSearch = document.querySelector('#global-search'); globalSearch.addEventListener('keydown', event => {
  if (event.key === 'Enter') {
    event.preventDefault();
    if (state.admin) {
      toast('Tìm kiếm giao dịch không khả dụng ở chế độ Admin.');
      return;

    }
    state.search = globalSearch.value;
    state.txFilter = 'all';
    navigate('transactions');

  }

});
document.querySelector('#top-search-icon').innerHTML = icon('search'); document.querySelector('#bell-icon').innerHTML = icon('bell');
window.addEventListener('hashchange', () => {
  const page = decodeURIComponent(location.hash.slice(1));
  if (META[page]) {
    const targetAdmin = page.startsWith('admin-');
    if (targetAdmin !== state.admin) state.admin = targetAdmin;
    navigate(page);

  }

});
preloadViews(Object.keys(META));
const initial = decodeURIComponent(location.hash.slice(1)); if (initial.startsWith('admin-')) state.admin = true; navigate(META[initial] ? initial : 'overview');
