import { icon, safe, money, USER_NAV, ADMIN_NAV, META, formatMoneyInput } from './ui-core.js?v=wealth-ui-2';
import { createPages } from './pages.js?v=wealth-ui-2';
import { FIELDS } from './forms.js';
import { transactions, notifications, loadLiveData } from '../data/live-data.js?v=wealth-ui-2';
import { liveAction } from './live-pages.js?v=wealth-ui-2';
// WealthFlow Live Frontend Patch v1 — no financial fixture fallback.
import { authApi, errorMessage } from '../services/auth-api.js';
import { preloadViews } from './view-loader.js';
import { createWalletFeature } from './wallet-feature.js?v=wallet-ui-13';
import { handleGoalAction } from './goals.js?v=wealth-ui-2';
import { handleGoldAction } from './gold.js?v=wealth-ui-2';
import { handleLoanAction } from './loans.js?v=wealth-ui-2';

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
  txFilter: ['ALL'],
  notifFilter: 'all',
  search: '',
  onlyActiveWallets: true,
  walletTypeFilter: ['ALL'],
  reportRange: '6m',
  walletItems: [],
  walletSummary: null,
  walletLoading: false,
  walletError: '',
  walletRateWarning: '',
  walletRatesSynced: false

};
const app = document.querySelector('#app-content'); const nav = document.querySelector('#sidebar-nav'); const sidebar = document.querySelector('#sidebar'); const dialog = document.querySelector('#action-dialog');
let walletFeature;
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
  const unreadNotifs = notifications.filter(n => !n.isRead && !n.read_at && !n.readAt).length;
  nav.innerHTML = groups.map(group => `<div class="sidebar-label">\
${group.group}\
</div>\
<div class="sidebar-group">\
${group.items.map(([id, label, ico]) => `<button type="button" data-page="${id}" class="nav-link ${state.page === id ? 'is-active' : ''}" ${state.page === id ? 'aria-current="page"' : ''}>${icon(ico)}\
<span>\
${label}</span>${id === 'notifications' ? (unreadNotifs ? `<span class="nav-count">${unreadNotifs}</span>` : '') : ''}</button>`).join('')}</div>`).join('') + (currentUser.role === 'ADMIN' ? `<div class="sidebar-label">QUẢN TRỊ</div>\
<div class="sidebar-group">\
<button class="nav-link" data-action="switch-role">\
${icon(state.admin ? 'user' : 'lock')}\
<span>\
${state.admin ? 'Quay về người dùng' : 'Khu vực quản trị'}\
</span>\
</button>\
</div>` : '');
  const dot = document.querySelector('.notify-dot');
  if (dot) dot.style.display = (!state.admin && unreadNotifs > 0) ? '' : 'none';
  document.body.classList.toggle('admin-mode', state.admin);

}
let navigateVersion = 0;
async function navigate(page) {
  const thisNavigation = ++navigateVersion;
  if (page.startsWith('admin-') && currentUser.role !== 'ADMIN') page = 'overview';
  state.admin = page.startsWith('admin-') && currentUser.role === 'ADMIN';
  if (!META[page]) return;
  state.page = page;
  renderNav();
  document.querySelector('#breadcrumb-name').textContent = META[page][0];
  // Account-scoped fetch: empty accounts must stay empty, never use fixtures.
  app.innerHTML = '<div class="panel" role="status">Đang đồng bộ dữ liệu từ máy chủ…</div>';
  await loadLiveData({ admin: currentUser.role === 'ADMIN' });
  if (thisNavigation !== navigateVersion) return;
  renderNav();
  if (page === 'wallets') await walletFeature.load();
  if (thisNavigation !== navigateVersion) return;
  app.innerHTML = await PAGES[page]();
  closeSidebar();
  const hash = '#' + page;
  if (location.hash !== hash) history.replaceState(null, '', hash);
  window.scrollTo({ top: 0, behavior: 'instant' });
}
function closeSidebar() {
  sidebar.classList.remove('is-open');
  document.querySelector('#mobile-overlay').classList.remove('is-open');
  document.querySelector('#sidebar-toggle').setAttribute('aria-expanded', 'false');

}
const { PAGES, txTable } = createPages({ state, heading, btn, panel, currentUser });
walletFeature = createWalletFeature({ state, dialog, toast, render: () => { if (state.page === 'wallets') void PAGES.wallets().then(html => { if (state.page === 'wallets') app.innerHTML = html; }); } });
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

