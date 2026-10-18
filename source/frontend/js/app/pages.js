/** WealthFlow pages: ONLY live server data. No seeded demo records or hardcoded finance KPIs. */
import { META } from './ui-core.js';
import { liveRender, liveTransactionTable } from './live-pages.js';

export function createPages({ state, heading, currentUser }) {
  const ctx = { state, heading, currentUser };
  const PAGES = Object.fromEntries(Object.keys(META).map(key => [key, () => liveRender(key, ctx)]));
  return { PAGES, txTable: liveTransactionTable };
}
