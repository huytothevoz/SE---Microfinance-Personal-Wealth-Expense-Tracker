/** Offline interface contract test. Does not start a server, send email or write to a database. */
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
const requests = [];
globalThis.document = { querySelector: () => null };
globalThis.fetch = async (path, init = {}) => {
  requests.push({ path, init });
  const paths = {
    '/api/wallets': { wallets: [{ id: 1, name: 'Ví kiểm thử', wallet_type: 'CASH', currency: 'VND', cached_balance: '25000' }] },
    '/api/transactions': { transactions: [{ id: 2, description: 'Gửi xe', transaction_type: 'EXPENSE', amount: 10000, currency: 'VND', occurred_on: '2026-10-18', category_name: 'Đi lại', status: 'POSTED' }] },
    '/api/budgets': { budgets: [{ id: 3, categoryId: 5, categoryName: 'Đi lại', budgetMonth: '2026-10', limitAmount: 50000, spentAmount: 42000, remainingAmount: 8000, usagePercent: 84, alertLevel: 'YELLOW' }] },
    '/api/recurring': { schedules: [{ id: 4, name: 'Tiền thuê', amount: 3000000, next_due_date: '2026-11-01' }] },
    '/api/categories': { categories: [{ id: 5, name: 'Đi lại', category_type: 'EXPENSE' }] },
    '/api/notifications': { notifications: [{ id: 6, title: 'Cảnh báo Ngân sách (Vàng): Đi lại', message: 'Chi tiêu đạt 84%.', notification_type: 'BUDGET_YELLOW', priority: 'MEDIUM', is_read: 0, created_at: '2026-10-18 10:00:00' }], unreadCount: 1 },
  };
  if (['POST', 'PUT', 'DELETE'].includes(init.method)) {
    assert.equal(init.headers['X-WealthFlow'], '1');
    return { status: 201, ok: true, headers: new Map([['content-type', 'application/json']]), json: async () => ({ success: true, data: { message: 'ok' } }) };
  }
  if (paths[path]) return { status: 200, ok: true, headers: new Map([['content-type', 'application/json']]), json: async () => ({ success: true, data: paths[path] }) };
  return { status: 404, ok: false, headers: new Map([['content-type', 'application/json']]), json: async () => ({ success: false }) };
};
const data = await import('../source/frontend/js/data/live-data.js');
const { liveRender } = await import('../source/frontend/js/app/live-pages.js');
await data.loadLiveData();
assert.equal(data.wallets[0].balance, 25000, 'FR02: wallet cached_balance mapping');
assert.equal(data.transactions[0].date, '2026-10-18', 'FR03: transaction occurred_on mapping');
assert.equal(data.budgets[0].spent, 42000, 'FR05: spentAmount mapping');
assert.equal(data.budgets[0].alertLevel, 'YELLOW', 'FR05: alertLevel mapping');
assert.equal(data.notifications[0].isRead, false, 'FR05: notification isRead mapping');
assert.equal(data.recurring.length, 1, 'FR06: schedules collection mapping');
assert.equal(data.categories[0].type, 'EXPENSE', 'FR03/FR05 category type mapping');
assert.equal(data.dataStatus.loans.state, 'unavailable', 'Missing API must not be shown as empty list');
const render = (name, extraState = {}) => liveRender(name, { state: { search: '', txFilter: 'all', notifFilter: 'all', ...extraState }, heading: () => '<h1>test</h1>', currentUser: { fullName: 'Test', email: 'test@example.com', baseCurrency: 'VND', role: 'USER' } });
assert.match(render('wallets'), /25\.000/, 'Wallet value displayed from server only');
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
const walletFeatureSource = await readFile(new URL('../source/frontend/js/app/wallet-feature.js', import.meta.url), 'utf8');
const walletViewSource = await readFile(new URL('../source/frontend/js/app/wallets.js', import.meta.url), 'utf8');
const appCssSource = await readFile(new URL('../source/frontend/css/app.css', import.meta.url), 'utf8');
const uiCoreSource = await readFile(new URL('../source/frontend/js/app/ui-core.js', import.meta.url), 'utf8');
const mainSource = await readFile(new URL('../source/frontend/js/app/main.js', import.meta.url), 'utf8');
const routesSource = await readFile(new URL('../source/backend/public/index.php', import.meta.url), 'utf8');
assert.match(uiCoreSource, /\['settings', 'Cài đặt & bảo mật', 'gear'\][\s\S]+settings: \['Cài đặt & bảo mật'/, 'Settings navigation and page title use one consistent label');
assert.match(mainSource, /#user-menu'[\s\S]+navigate\(state\.admin \? 'admin-settings' : 'settings'\)/, 'Account button opens Settings & Security');
assert.match(mainSource, /authApi\('change-password'/, 'Password form calls the real change-password API');
assert.ok(/authApi\('logout'/.test(mainSource) && /post\('\/auth\/logout'/.test(routesSource), 'Logout control is connected to the backend route');
assert.match(walletFeatureSource, /50000[\s\S]+CUSTOM/, 'Opening balance offers 50,000 VND presets and custom input');
assert.match(walletFeatureSource, /if \(!isVnd\) preset\.value = 'CUSTOM'/, 'USD/EUR opening balance always uses manual input');
assert.doesNotMatch(walletFeatureSource, /VND có các mức gợi ý/, 'Wallet creation omits redundant explanatory note');
assert.doesNotMatch(walletFeatureSource, /Mỗi điều chỉnh tạo một giao dịch/, 'Wallet adjustment omits internal accounting note');
assert.ok(
  /wallet-opening-custom" type="text"/.test(walletFeatureSource) && /custom\.pattern = isVnd/.test(walletFeatureSource),
  'Wallet amount fields apply locale-aware strict patterns'
);
assert.match(walletFeatureSource, /Thêm hoặc bớt tiền[\s\S]+Lưu trữ ví/, 'Wallet edit menu groups adjustment and archive actions');
assert.match(walletViewSource, /Chỉnh sửa<\/button>/, 'Wallet card exposes one concise edit action');
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
console.log('OK: 37 offline API/frontend contract checks passed. Does NOT verify MySQL or SMTP.');
