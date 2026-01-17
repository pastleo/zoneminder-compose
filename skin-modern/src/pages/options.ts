/**
 * ZoneMinder Modern Skin - Options Page
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

interface ServerData {
  Id: number;
  Name: string;
  Protocol: string;
  Hostname: string;
  Port: number;
  PathToIndex: string;
  PathToZMS: string;
  PathToApi: string;
  zmstats: boolean;
  zmaudit: boolean;
  zmtrigger: boolean;
  zmeventnotification: boolean;
}

interface StorageData {
  Id: number;
  Name: string;
  Path: string;
  Url: string;
  ServerId: number | null;
  Type: string;
  Scheme: string;
  DoDelete: boolean;
  Enabled: boolean;
}

function configureDeleteButton(checkbox: HTMLInputElement): void {
  const form = checkbox.form;
  if (!form) return;

  const deleteBtn = form.querySelector<HTMLButtonElement>('[name="deleteBtn"]');
  if (!deleteBtn) return;

  const checkboxes = form.querySelectorAll<HTMLInputElement>(
    `input[type="checkbox"][name="${checkbox.name}"]:not(:disabled)`
  );

  const anyChecked = Array.from(checkboxes).some((cb) => cb.checked);
  deleteBtn.disabled = !anyChecked;
}

function initDeleteCheckboxes(): void {
  const checkboxes = document.querySelectorAll<HTMLInputElement>(
    'input[type="checkbox"][data-on-click-this="configureDeleteButton"]'
  );

  checkboxes.forEach((checkbox) => {
    checkbox.addEventListener('change', () => configureDeleteButton(checkbox));
  });
}

function getServersData(): Record<number, ServerData> {
  const dataEl = document.getElementById('serversData');
  if (!dataEl) return {};
  try {
    return JSON.parse(dataEl.textContent || '{}');
  } catch {
    return {};
  }
}

function openServerModal(serverId: number): void {
  const modal = document.getElementById('serverModal') as HTMLDialogElement;
  if (!modal) return;

  const servers = getServersData();
  const server = servers[serverId];

  const titleEl = document.getElementById('serverModalTitle');
  if (titleEl) {
    titleEl.textContent = server ? `Server - ${server.Name}` : 'New Server';
  }

  (document.getElementById('serverModalId') as HTMLInputElement).value = serverId.toString();
  (document.getElementById('serverName') as HTMLInputElement).value = server?.Name || '';
  (document.getElementById('serverProtocol') as HTMLInputElement).value = server?.Protocol || '';
  (document.getElementById('serverHostname') as HTMLInputElement).value = server?.Hostname || '';
  (document.getElementById('serverPort') as HTMLInputElement).value = server?.Port?.toString() || '';
  (document.getElementById('serverPathToIndex') as HTMLInputElement).value = server?.PathToIndex || '';
  (document.getElementById('serverPathToZMS') as HTMLInputElement).value = server?.PathToZMS || '';
  (document.getElementById('serverPathToApi') as HTMLInputElement).value = server?.PathToApi || '';
  (document.getElementById('serverZmstats') as HTMLInputElement).checked = server?.zmstats || false;
  (document.getElementById('serverZmaudit') as HTMLInputElement).checked = server?.zmaudit || false;
  (document.getElementById('serverZmtrigger') as HTMLInputElement).checked = server?.zmtrigger || false;
  (document.getElementById('serverZmeventnotification') as HTMLInputElement).checked = server?.zmeventnotification || false;

  modal.showModal();
}

function initServerModal(): void {
  document.querySelectorAll<HTMLAnchorElement>('.serverCol').forEach((link) => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const sid = parseInt(link.dataset.sid || '0', 10);
      openServerModal(sid);
    });
  });

  const newServerBtn = document.getElementById('NewServerBtn');
  if (newServerBtn) {
    newServerBtn.addEventListener('click', () => {
      openServerModal(0);
    });
  }
}

function getStorageData(): Record<number, StorageData> {
  const dataEl = document.getElementById('storageData');
  if (!dataEl) return {};
  try {
    return JSON.parse(dataEl.textContent || '{}');
  } catch {
    return {};
  }
}

function openStorageModal(storageId: number): void {
  const modal = document.getElementById('storageModal') as HTMLDialogElement;
  if (!modal) return;

  const storages = getStorageData();
  const storage = storages[storageId];

  const titleEl = document.getElementById('storageModalTitle');
  if (titleEl) {
    titleEl.textContent = storage ? `Storage - ${storage.Name}` : 'New Storage';
  }

  (document.getElementById('storageModalId') as HTMLInputElement).value = storageId.toString();
  (document.getElementById('storageName') as HTMLInputElement).value = storage?.Name || '';
  (document.getElementById('storagePath') as HTMLInputElement).value = storage?.Path || '';
  (document.getElementById('storageUrl') as HTMLInputElement).value = storage?.Url || '';
  (document.getElementById('storageServerId') as HTMLSelectElement).value = storage?.ServerId?.toString() || '';
  (document.getElementById('storageType') as HTMLSelectElement).value = storage?.Type || 'local';
  (document.getElementById('storageScheme') as HTMLSelectElement).value = storage?.Scheme || 'Deep';
  (document.getElementById('storageDoDelete') as HTMLInputElement).checked = storage?.DoDelete ?? true;
  (document.getElementById('storageEnabled') as HTMLInputElement).checked = storage?.Enabled ?? true;

  modal.showModal();
}

function initStorageModal(): void {
  document.querySelectorAll<HTMLAnchorElement>('.storageCol').forEach((link) => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const sid = parseInt(link.dataset.sid || '0', 10);
      openStorageModal(sid);
    });
  });

  const newStorageBtn = document.getElementById('NewStorageBtn');
  if (newStorageBtn) {
    newStorageBtn.addEventListener('click', () => {
      openStorageModal(0);
    });
  }
}

function initModalCloseButtons(): void {
  document.querySelectorAll<HTMLButtonElement>('[data-close-modal]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const modalId = btn.dataset.closeModal;
      if (modalId) {
        const modal = document.getElementById(modalId) as HTMLDialogElement;
        modal?.close();
      }
    });
  });
}

export function init(): void {
  initDeleteCheckboxes();
  initServerModal();
  initStorageModal();
  initModalCloseButtons();
}
