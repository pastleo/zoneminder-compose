/**
 * ZoneMinder Modern Skin - Events Page
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

import { EventCards } from '../lib/event-cards.ts';

let eventCards: EventCards | null = null;

export function init(): void {
  initEventCards();
  initToolbarButtons();
}

function getFilterParamsFromUrl(): Record<string, string> {
  const params: Record<string, string> = {};
  const urlParams = new URLSearchParams(window.location.search);
  
  for (const [key, value] of urlParams.entries()) {
    if (key.startsWith('filter[')) {
      params[key] = value;
    }
  }
  return params;
}

function initEventCards(): void {
  const filterParams = getFilterParamsFromUrl();
  
  eventCards = new EventCards({
    containerId: 'eventCards',
    limit: 24,
    showMonitorName: true,
    showCheckboxes: true,
    showPagination: true,
    filterParams: Object.keys(filterParams).length > 0 ? filterParams : undefined,
    onSelectionChange: updateToolbarState,
  });
  eventCards.load();
}

function updateToolbarState(selectedIds: Set<number>): void {
  const hasSelection = selectedIds.size > 0;
  
  const archiveBtn = document.getElementById('eventCardsArchiveBtn') as HTMLButtonElement | null;
  const unarchiveBtn = document.getElementById('eventCardsUnarchiveBtn') as HTMLButtonElement | null;
  const exportBtn = document.getElementById('eventCardsExportBtn') as HTMLButtonElement | null;
  const deleteBtn = document.getElementById('eventCardsDeleteBtn') as HTMLButtonElement | null;

  if (archiveBtn) archiveBtn.disabled = !hasSelection;
  if (unarchiveBtn) unarchiveBtn.disabled = !hasSelection;
  if (exportBtn) exportBtn.disabled = !hasSelection;
  if (deleteBtn) deleteBtn.disabled = !hasSelection;
}

function initToolbarButtons(): void {
  const backBtn = document.getElementById('backBtn') as HTMLButtonElement | null;
  if (backBtn) {
    backBtn.disabled = !document.referrer.length;
    backBtn.addEventListener('click', (e: Event) => {
      e.preventDefault();
      window.history.back();
    });
  }
}
