import { icon, safe, money, USER_NAV, ADMIN_NAV, META } from './ui-core.js?v=settings-ui-1';
import { createPages } from './pages.js?v=wallet-ui-9';
import { FIELDS } from './forms.js';
import { transactions, notifications, loadLiveData } from '../data/live-data.js';
import { liveAction } from './live-pages.js';
// WealthFlow Live Frontend Patch v1 — no financial fixture fallback.
import { authApi, errorMessage } from '../services/auth-api.js';
import { preloadViews } from './view-loader.js';
import { createWalletFeature } from './wallet-feature.js?v=wallet-ui-10';

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
  notifFilter: 'all',
  search: '',
  onlyActiveWallets: true,
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
  document.querySelector('#modal-overline').textContent = 'CHI TIẾT GIAO DỊCH';
  document.querySelector('#modal-title').textContent = t.name;
  document.querySelector('#modal-content').innerHTML = `
    <dl class="detail-grid">
      <div>
        <dt>Mã giao dịch</dt>
        <dd>${safe(t.id)}</dd>
      </div>
      <div>
        <dt>Trạng thái</dt>
        <dd>${t.status === 'DRAFT' ? 'Bản nháp (chưa ghi sổ)' : 'Posted (đã ghi sổ)'}</dd>
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
        <dt>Tỷ giá snapshot</dt>
        <dd>${safe(t.rate)}</dd>
      </div>
    </dl>
    <hr class="modal-rule">
    <p class="description">${safe(t.details)}</p>
    <div class="hint-box">
      <strong>Bút toán Nợ/Có:</strong> xem thông tin ghi sổ và các giao dịch điều chỉnh liên quan trong lịch sử.
    </div>
    <div class="modal-footer">
      <button class="btn btn--outline btn-sm" data-close-modal>Đóng</button>
      ${t.status === 'POSTED' ? `<button class="btn btn--primary btn-sm" data-action="reversal">Xem quy trình đảo giao dịch</button>` : ''}
    </div>
  `;
  dialog.showModal();
}
function exportTransactionsCSV() {
  const rows = [
    [
      'Mã giao dịch',
      'Tên giao dịch',
      'Danh mục',
      'Ví',
      'Ngày',
      'Số tiền VND',
      'Trạng thái'

    ],
    ...transactions.map(t => [
      t.id,
      t.name,
      t.category,
      t.wallet,
      t.date,
      String(t.amount),
      t.status

    ])

  ];
  const neutralize = v => {
    const text = String(v).replace(/^[\s\u0000-\u001f]+/, '');
    return /^[=+\-@]/.test(text) ? "'" + String(v) : String(v);

  };
  const csv = rows.map(row => row.map(col => '"' + neutralize(col).replaceAll('"', '""') + '"').join(',')).join('\r\n');
  const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = 'wealthflow_giao_dich.csv';
  anchor.click();
  setTimeout(() => URL.revokeObjectURL(url), 2000);
  toast('Đã xuất danh sách giao dịch hiện hiển thị.');

}
function applyTransactionSearch() {
  const filtered = transactions.filter(t => (state.txFilter === 'all' || t.type === state.txFilter) && [t.name, t.id, t.category, t.wallet].join(' ').toLocaleLowerCase('vi').includes(state.search.toLocaleLowerCase('vi')));
  const target = document.querySelector('#transaction-table');
  if (target) target.innerHTML = txTable(filtered);

}
function handleAction(action, trigger) {
 if (liveAction(action, { dialog, toast, reload: () => navigate(state.page) })) return;
 if (walletFeature.handleAction(action, trigger)) return;
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
    toast('Tính năng đối soát đang được phát triển. Chưa có kết quả đối soát được xác nhận.');
    return;

  }
  if (action === 'reversal') {
    dialog.close();
    toast('Chức năng đảo giao dịch đang được phát triển. Giao dịch gốc không bị thay đổi.');
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
  const detail = event.target.closest('[data-detail]');
  if (detail) {
    showDetails(detail.dataset.detail);
    return;

  }
  const filter = event.target.closest('[data-tx-filter]');
  if (filter) {
    state.txFilter = filter.dataset.txFilter;
    navigate('transactions');
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
  if (event.target.id === 'transaction-search') {
    state.search = event.target.value;
    applyTransactionSearch();

  }

});
document.addEventListener('submit', async event => {
  if (['wallet-create-form', 'wallet-adjust-form', 'wallet-edit-form', 'wallet-archive-form', 'wallet-delete-form'].includes(event.target.id)) {
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
