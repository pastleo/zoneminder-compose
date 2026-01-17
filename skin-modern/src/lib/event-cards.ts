/**
 * ZoneMinder Modern Skin - Event Cards Component
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

import { confirm } from './modal.ts';

export interface EventRow {
  Id: string | number;
  Name: string;
  Cause: string;
  Notes: string;
  StartDateTime: string;
  EndDateTime: string;
  Length: string;
  Frames: number;
  AlarmFrames: number;
  TotScore: number;
  AvgScore: number;
  MaxScore: number;
  MaxScoreFrameId?: number;
  MonitorId?: number;
  Monitor?: string;
  MonitorName?: string;
  StorageId?: number;
  Storage?: string;
  DiskSpace?: string;
  Archived?: number | boolean;
  imgHtml?: string;
}

export interface EventsResponse {
  rows: EventRow[];
  total: number;
}

export interface EventCardsConfig {
  containerId: string;
  monitorId?: number;
  limit?: number;
  thumbnailWidth?: number;
  showMonitorName?: boolean;
  showCheckboxes?: boolean;
  showPagination?: boolean;
  filterParams?: Record<string, string>;
  onEventsLoaded?: (events: EventRow[], total: number) => void;
  onSelectionChange?: (selectedIds: Set<number>) => void;
}

const DEFAULT_LIMIT = 12;
const DEFAULT_THUMBNAIL_WIDTH = 400;

export class EventCards {
  private config: EventCardsConfig;
  private events: EventRow[] = [];
  private selectedIds = new Set<number>();
  private currentPage = 1;
  private totalEvents = 0;
  private isLoading = false;

  private container: HTMLElement | null = null;
  private gridEl: HTMLElement | null = null;
  private loadingEl: HTMLElement | null = null;
  private emptyEl: HTMLElement | null = null;

  constructor(config: EventCardsConfig) {
    this.config = {
      limit: DEFAULT_LIMIT,
      thumbnailWidth: DEFAULT_THUMBNAIL_WIDTH,
      showMonitorName: false,
      showCheckboxes: false,
      showPagination: false,
      ...config,
    };
    this.initElements();
    this.initEventListeners();
  }

  private initElements(): void {
    const { containerId } = this.config;
    this.container = document.getElementById(containerId);
    if (!this.container) return;

    this.gridEl = document.getElementById(`${containerId}Grid`);
    this.loadingEl = document.getElementById(`${containerId}Loading`);
    this.emptyEl = document.getElementById(`${containerId}Empty`);
  }

  private initEventListeners(): void {
    const { containerId, showPagination, showCheckboxes } = this.config;

    const refreshBtn = document.getElementById(`${containerId}RefreshBtn`);
    refreshBtn?.addEventListener('click', () => this.load());

    if (showPagination) {
      const prevBtn = document.getElementById(`${containerId}PrevPage`);
      const nextBtn = document.getElementById(`${containerId}NextPage`);
      const pageSizeSelect = document.getElementById(`${containerId}PageSize`) as HTMLSelectElement | null;

      prevBtn?.addEventListener('click', () => this.goToPage(this.currentPage - 1));
      nextBtn?.addEventListener('click', () => this.goToPage(this.currentPage + 1));
      pageSizeSelect?.addEventListener('change', () => {
        this.config.limit = parseInt(pageSizeSelect.value, 10) || DEFAULT_LIMIT;
        this.currentPage = 1;
        this.load();
      });
    }

    if (showCheckboxes) {
      const selectAllCheckbox = document.getElementById(`${containerId}SelectAll`) as HTMLInputElement | null;
      selectAllCheckbox?.addEventListener('change', () => {
        if (selectAllCheckbox.checked) {
          this.events.forEach(e => this.selectedIds.add(Number(e.Id)));
        } else {
          this.selectedIds.clear();
        }
        this.updateCardCheckboxes();
        this.config.onSelectionChange?.(this.selectedIds);
      });

      this.initToolbarButtons();
    }
  }

  private initToolbarButtons(): void {
    const { containerId } = this.config;

    const archiveBtn = document.getElementById(`${containerId}ArchiveBtn`);
    const unarchiveBtn = document.getElementById(`${containerId}UnarchiveBtn`);
    const exportBtn = document.getElementById(`${containerId}ExportBtn`);
    const deleteBtn = document.getElementById(`${containerId}DeleteBtn`);

    archiveBtn?.addEventListener('click', () => this.archiveSelected(true));
    unarchiveBtn?.addEventListener('click', () => this.archiveSelected(false));
    exportBtn?.addEventListener('click', () => this.exportSelected());
    deleteBtn?.addEventListener('click', () => this.deleteSelected());
  }

  async load(): Promise<void> {
    if (this.isLoading || !this.gridEl) return;
    this.isLoading = true;

    this.showLoading(true);
    this.clearCards();

    try {
      const { rows, total } = await this.fetchEvents();
      this.events = rows;
      this.totalEvents = total;
      this.renderCards();
      this.updatePagination();
      this.config.onEventsLoaded?.(rows, total);
    } catch (err) {
      console.error('[event-cards] Failed to load events:', err);
    } finally {
      this.showLoading(false);
      this.isLoading = false;
    }
  }

  private async fetchEvents(): Promise<EventsResponse> {
    const { monitorId, limit, filterParams, showPagination } = this.config;
    const params = new URLSearchParams({
      view: 'request',
      request: 'events',
      task: 'query',
      order: 'desc',
      sort: 'Id',
      limit: String(limit || DEFAULT_LIMIT),
    });

    if (showPagination) {
      const offset = (this.currentPage - 1) * (limit || DEFAULT_LIMIT);
      params.set('offset', String(offset));
    }

    if (monitorId) {
      params.set('filter[Query][terms][0][attr]', 'MonitorId');
      params.set('filter[Query][terms][0][op]', '=');
      params.set('filter[Query][terms][0][val]', String(monitorId));
    }

    if (filterParams) {
      Object.entries(filterParams).forEach(([key, value]) => {
        params.set(key, value);
      });
    }

    const response = await fetch(`?${params.toString()}`, { credentials: 'include' });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return response.json();
  }

  private renderCards(): void {
    if (!this.gridEl) return;

    if (this.events.length === 0) {
      this.emptyEl?.classList.remove('hidden');
      return;
    }

    this.emptyEl?.classList.add('hidden');
    const html = this.events.map(event => this.renderCard(event)).join('');
    this.gridEl.insertAdjacentHTML('beforeend', html);
    this.attachCardEventListeners();
  }

  private renderCard(event: EventRow): string {
    const { showMonitorName, showCheckboxes, monitorId } = this.config;
    const eid = event.Id;
    const filterQuery = monitorId ? this.getMonitorFilterQuery(monitorId) : '';
    const startDate = event.StartDateTime ? this.formatDateTime(event.StartDateTime) : '-';
    const endDate = event.EndDateTime ? this.formatDateTime(event.EndDateTime) : '-';
    const duration = event.Length || '-';

    const totScore = Number(event.TotScore) || 0;
    const avgScore = Number(event.AvgScore) || 0;
    const maxScore = Number(event.MaxScore) || 0;
    const maxScoreClass = maxScore >= 75 ? 'text-error' : maxScore >= 50 ? 'text-warning' : 'text-success';
    const isChecked = this.selectedIds.has(Number(eid));

    const storage = event.Storage || 'Default';
    const diskSpace = event.DiskSpace || '-';

    const thumbnailHtml = event.imgHtml
      ? `<a href="?view=event&eid=${eid}${filterQuery}" class="block aspect-video bg-base-300 rounded-lg overflow-hidden mb-2">${this.resizeThumbnail(event.imgHtml)}</a>`
      : '';

    const checkboxHtml = showCheckboxes
      ? `<input type="checkbox" class="checkbox checkbox-sm event-checkbox" data-eid="${eid}" ${isChecked ? 'checked' : ''} />`
      : '';

    const monitorName = event.Monitor || event.MonitorName;
    const monitorNameHtml = showMonitorName && monitorName
      ? `<div class="text-xs opacity-50 truncate">${this.escapeHtml(monitorName)}</div>`
      : '';

    const isArchived = event.Archived === 1 || event.Archived === true;
    const archivedBadge = isArchived
      ? '<span class="badge badge-xs badge-secondary" title="Protected from automatic deletion">Archived</span>'
      : '';

    return `
      <div class="card bg-base-300 shadow-sm hover:shadow-md transition-shadow event-card" data-eid="${eid}">
        <div class="card-body p-3">
          ${thumbnailHtml}
          <div class="flex items-start justify-between gap-2">
            <div class="flex items-start gap-2 flex-1 min-w-0">
              ${checkboxHtml}
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <a href="?view=event&eid=${eid}${filterQuery}" class="font-medium text-sm link link-hover link-primary truncate">
                    ${this.escapeHtml(String(event.Name || `Event ${eid}`))}
                  </a>
                  <span class="text-xs opacity-40">#${eid}</span>
                </div>
                ${monitorNameHtml}
              </div>
            </div>
            <button type="button" class="btn btn-ghost btn-xs btn-square text-error delete-event-btn" data-eid="${eid}" title="Delete">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
              </svg>
            </button>
          </div>
          
          <!-- Time info -->
          <div class="grid grid-cols-2 gap-x-2 mt-2 text-xs opacity-70">
            <div title="Start Time"><span class="opacity-60">Start:</span> ${startDate}</div>
            <div title="End Time"><span class="opacity-60">End:</span> ${endDate}</div>
          </div>
          
          <!-- Cause and duration badges -->
          <div class="flex flex-wrap gap-1 mt-2">
            <span class="badge badge-xs badge-outline">${this.escapeHtml(event.Cause || 'Unknown')}</span>
            <span class="badge badge-xs badge-outline">${duration}s</span>
            ${archivedBadge}
          </div>
          
          <!-- Scores with labels and tooltips -->
          <div class="grid grid-cols-3 gap-1 mt-2 text-xs">
            <div class="tooltip tooltip-bottom" data-tip="Average motion detection score across all alarm frames">
              <span class="opacity-60">Avg:</span> <span class="font-medium">${avgScore}</span>
            </div>
            <div class="tooltip tooltip-bottom" data-tip="Maximum motion detection score in any single frame">
              <span class="opacity-60">Max:</span> <span class="font-medium ${maxScoreClass}">${maxScore}</span>
            </div>
            <div class="tooltip tooltip-bottom" data-tip="Sum of all motion detection scores">
              <span class="opacity-60">Total:</span> <span class="font-medium">${totScore}</span>
            </div>
          </div>
          
          <!-- Frame counts -->
          <div class="grid grid-cols-2 gap-1 mt-1 text-xs opacity-70">
            <div title="Total Frames">
              <span class="opacity-60">Frames:</span> ${event.Frames || 0}
            </div>
            <div title="Alarm Frames">
              <span class="opacity-60">Alarms:</span> ${event.AlarmFrames || 0}
            </div>
          </div>
          
          <!-- Storage info -->
          <div class="grid grid-cols-2 gap-1 mt-1 text-xs opacity-70">
            <div title="Storage Area">
              <span class="opacity-60">Storage:</span> ${this.escapeHtml(storage)}
            </div>
            <div title="Disk Space Used">
              <span class="opacity-60">Size:</span> ${this.escapeHtml(diskSpace)}
            </div>
          </div>
        </div>
      </div>
    `;
  }

  private attachCardEventListeners(): void {
    if (!this.gridEl) return;

    this.gridEl.querySelectorAll('.delete-event-btn').forEach(btn => {
      btn.addEventListener('click', (e) => this.handleDeleteEvent(e));
    });

    if (this.config.showCheckboxes) {
      this.gridEl.querySelectorAll('.event-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', (e) => this.handleCheckboxChange(e));
      });
    }
  }

  private async handleDeleteEvent(e: Event): Promise<void> {
    e.preventDefault();
    e.stopPropagation();

    const btn = e.currentTarget as HTMLElement;
    const eid = btn.dataset.eid;
    if (!eid) return;

    const confirmed = await confirm(
      'Are you sure you want to delete this event? This action cannot be undone.',
      'Delete Event'
    );

    if (!confirmed) return;

    try {
      await this.deleteEvents([Number(eid)]);
      this.removeCardFromDom(eid);
    } catch (err) {
      console.error('[event-cards] Failed to delete event:', err);
    }
  }

  private handleCheckboxChange(e: Event): void {
    const checkbox = e.target as HTMLInputElement;
    const eid = Number(checkbox.dataset.eid);

    if (checkbox.checked) {
      this.selectedIds.add(eid);
    } else {
      this.selectedIds.delete(eid);
    }

    this.updateSelectAllCheckbox();
    this.config.onSelectionChange?.(this.selectedIds);
  }

  private updateCardCheckboxes(): void {
    if (!this.gridEl) return;
    this.gridEl.querySelectorAll('.event-checkbox').forEach((checkbox) => {
      const input = checkbox as HTMLInputElement;
      const eid = Number(input.dataset.eid);
      input.checked = this.selectedIds.has(eid);
    });
  }

  private updateSelectAllCheckbox(): void {
    const { containerId } = this.config;
    const selectAllCheckbox = document.getElementById(`${containerId}SelectAll`) as HTMLInputElement | null;
    if (!selectAllCheckbox) return;

    const allSelected = this.events.length > 0 && this.events.every(e => this.selectedIds.has(Number(e.Id)));
    const someSelected = this.selectedIds.size > 0;

    selectAllCheckbox.checked = allSelected;
    selectAllCheckbox.indeterminate = someSelected && !allSelected;
  }

  private async archiveSelected(archive: boolean): Promise<void> {
    if (this.selectedIds.size === 0) return;

    const task = archive ? 'archive' : 'unarchive';
    const params = new URLSearchParams({ request: 'events', task });
    this.selectedIds.forEach(eid => params.append('eids[]', String(eid)));

    try {
      const response = await fetch(`?${params.toString()}`, { credentials: 'include' });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      await this.load();
    } catch (err) {
      console.error(`[event-cards] Failed to ${task} events:`, err);
    }
  }

  private exportSelected(): void {
    if (this.selectedIds.size === 0) return;
    const eids = Array.from(this.selectedIds).join(',');
    window.location.href = `?view=export&eids=${eids}`;
  }

  private async deleteSelected(): Promise<void> {
    if (this.selectedIds.size === 0) return;

    const confirmed = await confirm(
      `Are you sure you want to delete ${this.selectedIds.size} event(s)? This action cannot be undone.`,
      'Delete Events'
    );

    if (!confirmed) return;

    try {
      await this.deleteEvents(Array.from(this.selectedIds));
      this.selectedIds.clear();
      await this.load();
    } catch (err) {
      console.error('[event-cards] Failed to delete events:', err);
    }
  }

  private async deleteEvents(eids: number[]): Promise<void> {
    const params = new URLSearchParams({ request: 'events', task: 'delete' });
    eids.forEach(eid => params.append('eids[]', String(eid)));

    const response = await fetch(`?${params.toString()}`, { credentials: 'include' });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
  }

  private removeCardFromDom(eid: string): void {
    const card = this.gridEl?.querySelector(`.event-card[data-eid="${eid}"]`);
    card?.remove();

    this.events = this.events.filter(e => String(e.Id) !== eid);
    this.selectedIds.delete(Number(eid));

    if (this.events.length === 0) {
      this.emptyEl?.classList.remove('hidden');
    }
  }

  private goToPage(page: number): void {
    const totalPages = Math.ceil(this.totalEvents / (this.config.limit || DEFAULT_LIMIT));
    if (page < 1 || page > totalPages) return;
    this.currentPage = page;
    this.load();
  }

  private updatePagination(): void {
    if (!this.config.showPagination) return;

    const { containerId, limit } = this.config;
    const pageSize = limit || DEFAULT_LIMIT;
    const totalPages = Math.max(1, Math.ceil(this.totalEvents / pageSize));

    const prevBtn = document.getElementById(`${containerId}PrevPage`) as HTMLButtonElement | null;
    const nextBtn = document.getElementById(`${containerId}NextPage`) as HTMLButtonElement | null;
    const pageInfo = document.getElementById(`${containerId}PageInfo`);
    const totalEl = document.getElementById(`${containerId}Total`);

    if (prevBtn) prevBtn.disabled = this.currentPage <= 1;
    if (nextBtn) nextBtn.disabled = this.currentPage >= totalPages;
    if (pageInfo) pageInfo.textContent = `${this.currentPage} / ${totalPages}`;
    if (totalEl) totalEl.textContent = `${this.totalEvents} event(s)`;
  }

  private showLoading(show: boolean): void {
    this.loadingEl?.classList.toggle('hidden', !show);
  }

  private clearCards(): void {
    this.gridEl?.querySelectorAll('.event-card').forEach(el => el.remove());
    this.emptyEl?.classList.add('hidden');
  }

  private getMonitorFilterQuery(monitorId: number): string {
    return `&filter[Query][terms][0][attr]=MonitorId&filter[Query][terms][0][op]=%3d&filter[Query][terms][0][val]=${monitorId}`;
  }

  private formatDateTime(dateStr: string): string {
    try {
      const date = new Date(dateStr);
      return date.toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      });
    } catch {
      return dateStr;
    }
  }

  private escapeHtml(str: string): string {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  private resizeThumbnail(imgHtml: string): string {
    const targetWidth = this.config.thumbnailWidth || DEFAULT_THUMBNAIL_WIDTH;
    
    let result = imgHtml
      .replace(/\swidth="\d+"/, ` width="${targetWidth}"`)
      .replace(/\sheight="\d+"/, ' height="auto"');
    
    result = result.replace(
      /(src="[^"]*)(width)=(\d+)/,
      `$1$2=${targetWidth}`
    );
    
    result = result.replace(
      /(src="[^"]*)(height)=(\d+)/,
      (match, prefix, key, oldHeight) => {
        return `${prefix}${key}=0`;
      }
    );
    
    return result;
  }

  getSelectedIds(): Set<number> {
    return new Set(this.selectedIds);
  }

  clearSelection(): void {
    this.selectedIds.clear();
    this.updateCardCheckboxes();
    this.updateSelectAllCheckbox();
    this.config.onSelectionChange?.(this.selectedIds);
  }
}
