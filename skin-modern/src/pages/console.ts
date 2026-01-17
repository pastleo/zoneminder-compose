/**
 * ZoneMinder Modern Skin - Console Page
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

import { createModal, showModal, confirm } from '../lib/modal.ts';

const FUNCTIONS = ['None', 'Monitor', 'Modect', 'Record', 'Mocord', 'Nodect'];

export function init(): void {
  initSelectAllFilters();
  initFunctionModal();
  initBatchSelection();
  initAutoRefresh();
  initDragSort();
}

function initSelectAllFilters(): void {
  document.querySelectorAll('[data-select-all]').forEach((allCheckbox) => {
    const groupName = allCheckbox.getAttribute('data-select-all');
    if (!groupName) return;

    const groupCheckboxes = document.querySelectorAll<HTMLInputElement>(
      `input[name="${groupName}"]`
    );

    allCheckbox.addEventListener('change', function (this: HTMLInputElement) {
      if (this.checked) {
        groupCheckboxes.forEach((cb) => {
          cb.checked = false;
        });
      }
    });

    groupCheckboxes.forEach((cb) => {
      cb.addEventListener('change', () => {
        const anyChecked = Array.from(groupCheckboxes).some((c) => c.checked);
        (allCheckbox as HTMLInputElement).checked = !anyChecked;
      });
    });
  });
}

function initFunctionModal(): void {
  // Create the modal once
  createModal({
    id: 'function-modal',
    title: 'Monitor Function',
    content: buildFunctionFormHtml(),
    actions: [
      { label: 'Cancel', className: 'btn btn-ghost', onClick: () => {}, closeOnClick: true },
      {
        label: 'Save',
        className: 'btn btn-primary',
        type: 'submit',
        onClick: handleFunctionSave,
        closeOnClick: false,
      },
    ],
  });

  // Setup form change handlers
  const form = document.getElementById('function-form') as HTMLFormElement;
  const functionSelect = form?.elements.namedItem('newFunction') as HTMLSelectElement;
  if (functionSelect) {
    functionSelect.addEventListener('change', () => {
      updateFunctionFormVisibility(functionSelect.value);
    });
  }

  // Attach click handlers to all function buttons
  document.querySelectorAll('.function-btn').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      const target = e.currentTarget as HTMLElement;
      openFunctionModal({
        mid: target.dataset.mid || '',
        name: target.dataset.name || '',
        function: target.dataset.function || 'None',
        enabled: target.dataset.enabled === '1',
        decoding: target.dataset.decoding === '1',
      });
    });
  });
}

function buildFunctionFormHtml(): string {
  const options = FUNCTIONS.map(
    (fn) => `<option value="${fn}">${fn}</option>`
  ).join('');

  // Get CSRF token from the page
  const csrfInput = document.querySelector<HTMLInputElement>('input[name="__csrf_magic"]');
  const csrfToken = csrfInput?.value || '';

  return `
    <form id="function-form" method="post" action="?view=function">
      <input type="hidden" name="__csrf_magic" value="${csrfToken}" />
      <input type="hidden" name="action" value="save" />
      <input type="hidden" name="mid" value="" />

      <div class="mb-4">
        <span id="function-monitor-name" class="font-semibold"></span>
      </div>

      <div class="form-control mb-4">
        <label class="label">
          <span class="label-text">Function</span>
        </label>
        <select name="newFunction" class="select select-bordered w-full">
          ${options}
        </select>
      </div>

      <div id="analysis-row" class="form-control mb-2">
        <label class="label cursor-pointer justify-start gap-3">
          <input type="checkbox" name="newEnabled" value="1" class="checkbox checkbox-primary" />
          <span class="label-text">Analysis Enabled</span>
        </label>
      </div>

      <div id="decoding-row" class="form-control mb-2">
        <label class="label cursor-pointer justify-start gap-3">
          <input type="checkbox" name="newDecodingEnabled" value="1" class="checkbox checkbox-primary" />
          <span class="label-text">Decoding Enabled</span>
        </label>
      </div>
    </form>
  `;
}

interface MonitorData {
  mid: string;
  name: string;
  function: string;
  enabled: boolean;
  decoding: boolean;
}

function openFunctionModal(monitor: MonitorData): void {
  const form = document.getElementById('function-form') as HTMLFormElement;
  if (!form) return;

  // Set form values
  (form.elements.namedItem('mid') as HTMLInputElement).value = monitor.mid;
  (form.elements.namedItem('newFunction') as HTMLSelectElement).value = monitor.function;
  (form.elements.namedItem('newEnabled') as HTMLInputElement).checked = monitor.enabled;
  (form.elements.namedItem('newDecodingEnabled') as HTMLInputElement).checked = monitor.decoding;

  // Update monitor name display
  const nameEl = document.getElementById('function-monitor-name');
  if (nameEl) nameEl.textContent = monitor.name;

  // Update visibility based on function
  updateFunctionFormVisibility(monitor.function);

  showModal('function-modal');
}

function updateFunctionFormVisibility(fn: string): void {
  const analysisRow = document.getElementById('analysis-row');
  const decodingRow = document.getElementById('decoding-row');

  // Analysis is hidden for Monitor and None (they don't do motion detection)
  if (analysisRow) {
    analysisRow.style.display = fn === 'Monitor' || fn === 'None' ? 'none' : '';
  }

  // Decoding only applies to Record and Nodect
  if (decodingRow) {
    decodingRow.style.display = fn === 'Record' || fn === 'Nodect' ? '' : 'none';
  }
}

function handleFunctionSave(_e: Event, modal: HTMLDialogElement): void {
  const form = document.getElementById('function-form') as HTMLFormElement;
  if (form) {
    form.submit();
    modal.close();
  }
}

// ============ Batch Selection ============

function initBatchSelection(): void {
  const toolbar = document.getElementById('batchToolbar');
  const checkboxes = document.querySelectorAll<HTMLInputElement>('.monitor-checkbox');
  const form = document.getElementById('monitorActionsForm') as HTMLFormElement;

  if (!toolbar || !checkboxes.length || !form) return;

  const selectedCountEl = document.getElementById('selectedCount');
  const editBtn = document.getElementById('editBtn');
  const cloneBtn = document.getElementById('cloneBtn');
  const deleteBtn = document.getElementById('deleteBtn');
  const cancelBtn = document.getElementById('cancelSelectBtn');

  // Update toolbar state when checkboxes change
  function updateToolbar(): void {
    const checked = document.querySelectorAll<HTMLInputElement>('.monitor-checkbox:checked');
    const count = checked.length;

    if (selectedCountEl) selectedCountEl.textContent = String(count);

    if (count > 0) {
      toolbar!.classList.remove('hidden');
      // Clone only available for single selection
      if (cloneBtn) {
        cloneBtn.classList.toggle('hidden', count !== 1);
      }
    } else {
      toolbar!.classList.add('hidden');
    }

    // Highlight selected cards
    document.querySelectorAll('.monitor-card').forEach((card) => {
      const checkbox = card.querySelector<HTMLInputElement>('.monitor-checkbox');
      card.classList.toggle('ring-2', checkbox?.checked ?? false);
      card.classList.toggle('ring-primary', checkbox?.checked ?? false);
    });
  }

  checkboxes.forEach((cb) => {
    cb.addEventListener('change', updateToolbar);
  });

  // Edit button
  editBtn?.addEventListener('click', () => {
    const mids = getSelectedMids();
    if (mids.length === 1) {
      window.location.href = `?view=monitor&mid=${mids[0]}`;
    } else if (mids.length > 1) {
      window.location.href = `?view=monitors&${mids.map((m) => `mids[]=${m}`).join('&')}`;
    }
  });

  // Clone button
  cloneBtn?.addEventListener('click', () => {
    const mids = getSelectedMids();
    if (mids.length === 1) {
      window.location.href = `?view=monitor&dupId=${mids[0]}`;
    }
  });

  // Delete button
  deleteBtn?.addEventListener('click', async () => {
    const count = getSelectedMids().length;
    const confirmed = await confirm(
      `Warning: Deleting ${count} monitor(s) will also delete all events and database entries associated with them.\n\nAre you sure you wish to delete?`,
      'Delete Monitors'
    );
    if (confirmed) {
      const actionInput = form.querySelector<HTMLInputElement>('input[name="action"]');
      if (actionInput) actionInput.value = 'delete';
      form.submit();
    }
  });

  // Cancel button
  cancelBtn?.addEventListener('click', () => {
    checkboxes.forEach((cb) => {
      cb.checked = false;
    });
    updateToolbar();
  });
}

function getSelectedMids(): string[] {
  return Array.from(document.querySelectorAll<HTMLInputElement>('.monitor-checkbox:checked')).map(
    (cb) => cb.value
  );
}

// ============ Auto Refresh ============

function initAutoRefresh(): void {
  const main = document.querySelector('main');
  const refreshSeconds = main?.dataset.refresh;

  if (!refreshSeconds || refreshSeconds === '0') return;

  const interval = parseInt(refreshSeconds, 10) * 1000;
  if (isNaN(interval) || interval <= 0) return;

  console.log(`[skin-modern] Auto-refresh enabled: ${refreshSeconds}s`);

  setInterval(() => {
    // Don't refresh if user has selected monitors (would lose selection)
    const hasSelection = document.querySelectorAll('.monitor-checkbox:checked').length > 0;
    if (!hasSelection) {
      window.location.reload();
    }
  }, interval);
}

// ============ Drag & Drop Sorting ============

function initDragSort(): void {
  const grid = document.getElementById('monitorsGrid');
  if (!grid) return;

  const cards = grid.querySelectorAll<HTMLElement>('.monitor-card[draggable="true"]');
  if (!cards.length) return;

  let draggedCard: HTMLElement | null = null;

  cards.forEach((card) => {
    // Only allow drag from handle
    const handle = card.querySelector('.drag-handle');
    if (handle) {
      handle.addEventListener('mousedown', () => {
        card.setAttribute('data-drag-allowed', 'true');
      });
      handle.addEventListener('mouseup', () => {
        card.removeAttribute('data-drag-allowed');
      });
    }

    card.addEventListener('dragstart', (e) => {
      // Only allow drag if started from handle
      if (card.getAttribute('data-drag-allowed') !== 'true') {
        e.preventDefault();
        return;
      }

      draggedCard = card;
      card.classList.add('opacity-50', 'scale-95');
      e.dataTransfer!.effectAllowed = 'move';
      e.dataTransfer!.setData('text/plain', card.id);
    });

    card.addEventListener('dragend', () => {
      if (draggedCard) {
        draggedCard.classList.remove('opacity-50', 'scale-95');
        draggedCard.removeAttribute('data-drag-allowed');
      }
      draggedCard = null;

      // Remove all drop indicators
      grid.querySelectorAll('.drop-indicator').forEach((el) => el.remove());
      grid.querySelectorAll('.drag-over').forEach((el) => el.classList.remove('drag-over'));
    });

    card.addEventListener('dragover', (e) => {
      e.preventDefault();
      if (!draggedCard || draggedCard === card) return;

      e.dataTransfer!.dropEffect = 'move';
      card.classList.add('drag-over');
    });

    card.addEventListener('dragleave', () => {
      card.classList.remove('drag-over');
    });

    card.addEventListener('drop', (e) => {
      e.preventDefault();
      card.classList.remove('drag-over');

      if (!draggedCard || draggedCard === card) return;

      // Check if dragged card is already immediately before the target
      const isAlreadyBefore = draggedCard.nextElementSibling === card;

      // Determine drop position based on mouse position
      const rect = card.getBoundingClientRect();
      const midY = rect.top + rect.height / 2;
      let insertBefore = e.clientY < midY;

      // If dragging to the next position (moving down by 1), always insert after
      // Otherwise dropping on "top half" would result in no change
      if (isAlreadyBefore) {
        insertBefore = false;
      }

      if (insertBefore) {
        grid.insertBefore(draggedCard, card);
      } else {
        const nextSibling = card.nextElementSibling;
        if (nextSibling) {
          grid.insertBefore(draggedCard, nextSibling);
        } else {
          grid.appendChild(draggedCard);
        }
      }

      // Save the new order
      saveMonitorOrder();
    });
  });
}

function saveMonitorOrder(): void {
  const grid = document.getElementById('monitorsGrid');
  if (!grid) return;

  const monitorIds = Array.from(grid.querySelectorAll('.monitor-card'))
    .map((card) => card.id) // e.g., "monitor_id-123"
    .filter((id) => id);

  // Get CSRF token from the page
  const csrfInput = document.querySelector<HTMLInputElement>('input[name="__csrf_magic"]');
  const csrfToken = csrfInput?.value || '';

  // POST to ZoneMinder backend
  const params = new URLSearchParams();
  params.append('action', 'sort');
  params.append('__csrf_magic', csrfToken);
  monitorIds.forEach((id) => params.append('monitor_ids[]', id));

  fetch('?request=console', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: params.toString(),
  })
    .then((res) => {
      if (!res.ok) {
        console.error('[skin-modern] Failed to save monitor order:', res.status);
      } else {
        console.log('[skin-modern] Monitor order saved');
      }
    })
    .catch((err) => {
      console.error('[skin-modern] Failed to save monitor order:', err);
    });
}