function showDetails(id) {
  const t = transactions.find(row => row.id === id);
  if (!t) return;
  document.querySelector('#modal-overline').textContent = '';
  document.querySelector('#modal-title').textContent = 'Chi tiết giao dịch';
  document.querySelector('#modal-content').innerHTML = `
    <dl class="detail-grid">
      <div>
        <dt>Mã giao dịch</dt>
        <dd>${safe(t.id)}</dd>
      </div>
      <div>
        <dt>Danh mục</dt>
        <dd>${safe(t.category)}</dd>
      </div>
      <div>
        <dt>Ngày giao dịch</dt>
        <dd>${safe(t.date)}</dd>
      </div>
      <div>
        <dt>Ví liên quan</dt>
        <dd>${safe(t.wallet)}</dd>
      </div>
      <div>
        <dt>Số tiền</dt>
        <dd>${money(Math.abs(t.amount))}</dd>
      </div>
      <div>
        <dt>Tiền tệ</dt>
        <dd>${safe(t.currency)}</dd>
      </div>
      <div>
        <dt>Tỷ giá lúc ghi nhận</dt>
        <dd>${safe(t.rate)}</dd>
      </div>
    </dl>
    <div class="modal-footer">
      <button class="btn btn--outline btn-sm" data-close-modal>Đóng</button>
    </div>
  `;
  dialog.showModal();
}
function exportTransactionsCSV() {
  if (liveAction('live:export-csv:reports', { dialog, toast, reload: () => navigate(state.page) })) return;
}
function applyTransactionSearch() {
  const filters = (Array.isArray(state.txFilter) ? state.txFilter : [state.txFilter || 'ALL'])
    .map(value => String(value).toUpperCase())
    .filter(value => value !== 'ALL');
  const filtered = transactions.filter(t => (!filters.length || filters.includes(String(t.type).toUpperCase()))
    && [t.name, t.id, t.category, t.wallet].join(' ').toLocaleLowerCase('vi').includes(state.search.toLocaleLowerCase('vi')));
  const target = document.querySelector('#transaction-table');
  if (target) target.innerHTML = txTable(filtered);

}
function openTransactionFilter() {
  const options = [['ALL','Tất cả'],['INCOME','Thu nhập'],['EXPENSE','Chi tiêu'],['TRANSFER','Chuyển tiền'],['SAVINGS_GOAL','Mục tiêu tiết kiệm']];
  const selected = (Array.isArray(state.txFilter) ? state.txFilter : [state.txFilter || 'ALL'])
    .map(value => String(value).toUpperCase());
  const checked = selected.length ? selected : ['ALL'];
  document.querySelector('#modal-overline').textContent = '';
  document.querySelector('#modal-title').textContent = 'Lọc theo loại giao dịch';
  document.querySelector('#modal-content').innerHTML = `<form id="transaction-filter-form">
    <div class="wallet-filter-options">${options.map(([value,label]) => `<label class="wallet-filter-option">
      <input type="checkbox" name="transactionTypeFilter" value="${value}" ${checked.includes(value) ? 'checked' : ''}>
      <span>${label}</span>
    </label>`).join('')}</div>
    <div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">Áp dụng</button></div>
  </form>`;
  dialog.showModal();
}
function handleTransactionFilterChange(target) {
  if (target.name !== 'transactionTypeFilter') return;
  const form = target.closest('#transaction-filter-form');
  if (!form) return;
  const all = form.querySelector('input[value="ALL"]');
  const types = [...form.querySelectorAll('input[name="transactionTypeFilter"]:not([value="ALL"])')];
  if (target.value === 'ALL' && target.checked) types.forEach(input => { input.checked = false; });
  else if (target.value !== 'ALL' && target.checked) all.checked = false;
  if (!types.some(input => input.checked)) all.checked = true;
}
function handleAction(action, trigger) {
  if (liveAction(action, { dialog, toast, reload: () => navigate(state.page) })) return;
  if (walletFeature.handleAction(action, trigger)) return;
  if (handleGoalAction(action, { dialog, toast, reload: () => navigate(state.page) })) return;
  if (handleGoldAction(action, { dialog, toast, reload: () => navigate(state.page) })) return;
  if (handleLoanAction(action, { dialog, toast, reload: () => navigate(state.page) })) return;
  if (action === 'transaction-filter') { openTransactionFilter(); return; }
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
  if (action === 'export') {
    exportTransactionsCSV();
    return;

  }
  if (action === 'reconcile') {
    liveAction('live:reconcile:reports', { dialog, toast, reload: () => navigate(state.page) });
    return;

  }
  if (action === 'reversal') {
    dialog.close();
    toast('Vui lòng chọn nút "Đảo bút toán" tại giao dịch muốn đảo.');
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
    if (page.matches('a')) event.preventDefault();
    navigate(page.dataset.page);
    return;

  }
  const detail = event.target.closest('[data-detail]');
  if (detail) {
    showDetails(detail.dataset.detail);
    return;

  }
  const notifFilter = event.target.closest('[data-notif-filter]');
  if (notifFilter) {
    state.notifFilter = notifFilter.dataset.notifFilter;
    navigate('notifications');
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
  if (event.target.matches('[data-money-input]')) {
    event.target.value = formatMoneyInput(event.target.value, event.target.dataset.moneyDecimals === 'true');
  }
  if (event.target.id === 'transaction-search') {
    state.search = event.target.value;
    applyTransactionSearch();

  }

});
document.addEventListener('change', event => {
  walletFeature.handleChange(event.target);
  handleTransactionFilterChange(event.target);
});
document.addEventListener('submit', async event => {
  if (event.target.id === 'transaction-filter-form') {
    event.preventDefault();
    const selected = [...event.target.querySelectorAll('input[name="transactionTypeFilter"]:checked')]
      .map(input => input.value)
      .filter(value => value !== 'ALL');
    state.txFilter = selected.length ? selected : ['ALL'];
    dialog.close();
    navigate('transactions');
    return;
  }
  if (['wallet-filter-form', 'wallet-create-form', 'wallet-adjust-form', 'wallet-edit-form', 'wallet-archive-form', 'wallet-delete-form'].includes(event.target.id)) {
    event.preventDefault();
    await walletFeature.handleSubmit(event.target);
    return;
  }
  if (event.target.id !== 'action-form') return;
  event.preventDefault();
  dialog.close();
  toast('Tính năng lưu chưa khả dụng. Không có thay đổi nào được thực hiện.');

});
document.querySelector('#modal-close').addEventListener('click', () => dialog.close());
// Only explicit controls close a form; moving/clicking outside never discards changes.
dialog.addEventListener('cancel', event => event.preventDefault());
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
    state.txFilter = ['ALL'];
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
const initial = decodeURIComponent(location.hash.slice(1));
const defaultPage = currentUser.role === 'ADMIN' ? 'admin-overview' : 'overview';
const startPage = META[initial] ? initial : defaultPage;
if (startPage.startsWith('admin-')) state.admin = true;
navigate(startPage);
