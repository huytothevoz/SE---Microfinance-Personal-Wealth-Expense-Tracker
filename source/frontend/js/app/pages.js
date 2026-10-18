/**
 * WealthFlow Pages Router & View Aggregator
 * Orchestrates modular page controllers and maps route names to view renders.
 */
import { renderOverview } from './analytics.js';
import { renderWalletPage } from './wallets.js?v=wallet-refactor-20261018-5';
import { renderTransactionPage, renderCategoryPage } from './transactions.js?v=wallet-refactor-20261018-5';
import { renderBudgetPage, renderRecurringPage } from './budgets.js';
import { renderLoanPage } from './loans.js';
import { renderGoldPage } from './gold.js';
import { renderGoalsPage } from './goals.js';
import { renderReportPage, renderReconciliationPage } from './reports.js';
import {
  renderAdminOverviewPage,
  renderAdminUsersPage,
  renderAdminCategoriesPage,
  renderAdminBroadcastPage,
  renderAdminSettingsPage,
  renderNotificationsPage,
  renderSettingsPage
} from './admin.js';

export function createPages({ state, currentUser }) {
  const PAGES = {
    overview: () => renderOverview(),
    transactions: () => renderTransactionPage({ state }),
    wallets: () => renderWalletPage({ state }),
    categories: () => renderCategoryPage({ state }),
    budgets: () => renderBudgetPage(),
    recurring: () => renderRecurringPage(),
    loans: () => renderLoanPage(),
    gold: () => renderGoldPage(),
    goals: () => renderGoalsPage(),
    reports: () => renderReportPage(),
    reconciliation: () => renderReconciliationPage(),
    notifications: () => renderNotificationsPage(),
    settings: () => renderSettingsPage({ currentUser }),
    'admin-overview': () => renderAdminOverviewPage(),
    'admin-users': () => renderAdminUsersPage(),
    'admin-categories': () => renderAdminCategoriesPage(),
    'admin-broadcasts': () => renderAdminBroadcastPage(),
    'admin-settings': () => renderAdminSettingsPage()
  };

  return { PAGES };
}
