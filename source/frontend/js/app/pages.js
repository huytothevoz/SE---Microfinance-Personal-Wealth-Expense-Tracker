/** Refactor SPA view aggregator: no demo fixtures, never return fabricated account data. */
import { META } from './ui-core.js';
import { liveRender, liveTransactionTable } from './live-pages.js';
import { renderWalletPage } from './wallets.js';

export function createPages({ state, heading, currentUser }) {
  const ctx = { state, heading, currentUser };
  const PAGES = {};
  for (const name of Object.keys(META)) {
    PAGES[name] = name === 'wallets'
      ? () => renderWalletPage({ state })
      : () => liveRender(name, ctx);
  }
  return { PAGES, txTable: liveTransactionTable };
}
