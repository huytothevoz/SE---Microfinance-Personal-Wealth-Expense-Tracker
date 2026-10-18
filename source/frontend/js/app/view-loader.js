/**
 * WealthFlow View Loader
 * Loads independent HTML template files from frontend/views/*.html
 * with client-side in-memory caching for instant page transitions.
 */

const viewCache = new Map();

export async function loadView(viewName) {
  if (viewCache.has(viewName)) {
    return viewCache.get(viewName);
  }

  const response = await fetch(`frontend/views/${viewName}.html`, {
    cache: 'no-store'
  });

  if (!response.ok) {
    throw new Error(`Không thể nạp view template: ${viewName} (${response.status})`);
  }

  const html = await response.text();
  viewCache.set(viewName, html);
  return html;
}

/**
 * Preloads all known views in the background to ensure zero latency navigation
 */
export function preloadViews(viewNames = []) {
  return Promise.allSettled(viewNames.map(name => loadView(name)));
}
