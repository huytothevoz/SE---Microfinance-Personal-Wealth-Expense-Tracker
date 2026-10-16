/** Page templates. Keep rendering and application state separate from routing/events. */
import { wallets, transactions, budgets, recurring, goals, loans, gold, notifications, categories, adminUsers, trend, incomeTrend, expenseTrend } from '../data/fixtures.js';
import { icon, safe, money, shortMoney, percent } from './ui-core.js';

export function createPages({ state, heading, btn, panel, currentUser }) {
function kpi(label, value, desc, ico = 'chart', tone = '', delta = '') {
  return `<div class="kpi-card">\
<div class="kpi-top">\
<span class="kpi-label">\
${label}\
</span>\
<div class="kpi-icon ${tone}">${icon(ico)}\
</div>\
</div>\
<div class="kpi-number">\
${value}\
</div>\
<div class="kpi-desc">\
${delta ? `<span class="delta ${delta.startsWith('-') ? 'down' : ''}">${delta}</span>` : ''}${desc}</div></div>`;

}
function lineChart() {
  const max = 145, min = 65, coords = trend.map((v, i) => `${30 + i * 78},${190 - (v - min) / (max - min) * 155}`);
  const area = `M${coords[0]} L${coords.slice(1).join(' L')} L498,190 L30,190 Z`;
  return `<svg class="chart-svg" viewBox="0 0 525 220" role="img" aria-label="Tài sản ròng tăng từ 76 lên 128,45 triệu đồng qua 7 kỳ">\
<defs>\
<linearGradient id="netFill" x1="0" y1="0" x2="0" y2="1">\
<stop offset="0" stop-color="#39a5b4" stop-opacity=".2"/>\
<stop offset="1" stop-color="#39a5b4" stop-opacity="0"/>\
</linearGradient>\
</defs>\
${[36, 85, 135, 185].map(y => `<path class="chart-gridline" d="M30 ${y}H510"/>`).join('')}\
<path d="${area}" fill="url(#netFill)"/>\
<path d="M${coords.join(' L')}" fill="none" stroke="#17899d" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"/>\
<circle cx="498" cy="${190 - (128.45 - min) / (max - min) * 155}" r="5" fill="#17899d" stroke="white" stroke-width="3"/>\
<text x="5" y="40" class="chart-label">140tr</text>\
<text x="5" y="90" class="chart-label">115tr</text>\
<text x="5" y="140" class="chart-label">90tr</text>\
<text x="5" y="190" class="chart-label">65tr</text>\
</svg>\
<div class="chart-months">\
<span>Thg 4</span>\
<span>Thg 5</span>\
<span>Thg 6</span>\
<span>Thg 7</span>\
<span>Thg 8</span>\
<span>Thg 9</span>\
<span>Thg 10</span>\
</div>`;

}
function budgetsUI(limit = 5) {
  return `<div class="budget-stack">\
${budgets.slice(0, limit).map(b => {
    const used = percent(b.spent, b.limit);
    return `<div class="budget-row">\
<div class="budget-info">\
<strong>\
${safe(b.name)}\
</strong>\
<span>\
${money(b.spent)} / ${money(b.limit)}\
</span>\
</div>\
<div class="bar-track">\
<div class="bar-fill ${used >= 100 ? 'danger' : used >= 80 ? 'warn' : ''}" style="width:${Math.min(used, 100)}%">\
</div>\
</div>\
<div class="budget-foot">\
${used}% hạn mức ${used >= 100 ? '· Đã vượt mức' : used >= 80 ? '· Cần chú ý' : ''}</div></div>`

  }).join('')}</div>`;

}
function txTable(items = transactions) {
  if (!items.length) return `<div class="empty-state">\
<span class="empty-icon">⌕</span>\
<strong>Không có giao dịch phù hợp</strong>Thử thay đổi từ khóa hoặc bộ lọc.</div>`;
  return `<div class="data-table-wrap">\
<table class="data-table">\
<thead>\
<tr>\
<th>Giao dịch</th>\
<th>Danh mục</th>\
<th>Ngày</th>\
<th>Ví / nguồn tiền</th>\
<th>Giá trị</th>\
<th>Trạng thái</th>\
<th>\
</th>\
</tr>\
</thead>\
<tbody>\
${items.map(t => `<tr>\
<td>\
<div class="table-name">\
<span class="table-icon">\
${safe(t.icon)}\
</span>\
<span>\
${safe(t.name)}<small>${safe(t.id)}</small></span></div></td><td>${safe(t.category)}</td><td>${safe(t.date)}</td><td>${safe(t.wallet)}</td><td class="amount ${t.type === 'income' ? 'positive' : t.type === 'expense' ? 'negative' : ''}">${t.type === 'income' ? '+' : t.type === 'expense' ? '−' : '⇄'} ${money(Math.abs(t.amount))}\
</td>\
<td>\
<span class="badge ${t.status === 'DRAFT' ? 'warn' : 'neutral'}">${t.status === 'DRAFT' ? 'Bản nháp' : 'Đã ghi sổ'}\
</span>\
</td>\
<td>\
<button class="text-action" data-detail="${safe(t.id)}">Chi tiết</button></td></tr>`).join('')}</tbody></table></div>`;

}
function goalsUI() {
  return `<div class="card-grid">\
${goals.map(g => `<article class="goal-card">\
<div class="goal-top">\
<span class="goal-emoji">\
${g.icon}\
</span>\
<div>\
<h3>\
${g.name}\
</h3>\
<p>Thời hạn ${g.deadline}\
</p>\
</div>\
</div>\
<div class="goal-amount">\
<strong>\
${money(g.current)}</strong><small>/ ${money(g.target)}\
</small>\
</div>\
<div class="bar-track">\
<div class="bar-fill" style="width:${percent(g.current, g.target)}%">\
</div>\
</div>\
<div class="goal-footer">\
<span>\
${percent(g.current, g.target)}% hoàn thành</span>\
<strong>Cần thêm ${money(g.monthly)}/tháng</strong>\
</div>\
<button class="btn btn--outline btn-sm" data-action="goal-allocate">\
${icon('plus')} Phân bổ tích lũy</button>\
<div class="status-row">\
<button class="text-action" data-action="goal-complete">Hoàn thành</button>\
<button class="text-action" data-action="goal-cancel">Hủy mục tiêu</button>\
</div>\
</article>`).join('')}</div>`;

}
function reportBarChart() {
  const max = 20;
  return `<div class="chart-bars">\
${incomeTrend.map((v, i) => `<div class="chart-bar-group">\
<div class="chart-bar" style="height:${v / max * 100}%" title="Thu nhập: ${v} triệu">\
</div>\
<div class="chart-bar out" style="height:${expenseTrend[i] / max * 100}%" title="Chi tiêu: ${expenseTrend[i]} triệu"></div></div>`).join('')}\
</div>\
<div class="chart-bars-labels">\
${[
      'Thg 5',
      'Thg 6',
      'Thg 7',
      'Thg 8',
      'Thg 9',
      'Thg 10'

    ].map(m => `<span>\
${m}</span>`).join('')}\
</div>\
<div class="chart-summary">\
<span class="legend">Thu nhập</span>\
<span class="legend secondary">Chi tiêu</span>\
<span>Đơn vị: triệu VND</span>\
</div>`;

}
function monthlySummary() {
  return `<div class="report-stats">\
<div class="report-stat">\
<span>Thu nhập tháng</span>\
<strong class="positive">\
${money(18500000)}\
</strong>\
</div>\
<div class="report-stat">\
<span>Chi tiêu tháng</span>\
<strong>\
${money(8240000)}\
</strong>\
</div>\
<div class="report-stat">\
<span>Saving Rate</span>\
<strong>55,46%</strong>\
</div>\
</div>`;

}
function overview() {
  return `${heading('overview', btn('Thêm giao dịch', 'tx-add'))}\
<div class="kpi-grid">\
${kpi('Tài sản ròng', money(128450000), 'So với kỳ trước', 'chart', '', '+12,8%')}${kpi('Tổng tài sản', money(143550000), 'Ví tiền + tiết kiệm + vàng', 'wallet', 'blue', '+8,2%')}${kpi('Tổng nợ phải trả', money(15100000), 'Chỉ gồm dư nợ đi vay', 'coin', 'orange')}${kpi('Dòng tiền thuần', money(10260000), 'Thu nhập − Chi tiêu tháng này', 'arrows', 'green', '+5,3%')}\
</div>\
<div class="panel-grid">\
${panel('Biến động tài sản ròng', 'Diễn biến 7 kỳ gần nhất · VND', lineChart(), '<span class="panel-pill">7 tháng gần nhất</span>')}${panel('Cơ cấu tài sản',
    'Phân bổ nguồn lực hiện có',
    `<div class="doughnut-wrap">\
<div class="donut">\
<div class="donut-center">\
<strong>143,55tr</strong>\
<span>Tổng tài sản</span>\
</div>\
</div>\
<div class="donut-legend">\
<span>\
<i style="background:#197c96">\
</i>Tiết kiệm<strong>45%</strong>\
</span>\
<span>\
<i style="background:#8ecac5">\
</i>Vàng tích lũy<strong>36%</strong>\
</span>\
<span>\
<i style="background:#aac7e5">\
</i>Ngân hàng<strong>15%</strong>\
</span>\
<span>\
<i style="background:#e3bb83">\
</i>Khác<strong>4%</strong>\
</span>\
</div>\
</div>`)}\
</div>\
<div class="panel-grid">\
${panel('Ngân sách tháng 10', 'Cảnh báo theo ngưỡng 80% / 100%', budgetsUI(3), `<button class="text-action" data-page="budgets">Xem ngân sách →</button>`)}${panel('Mục tiêu đang thực hiện',
      'Các cột mốc tích lũy quan trọng',
      goals.slice(0, 2).map(g => `<div class="budget-row" style="margin:0 0 20px">\
<div class="budget-info">\
<strong>\
${g.name}\
</strong>\
<span>\
${percent(g.current, g.target)}%</span>\
</div>\
<div class="bar-track">\
<div class="bar-fill" style="width:${percent(g.current, g.target)}%">\
</div>\
</div>\
<div class="budget-foot">\
${money(g.current)} / ${money(g.target)}</div></div>`).join(''),
      `<button class="text-action" data-page="goals">Tất cả mục tiêu →</button>`)}</div>${panel('Giao dịch gần đây',
        'Dòng tiền theo kỳ',
        txTable(transactions.slice(0, 5)),
        `<button class="text-action" data-page="transactions">Xem tất cả →</button>`)}\
<div class="foot-source">Tài sản ròng = Tổng tài sản − Tổng nợ · Dòng tiền thuần = Thu nhập − Chi tiêu.</div>`;

}
function transactionPage() {
  const filtered = transactions.filter(t => (state.txFilter === 'all' || t.type === state.txFilter) && [t.name, t.id, t.category, t.wallet].join(' ').toLocaleLowerCase('vi').includes(state.search.toLocaleLowerCase('vi')));
  return `${heading('transactions', btn('Ghi giao dịch', 'tx-add') + btn('Chuyển ví', 'transfer', 'outline', 'arrows'))}\
<div class="kpi-grid">\
${kpi('Thu nhập tháng', money(18500000), 'Không bao gồm tiền nhận vay', 'arrows', 'green')}${kpi('Chi tiêu tháng', money(8240000), 'Không bao gồm khoản trả nợ gốc', 'wallet', 'orange')}${kpi('Chuyển nội bộ', money(2500000), 'Không tính thu / chi', 'repeat', 'blue')}${kpi('Giao dịch nháp', '1 bản nháp', 'Chưa vào báo cáo', 'file')}</div>${panel('Lịch sử giao dịch',
    'Theo dõi Posted, Draft và chi tiết bút toán',
    `<div class="filter-row">\
<div class="tabs" role="tablist">\
${[['all', 'Tất cả'], ['income', 'Thu nhập'], ['expense', 'Chi tiêu'], ['transfer', 'Chuyển ví']].map(([id, label]) => `<button class="tab ${state.txFilter === id ? 'active' : ''}" data-tx-filter="${id}" role="tab" aria-selected="${state.txFilter === id}">${label}</button>`).join('')}\
</div>\
<div class="filter-controls">\
<input id="transaction-search" class="input-sm" type="search" placeholder="Tìm tên, mã, danh mục..." value="${safe(state.search)}" aria-label="Tìm kiếm giao dịch">\
<button class="btn btn--outline btn-sm" data-action="export">\
${icon('download')} Xuất CSV</button>\
</div>\
</div>\
<div id="transaction-table">\
${txTable(filtered)}</div>`)}\
<div class="hint-box section-gap" style="margin-top:18px">\
<strong>Nguyên tắc ghi sổ:</strong> bản ghi đã Posted không sửa/xóa vật lý; thao tác điều chỉnh về sau phải tạo bút toán đảo (Reversal) và giao dịch thay thế. </div>`;

}
function walletPage() {
  const items = state.walletItems || [];
  const summary = state.walletSummary || { baseCurrency: 'VND', totalBalanceBase: '0', activeCount: 0, archivedCount: 0, hasUnconvertedBalances: false };
  const shown = items.filter(wallet => wallet.status === 'ACTIVE' || !state.onlyActiveWallets);
  const labels = { CASH: 'Tiền mặt', BANK: 'Ngân hàng', E_WALLET: 'Ví điện tử', SAVINGS: 'Tiết kiệm' };
  const amount = (value, currency) => `${Number(value).toLocaleString('vi-VN', { minimumFractionDigits: currency === 'VND' ? 0 : 2, maximumFractionDigits: currency === 'VND' ? 0 : 2 })} ${currency === 'VND' ? 'đ' : currency}`;
  const cardAttributes = color => {
    if (!/^#[0-9a-f]{6}$/i.test(color || '')) return `class="wallet-card wallet-card--${safe(color || 'blue')}"`;
    const red = Number.parseInt(color.slice(1, 3), 16);
    const green = Number.parseInt(color.slice(3, 5), 16);
    const blue = Number.parseInt(color.slice(5, 7), 16);
    const light = red * 0.299 + green * 0.587 + blue * 0.114 > 160;
    const variables = light
      ? '--wallet-ink:#102a4d;--wallet-muted:#314b67;--wallet-chip:#0b1f3314;--wallet-danger:#8f1c13'
      : '--wallet-ink:#fff;--wallet-muted:#d7e3ee;--wallet-chip:#ffffff20;--wallet-danger:#ffd2d2';
    return `class="wallet-card wallet-card--custom" style="--wallet-color:${color};${variables}"`;
  };
  const cards = state.walletLoading
    ? '<div class="empty-state"><span class="empty-icon">◷</span><strong>Đang tải dữ liệu ví</strong></div>'
    : state.walletError
      ? `<div class="empty-state"><span class="empty-icon">!</span><strong>Không thể tải dữ liệu ví</strong><p>${safe(state.walletError)}</p><button class="btn btn--outline btn-sm" data-action="wallet-retry">Thử lại</button></div>`
      : shown.length === 0
        ? `<div class="empty-state"><span class="empty-icon">◎</span><strong>${items.length ? 'Không có ví phù hợp' : 'Chưa có ví nào'}</strong><p>${items.length ? 'Không có ví lưu trữ để hiển thị.' : 'Tạo ví đầu tiên để bắt đầu quản lý nguồn tiền.'}</p></div>`
        : `<div class="card-grid section-gap">${shown.map(wallet => `<article ${cardAttributes(wallet.colorKey)}>\
<div class="wallet-header">\
<div class="wallet-identity"><span class="wallet-icon">${icon(wallet.iconKey)}</span><div><span class="wallet-name">${safe(wallet.name)}</span><span class="wallet-sub">${safe(labels[wallet.type] || wallet.type)} · ${safe(wallet.currency)}</span></div></div>\
<span class="wallet-type">${wallet.status === 'ARCHIVED' ? 'Đã lưu trữ' : safe(labels[wallet.type] || wallet.type)}</span>\
</div>\
<div><div class="wallet-balance">${amount(wallet.balance, wallet.currency)}</div>\
<div class="wallet-meta">${wallet.baseBalance === null ? `Chưa có tỷ giá quy đổi sang ${safe(wallet.baseCurrency)}` : wallet.currency === wallet.baseCurrency ? `Tiền cơ sở ${safe(wallet.baseCurrency)}` : `≈ ${amount(wallet.baseBalance, wallet.baseCurrency)}`}</div>\
${wallet.status === 'ACTIVE' ? `<div class="wallet-actions"><button class="text-action" data-action="wallet-edit" data-wallet-id="${wallet.id}">Chỉnh sửa ví</button></div>` : `<div class="wallet-actions"><button class="text-action text-action--danger" data-action="wallet-delete-permanent" data-wallet-id="${wallet.id}">Xóa vĩnh viễn</button></div>`}</div></article>`).join('')}</div>`;
  const foreignRates = items.filter(wallet => wallet.status === 'ACTIVE' && wallet.currency !== wallet.baseCurrency)
    .filter((wallet, index, rows) => rows.findIndex(item => item.currency === wallet.currency) === index);

  return `${heading('wallets', btn('Tạo ví mới', 'wallet-add-real') + btn('Cập nhật tỷ giá', 'wallet-rates-refresh', 'outline', 'repeat'))}\
<div class="filter-row">\
<p class="description">Quản lý tiền mặt, ngân hàng, ví điện tử và tài khoản tiết kiệm. Số dư ngoại tệ được quy đổi khi có tỷ giá hợp lệ.</p>\
${summary.archivedCount > 0 ? `<button class="btn btn--outline btn-sm" data-action="wallet-toggle">\
${icon('layers')} ${state.onlyActiveWallets ? 'Hiện ví lưu trữ' : 'Ẩn ví lưu trữ'}\
</button>` : ''}\
</div>\
${cards}\
<div class="info-grid">\
${panel('Tổng số dư các ví',
    'Chưa bao gồm tài sản vàng',
    `<div class="metric-list">\
<div class="metric-item">\
<span>Tổng số dư quy đổi (${safe(summary.baseCurrency)})</span>\
<strong>${amount(summary.totalBalanceBase, summary.baseCurrency)}</strong>\
</div>\
<div class="metric-item">\
<span>Ví đang sử dụng</span>\
<strong>${summary.activeCount}</strong>\
</div>\
<div class="metric-item">\
<span>Ví đã lưu trữ</span>\
<strong>${summary.archivedCount}</strong>\
</div>\
</div>`)}${panel('Quy đổi tiền tệ',
      'Tỷ giá mới nhất trong hệ thống',
      `<div class="metric-list">\
<div class="metric-item">\
<span>${safe(summary.baseCurrency)} (tiền cơ sở)</span>\
<strong>1</strong>\
</div>\
${foreignRates.length ? foreignRates.map(wallet => `<div class="metric-item"><span>${safe(wallet.currency)} → ${safe(wallet.baseCurrency)}<small>${wallet.rateProvider === 'VIETCOMBANK_TRANSFER' ? 'Vietcombank · tỷ giá chuyển khoản' : safe(wallet.rateProvider || 'Nguồn hệ thống')}</small></span><strong>${wallet.conversionRate === null ? 'Chưa có tỷ giá' : Number(wallet.conversionRate).toLocaleString('vi-VN', { maximumFractionDigits: 8 })}</strong></div>`).join('') : '<div class="metric-item"><span>Ngoại tệ đang theo dõi</span><strong>Không có</strong></div>'}\
</div>`)}\
</div>\
${state.walletRateWarning ? `<div class="hint-box section-gap">Không cập nhật được tỷ giá mới: ${safe(state.walletRateWarning)} Dữ liệu gần nhất trong hệ thống vẫn được sử dụng.</div>` : ''}\
${summary.hasUnconvertedBalances ? '<div class="hint-box section-gap">Một số số dư ngoại tệ chưa được cộng vào tổng do thiếu tỷ giá hợp lệ.</div>' : ''}`;

}
function categoryPage() {
  return `${heading('categories', btn('Tạo danh mục', 'category-add'))}${panel('Danh mục cá nhân',
    'Dùng để phân loại, lọc giao dịch và lập ngân sách',
    `<div class="card-grid">\
${categories.map((c, i) => `<div class="mini-panel">\
<div class="status-row">\
<div class="goal-top">\
<span class="goal-emoji" style="height:35px;width:35px">\
${i % 2 ? '◈' : '◎'}\
</span>\
<div>\
<h3>\
${c.name}\
</h3>\
<p>Danh mục ${c.type.toLowerCase()}\
</p>\
</div>\
</div>\
<span class="badge ${c.type === 'Thu nhập' ? 'blue' : 'neutral'}">${c.type}\
</span>\
</div>\
<div style="margin-top:18px">\
<button class="text-action" data-action="category-edit">Xem / chỉnh sửa →</button>\
</div>\
</div>`).join('')}</div>`)}\
<div class="hint-box" style="margin-top:16px">Danh mục cá nhân có thể đổi tên, lưu trữ; danh mục mặc định do Admin quản lý riêng, không xem được dữ liệu tài chính người dùng.</div>`;

}
function budgetPage() {
  return `${heading('budgets', btn('Tạo ngân sách', 'budget-add') + btn('Chỉnh sửa / lưu trữ', 'budget-manage', 'outline', 'gear'))}\
<div class="kpi-grid">\
${kpi('Tổng ngân sách', money(7700000), '5 danh mục tháng 10', 'pie')}${kpi('Chi tiêu thuộc ngân sách', money(5770000), 'Theo danh mục', 'wallet', 'blue')}${kpi('Cảnh báo vàng', '1 danh mục', 'Đạt từ 80% hạn mức', 'bell', 'orange')}${kpi('Vượt ngân sách', '1 danh mục', 'Đạt từ 100% hạn mức', 'info', 'orange')}</div>${panel('Hạn mức theo danh mục', 'Kỳ ngân sách: 10/2026', budgetsUI(), `<span class="panel-pill">Tháng 10/2026</span>`)}\
<div class="info-grid" style="margin-top:17px">\
<div class="mini-panel">\
<h3>Ngưỡng cảnh báo</h3>\
<p>\
<span class="badge warn">Từ 80%</span> Ngân sách chuyển trạng thái cần chú ý.<br>\
<br>\
<span class="badge danger">Từ 100%</span> Cảnh báo vượt ngưỡng; ứng dụng không chặn giao dịch thật.</p>\
</div>\
<div class="mini-panel">\
<h3>Giữ ngân sách chính xác</h3>\
<p>Ngân sách tính chi tiêu ròng, không tính chuyển ví nội bộ hoặc trả nợ gốc. Mỗi ngưỡng cảnh báo chỉ nên gửi thông báo một lần trong một kỳ ngân sách.</p>\
</div>\
</div>`;

}
function recurringPage() {
  return `${heading('recurring', btn('Tạo lịch định kỳ', 'recurring-add'))}\
<div class="kpi-grid">\
${kpi('Lịch đang hoạt động', '3 lịch', 'Đang theo dõi', 'repeat')}${kpi('Sắp đến hạn', money(369000), 'Trong tháng 10', 'calendar', 'orange')}${kpi('Tạo bản nháp', '2 lịch', 'Chế độ mặc định', 'file', 'blue')}${kpi('Tự động ghi sổ', '1 lịch', 'Người dùng chủ động bật', 'check', 'green')}</div>${panel('Các khoản định kỳ',
    'Hỗ trợ nhắc tạo Draft / tự động ghi sổ (sẽ xử lý bằng PHP scheduler)',
    `<div class="data-table-wrap">\
<table class="data-table">\
<thead>\
<tr>\
<th>Khoản định kỳ</th>\
<th>Ví</th>\
<th>Hạn kế tiếp</th>\
<th>Số tiền</th>\
<th>Chế độ</th>\
<th>Trạng thái</th>\
<th>\
</th>\
</tr>\
</thead>\
<tbody>\
${recurring.map(r => `<tr>\
<td>\
<div class="table-name">\
<span class="table-icon">↻</span>\
<span>\
${r.name}<small>${r.category}</small></span></div></td><td>${r.wallet}</td><td>${r.date}</td><td class="amount">${money(r.amount)}</td><td>${r.mode}\
</td>\
<td>\
<span class="badge ${r.active ? '' : 'neutral'}">${r.active ? 'Hoạt động' : 'Tạm dừng'}\
</span>\
</td>\
<td>\
<button class="text-action" data-action="recurring-toggle">\
${r.active ? 'Tạm dừng' : 'Kích hoạt'}\
</button> · <button class="text-action" data-action="recurring-delete">Xóa</button>\
</td>\
</tr>`).join('')}</tbody></table></div>`)}\
<div class="hint-box" style="margin-top:17px">Lịch định kỳ chạy theo ngày đã thiết lập; khi tháng không đủ ngày, hệ thống áp dụng ngày cuối tháng. Mỗi kỳ chỉ được ghi sổ một lần.</div>`;

}
function loanPage() {
  return `${heading('loans', btn('Tạo khoản vay / nợ', 'loan-add') + btn('Ghi trả nợ', 'loan-pay', 'outline', 'arrows'))}\
<div class="kpi-grid">\
${kpi('Dư nợ phải trả', money(15100000), '2 khoản đi vay', 'coin', 'orange')}${kpi('Khoản cho vay còn lại', money(1200000), 'Theo dõi riêng tài sản cho vay', 'arrows', 'blue')}${kpi('Đã thanh toán gốc', money(9900000), 'Chưa tính lãi/phí', 'check', 'green')}${kpi('Sắp đến hạn', '20/10/2026', 'Khoản vay mua laptop', 'calendar')}\
</div>\
<div class="card-grid section-gap">\
${loans.map(l => `<article class="loan-card">\
<div class="status-row">\
<span class="badge ${l.kind === 'Đi vay' ? 'warn' : 'blue'}">${l.kind}\
</span>\
<span class="description">\
${l.id.toUpperCase()}\
</span>\
</div>\
<div>\
<h3>\
${l.name}\
</h3>\
<p>Đối tác: ${l.partner}\
</p>\
</div>\
<div>\
<span class="description">Dư gốc còn lại</span>\
<div class="loan-amount">\
${money(l.remaining)}\
</div>\
<div class="budget-foot" style="margin:7px 0">Đã thanh toán ${money(l.paid)} / ${money(l.principal)}\
</div>\
<div class="bar-track">\
<div class="bar-fill" style="width:${percent(l.paid, l.principal)}%">\
</div>\
</div>\
</div>\
<div class="status-row">\
<span class="description">Hạn tiếp: ${l.due}\
</span>\
<button class="text-action" data-action="loan-view">Xem lịch trả →</button>\
</div>\
</article>`).join('')}\
</div>\
<div class="info-grid">\
${panel('Quy tắc trả nợ',
    'Phân biệt phần gốc và lãi/phí',
    `<div class="metric-list">\
<div class="metric-item">\
<span>Trả gốc</span>\
<strong>Giảm dư nợ và ví</strong>\
</div>\
<div class="metric-item">\
<span>Trả lãi / phí</span>\
<strong>Hạch toán chi phí</strong>\
</div>\
<div class="metric-item">\
<span>Nhận tiền đi vay</span>\
<strong>Không tính thu nhập</strong>\
</div>\
</div>`)}${panel('Nhắc lịch trả nợ',
      'Nhắc nhở thanh toán',
      `<div class="hint-box">\
<strong>Theo dõi:</strong> nhắc trước hạn 3 ngày, ghi nhận tiến độ thanh toán và chuyển trạng thái Đã trả xong khi dư gốc về 0.</div>`)}</div>`;

}
function goldPage() {
  return `${heading('gold', btn('Thêm tài sản vàng', 'gold-add') + btn('Cập nhật định giá', 'gold-revalue', 'outline', 'chart'))}\
<div class="kpi-grid">\
${kpi('Giá trị tài sản vàng', money(52000000), 'Tài sản tích lũy ngoài ví', 'diamond', '', '+4,1%')}${kpi('Số loại vàng', '2 loại', 'Nhẫn 9999 · SJC', 'layers', 'blue')}${kpi('Tổng khối lượng', '5 chỉ', 'Đơn vị chỉ vàng', 'coin', 'orange')}${kpi('Ngày định giá', '12/10/2026', 'Theo lần định giá gần nhất', 'calendar', 'green')}\
</div>\
<div class="card-grid section-gap">\
${gold.map(g => `<article class="gold-card">\
<div class="status-row">\
<span class="goal-emoji">◇</span>\
<span class="badge blue">Đang sở hữu</span>\
</div>\
<div>\
<h3>\
${g.name}\
</h3>\
<p>Số lượng: ${g.weight}\
</p>\
</div>\
<div>\
<span class="description">Giá trị định giá</span>\
<div class="loan-amount">\
${money(g.value)}\
</div>\
<p>\
${money(g.price)} / chỉ · Cập nhật ${g.date}\
</p>\
</div>\
<button class="text-action" style="text-align:left" data-action="gold-liquidate">Thanh lý / xóa tài sản →</button>\
</article>`).join('')}\
</div>\
<div class="hint-box">Hỗ trợ theo dõi vàng nhẫn và vàng miếng SJC. Không bao gồm nhà đất, xe cộ hay khấu hao. Định giá vàng có thể biến động và cần dữ liệu thị giá đáng tin cậy khi tích hợp thực tế.</div>`;

}
function goalsPage() {
  return `${heading('goals', btn('Tạo mục tiêu', 'goal-add'))}\
<div class="kpi-grid">\
${kpi('Mục tiêu đang hoạt động', '3 mục tiêu', 'Đang tích lũy', 'target')}${kpi('Tổng cần tích lũy', money(55000000), 'Tổng giá trị đích', 'coin', 'blue')}${kpi('Đã phân bổ', money(32400000), 'Không cộng thêm vào tổng tài sản', 'check', 'green')}${kpi('Tiến độ tổng', '58,9%', 'Tiến độ tích lũy', 'chart', 'orange')}</div>${goalsUI()}\
<div class="hint-box" style="margin-top:17px">\
<strong>Phân bổ tích lũy:</strong> phân bổ tiền cho mục tiêu chỉ gắn nhãn khoản tích lũy, không làm tăng tổng tài sản ròng. Hệ thống dự kiến gợi ý số tiền cần để dành mỗi tháng theo thời hạn.</div>`;

}
function reportPage() {
  return `${heading('reports', btn('Xuất Xuất CSV', 'export', 'outline', 'download'))}\
<div class="filter-row">\
<div class="tabs">\
<button class="tab active" type="button">Tổng quan báo cáo</button>\
<button class="tab" type="button" data-page="reconciliation">Đối soát</button>\
</div>\
<span class="date-filter">Tháng 5 – 10/2026</span>\
</div>\
<div class="kpi-grid">\
${kpi('Net Worth', money(128450000), 'Tổng tài sản trừ nợ', 'chart')}${kpi('Net Cash Flow', money(10260000), 'Thu nhập trừ chi tiêu', 'arrows', 'green')}${kpi('Saving Rate', '55,46%', '(18,5 − 8,24) / 18,5 × 100%', 'pie', 'blue')}${kpi('Tài sản tích lũy', money(117000000), 'Tiết kiệm + vàng', 'diamond', 'orange')}\
</div>\
<div class="panel-grid">\
${panel('Dòng tiền 6 tháng', 'Biểu đồ so sánh thu nhập và chi phí', reportBarChart())}${panel('Phân tích tiết kiệm',
    'Tháng 10/2026',
    `${monthlySummary()}\
<div class="hint-box" style="margin-top:18px">Saving Rate = (Income − Expense) / Income. Nếu Income ≤ 0, hiển thị “N/A”, không chia cho 0. Âm khi chi tiêu vượt thu nhập.</div>`)}</div>${panel('Biến động tài sản ròng', 'Theo lịch sử snapshot hàng ngày / theo kỳ', lineChart())}\
<div class="foot-source">Báo cáo chưa lấy dữ liệu MySQL và không đại diện cho lịch sử giao dịch thật.</div>`;

}
function reconciliationPage() {
  return `${heading('reconciliation', btn('Tải Xuất CSV', 'export', 'outline', 'download') + btn('Kiểm tra đối soát', 'reconcile', 'primary', 'check'))}\
<div class="kpi-grid">\
${kpi('Ví đang theo dõi', '5 ví', 'Bao gồm ví USD', 'wallet')}${kpi('Ví khớp sổ', '5 / 5', 'Trạng thái đối chiếu', 'check', 'green')}${kpi('Giao dịch Posted', '7 bản ghi', 'Đã loại trừ bản nháp', 'file', 'blue')}${kpi('Xuất dữ liệu', 'CSV UTF-8 BOM', 'Có bảo vệ công thức', 'download', 'orange')}</div>${panel('Đối chiếu số dư ví',
    'So sánh số dư hiện tại với lịch sử bút toán',
    `<div class="data-table-wrap">\
<table class="data-table">\
<thead>\
<tr>\
<th>Ví</th>\
<th>Số dư ghi nhận</th>\
<th>Số dư từ sổ cái</th>\
<th>Chênh lệch</th>\
<th>Trạng thái</th>\
</tr>\
</thead>\
<tbody>\
${wallets.filter(w => w.active).map(w => `<tr>\
<td>\
<strong>\
${w.name}</strong></td><td>${w.currency === 'USD' ? w.balance + ' USD' : money(w.balance)}</td><td>${w.currency === 'USD' ? w.balance + ' USD' : money(w.balance)}</td><td>0 ${w.currency}\
</td>\
<td>\
<span class="badge">Mô phỏng khớp</span>\
</td>\
</tr>`).join('')}</tbody></table></div>`)}\
<div class="hint-box" style="margin-top:18px">\
<strong>Chưa chạy phép đối soát:</strong> khi triển khai, PHP phải tính lại số dư từ ledger entries của từng ví, so sánh với số dư cache và phát hiện sai khác. Chỉ sử dụng kết quả đối soát do máy chủ xác nhận.</div>`;

}
function notificationsPage() {
  return `${heading('notifications', btn('Cấu hình thông báo', 'notification-settings', 'outline', 'gear'))}${panel('Hộp thư thông báo',
    'Cảnh báo ngân sách, nhắc nợ và broadcast từ hệ thống',
    notifications.map(n => `<div class="notification-item">\
<span class="notification-icon">\
${n.type === 'danger' ? '!' : n.type === 'warn' ? '◷' : '✧'}\
</span>\
<div class="notification-copy">\
<strong>\
${n.title}\
</strong>\
<p>\
${n.content}</p><small>${n.time}\
</small>\
</div>\
<span class="badge ${n.type === 'danger' ? 'danger' : n.type === 'warn' ? 'warn' : 'blue'}">${n.type === 'danger' ? 'Khẩn' : n.type === 'warn' ? 'Cần chú ý' : 'Thông tin'}</span></div>`).join(''))}\
<div class="hint-box" style="margin-top:17px">Ngưỡng ngân sách và thời hạn khoản vay được dùng để gửi nhắc nhở phù hợp.</div>`;

}
function settingsPage() {
  return `${heading('settings')}\
<div class="info-grid">\
${panel('Hồ sơ cá nhân',
    'Thông tin đăng ký và ưu tiên hiển thị',
    `<div class="settings-row">\
<div>\
<strong>Họ và tên</strong>\
<p>${safe(currentUser.fullName)}</p>\
</div>\
<button class="btn btn--outline btn-sm" data-action="profile-edit">Chỉnh sửa</button>\
</div>\
<div class="settings-row">\
<div>\
<strong>Email</strong>\
<p>${safe(currentUser.email)}</p>\
</div>\
<span class="badge neutral">Đã đăng ký</span>\
</div>\
<div class="settings-row">\
<div>\
<strong>Tiền cơ sở</strong>\
<p>${safe(currentUser.baseCurrency)}</p>\
</div>\
<button class="btn btn--outline btn-sm" data-action="currency-setting">Chi tiết</button>\
</div>\
<div class="settings-row">\
<div>\
<strong>Ngôn ngữ</strong>\
<p>Tiếng Việt (UTF-8) · cấu trúc sẵn sàng mở rộng i18n</p>\
</div>\
<span class="badge blue">vi-VN</span>\
</div>`)}${panel('Tài khoản & bảo mật',
      'Quản lý mật khẩu và phiên đăng nhập',
      `<div class="settings-row">\
<div>\
<strong>Đổi mật khẩu</strong>\
<p>Xác nhận mật khẩu cũ, thu hồi phiên khác.</p>\
</div>\
<button class="btn btn--outline btn-sm" data-action="password">Thiết lập</button>\
</div>\
<div class="settings-row">\
<div>\
<strong>Phiên đăng nhập</strong>\
<p>Đăng xuất sẽ kết thúc ngay phiên đăng nhập hiện tại.</p>\
</div>\
<span class="badge green">Đang hoạt động</span>\
</div>\
<div class="settings-row">\
<div>\
<strong>Yêu cầu xóa dữ liệu</strong>\
<p>Quản lý thông tin và quyền riêng tư của tài khoản.</p>\
</div>\
<button class="btn btn--outline btn-sm" data-action="account-delete">Xem quy trình</button>\
</div>\
<div class="settings-row">\
<div>\
<strong>Đăng xuất</strong>\
<p>Kết thúc phiên và quay về trang đăng nhập.</p>\
</div>\
<button class="btn btn--outline btn-sm" data-action="logout">Đăng xuất</button>\
</div>`)}</div>`;

}
function adminOverviewPage() {
  return `${heading('admin-overview', btn('Soạn thông báo', 'broadcast-add'))}\
<div class="kpi-grid">\
${kpi('Tổng người dùng', '1.284', 'Thông tin hệ thống', 'users')}${kpi('Tài khoản Active', '1.237', 'Không bao gồm tài khoản khóa', 'check', 'green')}${kpi('Đăng ký mới tháng', '86', 'Tháng 10/2026', 'chart', 'blue', '+9,4%')}${kpi('Tài khoản Locked', '14', 'Cần xử lý theo quy trình', 'lock', 'orange')}\
</div>\
<div class="panel-grid">\
${panel('Hoạt động tài khoản', 'Thống kê người dùng theo tháng', reportBarChart())}${panel('Phân bố tiền cơ sở',
    'Thống kê tổng hợp, không có số dư',
    `<div class="doughnut-wrap">\
<div class="donut" style="background:conic-gradient(#197c96 0 85%,#aac7e5 85% 96%,#e3bb83 96%)">\
<div class="donut-center">\
<strong>1.284</strong>\
<span>Người dùng</span>\
</div>\
</div>\
<div class="donut-legend">\
<span>\
<i style="background:#197c96">\
</i>VND<strong>85%</strong>\
</span>\
<span>\
<i style="background:#aac7e5">\
</i>USD<strong>11%</strong>\
</span>\
<span>\
<i style="background:#e3bb83">\
</i>Khác<strong>4%</strong>\
</span>\
</div>\
</div>`)}\
</div>\
<div class="hint-box">\
<strong>Bảo mật dữ liệu:</strong> trang Admin không được cung cấp số dư, tài sản, khoản nợ hoặc lịch sử giao dịch tài chính của bất kỳ người dùng nào. Dữ liệu quản trị phải được tổng hợp và ẩn danh theo quyền được cấp.</div>`;

}
function adminUsersPage() {
  return `${heading('admin-users', btn('Xem nhật ký Admin', 'admin-logs', 'outline', 'file'))}${panel('Danh sách người dùng',
    'Theo dõi trạng thái, tìm kiếm và khóa/mở khóa khi có căn cứ',
    `<div class="data-table-wrap">\
<table class="data-table">\
<thead>\
<tr>\
<th>Tài khoản</th>\
<th>Ngày tạo</th>\
<th>Tiền cơ sở</th>\
<th>Trạng thái</th>\
<th>Thao tác</th>\
</tr>\
</thead>\
<tbody>\
${adminUsers.map(u => `<tr>\
<td>\
<div class="table-name">\
<span class="table-icon">\
${icon('user')}\
</span>\
<span>\
${u.name}<small>${u.email} · ${u.id}</small></span></div></td><td>${u.created}</td><td>${u.currency}\
</td>\
<td>\
<span class="badge ${u.status === 'Active' ? '' : 'danger'}">${u.status}\
</span>\
</td>\
<td>\
<button class="text-action" data-action="admin-user-${u.status === 'Active' ? 'lock' : 'unlock'}">${u.status === 'Active' ? 'Khóa tài khoản' : 'Mở khóa'}</button></td></tr>`).join('')}</tbody></table></div>`)}\
<div class="hint-box" style="margin-top:18px">Mọi quyết định khóa tài khoản cần có lý do và được lưu vào nhật ký quản trị. Không thể khóa tài khoản quản trị duy nhất.</div>`;

}
function adminCategoriesPage() {
  return `${heading('admin-categories', btn('Thêm danh mục mẫu', 'admin-category-add'))}${panel('Bộ danh mục mặc định',
    'Sẽ được khởi tạo cho người dùng mới',
    `<div class="card-grid">\
${categories.map(c => `<div class="mini-panel">\
<div class="status-row">\
<h3>\
${c.name}\
</h3>\
<span class="badge ${c.type === 'Thu nhập' ? 'blue' : 'neutral'}">${c.type}\
</span>\
</div>\
<p>Danh mục gốc của hệ thống</p>\
<div style="margin-top:15px">\
<button class="text-action" data-action="admin-category-edit">Điều chỉnh →</button>\
</div>\
</div>`).join('')}</div>`)}\
<div class="hint-box" style="margin-top:17px">Danh mục mặc định chỉ dùng làm mẫu khi tạo tài khoản mới. Không được tự ý làm thay đổi các danh mục hoặc giao dịch cá nhân đã tồn tại.</div>`;

}
function adminBroadcastPage() {
  return `${heading('admin-broadcasts', btn('Tạo broadcast', 'broadcast-add'))}${panel('Thông báo toàn hệ thống',
    'Nội dung gửi đến trung tâm thông báo của người dùng',
    `<div class="notification-item">\
<span class="notification-icon">✧</span>\
<div class="notification-copy">\
<strong>Chào mừng đến WealthFlow</strong>\
<p>Thông tin cập nhật từ WealthFlow.</p>\
<small>10/10/2026 · Thông tin</small>\
</div>\
<span class="badge blue">Thông tin</span>\
</div>\
<div class="notification-item">\
<span class="notification-icon">◷</span>\
<div class="notification-copy">\
<strong>Thông báo bảo trì dự kiến</strong>\
<p>Thông báo bảo trì sẽ được gửi đến người dùng khi có lịch vận hành.</p>\
<small>Chưa gửi</small>\
</div>\
<span class="badge warn">Nháp</span>\
</div>`)}\
<div class="hint-box" style="margin-top:17px">Chỉ Admin được tạo broadcast. Cần kiểm tra tiêu đề, nội dung, mức ưu tiên và lưu dấu vết tác giả khi triển khai.</div>`;

}
function adminSettingsPage() {
  return `${heading('admin-settings', btn('Điều chỉnh tham số', 'admin-config'))}\
<div class="info-grid">\
${panel('Tham số vận hành',
    'Cấu hình mặc định',
    `<div class="metric-list">\
<div class="metric-item">\
<span>Cảnh báo ngân sách</span>\
<strong>80%</strong>\
</div>\
<div class="metric-item">\
<span>Vượt ngân sách</span>\
<strong>100%</strong>\
</div>\
<div class="metric-item">\
<span>TTL access token tối đa</span>\
<strong>60 phút</strong>\
</div>\
<div class="metric-item">\
<span>Nhắc khoản vay</span>\
<strong>Trước hạn 3 ngày</strong>\
</div>\
<div class="metric-item">\
<span>Hạn ngạch tệp xuất</span>\
<strong>Chưa thiết lập</strong>\
</div>\
</div>`)}${panel('Admin Audit Log',
      'Lịch sử thao tác quản trị',
      `<div class="empty-state"><span class="empty-icon">◷</span><strong>Chưa có hoạt động</strong><p>Nhật ký thao tác sẽ xuất hiện tại đây.</p></div>\
<div class="hint-box">Các hành động khóa tài khoản, mở khóa, cập nhật cấu hình và phát broadcast cần có nhật ký với thời gian và Admin thực hiện.</div>`)}</div>`;

}
const PAGES = {
  overview,
  transactions: transactionPage,
  wallets: walletPage,
  categories: categoryPage,
  budgets: budgetPage,
  recurring: recurringPage,
  loans: loanPage,
  gold: goldPage,
  goals: goalsPage,
  reports: reportPage,
  reconciliation: reconciliationPage,
  notifications: notificationsPage,
  settings: settingsPage,
  'admin-overview': adminOverviewPage,
  'admin-users': adminUsersPage,
  'admin-categories': adminCategoriesPage,
  'admin-broadcasts': adminBroadcastPage,
  'admin-settings': adminSettingsPage

};

  return { PAGES, txTable };
}
