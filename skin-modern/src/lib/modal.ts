/**
 * ZoneMinder Modern Skin - Modal utility using native <dialog> element
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

export interface ModalOptions {
  id: string;
  title?: string;
  content: string;
  actions?: ModalAction[];
  onClose?: () => void;
  className?: string;
}

export interface ModalAction {
  label: string;
  className?: string;
  onClick?: (e: Event, modal: HTMLDialogElement) => void;
  type?: 'button' | 'submit';
  closeOnClick?: boolean;
}

// Store references to created modals
const modals = new Map<string, HTMLDialogElement>();

/**
 * Create or update a modal dialog
 */
export function createModal(options: ModalOptions): HTMLDialogElement {
  const { id, title, content, actions = [], onClose, className = '' } = options;

  // Check if modal already exists
  let dialog = modals.get(id);
  if (!dialog) {
    dialog = document.createElement('dialog');
    dialog.id = id;
    dialog.className = `modal ${className}`;
    document.body.appendChild(dialog);
    modals.set(id, dialog);

    // Handle backdrop click to close
    dialog.addEventListener('click', (e) => {
      if (e.target === dialog) {
        dialog.close();
      }
    });

    // Handle close event
    dialog.addEventListener('close', () => {
      onClose?.();
    });
  }

  // Build modal content
  const actionsHtml = actions.length
    ? `<div class="modal-action">
        ${actions
          .map(
            (action, i) =>
              `<button type="${action.type || 'button'}" class="${action.className || 'btn'}" data-action-index="${i}">${action.label}</button>`
          )
          .join('')}
      </div>`
    : '';

  dialog.innerHTML = `
    <div class="modal-box">
      ${title ? `<h3 class="font-bold text-lg">${title}</h3>` : ''}
      <div class="modal-content py-4">${content}</div>
      ${actionsHtml}
    </div>
    <form method="dialog" class="modal-backdrop">
      <button>close</button>
    </form>
  `;

  // Attach action handlers
  actions.forEach((action, i) => {
    const btn = dialog!.querySelector(`[data-action-index="${i}"]`);
    if (btn) {
      btn.addEventListener('click', (e) => {
        action.onClick?.(e, dialog!);
        if (action.closeOnClick !== false) {
          dialog!.close();
        }
      });
    }
  });

  return dialog;
}

/**
 * Show a modal by ID (must be created first)
 */
export function showModal(id: string): void {
  const dialog = modals.get(id) || (document.getElementById(id) as HTMLDialogElement);
  if (dialog) {
    dialog.showModal();
  }
}

/**
 * Close a modal by ID
 */
export function closeModal(id: string): void {
  const dialog = modals.get(id) || (document.getElementById(id) as HTMLDialogElement);
  if (dialog) {
    dialog.close();
  }
}

/**
 * Get a modal element by ID
 */
export function getModal(id: string): HTMLDialogElement | null {
  return modals.get(id) || (document.getElementById(id) as HTMLDialogElement);
}

/**
 * Quick confirm dialog
 */
export function confirm(message: string, title = 'Confirm'): Promise<boolean> {
  return new Promise((resolve) => {
    const modal = createModal({
      id: 'confirm-dialog',
      title,
      content: `<p>${message}</p>`,
      actions: [
        {
          label: 'Cancel',
          className: 'btn btn-ghost',
          onClick: () => resolve(false),
        },
        {
          label: 'OK',
          className: 'btn btn-primary',
          onClick: () => resolve(true),
        },
      ],
      onClose: () => resolve(false),
    });
    modal.showModal();
  });
}

/**
 * Quick alert dialog
 */
export function alert(message: string, title = 'Alert'): Promise<void> {
  return new Promise((resolve) => {
    const modal = createModal({
      id: 'alert-dialog',
      title,
      content: `<p>${message}</p>`,
      actions: [
        {
          label: 'OK',
          className: 'btn btn-primary',
          onClick: () => resolve(),
        },
      ],
      onClose: () => resolve(),
    });
    modal.showModal();
  });
}
