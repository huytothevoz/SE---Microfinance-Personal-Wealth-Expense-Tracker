import { icon, safe, money, USER_NAV, ADMIN_NAV, META, formatMoneyInput, heading, btn, panel } from './ui-core.js?v=wealth-ui-2';
import { createPages } from './pages.js?v=wealth-ui-2';
import { transactions, notifications, loadLiveData } from '../data/live-data.js?v=wealth-ui-2';
import { liveAction } from './live-pages.js?v=wealth-ui-2';
// WealthFlow Live Frontend Patch v1 — no financial fixture fallback.
import { authApi, errorMessage } from '../services/auth-api.js';
import { preloadViews } from './view-loader.js';
import { createWalletFeature } from './wallet-feature.js?v=wallet-ui-13';
import { handleGoalAction } from './goals.js?v=wealth-ui-3';
import { handleGoldAction } from './gold.js?v=wealth-ui-2';
import { handleLoanAction } from './loans.js?v=wealth-ui-2';
import { runReconciliationAudit } from './reports.js';
import { ReportApi } from '../services/report-api.js';
import { handleAdminAction } from './admin.js';
import { checkUrgentBroadcasts } from './urgent-broadcast.js';
import { showTransactionDetails, applyTransactionSearch, openTransactionFilter, handleTransactionFilterChange, handleTransactionFilterSubmit } from './transactions.js';
import { handleSecurityAction } from './profile-security.js';
import { handleStubAction, handleStubSubmit } from './stub-actions.js';

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
  if (dot) dot.style.display = unreadNotifs > 0 ? '' : 'none';
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
  checkUrgentBroadcasts({ state, notifications, renderNav });
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
function handleAction(action, trigger) {
  if (liveAction(action, { dialog, toast, reload: () => navigate(state.page) })) return;
  if (walletFeature.handleAction(action, trigger)) return;
  if (handleGoalAction(action, { dialog, toast, reload: () => navigate(state.page) })) return;
  if (handleGoldAction(action, { dialog, toast, reload: () => navigate(state.page) })) return;
  if (handleLoanAction(action, { dialog, toast, reload: () => navigate(state.page) })) return;
  if (handleAdminAction(action, trigger, { dialog, toast, reload: () => navigate(state.page) })) return;
  if (handleSecurityAction(action, { dialog, toast })) return;
  if (action === 'transaction-filter') { openTransactionFilter({ state, dialog }); return; }
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
    void (async () => {
      try {
        toast('Đang chuẩn bị tệp CSV từ máy chủ...');
        await ReportApi.downloadCsv();
        toast('Tải xuống tệp CSV thành công!');
      } catch (err) {
        toast(err.message || 'Lỗi khi tải xuất tệp CSV.', 'error');
      }
    })();
    return;
  }
  if (action === 'reconcile') {
    void (async () => {
      try {
        toast('Đang kiểm tra đối soát với sổ cái kép...');
        const res = await runReconciliationAudit();
        if (res.isAllMatched) {
          toast('Đối soát thành công: Toàn bộ ví khớp đúng với sổ cái kép!');
        } else {
          toast(`Cảnh báo an ninh: Phát hiện ${res.mismatchedWallets} ví bị lệch số dư!`, 'error');
        }
      } catch (err) {
        toast(err.message || 'Lỗi khi chạy đối soát sổ cái.', 'error');
      }
    })();
    return;
  }
  handleStubAction(action, { dialog, toast });
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
    showTransactionDetails(detail.dataset.detail, { dialog });
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
    applyTransactionSearch({ state, txTableRenderer: txTable });
  }
});

document.addEventListener('change', event => {
  walletFeature.handleChange(event.target);
  handleTransactionFilterChange(event.target);
});

document.addEventListener('submit', async event => {
  if (handleTransactionFilterSubmit(event, { state, dialog, navigate })) return;
  if (['wallet-filter-form', 'wallet-create-form', 'wallet-adjust-form', 'wallet-edit-form', 'wallet-archive-form', 'wallet-delete-form'].includes(event.target.id)) {
    event.preventDefault();
    await walletFeature.handleSubmit(event.target);
    return;
  }
  if (handleStubSubmit(event, { dialog, toast })) return;
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
const initial = decodeURIComponent(location.hash.slice(1)); if (initial.startsWith('admin-')) state.admin = true; navigate(META[initial] ? initial : 'overview');
