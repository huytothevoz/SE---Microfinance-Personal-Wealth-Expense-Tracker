/** WealthFlow live, account-scoped page templates. No static financial figures. */
import { wallets, transactions, budgets, recurring, goals, loans, gold, notifications, categories, adminUsers, dataStatus, mutateLiveData, loadLiveData } from '../data/live-data.js?v=transaction-ui-2';
import { icon } from './ui-core.js';
import { httpClient } from '../services/http-client.js';

const ITEMS = { wallets, transactions, budgets, recurring, goals, loans, gold, notifications, categories, adminUsers };
const TITLES = { wallets: 'Ví', transactions: 'Giao dịch', categories: 'Danh mục', budgets: 'Ngân sách', recurring: 'Giao dịch định kỳ', goals: 'Mục tiêu tiết kiệm', loans: 'Vay và cho vay', gold: 'Tài sản vàng', notifications: 'Thông báo', adminUsers: 'Người dùng' };
const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const vnd = n => Number.isFinite(Number(n)) ? new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(Number(n)) : '—';
const val = (obj, ...keys) => keys.map(k => obj?.[k]).find(x => x !== null && x !== undefined && x !== '');
const count = list => list.length;
const sum = list => list.reduce((s, row) => s + Math.abs(Number(row.amount || 0)), 0);
const ownMonth = iso => String(iso ?? '').slice(0, 7);
const month = () => { const d = new Date(); return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2, '0')}`; };
const posted = () => transactions.filter(t => t.status === 'POSTED');
const TRANSACTION_TYPE_LABELS = { income: 'Thu nhập', expense: 'Chi tiêu', transfer: 'Chuyển tiền', other: 'Số dư' };
const stateOf = key => dataStatus[key]?.state || 'idle';
const ready = key => stateOf(key) === 'ready';
const loading = key => stateOf(key) === 'loading' || stateOf(key) === 'idle';
const message = (title, description) => `<div class="empty-state wf-empty"><strong>${esc(title)}</strong><p>${esc(description)}</p></div>`;
const problem = key => loading(key) ? message('Đang tải dữ liệu…', 'Đang lấy dữ liệu của tài khoản hiện tại từ máy chủ.')
  : ready(key) ? null : `<div class="wf-error" role="alert"><strong>${esc(TITLES[key] || key)}: Chưa kết nối API</strong><p>${esc(dataStatus[key]?.message || 'Chưa thể tải dữ liệu.')}</p><small>Không sử dụng dữ liệu mô phỏng để thay thế.</small></div>`;
const empty = (key, what) => message(`Chưa có ${what}`, 'Tài khoản mới bắt đầu trống. Hãy thêm dữ liệu đầu tiên để xem thống kê.');
const addButton = (key, text = `Thêm ${TITLES[key] || 'mới'}`) => `<button type="button" class="btn btn--primary btn-sm" data-action="live:create:${key}" ${(!ready(key) || !['transactions','categories','budgets','recurring'].includes(key)) ? 'disabled title="Chức năng ghi chưa được xác nhận an toàn với phiên bản backend này"' : ''}>${icon('plus')} ${esc(text)}</button>`;
const display = (x, fmt, currency='VND') => fmt === 'money' ? (Number.isFinite(Number(x)) ? new Intl.NumberFormat('vi-VN', {style:'currency',currency: ['VND','USD','EUR'].includes(String(currency)) ? String(currency) : 'VND',maximumFractionDigits: currency==='VND'?0:2}).format(Number(x)) : '—') : fmt === 'date' ? esc(String(x ?? '').slice(0, 10) || '—') : esc(x ?? '—');
const baseStyle = `<style>
.wf-empty {padding:38px 20px;line-height:1.6;text-align:center}.wf-empty p{margin:6px 0;color:var(--text-secondary,#69778a)}
.wf-error{margin:16px 0;border:1px solid #d8a9a9;border-radius:12px;padding:18px;background:rgba(190,55,55,.05)}.wf-error p{margin:8px 0}
.wf-muted{color:var(--text-secondary,#68778a)}.wf-graph{display:flex;gap:12px;height:200px;align-items:end;border-bottom:1px solid #cbd5e1;padding:10px 4px 18px}
.wf-bar-group{flex:1;display:flex;align-items:end;gap:3px;justify-content:center;height:100%;position:relative}
.wf-bar{min-width:12px;max-width:35px;flex:1;background:#16869e;border-radius:5px 5px 0 0}
.wf-bar.out{background:#ef9b71}.wf-bar-group small{position:absolute;top:100%;padding-top:5px;white-space:nowrap;font-size:10px}
.wf-progress{height:9px;border-radius:8px;background:#d7e3e9;overflow:hidden}.wf-progress i{display:block;background:#16869e;height:100%}
.wf-table-actions{white-space:nowrap}.wf-grid-actions{display:flex;flex-wrap:wrap;gap:8px}.wf-stat-note{font-size:12px;color:#68778a;margin-top:7px}
@media(max-width:640px){.wf-graph{height:150px;gap:3px}.wf-bar{min-width:5px}}
</style>`;
function table(key, columns, list = ITEMS[key]) {
  const issue = problem(key);
  if (issue) return issue;
  if (!list.length) return empty(key, TITLES[key]?.toLowerCase() || 'dữ liệu');
  return `<div class="data-table-wrap"><table class="data-table"><thead><tr>${columns.map(([label]) => `<th>${esc(label)}</th>`).join('')}<th></th></tr></thead><tbody>${list.map(row => `<tr>${columns.map(([, field, fmt]) => `<td>${display(val(row, ...field.split('|')), fmt, key==='wallets'?row.currency:'VND')}</td>`).join('')}<td class="wf-table-actions"><button type="button" class="text-action" data-action="live:detail:${key}:${encodeURIComponent(String(row.id ?? ''))}">Chi tiết</button>${key === 'recurring' && row.id && ['ACTIVE','PAUSED'].includes(String(row.status).toUpperCase()) ? `<button type="button" class="text-action" data-action="live:${String(row.status).toUpperCase() === 'ACTIVE' ? 'pause' : 'resume'}:recurring:${encodeURIComponent(String(row.id))}">${String(row.status).toUpperCase() === 'ACTIVE' ? 'Tạm dừng' : 'Tiếp tục'}</button>` : ''}</td></tr>`).join('')}</tbody></table></div>`;
}
function card(label, value, caption) { return `<div class="kpi-card"><div class="kpi-top"><span class="kpi-label">${esc(label)}</span></div><div class="kpi-number">${esc(value)}</div><div class="kpi-desc">${esc(caption)}</div></div>`; }
function monthStats() {
  if (!ready('transactions')) return null;
  const group = posted().filter(t => ownMonth(t.date) === month());
  const income = sum(group.filter(t => t.type === 'income'));
  const expense = sum(group.filter(t => t.type === 'expense'));
  return { income, expense, net: income - expense, rate: income > 0 ? (income - expense) / income * 100 : null };
}
function reports() {
  const issue = problem('transactions');
  if (issue) return issue;
  const stats = monthStats();
  const months = Array.from({ length: 6 }, (_, i) => {
    const d = new Date(); d.setDate(1); d.setMonth(d.getMonth() - (5 - i));
    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    const list = posted().filter(t => ownMonth(t.date) === key);
    return { key, income: sum(list.filter(t => t.type === 'income')), expense: sum(list.filter(t => t.type === 'expense')) };
  });
  const ceiling = Math.max(1, ...months.flatMap(m => [m.income, m.expense]));
  return `<div class="kpi-grid">${card('Thu nhập tháng',vnd(stats.income),'Không tính chuyển ví')}${card('Chi tiêu tháng',vnd(stats.expense),'Không tính chuyển ví')}${card('Dòng tiền thuần',vnd(stats.net),'Thu nhập trừ chi tiêu')}${card('Tỷ lệ tiết kiệm',stats.rate === null ? '—' : stats.rate.toFixed(1) + '%',stats.rate === null ? 'Chưa có thu nhập để tính' : 'Tính từ giao dịch đã ghi sổ')}</div>
  <section class="panel"><div class="panel-title"><h2>Thu nhập và chi tiêu · 6 tháng</h2></div><div class="wf-graph">${months.map(m => `<div class="wf-bar-group"><i class="wf-bar" style="height:${Math.max(0, m.income / ceiling * 100)}%" title="${esc(m.key)}: Thu ${vnd(m.income)}"></i><i class="wf-bar out" style="height:${Math.max(0, m.expense / ceiling * 100)}%" title="${esc(m.key)}: Chi ${vnd(m.expense)}"></i><small>${esc(m.key.slice(5))}</small></div>`).join('')}</div><p class="wf-stat-note">Xanh: thu nhập · Cam: chi tiêu. Dữ liệu lấy từ tài khoản đăng nhập, không phải dữ liệu mẫu.</p></section>`;
}
function overview(currentUser) {
  const stat = monthStats();
  const baseCurrency = String(currentUser.baseCurrency || 'VND').toUpperCase();
  // Balances across currencies MUST NOT be summed without converted base amounts.
  const walletConvertible = wallets.every(w => w.balanceBase != null || String(w.currency || baseCurrency).toUpperCase() === baseCurrency);
  const walletTotal = walletConvertible ? wallets.reduce((s,w) => s + Number(w.balanceBase ?? w.balance ?? 0), 0) : null;
  const goldConvertible = gold.every(g => val(g,'valueBase','currentValueBase','value','currentValue','valuation','marketValue') != null &&
    (val(g,'valueBase','currentValueBase') != null || String(g.currency || baseCurrency).toUpperCase() === baseCurrency));
  const goldTotal = goldConvertible ? gold.reduce((s,g) => s + Number(val(g,'valueBase','currentValueBase','value','currentValue','valuation','marketValue') || 0),0) : null;
  const loansConvertible = loans.every(l => val(l,'outstandingBase','outstanding','remainingPrincipal','balance') != null &&
    (l.outstandingBase != null || String(l.currency || baseCurrency).toUpperCase() === baseCurrency));
  const borrows = loans.filter(l => !['lend','lending','receivable'].includes(String(val(l,'type','direction') || 'borrow').toLowerCase()));
  const lends = loans.filter(l => ['lend','lending','receivable'].includes(String(val(l,'type','direction') || '').toLowerCase()));
  const loanAmount = l => Number(val(l,'outstandingBase','outstanding','remainingPrincipal','balance') || 0);
  const liabilities = loansConvertible ? borrows.reduce((s,l) => s + loanAmount(l),0) : null;
  const receivables = loansConvertible ? lends.reduce((s,l) => s + loanAmount(l),0) : null;
  const assetsReady = ready('wallets') && ready('gold') && ready('loans') && walletConvertible && goldConvertible && loansConvertible && baseCurrency === 'VND';
  const totalAssets = (walletTotal ?? 0) + (goldTotal ?? 0) + (receivables ?? 0);
  return `<div class="kpi-grid">${card('Tài sản ròng', assetsReady ? vnd(totalAssets-liabilities) : '—',assetsReady ? 'Ví + tài sản vàng + phải thu − phải trả' : 'Cần số liệu tài sản quy đổi và API đầy đủ')}
    ${card('Số dư ví',ready('wallets') && walletTotal !== null && baseCurrency === 'VND'?vnd(walletTotal):'—','Chỉ cộng số dư cùng tiền cơ sở')}
    ${card('Tổng nợ phải trả', ready('loans') && liabilities !== null && baseCurrency === 'VND'?vnd(liabilities):'—','Dư nợ gốc còn lại')}
    ${card('Dòng tiền thuần tháng',stat?vnd(stat.net):'—','Thu nhập trừ chi tiêu đã ghi sổ')}</div>
    <div class="panel-grid"><section class="panel"><div class="panel-title"><h2>Giao dịch gần đây</h2></div>${table('transactions', [['Giao dịch','name'],['Loại','type'],['Ngày','date','date'],['Giá trị','amount','money']],transactions.slice(0,5))}</section>
    <section class="panel"><div class="panel-title"><h2>Ngân sách</h2></div>${budgetCards(3)}</section></div>
    <section class="panel"><div class="panel-title"><h2>Tình hình thu–chi</h2></div>${reports()}</section>`;
}
function budgetCards(limit = Infinity, showActions = false) {
  const issue = problem('budgets'); if (issue) return issue;
  if (!budgets.length) return empty('budgets','ngân sách');
  return budgets.slice(0,limit).map(b => {
    const rate = b.limit > 0 ? Math.max(0, b.spent / b.limit * 100) : 0;
    const statusText = rate >= 100 ? 'Cảnh báo Đỏ · Đã vượt hạn mức (≥ 100%)' : rate >= 80 ? 'Cảnh báo Vàng · Sắp chạm hạn mức (≥ 80%)' : 'An toàn · Trong hạn mức (< 80%)';
    const remainingText = b.spent > b.limit ? `Vượt ${vnd(b.spent - b.limit)}` : `Còn lại ${vnd(Math.max(0, b.limit - b.spent))}`;
    const actionsHtml = showActions && b.id ? `<div class="wf-table-actions" style="margin-top:8px;display:flex;gap:12px">
      <button type="button" class="text-action" data-action="live:detail:budgets:${encodeURIComponent(String(b.id))}">Chi tiết</button>
      <button type="button" class="text-action" data-action="live:edit:budgets:${encodeURIComponent(String(b.id))}">Sửa hạn mức</button>
      <button type="button" class="text-action" data-action="live:delete:budgets:${encodeURIComponent(String(b.id))}">Lưu trữ</button>
    </div>` : '';
    return `<div class="budget-row"><div class="budget-info"><strong>${esc(b.name)}${b.month ? ` <small class="wf-muted">(${esc(b.month)})</small>` : ''}</strong><span>${vnd(b.spent)} / ${vnd(b.limit)}</span></div>
    <div class="wf-progress"><i style="width:${Math.min(rate,100)}%;background:${rate >=100?'#bb4545':rate>=80?'#e1a145':'#16869e'}"></i></div><div class="budget-foot">${rate.toFixed(1)}% · ${statusText} · ${remainingText}</div>${actionsHtml}</div>`;
  }).join('');
}

function budgetsPage() {
  const issue = problem('budgets');
  const totalLimit = ready('budgets') ? budgets.reduce((s, b) => s + Number(b.limit || 0), 0) : 0;
  const totalSpent = ready('budgets') ? budgets.reduce((s, b) => s + Number(b.spent || 0), 0) : 0;
  const overallRemaining = Math.max(0, totalLimit - totalSpent);
  const overallRate = totalLimit > 0 ? (totalSpent / totalLimit * 100) : 0;
  const redCount = ready('budgets') ? budgets.filter(b => b.limit > 0 && (b.spent / b.limit * 100) >= 100).length : 0;
  const yellowCount = ready('budgets') ? budgets.filter(b => b.limit > 0 && (b.spent / b.limit * 100) >= 80 && (b.spent / b.limit * 100) < 100).length : 0;
  const kpis = ready('budgets') ? `<div class="kpi-grid">
    ${card('Tổng ngân sách tháng', vnd(totalLimit), `${budgets.length} danh mục chi tiêu đang theo dõi`)}
    ${card('Chi tiêu ròng trong kỳ', vnd(totalSpent), 'Không tính chuyển ví nội bộ & trả nợ gốc')}
    ${card('Hạn mức còn lại', vnd(overallRemaining), `Tỷ lệ sử dụng chung: ${overallRate.toFixed(1)}%`)}
    ${card('Ngưỡng cảnh báo', `${redCount} Đỏ · ${yellowCount} Vàng`, 'Cảnh báo tự động ở mốc 80% và 100%')}
  </div>` : '';
  return `<div class="wf-grid-actions">${addButton('budgets', 'Thiết lập ngân sách')}</div>${kpis}<section class="panel"><div class="panel-title"><h2>Hạn mức ngân sách theo danh mục (${esc(month())})</h2></div>${issue || budgetCards(Infinity, true)}</section>`;
}

function notificationsPage(state = {}) {
  const issue = problem('notifications');
  if (issue) return `<section class="panel">${issue}</section>`;
  const unreadFilter = String(state.notifFilter || 'all') === 'unread';
  const unreadCount = notifications.filter(n => !n.isRead && !n.read_at && !n.readAt).length;
  const budgetAlertCount = notifications.filter(n => String(n.type || n.notification_type || '').toUpperCase() === 'BUDGET').length;
  const list = unreadFilter ? notifications.filter(n => !n.isRead && !n.read_at && !n.readAt) : notifications;
  const typeLabel = t => ({ BUDGET: 'Ngân sách', RECURRING: 'Định kỳ', LOAN: 'Khoản vay', BROADCAST: 'Hệ thống', SYSTEM: 'Hệ thống' }[String(t || '').toUpperCase()] || String(t || 'Thông báo'));
  return `<div class="kpi-grid">
    ${card('Tổng thông báo', String(notifications.length), 'Đồng bộ trực tiếp từ máy chủ')}
    ${card('Chưa đọc', String(unreadCount), unreadCount > 0 ? 'Cần kiểm tra các cảnh báo mới' : 'Bạn đã đọc hết thông báo')}
    ${card('Cảnh báo ngân sách', String(budgetAlertCount), 'Ngưỡng 80% (Vàng) và 100% (Đỏ)')}
  </div>
  <section class="panel">
    <div class="filter-row" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:14px">
      <div class="tabs">
        <button type="button" data-notif-filter="all" class="${!unreadFilter ? 'active' : ''}">Tất cả (${notifications.length})</button>
        <button type="button" data-notif-filter="unread" class="${unreadFilter ? 'active' : ''}">Chưa đọc (${unreadCount})</button>
      </div>
      <button type="button" class="btn btn--outline btn-sm" data-action="live:read-all:notifications" ${unreadCount === 0 ? 'disabled' : ''}>Đánh dấu tất cả đã đọc</button>
    </div>
    ${!list.length ? empty('notifications', unreadFilter ? 'thông báo chưa đọc' : 'thông báo') : `<div class="data-table-wrap"><table class="data-table"><thead><tr><th>Thông báo</th><th>Phân loại</th><th>Ngày tạo</th><th>Trạng thái</th><th></th></tr></thead><tbody>${list.map(row => {
      const unread = !row.isRead && !row.read_at && !row.readAt;
      const title = val(row, 'title', 'message') || 'Thông báo';
      const body = val(row, 'body', 'details') || '';
      const idEnc = encodeURIComponent(String(row.id ?? ''));
      return `<tr style="${unread ? 'font-weight:600' : ''}">
        <td><div><strong>${esc(title)}</strong>${body ? `<p class="wf-muted" style="margin:4px 0 0;font-weight:400;font-size:13px">${esc(body)}</p>` : ''}</div></td>
        <td>${esc(typeLabel(val(row, 'type', 'notification_type')))}</td>
        <td>${display(val(row, 'createdAt', 'created_at'), 'date')}</td>
        <td>${unread ? '<span style="color:#b45309">● Chưa đọc</span>' : '<span class="wf-muted">Đã đọc</span>'}</td>
        <td class="wf-table-actions">
          <button type="button" class="text-action" data-action="live:detail:notifications:${idEnc}">Chi tiết</button>
          ${unread && row.id ? `<button type="button" class="text-action" data-action="live:read:notifications:${idEnc}">Đã đọc</button>` : ''}
        </td>
      </tr>`;
    }).join('')}</tbody></table></div>`}
  </section>`;
}

export function liveTransactionTable(items = transactions) {
  const issue = problem('transactions');
  if (issue) return issue;
  if (!items.length) return empty('transactions', 'giao dịch');
  const amount = row => {
    const currency = ['VND','USD','EUR'].includes(String(row.currency).toUpperCase()) ? String(row.currency).toUpperCase() : 'VND';
    const formatted = new Intl.NumberFormat('vi-VN', {
      style: 'currency', currency, maximumFractionDigits: currency === 'VND' ? 0 : 2
    }).format(Math.abs(Number(row.amount || 0)));
    const sign = row.type === 'income' ? '+' : row.type === 'expense' ? '−' : '';
    const tone = row.type === 'income' ? ' positive' : row.type === 'expense' ? ' negative' : '';
    return `<span class="amount${tone}">${sign}${esc(formatted)}</span>`;
  };
  return `<div class="data-table-wrap"><table class="data-table transaction-table"><thead><tr>
    <th>Giao dịch</th><th>Loại</th><th>Danh mục</th><th>Ngày</th><th>Ví sử dụng</th><th>Số tiền</th><th></th>
  </tr></thead><tbody>${items.map(row => `<tr>
    <td><div class="table-name"><span class="table-icon" aria-hidden="true">${esc(row.icon)}</span><span>${esc(row.name)}<small>Mã #${esc(row.id)}</small></span></div></td>
    <td>${esc(TRANSACTION_TYPE_LABELS[row.type] || row.type || 'Giao dịch')}</td>
    <td>${esc(row.type === 'transfer' ? 'Chuyển nội bộ' : row.category)}</td>
    <td>${display(row.date, 'date')}</td>
    <td class="transaction-wallet">${esc(row.wallet)}</td>
    <td>${amount(row)}</td>
    <td class="wf-table-actions"><button type="button" class="text-action" data-action="live:detail:transactions:${encodeURIComponent(String(row.id))}">Chi tiết</button></td>
  </tr>`).join('')}</tbody></table></div>`;
}

function transactionList(state) {
  const savedFilters = Array.isArray(state.txFilter) ? state.txFilter : [state.txFilter || 'ALL'];
  const filters = savedFilters.map(value => String(value).toUpperCase());
  const selected = filters.filter(value => value !== 'ALL');
  const q = String(state.search || '').trim().toLocaleLowerCase('vi');
  const list = transactions.filter(t => (!selected.length || selected.includes(String(t.type).toUpperCase()))
    && (!q || [t.name,t.category,t.wallet,t.id].join(' ').toLocaleLowerCase('vi').includes(q)));
  const filterLabel = !selected.length ? 'Tất cả'
    : selected.length === 1 ? (TRANSACTION_TYPE_LABELS[selected[0].toLowerCase()] || selected[0])
      : `${selected.length} loại`;
  return `<div class="filter-row"><div class="filter-controls">
    <button type="button" class="btn btn--outline btn-sm wallet-filter-button transaction-filter-button" data-action="transaction-filter">
      <svg class="nav-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
      <span>Lọc theo loại giao dịch · ${esc(filterLabel)}</span>
    </button>
    <input id="transaction-search" class="input-sm" type="search" placeholder="Tìm tên, mã, danh mục hoặc ví" aria-label="Tìm kiếm giao dịch" value="${esc(state.search||'')}">
  </div></div>
  <div id="transaction-table">${liveTransactionTable(list)}</div>`;
}

function transactionDetail(item) {
  const currency = ['VND','USD','EUR'].includes(String(item.currency).toUpperCase()) ? String(item.currency).toUpperCase() : 'VND';
  const formattedAmount = new Intl.NumberFormat('vi-VN', {
    style: 'currency', currency, maximumFractionDigits: currency === 'VND' ? 0 : 2
  }).format(Math.abs(Number(item.amount || 0)));
  const sign = item.type === 'income' ? '+' : item.type === 'expense' ? '−' : '';
  const category = item.type === 'transfer' ? 'Chuyển nội bộ' : item.category;
  const specialStatus = item.status === 'DRAFT' ? '<span class="badge neutral">Bản nháp</span>'
    : item.status === 'REVERSED' ? '<span class="badge warn">Đã đảo</span>' : '';
  const rate = currency !== 'VND' && Number(item.rate) > 0
    ? `<div><small>Tỷ giá lúc ghi nhận</small><strong>1 ${esc(currency)} = ${vnd(item.rate)}</strong></div>` : '';
  return `<div class="transaction-detail-hero">
    <span class="transaction-detail-icon" aria-hidden="true">${esc(item.icon)}</span>
    <div><small>${esc(TRANSACTION_TYPE_LABELS[item.type] || item.type || 'Giao dịch')}</small><strong>${esc(item.name)}</strong></div>
    <b class="transaction-detail-amount">${sign}${esc(formattedAmount)}</b>
  </div>
  <div class="transaction-detail-grid">
    <div><small>Ví sử dụng</small><strong>${esc(item.wallet)}</strong></div>
    <div><small>Danh mục</small><strong>${esc(category)}</strong></div>
    <div><small>Ngày giao dịch</small><strong>${display(item.date, 'date')}</strong></div>
    <div><small>Mã giao dịch</small><strong>#${esc(item.id)}</strong></div>
    ${rate}
  </div>
  ${specialStatus ? `<div class="transaction-detail-status">${specialStatus}</div>` : ''}
  <div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Đóng</button></div>`;
}
function genericPage(key, cols) {
  return `<div class="wf-grid-actions">${addButton(key)}</div><section class="panel">${table(key,cols)}</section>`;
}
const tableColumns = {
  wallets:[['Tên ví','name'],['Loại','type'],['Tiền tệ','currency'],['Số dư','balance','money']],
  categories:[['Danh mục','name'],['Loại','type'],['Ghi chú','description']],
  recurring:[['Tên','name|description'],['Số tiền','amount','money'],['Chu kỳ','frequency|interval'],['Ngày tiếp theo','nextRun|nextRunAt','date']],
  goals:[['Mục tiêu','name'],['Đã tích lũy','current','money'],['Đích đến','target','money'],['Hạn hoàn thành','deadline','date']],
  loans:[['Khoản vay','name|title'],['Loại','type|direction'],['Dư gốc','outstanding|remainingPrincipal|balance','money'],['Hạn trả','dueDate','date']],
  gold:[['Tài sản','name|type'],['Số lượng','quantity'],['Định giá','value|currentValue|valuation','money']],
  notifications:[['Nội dung','message|title'],['Loại','type'],['Ngày','createdAt|created_at','date'],['Trạng thái','status']],
  adminUsers:[['Người dùng','fullName|name'],['Email','email'],['Quyền','role'],['Trạng thái','status']]
};
function settingsPage(user) {
  const row = (label, details, control = '') => `<div class="settings-row"><div><strong>${esc(label)}</strong><p>${esc(details)}</p></div>${control}</div>`;
  return `<div class="info-grid">
  <section class="panel"><h2>Hồ sơ và tài khoản</h2>
    ${row('Họ và tên', user.fullName)}
    ${row('Email', user.email)}
    ${row('Tiền cơ sở', user.baseCurrency || 'VND')}
    ${row('Ngôn ngữ', 'Tiếng Việt (vi-VN)')}
  </section>
  <section class="panel"><h2>Tài khoản và bảo mật</h2>
    ${row('Đổi mật khẩu', 'Yêu cầu mật khẩu hiện tại. Thu hồi các phiên cũ.', '<button class="btn btn--outline btn-sm" data-action="password">Đổi mật khẩu</button>')}
    ${row('Phiên đăng nhập', 'Tài khoản đang đăng nhập trên trình duyệt này. Dùng nút đăng xuất để kết thúc phiên.')}
    ${row('Đăng xuất', 'Kết thúc phiên đăng nhập hiện tại.', '<button class="btn btn--outline btn-sm" data-action="logout">Đăng xuất</button>')}
  </section>
  <section class="panel"><h2>Dữ liệu và quyền riêng tư</h2>
    ${row('Xuất dữ liệu giao dịch', 'Xuất các giao dịch đã tải từ API ra CSV. Danh sách có thể bị giới hạn theo phân trang.', '<button class="btn btn--outline btn-sm" data-action="export">Xuất CSV</button>')}
    ${row('Xóa tài khoản / tải toàn bộ dữ liệu', 'Chưa có API thực hiện. WealthFlow không tự ý xóa hoặc ghi đè dữ liệu.')}
  </section>
  <section class="panel"><h2>Thông báo và trải nghiệm</h2>
    ${row('Cảnh báo ngân sách', 'Được tính theo các ngưỡng 80% và 100% từ dữ liệu chi tiêu đã ghi sổ.')}
    ${row('Quản lý thông báo', 'Xem thông báo được đồng bộ từ máy chủ.', '<button class="btn btn--outline btn-sm" data-page="notifications">Xem thông báo</button>')}
    ${row('Tùy chỉnh giao diện', 'Chưa có API lưu tùy chọn giao diện theo tài khoản. Không hiển thị tùy chỉnh giả.')}
  </section></div>`;
}
export function liveRender(page, { state, heading, currentUser }) {
  if (page.startsWith('admin-') && currentUser.role !== 'ADMIN') return message('Không có quyền','Chỉ quản trị viên mới truy cập được mục này.');
  const section = page === 'overview' ? overview(currentUser)
    : page === 'transactions' ? `<div class="wf-grid-actions">${addButton('transactions','Ghi giao dịch')}</div><section class="panel">${transactionList(state)}</section>`
    : page === 'budgets' ? budgetsPage()
    : page === 'notifications' ? notificationsPage(state)
    : page === 'reports' || page === 'report' ? reports()
    : page === 'settings' ? settingsPage(currentUser)
    : page === 'admin-settings' ? message('Cấu hình quản trị', 'Các API quản trị chưa được xác minh ở bản nguồn này; không hiển thị dữ liệu giả.')
    : page === 'admin-overview' ? `<section class="panel">${table('adminUsers',tableColumns.adminUsers)}</section>`
    : page === 'admin-users' ? genericPage('adminUsers',tableColumns.adminUsers)
    : page === 'admin-categories' ? genericPage('categories',tableColumns.categories)
    : page === 'admin-audit' || page === 'reconcile' ? message('Chưa có dữ liệu đối soát thực','Chỉ hiển thị kết quả sau khi backend cung cấp endpoint và đã kiểm chứng số liệu.')
    : page in tableColumns ? genericPage(page,tableColumns[page])
    : message('Chức năng đang được tích hợp','Chưa có API thực cho màn hình này. Không hiển thị dữ liệu mẫu.');
  return baseStyle + heading(page) + section;
}

// Live forms mirror the actual Controller/Service DTOs, never demo-data fields.
const field = (name,label,type='text',extra='') => `<div class="field"><label for="live-${esc(name)}">${esc(label)}</label><input id="live-${esc(name)}" name="${esc(name)}" type="${type}" ${extra} required></div>`;
const select = (name,label,options,optional=false) => `<div class="field"><label for="live-${esc(name)}">${esc(label)}</label><select id="live-${esc(name)}" name="${esc(name)}" ${optional?'':'required'}>${optional?'<option value="">Không chọn</option>':''}${options.map(([v,l])=>`<option value="${esc(v)}">${esc(l)}</option>`).join('')}</select></div>`;
const walletOptions = () => wallets.filter(w => String(w.status||'ACTIVE').toUpperCase()==='ACTIVE').map(w=>[w.id,`${w.name} (${w.currency})`]);
const categoryOptions = (type='EXPENSE') => categories.filter(c => !type || c.type === type).map(c=>[c.id,c.name]);
const needReferenceData = name => name === 'transactions' ? (!ready('wallets') || !walletOptions().length)
 : name === 'recurring' ? (!ready('wallets') || !walletOptions().length || !ready('categories'))
 : name === 'budgets' ? (!ready('categories') || !categoryOptions().length) : false;
function createForm(name) {
  if (name==='categories') return field('name','Tên danh mục','text','maxlength="120"')+select('type','Loại danh mục',[['EXPENSE','Chi tiêu'],['INCOME','Thu nhập']]);
  if (name==='transactions') return field('description','Mô tả giao dịch','text','maxlength="255"')
    +select('type','Loại giao dịch',[['EXPENSE','Chi tiêu'],['INCOME','Thu nhập'],['TRANSFER','Chuyển tiền']])
    +field('amount','Số tiền','number','min="0.01" step="any"')
    +select('walletId','Ví thanh toán',walletOptions())
    +select('fromWalletId','Ví gửi',walletOptions())
    +select('toWalletId','Ví nhận',walletOptions())
    +select('categoryId','Danh mục',categories.map(c=>[c.id,`${c.name} · ${c.type}`]))
    +field('occurredOn','Ngày giao dịch','date');
  if (name==='budgets') return select('categoryId','Danh mục chi tiêu',categoryOptions())
    +field('month','Kỳ ngân sách','month')+field('limitAmount','Hạn mức','number','min="0.01" step="any"');
  if (name==='recurring') return field('name','Tên khoản định kỳ','text','maxlength="120"')
    +field('amount','Số tiền','number','min="0.01" step="any"')
    +select('walletId','Ví thanh toán',walletOptions())
    +select('categoryId','Danh mục chi tiêu (nếu có)',categoryOptions(),true)
    +field('dayOfMonth','Ngày lặp lại hằng tháng (1–31)','number','min="1" max="31" step="1"');
  return '';
}
export function liveAction(action, { dialog, toast, reload }) {
  if (!action.startsWith('live:')) return false;
  const [,verb,name,encodedId] = action.split(':');
  const collection = ITEMS[name];
  if (!collection) { toast('Phân hệ không hợp lệ.'); return true; }
  if (verb === 'detail') {
    const item = collection.find(x => String(x.id) === decodeURIComponent(encodedId || ''));
    if (!item) { toast('Không tìm thấy dữ liệu trong tài khoản này.'); return true; }
    if (name === 'transactions') {
      document.querySelector('#modal-overline').textContent = '';
      document.querySelector('#modal-title').textContent = 'Chi tiết giao dịch';
      document.querySelector('#modal-content').innerHTML = transactionDetail(item);
      dialog.showModal(); return true;
    }
    document.querySelector('#modal-overline').textContent = TITLES[name] || name;
    document.querySelector('#modal-title').textContent = String(val(item,'name','title','description','id') || 'Chi tiết');
    const dl = Object.entries(item).filter(([k,v]) => typeof v !== 'object' && !/password|secret|token/i.test(k)).slice(0,20)
      .map(([k,v]) => `<div><dt>${esc(k)}</dt><dd>${esc(v)}</dd></div>`).join('');
    document.querySelector('#modal-content').innerHTML = `<dl class="detail-grid">${dl}</dl><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Đóng</button></div>`;
    dialog.showModal(); return true;
  }
  if (name === 'notifications' && verb === 'read') {
    const id = decodeURIComponent(encodedId || '');
    if (!/^\d+$/.test(id)) { toast('Mã thông báo không hợp lệ.'); return true; }
    void httpClient(`api/notifications/${encodeURIComponent(id)}/read`, { method: 'POST', data: {} })
      .then(async () => { await loadLiveData(); reload(); toast('Đã đánh dấu thông báo là đã đọc.'); })
      .catch(error => toast(`Không thể cập nhật thông báo: ${error.message}`));
    return true;
  }
  if (name === 'notifications' && verb === 'read-all') {
    void httpClient('api/notifications/read-all', { method: 'POST', data: {} })
      .then(async () => { await loadLiveData(); reload(); toast('Đã đánh dấu tất cả thông báo là đã đọc.'); })
      .catch(error => toast(`Không thể cập nhật thông báo: ${error.message}`));
    return true;
  }
  if (name === 'budgets' && verb === 'edit') {
    const id = decodeURIComponent(encodedId || '');
    const item = collection.find(x => String(x.id) === id);
    if (!item || !/^\d+$/.test(id)) { toast('Không tìm thấy ngân sách trong tài khoản.'); return true; }
    document.querySelector('#modal-overline').textContent = 'CẬP NHẬT HẠN MỨC';
    document.querySelector('#modal-title').textContent = `Sửa ngân sách: ${item.name}`;
    document.querySelector('#modal-content').innerHTML = `<p class="form-hint">Điều chỉnh hạn mức ngân sách cho danh mục <strong>${esc(item.name)}</strong> (${esc(item.month || month())}). Đã chi ròng: <strong>${vnd(item.spent)}</strong>.</p>
    <form id="live-budget-edit-form"><div class="form-grid">${field('limitAmount', 'Hạn mức mới (VND)', 'number', `min="0.01" step="any" value="${esc(item.limit)}"`)}</div><p id="live-form-feedback" role="status" aria-live="polite"></p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">Cập nhật hạn mức</button></div></form>`;
    const form = document.querySelector('#live-budget-edit-form');
    form.addEventListener('submit', async e => {
      e.preventDefault(); if (!form.reportValidity()) return;
      const submit = form.querySelector('[type=submit]');
      const feedback = form.querySelector('#live-form-feedback');
      const limitAmount = Number(form.elements.namedItem('limitAmount').value);
      submit.disabled = true; feedback.textContent = 'Đang cập nhật hạn mức…';
      try {
        await mutateLiveData('budgets', 'PUT', { limitAmount }, id);
        dialog.close();
        await loadLiveData();
        reload();
        toast('Đã cập nhật hạn mức ngân sách.');
      } catch (error) { feedback.textContent = `Không thể cập nhật: ${error.message}`; }
      finally { submit.disabled = false; }
    });
    dialog.showModal();
    return true;
  }
  if (name === 'budgets' && verb === 'delete') {
    const id = decodeURIComponent(encodedId || '');
    const item = collection.find(x => String(x.id) === id);
    if (!item || !/^\d+$/.test(id)) { toast('Không tìm thấy ngân sách trong tài khoản.'); return true; }
    document.querySelector('#modal-overline').textContent = 'LƯU TRỮ NGÂN SÁCH';
    document.querySelector('#modal-title').textContent = `Lưu trữ ngân sách: ${item.name}`;
    document.querySelector('#modal-content').innerHTML = `<p class="form-hint">Ngân sách của danh mục <strong>${esc(item.name)}</strong> sẽ được chuyển sang trạng thái Lưu trữ (Archived). Các giao dịch chi tiêu đã ghi sổ không bị ảnh hưởng.</p>
    <form id="live-budget-delete-form"><p id="live-form-feedback" role="status" aria-live="polite"></p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">Xác nhận lưu trữ</button></div></form>`;
    const form = document.querySelector('#live-budget-delete-form');
    form.addEventListener('submit', async e => {
      e.preventDefault();
      const submit = form.querySelector('[type=submit]');
      const feedback = form.querySelector('#live-form-feedback');
      submit.disabled = true; feedback.textContent = 'Đang lưu trữ ngân sách…';
      try {
        await mutateLiveData('budgets', 'DELETE', null, id);
        dialog.close();
        await loadLiveData();
        reload();
        toast('Đã lưu trữ ngân sách thành công.');
      } catch (error) { feedback.textContent = `Không thể lưu trữ: ${error.message}`; }
      finally { submit.disabled = false; }
    });
    dialog.showModal();
    return true;
  }
  if (name === 'recurring' && (verb === 'pause' || verb === 'resume')) {
    const id = decodeURIComponent(encodedId || '');
    const item = collection.find(x => String(x.id) === id);
    if (!item || !/^\d+$/.test(id)) { toast('Không tìm thấy khoản định kỳ trong tài khoản.'); return true; }
    const currentStatus = String(item.status || '').toUpperCase();
    if ((verb === 'pause' && currentStatus !== 'ACTIVE') || (verb === 'resume' && currentStatus !== 'PAUSED')) {
      toast('Trạng thái khoản định kỳ đã thay đổi. Vui lòng tải lại.'); return true;
    }
    void httpClient(`api/recurring/${encodeURIComponent(id)}/${verb}`, { method: 'POST', data: {} })
      .then(async () => { await loadLiveData(); reload(); toast(verb === 'pause' ? 'Đã tạm dừng khoản định kỳ.' : 'Đã kích hoạt lại khoản định kỳ.'); })
      .catch(error => toast(`Không thể cập nhật khoản định kỳ: ${error.message}`));
    return true;
  }
  if (verb !== 'create' || !['transactions','categories','budgets','recurring'].includes(name)) { toast('Thao tác chưa có API được xác nhận. Không ghi dữ liệu giả.'); return true; }
  if (!ready(name)) { toast('Chưa có API thật để ghi dữ liệu.'); return true; }
  if (needReferenceData(name)) { toast('Hãy tạo ít nhất một ví và danh mục phù hợp trước; hoặc kiểm tra kết nối API.'); return true; }
  const isTransaction = name === 'transactions';
  document.querySelector('#modal-overline').textContent = isTransaction ? '' : 'DỮ LIỆU THẬT';
  document.querySelector('#modal-title').textContent = isTransaction ? 'Thêm Giao dịch' : `Thêm ${TITLES[name]}`;
  document.querySelector('#modal-content').innerHTML = `${isTransaction ? '' : '<p class="form-hint">Dữ liệu chỉ được ghi khi backend xác nhận thành công. Nếu API thiếu chức năng, hệ thống sẽ báo lỗi.</p>'}
  <form id="live-entry-form"><div class="form-grid">${createForm(name)}</div><p id="live-form-feedback" role="status" aria-live="polite"></p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">Lưu vào hệ thống</button></div></form>`;
  const form = document.querySelector('#live-entry-form');
  if (name==='transactions') {
    const types = form.elements.namedItem('type');
    const wallet = form.elements.namedItem('walletId');
    const fromWallet = form.elements.namedItem('fromWalletId');
    const toWallet = form.elements.namedItem('toWalletId');
    const category = form.elements.namedItem('categoryId');
    if (toWallet.options.length > 1) toWallet.selectedIndex = 1;
    function syncTransactionFields() {
      const isTransfer = types.value === 'TRANSFER';
      wallet.disabled = isTransfer;
      category.disabled = isTransfer;
      fromWallet.disabled = !isTransfer;
      toWallet.disabled = !isTransfer;
      wallet.closest('.field').hidden = isTransfer;
      category.closest('.field').hidden = isTransfer;
      fromWallet.closest('.field').hidden = !isTransfer;
      toWallet.closest('.field').hidden = !isTransfer;
      const choices = isTransfer ? [] : categoryOptions(types.value);
      if (!isTransfer) category.innerHTML = choices.map(([v,l])=>`<option value="${esc(v)}">${esc(l)}</option>`).join('');
      const submit = form.querySelector('[type=submit]');
      const feedback = form.querySelector('#live-form-feedback');
      const sameWallet = isTransfer && fromWallet.value === toWallet.value;
      const missingTransferWallets = isTransfer && (!fromWallet.value || !toWallet.value || walletOptions().length < 2);
      submit.disabled = isTransfer ? (missingTransferWallets || sameWallet) : !choices.length;
      feedback.textContent = missingTransferWallets
        ? 'Bạn cần có ít nhất hai ví đang sử dụng để chuyển tiền.'
        : sameWallet
          ? 'Ví gửi và ví nhận phải khác nhau.'
          : (!isTransfer && !choices.length ? 'Bạn cần tạo danh mục tương ứng trước khi ghi giao dịch.' : '');
    }
    types.addEventListener('change',syncTransactionFields);
    fromWallet.addEventListener('change',syncTransactionFields);
    toWallet.addEventListener('change',syncTransactionFields);
    syncTransactionFields();
  }
  if (name==='transactions') form.elements.namedItem('occurredOn').value = new Date().toLocaleDateString('en-CA');
  if (name==='budgets') form.elements.namedItem('month').value = new Date().toLocaleDateString('en-CA').slice(0,7);
  form.addEventListener('submit', async e => {
    e.preventDefault(); if (!form.reportValidity()) return;
    const submit = form.querySelector('[type=submit]');
    const feedback = document.querySelector('#live-form-feedback');
    const record = Object.fromEntries(new FormData(form));
    for (const key of ['amount','limitAmount','walletId','fromWalletId','toWalletId','categoryId','dayOfMonth']) if (record[key] !== undefined && record[key] !== '') record[key] = Number(record[key]);
    if (record.categoryId === '') delete record.categoryId;
    const isTransfer = name === 'transactions' && record.type === 'TRANSFER';
    if (name==='transactions' && !isTransfer) {
      const selected = wallets.find(w=>String(w.id)===String(record.walletId));
      record.currency = selected?.currency || 'VND';
    }
    if (isTransfer) delete record.type;
    if (name==='recurring') record.executionMode = 'DRAFT_REMINDER';
    submit.disabled = true; feedback.textContent = 'Đang lưu vào backend…';
    try {
      if (isTransfer) await httpClient('transactions/transfer', { method: 'POST', data: record });
      else await mutateLiveData(name,'POST',record);
      dialog.close();
      await loadLiveData();
      reload();
      toast('Đã lưu thành công trên máy chủ.');
    } catch (error) { feedback.textContent = `Không thể lưu: ${error.message}`; }
    finally { submit.disabled = false; }
  });
  dialog.showModal(); return true;
}
