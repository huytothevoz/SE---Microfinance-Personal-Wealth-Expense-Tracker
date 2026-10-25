/** Refactor SPA view aggregator: no demo fixtures, never return fabricated account data. */
import { META } from './ui-core.js?v=wealth-ui-2';
import { liveRender, liveTransactionTable } from './live-pages.js?v=wealth-ui-2';
import { renderWalletPage } from './wallets.js?v=wallet-ui-13';
import { renderGoalsPage } from './goals.js?v=wealth-ui-2';
import { renderGoldPage } from './gold.js?v=wealth-ui-2';
import { renderLoansPage } from './loans.js?v=wealth-ui-2';
import { renderReconciliationPage } from './reports.js';
import { renderAdminCategoriesPage, renderAdminSettingsPage, renderAdminBroadcastPage, renderNotificationsPage } from './admin.js';

export function createPages({ state, heading, currentUser }) {
  const ctx = { state, heading, currentUser };
  const PAGES = {};
  for (const name of Object.keys(META)) {
    PAGES[name] = name === 'wallets'
      ? () => renderWalletPage({ state })
      : name === 'goals' ? () => renderGoalsPage()
      : name === 'gold' ? () => renderGoldPage()
      : name === 'loans' ? () => renderLoansPage()
      : name === 'reconciliation' ? () => renderReconciliationPage()
      : name === 'admin-categories' ? () => renderAdminCategoriesPage()
      : name === 'admin-settings' ? () => renderAdminSettingsPage()
      : name === 'admin-broadcasts' ? () => renderAdminBroadcastPage()
      : name === 'notifications' ? () => renderNotificationsPage()
      : () => liveRender(name, ctx);
  }
  return { PAGES, txTable: liveTransactionTable };
}
