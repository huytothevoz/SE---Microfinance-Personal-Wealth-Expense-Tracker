/** Offline interface contract test. Does not start a server, send email or write to a database. */
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
const requests = [];
const transactionFixtures = [
  { id: 2, description: 'Gửi xe', transaction_type: 'EXPENSE', amount: 10000, currency: 'VND', occurred_on: '2026-10-18', category_name: 'Đi lại', status: 'POSTED' },
  ...Array.from({ length: 99 }, (_, index) => ({ id: 100 + index, description: `Giao dịch ${index + 1}`, transaction_type: 'EXPENSE', amount: 1000, currency: 'VND', occurred_on: '2026-10-17', category_name: 'Khác', status: 'POSTED' })),
  { id: 'goal-9', description: 'Chuyển vào mục tiêu: Mua xe', transaction_type: 'SAVINGS_GOAL', goal_action: 'DEPOSIT', goal_name: 'Mua xe', wallet_name: 'Ví chính', amount: 500000, currency: 'VND', occurred_on: '2026-10-16', category_name: 'Mục tiêu tiết kiệm', status: 'POSTED' }
];
globalThis.document = { querySelector: () => null };
globalThis.fetch = async (path, init = {}) => {
  requests.push({ path, init });
  const requestUrl = new URL(path, 'https://wealthflow.test');
  const route = requestUrl.pathname;
  const offset = Number(requestUrl.searchParams.get('offset') || 0);
  const limit = Number(requestUrl.searchParams.get('limit') || 100);
  const paths = {
    '/api/wallets': { wallets: [{ id: 1, name: 'Ví kiểm thử', wallet_type: 'CASH', currency: 'VND', cached_balance: '25000' }] },
    '/api/transactions': { transactions: transactionFixtures.slice(offset, offset + limit) },
    '/api/budgets': { budgets: [{ id: 3, categoryId: 5, categoryName: 'Đi lại', budgetMonth: '2026-10', limitAmount: 50000, spentAmount: 42000, remainingAmount: 8000, usagePercent: 84, alertLevel: 'YELLOW' }] },
    '/api/recurring': { schedules: [{ id: 4, name: 'Tiền thuê', amount: 3000000, next_due_date: '2026-11-01' }] },
    '/api/categories': { categories: [{ id: 5, name: 'Đi lại', category_type: 'EXPENSE' }] },
    '/api/notifications': { notifications: [{ id: 6, title: 'Cảnh báo Ngân sách (Vàng): Đi lại', message: 'Chi tiêu đạt 84%.', notification_type: 'BUDGET_YELLOW', priority: 'MEDIUM', is_read: 0, created_at: '2026-10-18 10:00:00' }], unreadCount: 1 },
    '/api/goals': { goals: [{ id: 9, name: 'Mua xe', targetAmount: '50000000', currentAmount: '500000', remainingAmount: '49500000', progressPercent: '1.00', deadline: '2027-10-18', status: 'ACTIVE' }] },
    '/api/gold': { gold: [{ id: 11, name: 'Vàng miếng SJC', goldType: 'SJC_BAR', quantityChi: '1.0000', valuationPerChi: '8500000.00000000', currentValue: '8500000.00000000', valuationDate: '2026-10-18', status: 'HELD' }] },
    '/api/loans': { loans: [{ id: 12, title: 'Khoản vay học tập', direction: 'BORROWED', currency: 'VND', remainingPrincipal: '10000000.00000000', nextDueDate: '2026-11-18', status: 'ACTIVE' }] },
    '/api/analytics/net-worth': { dashboard: { currency: 'VND', netWorth: '-1475000.00000000', assets: { wallets: '25000.00000000', gold: '8500000.00000000', receivableLoans: '0.00000000', totalAssets: '8525000.00000000', breakdown: { cash: '25000.00000000', bank: '0.00000000', eWallet: '0.00000000', savings: '0.00000000', gold: '8500000.00000000', receivableLoans: '0.00000000' } }, liabilities: { creditWallets: '0.00000000', payableLoans: '10000000.00000000', totalLiabilities: '10000000.00000000' }, cashFlow: { month: '2026-10', income: '25000000.00000000', expense: '1000000.00000000', net: '24000000.00000000', savingRate: '96.00' }, goals: { targetAmount: '50000000.00000000', currentAmount: '500000.00000000', activeCount: 1, items: [{ id: 9, name: 'Mua xe', targetAmount: '50000000.00000000', currentAmount: '500000.00000000', progressPercent: '1.00', deadline: '2027-10-18' }] } } },
  };
  if (['POST', 'PUT', 'DELETE'].includes(init.method)) {
    assert.equal(init.headers['X-WealthFlow'], '1');
    return { status: 201, ok: true, headers: new Map([['content-type', 'application/json']]), json: async () => ({ success: true, data: { message: 'ok' } }) };
  }
  if (paths[route]) return { status: 200, ok: true, headers: new Map([['content-type', 'application/json']]), json: async () => ({ success: true, data: paths[route] }) };
  return { status: 404, ok: false, headers: new Map([['content-type', 'application/json']]), json: async () => ({ success: false }) };
};
const data = await import('../source/frontend/js/data/live-data.js?v=wealth-ui-1');
const { liveRender } = await import('../source/frontend/js/app/live-pages.js');
await data.loadLiveData();
assert.equal(data.wallets[0].balance, 25000, 'FR02: wallet cached_balance mapping');
assert.equal(data.transactions[0].date, '2026-10-18', 'FR03: transaction occurred_on mapping');
assert.equal(data.transactions.length, 101, 'Transaction loader automatically retrieves pages after the first 100 rows');
const goalTransaction = data.transactions.find(item => item.type === 'savings_goal');
assert.equal(goalTransaction?.wallet, 'Ví chính', 'Goal transaction keeps the selected wallet');
assert.equal(goalTransaction?.goalName, 'Mua xe', 'Goal transaction keeps the selected goal');
assert.equal(data.budgets[0].spent, 42000, 'FR05: spentAmount mapping');
assert.equal(data.budgets[0].alertLevel, 'YELLOW', 'FR05: alertLevel mapping');
assert.equal(data.notifications[0].isRead, false, 'FR05: notification isRead mapping');
assert.equal(data.recurring.length, 1, 'FR06: schedules collection mapping');
assert.equal(data.categories[0].type, 'EXPENSE', 'FR03/FR05 category type mapping');
assert.equal(data.dataStatus.loans.state, 'ready', 'FR08: Loans API is the authoritative debt source');
assert.equal(data.loans[0].remainingPrincipal, '10000000.00000000', 'FR08: remaining loan principal mapping');
assert.equal(data.gold[0].currentValue, '8500000.00000000', 'FR08: current gold valuation mapping');
assert.equal(data.analytics.netWorth, '-1475000.00000000', 'FR10: dashboard summary mapping');
const render = (name, extraState = {}) => liveRender(name, { state: { search: '', txFilter: 'all', notifFilter: 'all', ...extraState }, heading: () => '<h1>test</h1>', currentUser: { fullName: 'Test', email: 'test@example.com', baseCurrency: 'VND', role: 'USER' } });
assert.match(render('transactions'), /Chuyển vào mục tiêu: Mua xe/, 'All-transactions view includes savings-goal history');
assert.match(render('wallets'), /25\.000/, 'Wallet value displayed from server only');
assert.match(render('overview'), /1\.475\.000/, 'FR10: Dashboard renders backend-authoritative Net Worth');
assert.match(render('overview'), /10\.000\.000/, 'FR10: Dashboard renders liabilities from Loans');
assert.match(render('loans'), /Khoản vay học tập/, 'FR08: Loans page renders server data without a fake create action');
assert.doesNotMatch(render('loans'), /live:create:loans/, 'FR08: unsupported loan writes are not offered');
assert.match(render('budgets'), /Cảnh báo Vàng/, 'PLAN-03/04: Budget YELLOW threshold badge displayed');
assert.match(render('budgets'), /live:edit:budgets:3/, 'PLAN-03: Budget edit action rendered');
assert.match(render('budgets'), /live:delete:budgets:3/, 'PLAN-03: Budget archive action rendered');
assert.match(render('notifications'), /live:read-all:notifications/, 'PLAN-05: Mark all notifications read action rendered');
assert.match(render('notifications'), /live:read:notifications:6/, 'PLAN-05: Single notification mark read action rendered');
assert.match(render('settings'), /Dữ liệu và quyền riêng tư/, 'Restore detailed settings sections');
assert.match(render('settings'), /Đổi mật khẩu/, 'Keep password changing');
await data.mutateLiveData('transactions', 'POST', { walletId: 1, categoryId: 5, amount: 10000 });
assert.match(requests.at(-1).init.headers['Idempotency-Key'], /^[0-9a-f-]{36}$/i, 'Idempotency header for financial writes');
const { walletApi } = await import('../source/frontend/js/services/wallet-api.js');
await walletApi.create({ name: 'Ví mới', walletType: 'CASH', currency: 'VND', initialBalance: '0' });
assert.equal(requests.at(-1).path, 'api/wallets', 'Wallet creation uses the wallet endpoint');
assert.match(requests.at(-1).init.headers['Idempotency-Key'], /^[0-9a-f-]{36}$/i, 'Wallet creation carries an idempotency key');
await walletApi.adjust(7, { direction: 'INCREASE', amount: '50000' });
assert.equal(requests.at(-1).path, 'api/wallets/7/adjustments', 'Wallet adjustment uses its dedicated endpoint');
assert.match(requests.at(-1).init.headers['Idempotency-Key'], /^[0-9a-f-]{36}$/i, 'Wallet adjustment carries an idempotency key');
await walletApi.remove(8);
assert.equal(requests.at(-1).path, 'api/wallets/8', 'Permanent wallet deletion uses DELETE on the wallet endpoint');
assert.equal(requests.at(-1).init.method, 'DELETE', 'Permanent wallet deletion uses the DELETE method');
const { GoldApi } = await import('../source/frontend/js/services/gold-api.js');
await GoldApi.createGoldTransaction({ actionType: 'BUY', goldType: 'SJC_BAR', quantityChi: '1', pricePerChi: '8500000' });
assert.equal(requests.at(-1).path, 'api/gold/transactions', 'FR08: Gold purchase uses the dedicated endpoint');
assert.match(requests.at(-1).init.headers['Idempotency-Key'], /^[0-9a-f-]{36}$/i, 'FR08: Gold purchase carries an idempotency key');
const walletFeatureSource = await readFile(new URL('../source/frontend/js/app/wallet-feature.js', import.meta.url), 'utf8');
const walletViewSource = await readFile(new URL('../source/frontend/js/app/wallets.js', import.meta.url), 'utf8');
const appCssSource = await readFile(new URL('../source/frontend/css/app.css', import.meta.url), 'utf8');
const uiCoreSource = await readFile(new URL('../source/frontend/js/app/ui-core.js', import.meta.url), 'utf8');
const mainSource = await readFile(new URL('../source/frontend/js/app/main.js', import.meta.url), 'utf8');
const routesSource = await readFile(new URL('../source/backend/public/index.php', import.meta.url), 'utf8');
const ledgerDaoSource = await readFile(new URL('../source/backend/src/Modules/Transactions/LedgerDAO.php', import.meta.url), 'utf8');
assert.match(uiCoreSource, /\['settings', 'Cài đặt & bảo mật', 'gear'\][\s\S]+settings: \['Cài đặt & bảo mật'/, 'Settings navigation and page title use one consistent label');
assert.match(mainSource, /#user-menu'[\s\S]+navigate\(state\.admin \? 'admin-settings' : 'settings'\)/, 'Account button opens Settings & Security');
assert.match(mainSource, /authApi\('change-password'/, 'Password form calls the real change-password API');
assert.ok(/authApi\('logout'/.test(mainSource) && /post\('\/auth\/logout'/.test(routesSource), 'Logout control is connected to the backend route');
assert.match(ledgerDaoSource, /listGoalTransactions[\s\S]+SAVINGS_GOAL[\s\S]+goal_allocations/, 'Transaction history includes goal allocations without extra ledger writes');
assert.match(walletFeatureSource, /50000[\s\S]+CUSTOM/, 'Opening balance offers 50,000 VND presets and custom input');
assert.match(walletFeatureSource, /if \(!isVnd\) preset\.value = 'CUSTOM'/, 'USD/EUR opening balance always uses manual input');
assert.doesNotMatch(walletFeatureSource, /VND có các mức gợi ý/, 'Wallet creation omits redundant explanatory note');
assert.doesNotMatch(walletFeatureSource, /Mỗi điều chỉnh tạo một giao dịch/, 'Wallet adjustment omits internal accounting note');
assert.ok(
  /wallet-opening-custom" type="text"/.test(walletFeatureSource) && /custom\.pattern = isVnd/.test(walletFeatureSource),
  'Wallet amount fields apply locale-aware strict patterns'
);
assert.match(walletFeatureSource, /Thêm hoặc bớt tiền[\s\S]+Lưu trữ ví/, 'Wallet edit menu groups adjustment and archive actions');
assert.match(walletViewSource, /wallet-manage-button[\s\S]+Chi tiết<\/button>/, 'Wallet card exposes one concise manage action');
assert.match(walletViewSource, /wallet-delete[\s\S]+Xóa vĩnh viễn/, 'Archived wallet exposes permanent deletion');
assert.match(appCssSource, /\.wallet-card \{[\s\S]*?background: linear-gradient\(145deg,#fff 55%,var\(--wallet-soft\)\)/, 'Wallet cards keep the owner-selected type color palette');
const { createWalletFeature } = await import('../source/frontend/js/app/wallet-feature.js');
const feature = createWalletFeature({
  state: { walletItems: [] },
  dialog: {},
  toast: () => {},
  render: () => {}
});
assert.equal(feature.handleAction('wallet-toggle', { dataset: {} }), false, 'Wallet feature leaves archive visibility toggle to the page controller');
console.log('OK: offline API/frontend contract checks passed. Does NOT verify MySQL or SMTP.');
