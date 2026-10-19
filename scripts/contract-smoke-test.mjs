/** Offline interface contract test. Does not start a server, send email or write to a database. */
import assert from 'node:assert/strict';
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
  if (init.method === 'POST' || init.method === 'PUT' || init.method === 'DELETE') {
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
console.log('OK: 17 offline API/frontend contract checks passed. Does NOT verify DB or SMTP.');
