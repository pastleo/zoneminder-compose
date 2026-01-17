/**
 * ZoneMinder Modern Skin - Main JS Entry Point
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

// Page module registry - maps view names to dynamic imports
const pages: Record<string, () => Promise<{ init: () => void }>> = {
  console: () => import('./pages/console.ts'),
  watch: () => import('./pages/watch.ts'),
  options: () => import('./pages/options.ts'),
  events: () => import('./pages/events.ts'),
  monitor: () => import('./pages/monitor.ts'),
  event: () => import('./pages/event.ts'),
};

// Load page-specific module based on data-view attribute
async function loadPageModule(): Promise<void> {
  const view = document.body.dataset.view;
  if (!view) return;

  const loader = pages[view];
  if (loader) {
    try {
      const module = await loader();
      module.init();
      console.log(`[skin-modern] Loaded page module: ${view}`);
    } catch (err) {
      console.error(`[skin-modern] Failed to load page module: ${view}`, err);
    }
  }
}

// DOM ready helper
function ready(fn: () => void): void {
  if (document.readyState !== 'loading') {
    fn();
  } else {
    document.addEventListener('DOMContentLoaded', fn);
  }
}

// Simple DOM selectors for shared utilities
export function $(selector: string): Element | null {
  return document.querySelector(selector);
}

export function $$(selector: string): NodeListOf<Element> {
  return document.querySelectorAll(selector);
}

// Initialize
ready(() => {
  console.log('[skin-modern] JS bundle loaded');
  loadPageModule();
});
