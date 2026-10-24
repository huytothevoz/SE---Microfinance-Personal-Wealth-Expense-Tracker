/** WealthFlow live, account-scoped page templates. No static financial figures. */
import {
  wallets,
  transactions,
  budgets,
  recurring,
  goals,
  loans,
  gold,
  notifications,
  categories,
  adminUsers,
  adminCategories,
  adminBroadcasts,
  adminAuditLogs,
  adminConfig,
  analytics,
  reconciliation,
  adminOverview,
  dataStatus,
  mutateLiveData,
  loadLiveData
} from '../data/live-data.js?v=wealth-ui-2';
import { icon, formatMoneyInput, moneyInputValue } from './ui-core.js?v=wealth-ui-2';
import { httpClient } from './../services/http-client.js';
import { GoalApi } from '../services/goal-api.js';
import { ReportApi } from '../services/report-api.js';
import { AdminApi } from '../services/admin-api.js';

const ITEMS = {
  wallets,
  transactions,
  budgets,
  recurring,
  goals,
  loans,
  gold,
  notifications,
  categories,
  adminUsers,
  adminCategories,
  adminBroadcasts,
  adminAuditLogs,
  adminConfig
};
const TITLES = {
  wallets: 'Ví',
  transactions: 'Giao dịch',
  categories: 'Danh mục',
  budgets: 'Ngân sách',
  recurring: 'Giao dịch định kỳ',
  goals: 'Mục tiêu tiết kiệm',
  loans: 'Vay và cho vay',
  gold: 'Tài sản vàng',
  notifications: 'Thông báo',
  reconciliation: 'Đối soát sổ cái',
  adminUsers: 'Người dùng',
  adminCategories: 'Danh mục mặc định',
  adminBroadcasts: 'Thông báo hệ thống',
  adminConfig: 'Cấu hình hệ thống',
  adminAuditLogs: 'Nhật ký quản trị'
};
const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const vnd = n => Number.isFinite(Number(n)) ? new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(Number(n)) : '—';
const val = (obj, ...keys) => keys.map(k => obj?.[k]).find(x => x !== null && x !== undefined && x !== '');
const sum = list => list.reduce((s, row) => s + Math.abs(Number(row.amount || 0)), 0);
const ownMonth = iso => String(iso ?? '').slice(0, 7);
const month = () => { const d = new Date(); return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`; };
const posted = () => transactions.filter(t => t.status === 'POSTED');
const TRANSACTION_TYPE_LABELS = {
  income: 'Thu nhập',
  expense: 'Chi tiêu',
  transfer: 'Chuyển tiền',
  savings_goal: 'Mục tiêu tiết kiệm',
  loan_disbursement: 'Giải ngân khoản vay',
  loan_payment: 'Thanh toán khoản vay',
  other: 'Điều chỉnh / Mở sổ'
};
const stateOf = key => dataStatus[key]?.state || 'idle';
const ready = key => stateOf(key) === 'ready';
const loading = key => stateOf(key) === 'loading' || stateOf(key) === 'idle';
const message = (title, description) => `<div class="empty-state wf-empty"><strong>${esc(title)}</strong><p>${esc(description)}</p></div>`;
const problem = key => loading(key) ? message('Đang tải dữ liệu…', 'Đang lấy dữ liệu của tài khoản hiện tại từ máy chủ.')
  : ready(key) ? null : `<div class="wf-error" role="alert"><strong>${esc(TITLES[key] || key)}: Chưa kết nối API</strong><p>${esc(dataStatus[key]?.message || 'Chưa thể tải dữ liệu.')}</p><small>Không sử dụng dữ liệu mô phỏng để thay thế.</small></div>`;
const empty = (key, what) => message(`Chưa có ${what}`, 'Tài khoản mới bắt đầu trống. Hãy thêm dữ liệu đầu tiên để xem thống kê.');
const addButton = (key, text = `Thêm ${TITLES[key] || 'mới'}`) => `<button type="button" class="btn btn--primary btn-sm" data-action="live:create:${key}" ${(!ready(key) || !['transactions', 'categories', 'budgets', 'recurring', 'adminCategories', 'adminBroadcasts'].includes(key)) ? 'disabled title="Chức năng ghi chưa được xác nhận an toàn với phiên bản backend này"' : ''}>${icon('plus')} ${esc(text)}</button>`;
const display = (x, fmt, currency = 'VND') => fmt === 'money' ? (Number.isFinite(Number(x)) ? new Intl.NumberFormat('vi-VN', { style: 'currency', currency: ['VND', 'USD', 'EUR'].includes(String(currency)) ? String(currency) : 'VND', maximumFractionDigits: currency === 'VND' ? 0 : 2 }).format(Number(x)) : '—') : fmt === 'date' ? esc(String(x ?? '').slice(0, 10) || '—') : esc(x ?? '—');
const baseStyle = `<style>
.wf-empty {padding:38px 20px;line-height:1.6;text-align:center}.wf-empty p{margin:6px 0;color:var(--text-secondary,#69778a)}
.wf-error{margin:16px 0;border:1px solid #d8a9a9;border-radius:12px;padding:18px;background:rgba(190,55,55,.05)}.wf-error p{margin:8px 0}
.wf-muted{color:var(--text-secondary,#68778a)}.wf-graph{display:flex;gap:12px;height:200px;align-items:end;border-bottom:1px solid #cbd5e1;padding:10px 4px 18px}
.wf-bar-group{flex:1;display:flex;align-items:end;gap:3px;justify-content:center;height:100%;position:relative}
.wf-bar{min-width:12px;max-width:35px;flex:1;background:#16869e;border-radius:5px 5px 0 0}
.wf-bar.out{background:#ef9b71}.wf-bar-group small{position:absolute;top:100%;padding-top:5px;white-space:nowrap;font-size:10px}
.wf-progress{height:9px;border-radius:8px;background:#d7e3e9;overflow:hidden}.wf-progress i{display:block;background:#16869e;height:100%}
.wf-table-actions{white-space:nowrap;display:flex;gap:10px;justify-content:flex-end}.wf-grid-actions{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}.wf-stat-note{font-size:12px;color:#68778a;margin-top:7px}
.wf-code{font-family:ui-monospace,SFMono-Regular,Consolas,monospace;font-size:12px;background:#f1f5f9;padding:2px 6px;border-radius:4px}
@media(max-width:640px){.wf-graph{height:150px;gap:3px}.wf-bar{min-width:5px}}
</style>`;

function table(key, columns, list = ITEMS[key]) {
  const issue = problem(key);
  if (issue) return issue;
  if (!list.length) return empty(key, TITLES[key]?.toLowerCase() || 'dữ liệu');
  return `<div class="data-table-wrap"><table class="data-table"><thead><tr>${columns.map(([label]) => `<th>${esc(label)}</th>`).join('')}<th></th></tr></thead><tbody>${list.map(row => `<tr>${columns.map(([, field, fmt]) => `<td>${display(val(row, ...field.split('|')), fmt, key === 'wallets' ? row.currency : 'VND')}</td>`).join('')}<td class="wf-table-actions"><button type="button" class="text-action" data-action="live:detail:${key}:${encodeURIComponent(String(row.id ?? ''))}">Chi tiết</button>${key === 'recurring' && row.id && ['ACTIVE', 'PAUSED'].includes(String(row.status).toUpperCase()) ? `<button type="button" class="text-action" data-action="live:${String(row.status).toUpperCase() === 'ACTIVE' ? 'pause' : 'resume'}:recurring:${encodeURIComponent(String(row.id))}">${String(row.status).toUpperCase() === 'ACTIVE' ? 'Tạm dừng' : 'Tiếp tục'}</button>` : ''}</td></tr>`).join('')}</tbody></table></div>`;
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
  return `<div class="kpi-grid">${card('Thu nhập tháng', vnd(stats.income), 'Không tính chuyển ví')}${card('Chi tiêu tháng', vnd(stats.expense), 'Không tính chuyển ví')}${card('Dòng tiền thuần', vnd(stats.net), 'Thu nhập trừ chi tiêu')}${card('Tỷ lệ tiết kiệm', stats.rate === null ? '—' : stats.rate.toFixed(1) + '%', stats.rate === null ? 'Chưa có thu nhập để tính' : 'Tính từ giao dịch đã ghi sổ')}</div>
  <section class="panel"><div class="panel-title"><h2>Thu nhập và chi tiêu · 6 tháng</h2></div><div class="wf-graph">${months.map(m => `<div class="wf-bar-group"><i class="wf-bar" style="height:${Math.max(0, m.income / ceiling * 100)}%" title="${esc(m.key)}: Thu ${vnd(m.income)}"></i><i class="wf-bar out" style="height:${Math.max(0, m.expense / ceiling * 100)}%" title="${esc(m.key)}: Chi ${vnd(m.expense)}"></i><small>${esc(m.key.slice(5))}</small></div>`).join('')}</div><p class="wf-stat-note">Xanh: thu nhập · Cam: chi tiêu. Dữ liệu lấy từ tài khoản đăng nhập, không phải dữ liệu mẫu.</p></section>`;
}
function overview() {
  const issue = problem('analytics');
  if (issue) return `<section class="panel">${issue}<div class="wf-grid-actions"><button type="button" class="btn btn--outline btn-sm" data-page="overview">Thử tải lại</button></div></section>`;
  const assets = analytics.assets || {};
  const liabilities = analytics.liabilities || {};
  const cashFlow = analytics.cashFlow || {};
  const goalSummary = analytics.goals || {};
  const breakdown = assets.breakdown || {};
  const goalItems = Array.isArray(goalSummary.items) ? goalSummary.items : [];
  const goalHtml = goalItems.length ? goalItems.map(goal => {
    const progress = Math.max(0, Math.min(100, Number(goal.progressPercent || 0)));
    return `<div class="budget-row"><div class="budget-info"><strong>${esc(goal.name)}</strong><span>${vnd(goal.currentAmount)} / ${vnd(goal.targetAmount)}</span></div><div class="wf-progress"><i style="width:${progress}%"></i></div><div class="budget-foot">${progress.toLocaleString('vi-VN')}% · hạn ${display(goal.deadline, 'date')}</div></div>`;
  }).join('') : empty('goals', 'mục tiêu đang thực hiện');
  const allocationRows = [
    ['Tiền mặt', breakdown.cash],
    ['Ngân hàng', breakdown.bank],
    ['Ví điện tử', breakdown.eWallet],
    ['Tiết kiệm', breakdown.savings],
    ['Vàng', breakdown.gold],
    ['Khoản cho vay', breakdown.receivableLoans]
  ].filter(([, amount]) => Number(amount || 0) !== 0);
  const allocationHtml = allocationRows.length
    ? `<div class="goal-history">${allocationRows.map(([label, amount]) => `<div class="goal-history-row"><span><strong>${esc(label)}</strong></span><b>${vnd(amount)}</b></div>`).join('')}</div>`
    : message('Chưa có tài sản', 'Các nguồn tài sản hợp lệ của tài khoản hiện có giá trị bằng 0.');
  return `<div class="kpi-grid">
    ${card('Tài sản ròng', vnd(analytics.netWorth), 'Tổng tài sản − Tổng nợ phải trả')}
    ${card('Tổng tài sản', vnd(assets.totalAssets), 'Ví + vàng + khoản cho vay, không tính tiền mục tiêu lần hai')}
    ${card('Tổng nợ phải trả', vnd(liabilities.totalLiabilities), 'Ví tín dụng + dư nợ gốc đi vay')}
    ${card('Đang tích lũy', vnd(goalSummary.currentAmount), `${Number(goalSummary.activeCount || 0)} mục tiêu đang thực hiện`)}</div>
    <div class="kpi-grid">${card('Thu nhập tháng', vnd(cashFlow.income), 'Chỉ giao dịch thu nhập đã ghi sổ')}${card('Chi phí tháng', vnd(cashFlow.expense), 'Chỉ giao dịch chi phí đã ghi sổ')}${card('Dòng tiền thuần', vnd(cashFlow.net), 'Thu nhập − Chi phí')}${card('Tỷ lệ tiết kiệm', cashFlow.savingRate === null ? 'N/A' : `${esc(cashFlow.savingRate)}%`, cashFlow.savingRate === null ? 'Chưa có thu nhập để tính' : `Tháng ${esc(cashFlow.month)}`)}</div>
    <div class="panel-grid"><section class="panel"><div class="panel-title"><div><h2>Cơ cấu tài sản</h2><p>Nguồn số liệu do backend tổng hợp</p></div></div>${allocationHtml}</section><section class="panel"><div class="panel-title"><div><h2>Mục tiêu đang thực hiện</h2><p>Tiền phân bổ vẫn nằm trong ví, không cộng lại vào tài sản</p></div></div>${goalHtml}</section></div>
    <section class="panel"><div class="panel-title"><h2>Giao dịch gần đây</h2></div>${table('transactions', [['Giao dịch', 'name'], ['Loại', 'type'], ['Ngày', 'date', 'date'], ['Giá trị', 'amount', 'money']], transactions.slice(0, 5))}</section>`;
}
function budgetCards(limit = Infinity, showActions = false) {
  const issue = problem('budgets'); if (issue) return issue;
  if (!budgets.length) return empty('budgets', 'ngân sách');
  return budgets.slice(0, limit).map(b => {
    const rate = b.limit > 0 ? Math.max(0, b.spent / b.limit * 100) : 0;
    const statusText = rate >= 100 ? 'Cảnh báo Đỏ · Đã vượt hạn mức (≥ 100%)' : rate >= 80 ? 'Cảnh báo Vàng · Sắp chạm hạn mức (≥ 80%)' : 'An toàn · Trong hạn mức (< 80%)';
    const remainingText = b.spent > b.limit ? `Vượt ${vnd(b.spent - b.limit)}` : `Còn lại ${vnd(Math.max(0, b.limit - b.spent))}`;
    const actionsHtml = showActions && b.id ? `<div class="wf-table-actions" style="margin-top:8px;display:flex;gap:12px">
      <button type="button" class="text-action" data-action="live:detail:budgets:${encodeURIComponent(String(b.id))}">Chi tiết</button>
      <button type="button" class="text-action" data-action="live:edit:budgets:${encodeURIComponent(String(b.id))}">Sửa hạn mức</button>
      <button type="button" class="text-action" data-action="live:delete:budgets:${encodeURIComponent(String(b.id))}">Lưu trữ</button>
    </div>` : '';
    return `<div class="budget-row"><div class="budget-info"><strong>${esc(b.name)}${b.month ? ` <small class="wf-muted">(${esc(b.month)})</small>` : ''}</strong><span>${vnd(b.spent)} / ${vnd(b.limit)}</span></div>
    <div class="wf-progress"><i style="width:${Math.min(rate, 100)}%;background:${rate >= 100 ? '#bb4545' : rate >= 80 ? '#e1a145' : '#16869e'}"></i></div><div class="budget-foot">${rate.toFixed(1)}% · ${statusText} · ${remainingText}</div>${actionsHtml}</div>`;
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
  const budgetAlertCount = notifications.filter(n => String(n.type || n.notification_type || '').toUpperCase().includes('BUDGET')).length;
  const list = unreadFilter ? notifications.filter(n => !n.isRead && !n.read_at && !n.readAt) : notifications;
  const typeLabel = t => ({ BUDGET: 'Ngân sách', BUDGET_YELLOW: 'Ngân sách (80%)', BUDGET_RED: 'Ngân sách (100%)', RECURRING: 'Định kỳ', RECURRING_REMINDER: 'Định kỳ', LOAN: 'Khoản vay', LOAN_DUE: 'Khoản vay', BROADCAST: 'Hệ thống', SYSTEM_BROADCAST: 'Hệ thống', SYSTEM: 'Hệ thống' }[String(t || '').toUpperCase()] || String(t || 'Thông báo'));
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
    const currency = ['VND', 'USD', 'EUR'].includes(String(row.currency).toUpperCase()) ? String(row.currency).toUpperCase() : 'VND';
    const rawAmount = currency === 'VND' ? Number(row.amount || 0) : Number(row.amount_original ?? row.amount ?? 0);
    const formatted = new Intl.NumberFormat('vi-VN', {
      style: 'currency', currency, maximumFractionDigits: currency === 'VND' ? 0 : 2
    }).format(Math.abs(rawAmount));
    const sign = row.type === 'income' ? '+' : row.type === 'expense' ? '−' : row.type === 'savings_goal' ? (row.goalAction === 'WITHDRAWAL' ? '← ' : '→ ') : '';
    const tone = row.type === 'income' ? ' positive' : row.type === 'expense' ? ' negative' : '';
    return `<span class="amount${tone}">${sign}${esc(formatted)}</span>`;
  };
  return `<div class="data-table-wrap"><table class="data-table transaction-table"><thead><tr>
    <th>Giao dịch</th><th>Loại</th><th>Danh mục</th><th>Ngày</th><th>Ví sử dụng</th><th>Số tiền</th><th>Trạng thái</th><th></th>
  </tr></thead><tbody>${items.map(row => {
    const st = String(row.status || 'POSTED').toUpperCase();
    const badge = st === 'REVERSED'
      ? '<span class="badge warn">Đã đảo</span>'
      : st === 'DRAFT'
        ? '<span class="badge neutral">Bản nháp</span>'
        : '<span class="badge success">Đã ghi sổ</span>';
    const canReverse = st === 'POSTED' && /^\d+$/.test(String(row.id)) && !String(row.name || '').startsWith('[ĐẢO]');
    return `<tr>
    <td><div class="table-name"><span class="table-icon" aria-hidden="true">${esc(row.icon)}</span><span>${esc(row.name)}<small>Mã #${esc(row.id)}</small></span></div></td>
    <td>${esc(TRANSACTION_TYPE_LABELS[row.type] || row.type || 'Giao dịch')}</td>
    <td>${esc(row.type === 'transfer' ? 'Chuyển nội bộ' : row.type === 'savings_goal' ? (row.goalName || 'Mục tiêu tiết kiệm') : row.category)}</td>
    <td>${display(row.date, 'date')}</td>
    <td class="transaction-wallet">${esc(row.wallet)}</td>
    <td>${amount(row)}</td>
    <td>${badge}</td>
    <td class="wf-table-actions">
      <button type="button" class="text-action" data-action="live:detail:transactions:${encodeURIComponent(String(row.id))}">Chi tiết</button>
      ${canReverse ? `<button type="button" class="text-action" data-action="live:reverse:transactions:${encodeURIComponent(String(row.id))}">Đảo bút toán</button>` : ''}
    </td>
  </tr>`;
  }).join('')}</tbody></table></div>`;
}

function transactionList(state) {
  const savedFilters = Array.isArray(state.txFilter) ? state.txFilter : [state.txFilter || 'ALL'];
  const filters = savedFilters.map(value => String(value).toUpperCase());
  const selected = filters.filter(value => value !== 'ALL');
  const q = String(state.search || '').trim().toLocaleLowerCase('vi');
  const list = transactions.filter(t => (!selected.length || selected.includes(String(t.type).toUpperCase()))
    && (!q || [t.name, t.category, t.wallet, t.id].join(' ').toLocaleLowerCase('vi').includes(q)));
  const filterLabel = !selected.length ? 'Tất cả'
    : selected.length === 1 ? (TRANSACTION_TYPE_LABELS[selected[0].toLowerCase()] || selected[0])
      : `${selected.length} loại`;
  return `<div class="filter-row"><div class="filter-controls">
    <button type="button" class="btn btn--outline btn-sm wallet-filter-button transaction-filter-button" data-action="transaction-filter">
      <svg class="nav-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
      <span>Lọc theo loại giao dịch · ${esc(filterLabel)}</span>
    </button>
    <input id="transaction-search" class="input-sm" type="search" placeholder="Tìm tên, mã, danh mục hoặc ví" aria-label="Tìm kiếm giao dịch" value="${esc(state.search || '')}">
    <button type="button" class="btn btn--outline btn-sm" data-action="live:export-csv:reports">${icon('download')} Xuất CSV</button>
  </div></div>
  <div id="transaction-table">${liveTransactionTable(list)}</div>`;
}

function transactionDetail(item, entries = null) {
  const currency = ['VND', 'USD', 'EUR'].includes(String(item.currency).toUpperCase()) ? String(item.currency).toUpperCase() : 'VND';
  const formattedAmount = new Intl.NumberFormat('vi-VN', {
    style: 'currency', currency, maximumFractionDigits: currency === 'VND' ? 0 : 2
  }).format(Math.abs(Number(item.amount || 0)));
  const sign = item.type === 'income' ? '+' : item.type === 'expense' ? '−' : item.type === 'savings_goal' ? (item.goalAction === 'WITHDRAWAL' ? '← ' : '→ ') : '';
  const category = item.type === 'transfer' ? 'Chuyển nội bộ' : item.type === 'savings_goal' ? (item.goalName || 'Mục tiêu tiết kiệm') : item.category;
  const specialStatus = item.status === 'DRAFT' ? '<span class="badge neutral">Bản nháp</span>'
    : item.status === 'REVERSED' ? '<span class="badge warn">Đã đảo (REVERSED)</span>' : '<span class="badge success">Đã ghi sổ (POSTED)</span>';
  const rate = currency !== 'VND' && Number(item.rate) > 0
    ? `<div><small>Tỷ giá lúc ghi nhận</small><strong>1 ${esc(currency)} = ${vnd(item.rate)}</strong></div>` : '';
  const origLink = item.originalTransactionId || item.original_transaction_id
    ? `<div><small>Bút toán gốc bị đảo</small><strong>#${esc(item.originalTransactionId || item.original_transaction_id)}</strong></div>` : '';

  const entryList = Array.isArray(entries) ? entries : (Array.isArray(item.entries) ? item.entries : []);
  const entriesHtml = entryList.length ? `<div style="margin-top:16px">
    <h4 style="margin:0 0 8px;font-size:13px;text-transform:uppercase;color:#68778a">Bút toán kép (Double-Entry Ledger Lines)</h4>
    <div class="data-table-wrap"><table class="data-table"><thead><tr><th>Tài khoản sổ cái</th><th>Chiều</th><th>Nguyên tệ</th><th>Quy đổi (VND)</th></tr></thead><tbody>
      ${entryList.map(e => `<tr>
        <td><span class="wf-code">${esc(e.account_code || e.accountCode || '')}</span>${e.wallet_name ? ` <small class="wf-muted">(${esc(e.wallet_name)})</small>` : ''}</td>
        <td><span class="badge ${String(e.entry_side).toUpperCase() === 'DEBIT' ? 'blue' : 'neutral'}">${esc(e.entry_side)}</span></td>
        <td>${display(e.amount_original, 'money', e.currency || currency)}</td>
        <td>${vnd(e.amount_base)}</td>
      </tr>`).join('')}
    </tbody></table></div>
  </div>` : '';

  const canReverse = String(item.status || 'POSTED').toUpperCase() === 'POSTED' && /^\d+$/.test(String(item.id)) && !String(item.name || '').startsWith('[ĐẢO]');

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
    <div><small>Trạng thái</small><strong>${specialStatus}</strong></div>
    ${rate}
    ${origLink}
  </div>
  ${entriesHtml}
  <div class="modal-footer">
    ${canReverse ? `<button type="button" class="btn btn--danger btn-sm" data-action="live:reverse:transactions:${encodeURIComponent(String(item.id))}">Đảo giao dịch (Reversal)</button>` : ''}
    <button type="button" class="btn btn--outline" data-close-modal>Đóng</button>
  </div>`;
}

/**
 * FR12.02, FR12.03, FR12.04: Ledger Reconciliation, Trial Balance & CSV Export Page.
 */
function reconciliationPage() {
  const issue = problem('reconciliation');
  if (issue) {
    return `<div class="wf-grid-actions">
      <button type="button" class="btn btn--primary btn-sm" data-action="live:reconcile:reports">${icon('check')} Kiểm tra đối soát</button>
      <button type="button" class="btn btn--outline btn-sm" data-action="live:export-csv:reports">${icon('download')} Tải Xuất CSV</button>
    </div><section class="panel">${issue}</section>`;
  }

  const walletRows = Array.isArray(reconciliation.wallets) ? reconciliation.wallets : [];
  const tb = reconciliation.trialBalance || {};
  const tbAccounts = Array.isArray(tb.accounts) ? tb.accounts : [];
  const recentExports = Array.isArray(reconciliation.recentExports) ? reconciliation.recentExports : [];
  const isAllMatched = Boolean(reconciliation.isAllMatched);
  const matchedCount = Number(reconciliation.matchedCount ?? walletRows.filter(w => w.isMatched).length);
  const totalWallets = Number(reconciliation.totalWallets ?? walletRows.length);
  const mismatchCount = Number(reconciliation.mismatchCount ?? (totalWallets - matchedCount));

  return `<div class="wf-grid-actions">
    <button type="button" class="btn btn--primary btn-sm" data-action="live:reconcile:reports">${icon('check')} Kiểm tra đối soát ngay</button>
    <button type="button" class="btn btn--outline btn-sm" data-action="live:export-csv:reports">${icon('download')} Tải Xuất CSV (UTF-8 BOM)</button>
  </div>
  <div class="kpi-grid">
    ${card('Trạng thái sổ cái', isAllMatched ? 'Khớp toàn bộ (MATCHED)' : `Lệch ${mismatchCount} ví`, `Kiểm tra lúc: ${reconciliation.checkedAt || '—'}`)}
    ${card('Ví đã đối soát', `${matchedCount} / ${totalWallets} ví`, 'So sánh cached_balance với tổng phát sinh sổ cái')}
    ${card('Tổng phát sinh Nợ (Debit)', vnd(tb.totalDebitBase || 0), 'Chỉ tính giao dịch POSTED & REVERSED')}
    ${card('Tổng phát sinh Có (Credit)', vnd(tb.totalCreditBase || 0), `Độ lệch tổng: ${vnd(tb.differenceBase || 0)}`)}
  </div>
  <section class="panel">
    <div class="panel-title">
      <div>
        <h2>Đối soát số dư ví và sổ cái kép (FR12.02)</h2>
        <p>Đối chiếu trực tiếp giữa <span class="wf-code">wallets.cached_balance</span> và tổng đại số <span class="wf-code">ledger_entries</span> đã ghi sổ</p>
      </div>
    </div>
    ${!walletRows.length ? empty('wallets', 'ví để đối soát') : `<div class="data-table-wrap"><table class="data-table"><thead><tr>
      <th>Ví & tài khoản</th><th>Loại ví</th><th>Số dư ví (Cached)</th><th>Tổng bút toán sổ cái</th><th>Độ lệch (Delta)</th><th>Số bút toán</th><th>Kết quả</th>
    </tr></thead><tbody id="recon-table-body">${walletRows.map(w => `<tr>
      <td><strong>${esc(w.walletName)}</strong> <small class="wf-muted">(${esc(w.currency)})</small></td>
      <td>${esc(w.walletType)}</td>
      <td>${display(w.cachedBalance, 'money', w.currency)}</td>
      <td>${display(w.ledgerSum, 'money', w.currency)}</td>
      <td>${display(w.delta, 'money', w.currency)}</td>
      <td>${esc(w.entryCount)} dòng</td>
      <td><span class="badge ${w.isMatched ? 'success' : 'danger'}">${w.isMatched ? 'MATCHED (Khớp 100%)' : 'MISMATCHED (Lệch)'}</span></td>
    </tr>`).join('')}</tbody></table></div>`}
  </section>
  <div class="panel-grid">
    <section class="panel">
      <div class="panel-title">
        <div>
          <h2>Bảng cân đối thử · Trial Balance</h2>
          <p>Tổng hợp 5 nhóm tài khoản kế toán (ASSET, LIABILITY, EQUITY, INCOME, EXPENSE)</p>
        </div>
      </div>
      ${!tbAccounts.length ? message('Chưa có bút toán sổ cái', 'Hãy tạo ví hoặc ghi nhận giao dịch để xem bảng cân đối thử.') : `<div class="data-table-wrap"><table class="data-table"><thead><tr>
        <th>Tài khoản sổ cái</th><th>Nhóm</th><th>Tổng Nợ (VND)</th><th>Tổng Có (VND)</th><th>Số dư thuần</th>
      </tr></thead><tbody>${tbAccounts.map(a => `<tr>
        <td><span class="wf-code">${esc(a.accountCode)}</span>${a.walletName ? `<br><small class="wf-muted">${esc(a.walletName)}</small>` : ''}</td>
        <td><span class="badge neutral">${esc(a.accountType)}</span></td>
        <td>${vnd(a.totalDebitBase)}</td>
        <td>${vnd(a.totalCreditBase)}</td>
        <td><strong>${vnd(a.netBalanceBase)}</strong></td>
      </tr>`).join('')}</tbody></table></div>`}
    </section>
    <section class="panel">
      <div class="panel-title">
        <div>
          <h2>Xuất báo cáo CSV & Nhật ký băm SHA-256 (FR12.03, FR12.04)</h2>
          <p>Tự động chèn UTF-8 BOM và khử công thức độc hại (=, +, -, @)</p>
        </div>
      </div>
      <div class="goal-history">
        <div class="goal-history-row"><span><strong>Chuẩn mã hóa tệp</strong><br><small class="wf-muted">Hiển thị chuẩn dấu tiếng Việt trên Microsoft Excel</small></span><b>UTF-8 with BOM</b></div>
        <div class="goal-history-row"><span><strong>Chống CSV Formula Injection</strong><br><small class="wf-muted">Tự động thêm dấu nháy đơn (') trước =, +, -, @, Tab, CR</small></span><b>OWASP Enabled</b></div>
        <div class="goal-history-row"><span><strong>Giới hạn tối đa mỗi lần xuất</strong><br><small class="wf-muted">Đảm bảo hiệu năng truy vấn dưới 3 giây</small></span><b>10.000 dòng</b></div>
      </div>
      ${recentExports.length ? `<div style="margin-top:14px"><h4 style="margin:0 0 8px;font-size:13px;color:#68778a">Lịch sử xuất CSV gần đây (export_audit_logs)</h4><div class="data-table-wrap"><table class="data-table"><thead><tr><th>Thời gian</th><th>Số dòng</th><th>SHA-256 Hash</th></tr></thead><tbody>${recentExports.map(ex => `<tr><td>${display(ex.created_at, 'date')}</td><td>${esc(ex.row_count)} dòng</td><td><span class="wf-code" title="${esc(ex.file_hash)}">${esc(String(ex.file_hash || '').slice(0, 16))}…</span></td></tr>`).join('')}</tbody></table></div></div>` : ''}
    </section>
  </div>`;
}

/**
 * FR14.01, FR14.02, BR11: Admin Macro Overview Page (strictly no personal financial data).
 */
function adminOverviewPage() {
  const issue = problem('adminUsers');
  if (issue && !ready('adminOverview')) return `<section class="panel">${issue}</section>`;

  const totalUsers = adminOverview.totalUsers ?? adminUsers.length;
  const activeUsers = adminOverview.activeUsers ?? adminUsers.filter(u => u.status === 'ACTIVE').length;
  const lockedUsers = adminOverview.lockedUsers ?? adminUsers.filter(u => u.status === 'LOCKED').length;
  const newThisMonth = adminOverview.newUsersThisMonth ?? 0;
  const currencies = Array.isArray(adminOverview.currencyDistribution) ? adminOverview.currencyDistribution : [];
  const logs = Array.isArray(adminOverview.recentAuditLogs) ? adminOverview.recentAuditLogs : adminAuditLogs.slice(0, 8);

  return `<div class="kpi-grid">
    ${card('Tổng tài khoản', String(totalUsers), 'Chỉ thống kê vĩ mô · Tuân thủ BR11')}
    ${card('Đang hoạt động (ACTIVE)', String(activeUsers), 'Có quyền truy cập hệ thống')}
    ${card('Đang bị khóa (LOCKED)', String(lockedUsers), 'Đã thu hồi toàn bộ phiên đăng nhập')}
    ${card('Đăng ký mới tháng này', String(newThisMonth), `${adminOverview.activeDefaultCategories ?? adminCategories.length} danh mục mặc định đang bật`)}
  </div>
  <div class="panel-grid">
    <section class="panel">
      <div class="panel-title"><div><h2>Phân bổ tiền tệ cơ sở</h2><p>Thống kê cấu hình tiền tệ của người dùng (không truy xuất số dư ví)</p></div></div>
      ${!currencies.length ? message('Chưa có thống kê tiền tệ', 'Dữ liệu sẽ hiển thị khi có người dùng đăng ký.') : `<div class="goal-history">${currencies.map(c => `<div class="goal-history-row"><span><strong>Tiền cơ sở ${esc(c.currency)}</strong></span><b>${esc(c.count)} tài khoản</b></div>`).join('')}</div>`}
    </section>
    <section class="panel">
      <div class="panel-title"><div><h2>Nhật ký quản trị gần đây</h2><p>Ghi vết bất biến mọi thao tác của Admin</p></div></div>
      ${!logs.length ? message('Chưa có nhật ký quản trị', 'Mọi thao tác khóa/mở khóa, cấu hình và phát thông báo sẽ được ghi lại tại đây.') : `<div class="goal-history">${logs.slice(0, 6).map(l => `<div class="goal-history-row"><span><strong>${esc(l.action)}</strong><br><small class="wf-muted">${esc(l.adminName)} · ${display(l.createdAt, 'date')}</small></span><small>${esc(String(l.reason || '').slice(0, 60))}</small></div>`).join('')}</div>`}
    </section>
  </div>
  <section class="panel">
    <div class="panel-title"><div><h2>Danh sách người dùng hệ thống</h2><p>Quản trị viên không được xem số dư ví hay giao dịch cá nhân (FR14.02, BR11)</p></div></div>
    ${adminUsersTable()}
  </section>`;
}

function adminUsersTable() {
  const issue = problem('adminUsers');
  if (issue) return issue;
  if (!adminUsers.length) return empty('adminUsers', 'người dùng');
  return `<div class="data-table-wrap"><table class="data-table"><thead><tr>
    <th>Người dùng</th><th>Email</th><th>Vai trò</th><th>Tiền cơ sở</th><th>Ngày tạo</th><th>Trạng thái</th><th></th>
  </tr></thead><tbody id="admin-users-table-body">${adminUsers.map(u => {
    const isLocked = String(u.status).toUpperCase() === 'LOCKED';
    const idEnc = encodeURIComponent(String(u.id));
    return `<tr>
      <td><strong>${esc(u.fullName || u.name)}</strong> <small class="wf-muted">#${esc(u.id)}</small></td>
      <td>${esc(u.email)}</td>
      <td><span class="badge ${u.role === 'ADMIN' ? 'blue' : 'neutral'}">${esc(u.role)}</span></td>
      <td>${esc(u.baseCurrency || 'VND')}</td>
      <td>${display(u.createdAt, 'date')}</td>
      <td><span class="badge ${isLocked ? 'danger' : 'success'}">${isLocked ? 'LOCKED (Đã khóa)' : 'ACTIVE (Hoạt động)'}</span></td>
      <td class="wf-table-actions">
        ${isLocked
          ? `<button type="button" class="text-action" data-action="live:unlock:adminUsers:${idEnc}">Mở khóa</button>`
          : `<button type="button" class="text-action" data-action="live:lock:adminUsers:${idEnc}">Khóa tài khoản</button>`}
      </td>
    </tr>`;
  }).join('')}</tbody></table></div>`;
}

function adminUsersPage() {
  return `<section class="panel">
    <div class="panel-title"><div><h2>Quản lý trạng thái tài khoản người dùng (UC12, FR14)</h2><p>Khóa tài khoản vi phạm (bắt buộc nhập lý do ≥ 10 ký tự và thu hồi phiên ngay lập tức)</p></div></div>
    ${adminUsersTable()}
  </section>`;
}

/**
 * FR13.01: Default System Categories Management Page.
 */
function adminCategoriesPage() {
  const issue = problem('adminCategories');
  const incomeCount = adminCategories.filter(c => c.type === 'INCOME' && c.isActive).length;
  const expenseCount = adminCategories.filter(c => c.type === 'EXPENSE' && c.isActive).length;

  return `<div class="wf-grid-actions">${addButton('adminCategories', 'Thêm danh mục mặc định')}</div>
  <div class="kpi-grid">
    ${card('Tổng danh mục mẫu', String(adminCategories.length), 'Tự động gán cho người dùng mới khi đăng ký')}
    ${card('Danh mục Thu nhập (INCOME)', String(incomeCount), 'Đang kích hoạt')}
    ${card('Danh mục Chi tiêu (EXPENSE)', String(expenseCount), 'Đang kích hoạt')}
  </div>
  <section class="panel">
    <div class="panel-title"><div><h2>Danh mục hệ thống mặc định (FR13.01)</h2><p>Chỉnh sửa tên, phân loại thu/chi hoặc tạm ngưng áp dụng cho tài khoản đăng ký mới</p></div></div>
    ${issue || (!adminCategories.length ? empty('adminCategories', 'danh mục mặc định') : `<div class="data-table-wrap"><table class="data-table"><thead><tr>
      <th>Mã</th><th>Tên danh mục mặc định</th><th>Nhóm thu / chi</th><th>Trạng thái</th><th></th>
    </tr></thead><tbody>${adminCategories.map(c => {
      const idEnc = encodeURIComponent(String(c.id));
      return `<tr>
        <td>#${esc(c.id)}</td>
        <td><strong>${esc(c.name)}</strong></td>
        <td><span class="badge ${c.type === 'INCOME' ? 'blue' : 'neutral'}">${c.type === 'INCOME' ? 'INCOME (Thu nhập)' : 'EXPENSE (Chi tiêu)'}</span></td>
        <td><span class="badge ${c.isActive ? 'success' : 'warn'}">${c.isActive ? 'Đang kích hoạt' : 'Tạm ngưng'}</span></td>
        <td class="wf-table-actions">
          <button type="button" class="text-action" data-action="live:edit:adminCategories:${idEnc}">Sửa</button>
          <button type="button" class="text-action" data-action="live:toggle:adminCategories:${idEnc}">${c.isActive ? 'Tạm ngưng' : 'Kích hoạt'}</button>
        </td>
      </tr>`;
    }).join('')}</tbody></table></div>`)}
  </section>`;
}

/**
 * FR13.03: Broadcast Announcements Page.
 */
function adminBroadcastsPage() {
  const issue = problem('adminBroadcasts');
  return `<div class="wf-grid-actions">${addButton('adminBroadcasts', 'Soạn & Gửi thông báo toàn hệ thống')}</div>
  <section class="panel">
    <div class="panel-title"><div><h2>Lịch sử phát thông báo toàn hệ thống (FR13.03, SQ08)</h2><p>Sử dụng SQL Batch Fan-out gửi đồng loạt tới tất cả người dùng đang hoạt động (ACTIVE)</p></div></div>
    ${issue || (!adminBroadcasts.length ? empty('adminBroadcasts', 'thông báo phát thanh nào') : `<div class="data-table-wrap"><table class="data-table"><thead><tr>
      <th>Tiêu đề & Nội dung</th><th>Mức ưu tiên</th><th>Số người nhận</th><th>Quản trị viên</th><th>Thời gian gửi</th>
    </tr></thead><tbody>${adminBroadcasts.map(b => `<tr>
      <td><strong>${esc(b.title)}</strong><p class="wf-muted" style="margin:4px 0 0;font-size:13px">${esc(b.body)}</p></td>
      <td><span class="badge ${['CRITICAL', 'URGENT'].includes(b.priority) ? 'danger' : b.priority === 'WARNING' ? 'warn' : 'blue'}">${esc(b.priority)}</span></td>
      <td>${esc(b.recipientCount)} người dùng</td>
      <td>${esc(b.adminName)}</td>
      <td>${display(b.createdAt, 'date')}</td>
    </tr>`).join('')}</tbody></table></div>`)}
  </section>`;
}

/**
 * FR13.02: System Operational Config & Admin Audit Logs Page.
 */
function adminSettingsPage() {
  const configIssue = problem('adminConfig');
  const auditIssue = problem('adminAuditLogs');

  return `<section class="panel">
    <div class="panel-title"><div><h2>Tham số vận hành hệ thống (FR13.02)</h2><p>Kiểm tra ràng buộc miền giá trị hợp lệ (ngoài ngưỡng cho phép sẽ bị từ chối với mã 422)</p></div></div>
    ${configIssue || (!adminConfig.length ? empty('adminConfig', 'cấu hình hệ thống') : `<div class="data-table-wrap"><table class="data-table"><thead><tr>
      <th>Tham số</th><th>Mã cấu hình</th><th>Giá trị hiện tại</th><th>Miền hợp lệ</th><th></th>
    </tr></thead><tbody>${adminConfig.map(cfg => {
      const rangeText = cfg.type === 'int' && cfg.min !== null && cfg.max !== null
        ? `${cfg.min} .. ${cfg.max}`
        : Array.isArray(cfg.allowed)
          ? cfg.allowed.join(' | ')
          : 'Chuỗi';
      return `<tr>
        <td><strong>${esc(cfg.label)}</strong><p class="wf-muted" style="margin:3px 0 0;font-size:12px">${esc(cfg.description)}</p></td>
        <td><span class="wf-code">${esc(cfg.key)}</span></td>
        <td><strong>${esc(cfg.value)}</strong></td>
        <td><small class="wf-muted">${esc(rangeText)}</small></td>
        <td class="wf-table-actions">
          <button type="button" class="text-action" data-action="live:edit:adminConfig:${encodeURIComponent(String(cfg.key))}">Cập nhật</button>
        </td>
      </tr>`;
    }).join('')}</tbody></table></div>`)}
  </section>
  <section class="panel" style="margin-top:18px">
    <div class="panel-title"><div><h2>Nhật ký kiểm toán quản trị · Admin Audit Logs</h2><p>Ghi nhận đầy đủ Admin thực hiện, hành động, đối tượng bị tác động và lý do</p></div></div>
    ${auditIssue || (!adminAuditLogs.length ? empty('adminAuditLogs', 'nhật ký quản trị') : `<div class="data-table-wrap"><table class="data-table"><thead><tr>
      <th>Mã</th><th>Thời gian</th><th>Quản trị viên</th><th>Hành động</th><th>Tài khoản mục tiêu</th><th>Lý do / Chi tiết</th>
    </tr></thead><tbody>${adminAuditLogs.map(log => `<tr>
      <td>#${esc(log.id)}</td>
      <td>${display(log.createdAt, 'date')}</td>
      <td>${esc(log.adminName)}</td>
      <td><span class="wf-code">${esc(log.action)}</span></td>
      <td>${log.targetName ? `${esc(log.targetName)} (#${esc(log.targetUserId)})` : '—'}</td>
      <td>${esc(log.reason || '—')}</td>
    </tr>`).join('')}</tbody></table></div>`)}
  </section>`;
}

function genericPage(key, cols) {
  const canCreate = ['transactions', 'categories', 'budgets', 'recurring'].includes(key);
  return `${canCreate ? `<div class="wf-grid-actions">${addButton(key)}</div>` : ''}<section class="panel">${table(key, cols)}</section>`;
}
const tableColumns = {
  wallets: [['Tên ví', 'name'], ['Loại', 'type'], ['Tiền tệ', 'currency'], ['Số dư', 'balance', 'money']],
  categories: [['Danh mục', 'name'], ['Loại', 'type'], ['Ghi chú', 'description']],
  recurring: [['Tên', 'name|description'], ['Số tiền', 'amount', 'money'], ['Chu kỳ', 'frequency|interval'], ['Ngày tiếp theo', 'nextRun|nextRunAt', 'date']],
  goals: [['Mục tiêu', 'name'], ['Đã tích lũy', 'current', 'money'], ['Đích đến', 'target', 'money'], ['Hạn hoàn thành', 'deadline', 'date']],
  loans: [['Khoản vay', 'name|title'], ['Loại', 'type|direction'], ['Dư gốc', 'outstanding|remainingPrincipal|balance', 'money'], ['Hạn trả', 'dueDate', 'date']],
  gold: [['Tài sản', 'name|type'], ['Số lượng', 'quantity'], ['Định giá', 'value|currentValue|valuation', 'money']],
  notifications: [['Nội dung', 'message|title'], ['Loại', 'type'], ['Ngày', 'createdAt|created_at', 'date'], ['Trạng thái', 'status']],
  adminUsers: [['Người dùng', 'fullName|name'], ['Email', 'email'], ['Quyền', 'role'], ['Trạng thái', 'status']]
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
    ${row('Xuất dữ liệu giao dịch', 'Xuất các giao dịch đã tải từ API ra CSV chuẩn UTF-8 BOM chống Formula Injection.', '<button class="btn btn--outline btn-sm" data-action="live:export-csv:reports">Xuất CSV</button>')}
    ${row('Đối soát sổ cái kép', 'Kiểm tra tính nhất quán giữa số dư ví và các bút toán Nợ/Có.', '<button class="btn btn--outline btn-sm" data-page="reconciliation">Mở đối soát</button>')}
  </section>
  <section class="panel"><h2>Thông báo và trải nghiệm</h2>
    ${row('Cảnh báo ngân sách', 'Được tính theo các ngưỡng 80% và 100% từ dữ liệu chi tiêu đã ghi sổ.')}
    ${row('Quản lý thông báo', 'Xem thông báo được đồng bộ từ máy chủ.', '<button class="btn btn--outline btn-sm" data-page="notifications">Xem thông báo</button>')}
    ${row('Tùy chỉnh giao diện', 'Chưa có API lưu tùy chọn giao diện theo tài khoản. Không hiển thị tùy chỉnh giả.')}
  </section></div>`;
}
export function liveRender(page, { state, heading, currentUser }) {
  if (page.startsWith('admin-') && currentUser.role !== 'ADMIN') return message('Không có quyền', 'Chỉ quản trị viên mới truy cập được mục này.');
  const section = page === 'overview' ? overview(currentUser)
    : page === 'transactions' ? `<div class="wf-grid-actions">${addButton('transactions', 'Ghi giao dịch')}</div><section class="panel">${transactionList(state)}</section>`
    : page === 'budgets' ? budgetsPage()
    : page === 'notifications' ? notificationsPage(state)
    : page === 'reports' || page === 'report' ? reports()
    : page === 'reconciliation' || page === 'reconcile' ? reconciliationPage()
    : page === 'settings' ? settingsPage(currentUser)
    : page === 'admin-overview' ? adminOverviewPage()
    : page === 'admin-users' ? adminUsersPage()
    : page === 'admin-categories' ? adminCategoriesPage()
    : page === 'admin-broadcasts' ? adminBroadcastsPage()
    : page === 'admin-settings' || page === 'admin-audit' ? adminSettingsPage()
    : page in tableColumns ? genericPage(page, tableColumns[page])
    : message('Chức năng đang được tích hợp', 'Chưa có API thực cho màn hình này. Không hiển thị dữ liệu mẫu.');
  return baseStyle + heading(page) + section;
}

// Live forms mirror the actual Controller/Service DTOs, never demo-data fields.
const field = (name, label, type = 'text', extra = '') => `<div class="field"><label for="live-${esc(name)}">${esc(label)}</label><input id="live-${esc(name)}" name="${esc(name)}" type="${type}" ${extra} required></div>`;
const moneyField = (name, label, extra = '') => field(name, label, 'text', `inputmode="numeric" data-money-input maxlength="24" ${extra}`);
const select = (name, label, options, optional = false) => `<div class="field"><label for="live-${esc(name)}">${esc(label)}</label><select id="live-${esc(name)}" name="${esc(name)}" ${optional ? '' : 'required'}>${optional ? '<option value="">Không chọn</option>' : ''}${options.map(([v, l]) => `<option value="${esc(v)}">${esc(l)}</option>`).join('')}</select></div>`;
const walletOptions = () => wallets.filter(w => String(w.status || 'ACTIVE').toUpperCase() === 'ACTIVE').map(w => [w.id, `${w.name} (${w.currency})`]);
const categoryOptions = (type = 'EXPENSE') => categories.filter(c => !type || c.type === type).map(c => [c.id, c.name]);
const goalWalletOptions = () => walletOptions().filter(([id]) => String(wallets.find(w => String(w.id) === String(id))?.currency).toUpperCase() === 'VND');
const goalOptions = (action = 'DEPOSIT') => goals.filter(g => action === 'WITHDRAWAL' ? ['ACTIVE', 'COMPLETED'].includes(String(g.status).toUpperCase()) && Number(g.current) > 0 : String(g.status).toUpperCase() === 'ACTIVE').map(g => [g.id, `${g.name} · ${vnd(g.current)} / ${vnd(g.target)}`]);
const needReferenceData = name => name === 'transactions' ? (!ready('wallets') || !walletOptions().length)
  : name === 'recurring' ? (!ready('wallets') || !walletOptions().length || !ready('categories'))
    : name === 'budgets' ? (!ready('categories') || !categoryOptions().length) : false;

function createForm(name) {
  if (name === 'categories' || name === 'adminCategories') {
    return field('name', 'Tên danh mục', 'text', 'maxlength="120"') + select('type', 'Loại danh mục', [['EXPENSE', 'Chi tiêu (EXPENSE)'], ['INCOME', 'Thu nhập (INCOME)']]);
  }
  if (name === 'adminBroadcasts') {
    return field('title', 'Tiêu đề thông báo (tối đa 150 ký tự)', 'text', 'maxlength="150"')
      + select('priority', 'Mức độ ưu tiên', [['INFO', 'Thông tin (INFO)'], ['WARNING', 'Cảnh báo (WARNING)'], ['URGENT', 'Khẩn cấp (URGENT)'], ['MAINTENANCE', 'Bảo trì hệ thống (MAINTENANCE)']])
      + `<div class="field"><label for="live-body">Nội dung thông báo (tối đa 2.000 ký tự)</label><textarea id="live-body" name="body" rows="4" maxlength="2000" required placeholder="Nhập nội dung thông báo gửi tới toàn bộ người dùng đang hoạt động..."></textarea></div>`;
  }
  if (name === 'transactions') return field('description', 'Mô tả giao dịch', 'text', 'maxlength="255"')
    + select('type', 'Loại giao dịch', [['EXPENSE', 'Chi tiêu'], ['INCOME', 'Thu nhập'], ['TRANSFER', 'Chuyển tiền'], ['SAVINGS_GOAL', 'Chuyển vào mục tiêu tiết kiệm']])
    + select('goalAction', 'Thao tác với mục tiêu', [['DEPOSIT', 'Chuyển vào mục tiêu'], ['WITHDRAWAL', 'Lấy khỏi mục tiêu']])
    + moneyField('amount', 'Số tiền')
    + select('walletId', 'Ví sử dụng', walletOptions())
    + select('goalId', 'Mục tiêu tiết kiệm', goalOptions('DEPOSIT'))
    + select('fromWalletId', 'Ví gửi', walletOptions())
    + select('toWalletId', 'Ví nhận', walletOptions())
    + select('categoryId', 'Danh mục', categories.map(c => [c.id, `${c.name} · ${c.type}`]))
    + field('occurredOn', 'Ngày giao dịch', 'date');
  if (name === 'budgets') return select('categoryId', 'Danh mục chi tiêu', categoryOptions())
    + field('month', 'Kỳ ngân sách', 'month') + moneyField('limitAmount', 'Hạn mức');
  if (name === 'recurring') return field('name', 'Tên khoản định kỳ', 'text', 'maxlength="120"')
    + moneyField('amount', 'Số tiền')
    + select('walletId', 'Ví thanh toán', walletOptions())
    + select('categoryId', 'Danh mục chi tiêu (nếu có)', categoryOptions(), true)
    + field('dayOfMonth', 'Ngày lặp lại hằng tháng (1–31)', 'number', 'min="1" max="31" step="1"');
  return '';
}

function triggerCsvDownload(csvText, filename) {
  const hasBom = csvText.startsWith('\uFEFF');
  const blob = new Blob([hasBom ? csvText : '\uFEFF' + csvText], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = filename || 'wealthflow_transactions.csv';
  anchor.click();
  setTimeout(() => URL.revokeObjectURL(url), 2000);
}

export function liveAction(action, { dialog, toast, reload }) {
  if (!action.startsWith('live:')) return false;
  const [, verb, name, encodedId] = action.split(':');

  // FR12: Reconcile & CSV Export actions
  if (name === 'reports' && verb === 'reconcile') {
    toast('Đang đối soát số dư ví với sổ cái kép…');
    void ReportApi.runReconciliation({})
      .then(async res => {
        await loadLiveData();
        reload();
        const statusMsg = res.isAllMatched
          ? `Đối soát hoàn tất: Cả ${res.totalWallets} ví đều khớp 100% với sổ cái!`
          : `Cảnh báo: Phát hiện ${res.mismatchCount} ví có độ lệch với sổ cái.`;
        toast(statusMsg);
      })
      .catch(error => toast(`Không thể đối soát: ${error.message}`));
    return true;
  }

  if (name === 'reports' && verb === 'export-csv') {
    document.querySelector('#modal-overline').textContent = 'XUẤT BÁO CÁO GIAO DỊCH (FR12.03, FR12.04)';
    document.querySelector('#modal-title').textContent = 'Tải xuất tệp CSV chuẩn UTF-8 BOM';
    document.querySelector('#modal-content').innerHTML = `<p class="form-hint">Tệp CSV được chèn mã <strong>UTF-8 BOM</strong> để đọc chuẩn tiếng Việt trên Excel, đồng thời tự động khử các ký tự công thức độc hại (<code>=</code>, <code>+</code>, <code>-</code>, <code>@</code>) và ghi vết mã băm <strong>SHA-256</strong>.</p>
    <form id="live-export-csv-form">
      <div class="form-grid">
        ${select('type', 'Loại giao dịch', [['', 'Tất cả loại giao dịch'], ['EXPENSE', 'Chi tiêu (EXPENSE)'], ['INCOME', 'Thu nhập (INCOME)'], ['TRANSFER', 'Chuyển tiền nội bộ (TRANSFER)']], true)}
        ${select('walletId', 'Ví tài khoản', walletOptions(), true)}
        <div class="field"><label for="live-startDate">Từ ngày (tùy chọn)</label><input id="live-startDate" name="startDate" type="date"></div>
        <div class="field"><label for="live-endDate">Đến ngày (tùy chọn)</label><input id="live-endDate" name="endDate" type="date"></div>
      </div>
      <p id="live-form-feedback" role="status" aria-live="polite"></p>
      <div class="modal-footer">
        <button type="button" class="btn btn--outline" data-close-modal>Hủy</button>
        <button type="submit" class="btn btn--primary">${icon('download')} Tải tệp CSV</button>
      </div>
    </form>`;
    const form = document.querySelector('#live-export-csv-form');
    form.addEventListener('submit', async e => {
      e.preventDefault();
      const submit = form.querySelector('[type=submit]');
      const feedback = form.querySelector('#live-form-feedback');
      const params = Object.fromEntries(new FormData(form));
      submit.disabled = true;
      feedback.textContent = 'Đang kết xuất và tính mã băm SHA-256…';
      try {
        const res = await ReportApi.exportTransactionsCsv(params);
        triggerCsvDownload(res.csv || '', res.filename || 'wealthflow_transactions.csv');
        dialog.close();
        await loadLiveData();
        reload();
        toast(`Đã xuất ${res.rowCount ?? 0} dòng CSV (SHA-256: ${String(res.fileHash || '').slice(0, 12)}…).`);
      } catch (error) {
        feedback.textContent = `Không thể xuất CSV: ${error.message}`;
      } finally {
        submit.disabled = false;
      }
    });
    dialog.showModal();
    return true;
  }

  // FR03.03 & FR12.01: Reverse a POSTED transaction
  if (name === 'transactions' && verb === 'reverse') {
    const id = decodeURIComponent(encodedId || '');
    const item = transactions.find(x => String(x.id) === id);
    if (!item || !/^\d+$/.test(id)) {
      toast('Chỉ có thể đảo các giao dịch tài chính đã ghi sổ.');
      return true;
    }
    document.querySelector('#modal-overline').textContent = 'BÚT TOÁN ĐẢO NGƯỢC (BR06)';
    document.querySelector('#modal-title').textContent = `Đảo giao dịch #${item.id}: ${item.name}`;
    document.querySelector('#modal-content').innerHTML = `<p class="form-hint">Hệ thống không xóa cứng giao dịch. Thao tác này sẽ sinh một giao dịch đối ứng đảo ngược chiều Nợ/Có theo đúng tỷ giá lúc ghi nhận và hoàn lại số dư ví.</p>
    <form id="live-tx-reverse-form">
      <div class="form-grid">
        ${field('reason', 'Lý do đảo bút toán', 'text', 'maxlength="255" value="Điều chỉnh sai sót nhập liệu"')}
      </div>
      <p id="live-form-feedback" role="status" aria-live="polite"></p>
      <div class="modal-footer">
        <button type="button" class="btn btn--outline" data-close-modal>Hủy</button>
        <button type="submit" class="btn btn--danger">Xác nhận đảo bút toán</button>
      </div>
    </form>`;
    const form = document.querySelector('#live-tx-reverse-form');
    form.addEventListener('submit', async e => {
      e.preventDefault();
      if (!form.reportValidity()) return;
      const submit = form.querySelector('[type=submit]');
      const feedback = form.querySelector('#live-form-feedback');
      const reason = form.elements.namedItem('reason').value.trim();
      submit.disabled = true;
      feedback.textContent = 'Đang thực hiện bút toán đảo…';
      try {
        await httpClient(`api/transactions/${encodeURIComponent(id)}/reverse`, {
          method: 'POST',
          data: { reason }
        });
        dialog.close();
        await loadLiveData();
        reload();
        toast(`Đã đảo giao dịch #${id} và khôi phục số dư ví thành công.`);
      } catch (error) {
        feedback.textContent = `Không thể đảo giao dịch: ${error.message}`;
      } finally {
        submit.disabled = false;
      }
    });
    dialog.showModal();
    return true;
  }

  // UC12 / FR14.03 & FR14.04: Admin Lock / Unlock User
  if (name === 'adminUsers' && (verb === 'lock' || verb === 'unlock')) {
    const id = decodeURIComponent(encodedId || '');
    const user = adminUsers.find(u => String(u.id) === id);
    if (!user || !/^\d+$/.test(id)) {
      toast('Không tìm thấy tài khoản người dùng.');
      return true;
    }
    const isLock = verb === 'lock';
    document.querySelector('#modal-overline').textContent = isLock ? 'KHÓA TÀI KHOẢN & THU HỒI PHIÊN (FR14.03)' : 'MỞ KHÓA TÀI KHOẢN (FR14.04)';
    document.querySelector('#modal-title').textContent = `${isLock ? 'Khóa' : 'Mở khóa'}: ${user.fullName || user.email}`;
    document.querySelector('#modal-content').innerHTML = `<p class="form-hint">${isLock
      ? 'Khi bị khóa, toàn bộ phiên đăng nhập (Refresh/Access Token) của tài khoản này sẽ bị hủy hiệu lực ngay lập tức và được ghi vào nhật ký kiểm toán.'
      : 'Khôi phục trạng thái ACTIVE để người dùng có thể đăng nhập lại vào hệ thống.'}</p>
    <form id="live-admin-user-status-form">
      <div class="form-grid">
        <div class="field">
          <label for="live-admin-reason">${isLock ? 'Lý do khóa tài khoản (bắt buộc tối thiểu 10 ký tự) *' : 'Ghi chú mở khóa'}</label>
          <textarea id="live-admin-reason" name="reason" rows="3" ${isLock ? 'minlength="10" required' : ''} placeholder="${isLock ? 'Nhập lý do vi phạm hoặc yêu cầu bảo mật (tối thiểu 10 ký tự)...' : 'Đã xác minh và mở khóa tài khoản'}">${isLock ? '' : 'Đã xác minh và khôi phục quyền truy cập.'}</textarea>
        </div>
      </div>
      <p id="live-form-feedback" role="status" aria-live="polite"></p>
      <div class="modal-footer">
        <button type="button" class="btn btn--outline" data-close-modal>Hủy</button>
        <button type="submit" class="btn ${isLock ? 'btn--danger' : 'btn--primary'}">${isLock ? 'Xác nhận khóa & thu hồi phiên' : 'Xác nhận mở khóa'}</button>
      </div>
    </form>`;
    const form = document.querySelector('#live-admin-user-status-form');
    form.addEventListener('submit', async e => {
      e.preventDefault();
      if (!form.reportValidity()) return;
      const submit = form.querySelector('[type=submit]');
      const feedback = form.querySelector('#live-form-feedback');
      const reason = form.elements.namedItem('reason').value.trim();
      submit.disabled = true;
      feedback.textContent = 'Đang cập nhật trạng thái tài khoản…';
      try {
        if (isLock) await AdminApi.lockUser(id, reason);
        else await AdminApi.unlockUser(id, reason);
        dialog.close();
        await loadLiveData({ admin: true });
        reload();
        toast(isLock ? `Đã khóa tài khoản ${user.email} và thu hồi phiên đăng nhập.` : `Đã mở khóa tài khoản ${user.email}.`);
      } catch (error) {
        feedback.textContent = `Không thể thực hiện: ${error.message}`;
      } finally {
        submit.disabled = false;
      }
    });
    dialog.showModal();
    return true;
  }

  // FR13.01: Admin Edit / Toggle Default Category
  if (name === 'adminCategories' && (verb === 'edit' || verb === 'toggle')) {
    const id = decodeURIComponent(encodedId || '');
    const cat = adminCategories.find(c => String(c.id) === id);
    if (!cat || !/^\d+$/.test(id)) {
      toast('Không tìm thấy danh mục mặc định.');
      return true;
    }
    if (verb === 'toggle') {
      void AdminApi.updateCategory(id, { name: cat.name, categoryType: cat.type, isActive: !cat.isActive })
        .then(async () => {
          await loadLiveData({ admin: true });
          reload();
          toast(`Đã ${!cat.isActive ? 'kích hoạt' : 'tạm ngưng'} danh mục "${cat.name}".`);
        })
        .catch(error => toast(`Không thể cập nhật: ${error.message}`));
      return true;
    }
    document.querySelector('#modal-overline').textContent = 'DANH MỤC MẶC ĐỊNH (FR13.01)';
    document.querySelector('#modal-title').textContent = `Chỉnh sửa: ${cat.name}`;
    document.querySelector('#modal-content').innerHTML = `<form id="live-admin-cat-edit-form">
      <div class="form-grid">
        ${field('name', 'Tên danh mục mặc định', 'text', `maxlength="120" value="${esc(cat.name)}"`)}
        <div class="field"><label for="live-cat-type">Nhóm danh mục</label><select id="live-cat-type" name="categoryType"><option value="EXPENSE" ${cat.type === 'EXPENSE' ? 'selected' : ''}>Chi tiêu (EXPENSE)</option><option value="INCOME" ${cat.type === 'INCOME' ? 'selected' : ''}>Thu nhập (INCOME)</option></select></div>
      </div>
      <p id="live-form-feedback" role="status" aria-live="polite"></p>
      <div class="modal-footer">
        <button type="button" class="btn btn--outline" data-close-modal>Hủy</button>
        <button type="submit" class="btn btn--primary">Lưu thay đổi</button>
      </div>
    </form>`;
    const form = document.querySelector('#live-admin-cat-edit-form');
    form.addEventListener('submit', async e => {
      e.preventDefault();
      if (!form.reportValidity()) return;
      const submit = form.querySelector('[type=submit]');
      const feedback = form.querySelector('#live-form-feedback');
      const payload = Object.fromEntries(new FormData(form));
      submit.disabled = true;
      feedback.textContent = 'Đang lưu…';
      try {
        await AdminApi.updateCategory(id, { ...payload, isActive: cat.isActive });
        dialog.close();
        await loadLiveData({ admin: true });
        reload();
        toast('Đã cập nhật danh mục hệ thống mặc định.');
      } catch (error) {
        feedback.textContent = `Không thể cập nhật: ${error.message}`;
      } finally {
        submit.disabled = false;
      }
    });
    dialog.showModal();
    return true;
  }

  // FR13.02: Admin Edit System Config Parameter
  if (name === 'adminConfig' && verb === 'edit') {
    const key = decodeURIComponent(encodedId || '');
    const cfg = adminConfig.find(c => String(c.key) === key);
    if (!cfg) {
      toast('Không tìm thấy tham số cấu hình.');
      return true;
    }
    const controlHtml = Array.isArray(cfg.allowed)
      ? `<div class="field"><label for="live-cfg-val">${esc(cfg.label)}</label><select id="live-cfg-val" name="value">${cfg.allowed.map(opt => `<option value="${esc(opt)}" ${String(cfg.value) === String(opt) ? 'selected' : ''}>${esc(opt)}</option>`).join('')}</select></div>`
      : field('value', `${cfg.label} (${cfg.min ?? ''} .. ${cfg.max ?? ''})`, cfg.type === 'int' ? 'number' : 'text', `value="${esc(cfg.value)}" ${cfg.min !== null ? `min="${cfg.min}"` : ''} ${cfg.max !== null ? `max="${cfg.max}"` : ''}`);
    document.querySelector('#modal-overline').textContent = 'CẤU HÌNH VẬN HÀNH (FR13.02)';
    document.querySelector('#modal-title').textContent = `Cập nhật ${cfg.key}`;
    document.querySelector('#modal-content').innerHTML = `<p class="form-hint">${esc(cfg.description)}</p>
    <form id="live-admin-config-form">
      <div class="form-grid">${controlHtml}</div>
      <p id="live-form-feedback" role="status" aria-live="polite"></p>
      <div class="modal-footer">
        <button type="button" class="btn btn--outline" data-close-modal>Hủy</button>
        <button type="submit" class="btn btn--primary">Lưu cấu hình</button>
      </div>
    </form>`;
    const form = document.querySelector('#live-admin-config-form');
    form.addEventListener('submit', async e => {
      e.preventDefault();
      if (!form.reportValidity()) return;
      const submit = form.querySelector('[type=submit]');
      const feedback = form.querySelector('#live-form-feedback');
      const value = form.elements.namedItem('value').value.trim();
      submit.disabled = true;
      feedback.textContent = 'Đang kiểm tra miền hợp lệ và lưu cấu hình…';
      try {
        await AdminApi.updateSystemConfigKey(cfg.key, value);
        dialog.close();
        await loadLiveData({ admin: true });
        reload();
        toast(`Đã cập nhật tham số ${cfg.key} = ${value}.`);
      } catch (error) {
        feedback.textContent = `Không thể lưu: ${error.message}`;
      } finally {
        submit.disabled = false;
      }
    });
    dialog.showModal();
    return true;
  }

  const collection = ITEMS[name];
  if (!collection) { toast('Phân hệ không hợp lệ.'); return true; }
  if (verb === 'detail') {
    const item = collection.find(x => String(x.id) === decodeURIComponent(encodedId || ''));
    if (!item) { toast('Không tìm thấy dữ liệu trong tài khoản này.'); return true; }
    if (name === 'transactions') {
      document.querySelector('#modal-overline').textContent = '';
      document.querySelector('#modal-title').textContent = 'Chi tiết giao dịch & Bút toán kép';
      document.querySelector('#modal-content').innerHTML = transactionDetail(item);
      dialog.showModal();
      if (/^\d+$/.test(String(item.id)) && (!Array.isArray(item.entries) || !item.entries.length)) {
        void httpClient(`api/transactions/${encodeURIComponent(String(item.id))}`, { method: 'GET' })
          .then(res => {
            const fullTx = res?.transaction;
            if (fullTx && Array.isArray(fullTx.entries)) {
              item.entries = fullTx.entries;
              item.originalTransactionId = fullTx.original_transaction_id ?? item.originalTransactionId;
              document.querySelector('#modal-content').innerHTML = transactionDetail(item, fullTx.entries);
            }
          })
          .catch(() => {});
      }
      return true;
    }
    document.querySelector('#modal-overline').textContent = TITLES[name] || name;
    document.querySelector('#modal-title').textContent = String(val(item, 'name', 'title', 'description', 'id') || 'Chi tiết');
    const dl = Object.entries(item).filter(([k, v]) => typeof v !== 'object' && !/password|secret|token/i.test(k)).slice(0, 20)
      .map(([k, v]) => `<div><dt>${esc(k)}</dt><dd>${esc(v)}</dd></div>`).join('');
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
    <form id="live-budget-edit-form"><div class="form-grid">${moneyField('limitAmount', 'Hạn mức mới (VND)', `value="${esc(formatMoneyInput(item.limit))}"`)}</div><p id="live-form-feedback" role="status" aria-live="polite"></p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">Cập nhật hạn mức</button></div></form>`;
    const form = document.querySelector('#live-budget-edit-form');
    form.addEventListener('submit', async e => {
      e.preventDefault(); if (!form.reportValidity()) return;
      const submit = form.querySelector('[type=submit]');
      const feedback = form.querySelector('#live-form-feedback');
      const limitAmount = Number(moneyInputValue(form.elements.namedItem('limitAmount').value));
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
  if (verb !== 'create' || !['transactions', 'categories', 'budgets', 'recurring', 'adminCategories', 'adminBroadcasts'].includes(name)) { toast('Thao tác chưa có API được xác nhận. Không ghi dữ liệu giả.'); return true; }
  if (!ready(name)) { toast('Chưa có API thật để ghi dữ liệu.'); return true; }
  if (needReferenceData(name)) { toast('Hãy tạo ít nhất một ví và danh mục phù hợp trước; hoặc kiểm tra kết nối API.'); return true; }
  const isTransaction = name === 'transactions';
  document.querySelector('#modal-overline').textContent = isTransaction ? '' : 'DỮ LIỆU THẬT';
  document.querySelector('#modal-title').textContent = isTransaction ? 'Thêm Giao dịch' : `Thêm ${TITLES[name]}`;
  document.querySelector('#modal-content').innerHTML = `${isTransaction ? '' : '<p class="form-hint">Dữ liệu chỉ được ghi khi backend xác nhận thành công. Nếu API thiếu chức năng, hệ thống sẽ báo lỗi.</p>'}
  <form id="live-entry-form"><div class="form-grid">${createForm(name)}</div><p id="live-form-feedback" role="status" aria-live="polite"></p><div class="modal-footer"><button type="button" class="btn btn--outline" data-close-modal>Hủy</button><button type="submit" class="btn btn--primary">Lưu vào hệ thống</button></div></form>`;
  const form = document.querySelector('#live-entry-form');
  if (name === 'transactions') {
    const types = form.elements.namedItem('type');
    const wallet = form.elements.namedItem('walletId');
    const fromWallet = form.elements.namedItem('fromWalletId');
    const toWallet = form.elements.namedItem('toWalletId');
    const category = form.elements.namedItem('categoryId');
    const goalAction = form.elements.namedItem('goalAction');
    const goal = form.elements.namedItem('goalId');
    if (toWallet.options.length > 1) toWallet.selectedIndex = 1;
    function syncTransactionFields() {
      const isTransfer = types.value === 'TRANSFER';
      const isGoal = types.value === 'SAVINGS_GOAL';
      wallet.disabled = isTransfer;
      category.disabled = isTransfer || isGoal;
      goalAction.disabled = !isGoal;
      goal.disabled = !isGoal;
      fromWallet.disabled = !isTransfer;
      toWallet.disabled = !isTransfer;
      wallet.closest('.field').hidden = isTransfer;
      category.closest('.field').hidden = isTransfer || isGoal;
      goalAction.closest('.field').hidden = !isGoal;
      goal.closest('.field').hidden = !isGoal;
      fromWallet.closest('.field').hidden = !isTransfer;
      toWallet.closest('.field').hidden = !isTransfer;
      wallet.closest('.field').querySelector('label').textContent = isGoal && goalAction.value === 'DEPOSIT' ? 'Ví chuyển' : isGoal ? 'Ví nhận' : 'Ví sử dụng';
      const walletChoices = isGoal ? goalWalletOptions() : walletOptions();
      const previousWallet = wallet.value;
      wallet.innerHTML = walletChoices.map(([v, l]) => `<option value="${esc(v)}">${esc(l)}</option>`).join('');
      if (walletChoices.some(([v]) => String(v) === String(previousWallet))) wallet.value = previousWallet;
      const availableGoals = isGoal ? goalOptions(goalAction.value) : [];
      const previousGoal = goal.value;
      if (isGoal) goal.innerHTML = availableGoals.map(([v, l]) => `<option value="${esc(v)}">${esc(l)}</option>`).join('');
      if (availableGoals.some(([v]) => String(v) === String(previousGoal))) goal.value = previousGoal;
      const choices = (isTransfer || isGoal) ? [] : categoryOptions(types.value);
      if (!isTransfer && !isGoal) category.innerHTML = choices.map(([v, l]) => `<option value="${esc(v)}">${esc(l)}</option>`).join('');
      const submit = form.querySelector('[type=submit]');
      const feedback = form.querySelector('#live-form-feedback');
      const sameWallet = isTransfer && fromWallet.value === toWallet.value;
      const missingTransferWallets = isTransfer && (!fromWallet.value || !toWallet.value || walletOptions().length < 2);
      const missingGoalData = isGoal && (!wallet.value || !goal.value || !availableGoals.length);
      submit.disabled = isTransfer ? (missingTransferWallets || sameWallet) : isGoal ? missingGoalData : !choices.length;
      feedback.textContent = missingTransferWallets ? 'Bạn cần có ít nhất hai ví đang sử dụng để chuyển tiền.'
        : sameWallet ? 'Ví gửi và ví nhận phải khác nhau.'
          : missingGoalData ? 'Bạn cần có ví VND và một mục tiêu đang hoạt động.'
            : (!isTransfer && !isGoal && !choices.length ? 'Bạn cần tạo danh mục tương ứng trước khi ghi giao dịch.' : '');
    }
    types.addEventListener('change', syncTransactionFields);
    goalAction.addEventListener('change', syncTransactionFields);
    fromWallet.addEventListener('change', syncTransactionFields);
    toWallet.addEventListener('change', syncTransactionFields);
    syncTransactionFields();
  }
  if (name === 'transactions') form.elements.namedItem('occurredOn').value = new Date().toLocaleDateString('en-CA');
  if (name === 'budgets') form.elements.namedItem('month').value = new Date().toLocaleDateString('en-CA').slice(0, 7);
  form.addEventListener('submit', async e => {
    e.preventDefault(); if (!form.reportValidity()) return;
    const submit = form.querySelector('[type=submit]');
    const feedback = document.querySelector('#live-form-feedback');
    const record = Object.fromEntries(new FormData(form));
    for (const key of ['amount', 'limitAmount']) if (record[key] !== undefined && record[key] !== '') record[key] = Number(moneyInputValue(record[key]));
    for (const key of ['walletId', 'goalId', 'fromWalletId', 'toWalletId', 'categoryId', 'dayOfMonth']) if (record[key] !== undefined && record[key] !== '') record[key] = Number(record[key]);
    if (record.categoryId === '') delete record.categoryId;
    const isTransfer = name === 'transactions' && record.type === 'TRANSFER';
    const isGoal = name === 'transactions' && record.type === 'SAVINGS_GOAL';
    const isAdminOp = name === 'adminCategories' || name === 'adminBroadcasts';
    if (name === 'transactions' && !isTransfer && !isGoal) {
      const selected = wallets.find(w => String(w.id) === String(record.walletId));
      record.currency = selected?.currency || 'VND';
    }
    if (isTransfer) delete record.type;
    if (name === 'recurring') record.executionMode = 'DRAFT_REMINDER';
    submit.disabled = true; feedback.textContent = 'Đang lưu vào backend…';
    try {
      if (isTransfer) await httpClient('transactions/transfer', { method: 'POST', data: record });
      else if (isGoal) {
        const payload = { walletId: record.walletId, amount: record.amount, allocatedOn: record.occurredOn, note: record.description };
        if (record.goalAction === 'WITHDRAWAL') await GoalApi.withdrawFunds(record.goalId, payload);
        else await GoalApi.allocateFunds(record.goalId, payload);
      } else if (name === 'adminCategories') {
        await AdminApi.createCategory({ name: record.name, categoryType: record.type, isActive: true });
      } else if (name === 'adminBroadcasts') {
        await AdminApi.broadcastAnnouncement(record);
      } else {
        await mutateLiveData(name, 'POST', record);
      }
      dialog.close();
      await loadLiveData({ admin: isAdminOp });
      reload();
      toast('Đã lưu thành công trên máy chủ.');
    } catch (error) { feedback.textContent = `Không thể lưu: ${error.message}`; }
    finally { submit.disabled = false; }
  });
  dialog.showModal(); return true;
}
