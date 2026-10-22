/** Refactor SPA view aggregator: no demo fixtures, never return fabricated account data. */
import { META } from './ui-core.js?v=goal-ui-5';
import { liveRender, liveTransactionTable } from './live-pages.js?v=goal-ui-5';
import { renderWalletPage } from './wallets.js?v=wallet-ui-13';
import { renderGoalsPage } from './goals.js?v=goal-ui-5';

export function createPages({ state, heading, currentUser }) {
  const ctx = { state, heading, currentUser };
  const PAGES = {};
  for (const name of Object.keys(META)) {
    PAGES[name] = name === 'wallets'
      ? () => renderWalletPage({ state })
      : name === 'goals' ? () => renderGoalsPage()
      : () => liveRender(name, ctx);
  }
  return { PAGES, txTable: liveTransactionTable };
}
