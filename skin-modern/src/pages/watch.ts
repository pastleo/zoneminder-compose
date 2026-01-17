/**
 * ZoneMinder Modern Skin - Watch Page
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

import { MonitorStream } from '../lib/monitor-stream.ts';
import { EventCards } from '../lib/event-cards.ts';

const OVERLAY_HIDE_DELAY = 3000;

let monitorStream: MonitorStream | null = null;
let eventCards: EventCards | null = null;
let overlayHideTimeout: ReturnType<typeof setTimeout> | null = null;
let isPlaying = true;

export function init(): void {
  initMonitorStream();
  initEventCards();
  initToolbarButtons();
  initOverlayControls();
  initAlarmControls();
}

function getMonitorData() {
  const body = document.body;
  return {
    id: parseInt(body.dataset.monitorId || '0', 10),
    connKey: body.dataset.connkey || '',
    url: body.dataset.monitorUrl || window.location.pathname,
    url_to_zms: '/cgi-bin/nph-zms',
    width: parseInt(body.dataset.monitorWidth || '640', 10),
    height: parseInt(body.dataset.monitorHeight || '480', 10),
    type: body.dataset.monitorType || 'Local',
  };
}

function initMonitorStream(): void {
  const data = getMonitorData();
  if (!data.id || !data.connKey) {
    console.log('[watch] Missing monitor data, stream controls disabled');
    return;
  }
  
  monitorStream = new MonitorStream(data);
  
  monitorStream.onPause(() => {
    updatePlayPauseIcon(false);
  });
  
  monitorStream.onPlay(() => {
    updatePlayPauseIcon(true);
  });
  
  monitorStream.onAlarm(() => {
    eventCards?.load();
  });
  
  monitorStream.start();
}

function initEventCards(): void {
  const data = getMonitorData();
  if (!data.id) return;

  eventCards = new EventCards({
    containerId: 'events',
    monitorId: data.id,
    limit: 24,
    showCheckboxes: true,
    showPagination: true,
    onSelectionChange: updateEventToolbarState,
  });
  eventCards.load();

  (window as unknown as Record<string, unknown>).refreshEventCards = () => eventCards?.load();
}

function updateEventToolbarState(selectedIds: Set<number>): void {
  const hasSelection = selectedIds.size > 0;
  
  const archiveBtn = document.getElementById('eventsArchiveBtn') as HTMLButtonElement | null;
  const unarchiveBtn = document.getElementById('eventsUnarchiveBtn') as HTMLButtonElement | null;
  const exportBtn = document.getElementById('eventsExportBtn') as HTMLButtonElement | null;
  const deleteBtn = document.getElementById('eventsDeleteBtn') as HTMLButtonElement | null;

  if (archiveBtn) archiveBtn.disabled = !hasSelection;
  if (unarchiveBtn) unarchiveBtn.disabled = !hasSelection;
  if (exportBtn) exportBtn.disabled = !hasSelection;
  if (deleteBtn) deleteBtn.disabled = !hasSelection;
}

function initOverlayControls(): void {
  const overlay = document.getElementById('videoOverlay');
  const monitor = overlay?.closest('.monitor');
  if (!overlay || !monitor) return;
  
  const body = document.body;
  const hasReplayBuffer = body.dataset.streamReplayBuffer !== '0';
  
  setupOverlayVisibility(overlay, monitor as HTMLElement);
  setupPlayPauseButton();
  setupFullscreenButton(monitor as HTMLElement);
  
  if (hasReplayBuffer) {
    setupReplayBufferControls();
  }
}

function setupOverlayVisibility(overlay: HTMLElement, monitor: HTMLElement): void {
  const showOverlay = () => {
    overlay.classList.remove('opacity-0');
    overlay.classList.add('opacity-100');
    resetHideTimer(overlay);
  };
  
  const hideOverlay = () => {
    overlay.classList.remove('opacity-100');
    overlay.classList.add('opacity-0');
  };
  
  monitor.addEventListener('mousemove', showOverlay);
  monitor.addEventListener('mouseenter', showOverlay);
  monitor.addEventListener('mouseleave', hideOverlay);
  
  monitor.addEventListener('touchstart', () => {
    showOverlay();
    resetHideTimer(overlay);
  }, { passive: true });
  
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) {
      togglePlayPause();
    }
  });
}

function resetHideTimer(overlay: HTMLElement): void {
  if (overlayHideTimeout) {
    clearTimeout(overlayHideTimeout);
  }
  overlayHideTimeout = setTimeout(() => {
    overlay.classList.remove('opacity-100');
    overlay.classList.add('opacity-0');
  }, OVERLAY_HIDE_DELAY);
}

function setupPlayPauseButton(): void {
  const btn = document.getElementById('overlayPlayPauseBtn');
  btn?.addEventListener('click', togglePlayPause);
  
  const imageFeed = document.getElementById(`imageFeed${getMonitorData().id}`);
  imageFeed?.addEventListener('click', (e) => {
    if ((e.target as HTMLElement).closest('button, a')) return;
    togglePlayPause();
  });
}

function togglePlayPause(): void {
  if (isPlaying) {
    monitorStream?.pause();
  } else {
    monitorStream?.play();
  }
}

function updatePlayPauseIcon(playing: boolean): void {
  isPlaying = playing;
  const playIcon = document.getElementById('overlayPlayIcon');
  const pauseIcon = document.getElementById('overlayPauseIcon');
  
  if (playing) {
    playIcon?.classList.add('hidden');
    pauseIcon?.classList.remove('hidden');
  } else {
    playIcon?.classList.remove('hidden');
    pauseIcon?.classList.add('hidden');
  }
}



function setupFullscreenButton(monitor: HTMLElement): void {
  const btn = document.getElementById('overlayFullscreenBtn');
  const enterIcon = document.getElementById('overlayEnterFsIcon');
  const exitIcon = document.getElementById('overlayExitFsIcon');
  
  btn?.addEventListener('click', async () => {
    try {
      if (document.fullscreenElement) {
        await document.exitFullscreen();
      } else {
        await monitor.requestFullscreen();
      }
    } catch (err) {
      console.error('[watch] Fullscreen error:', err);
    }
  });
  
  document.addEventListener('fullscreenchange', () => {
    const isFullscreen = !!document.fullscreenElement;
    enterIcon?.classList.toggle('hidden', isFullscreen);
    exitIcon?.classList.toggle('hidden', !isFullscreen);
  });
}

function setupReplayBufferControls(): void {
  document.getElementById('overlayFastRevBtn')?.addEventListener('click', () => monitorStream?.fastReverse());
  document.getElementById('overlaySlowRevBtn')?.addEventListener('click', () => monitorStream?.slowReverse());
  document.getElementById('overlaySlowFwdBtn')?.addEventListener('click', () => monitorStream?.slowForward());
  document.getElementById('overlayFastFwdBtn')?.addEventListener('click', () => monitorStream?.fastForward());
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
  
  const refreshBtn = document.getElementById('refreshBtn');
  refreshBtn?.addEventListener('click', (e: Event) => {
    e.preventDefault();
    window.location.reload();
  });
}

function initAlarmControls(): void {
  const body = document.body;
  const canEditMonitors = body.dataset.canEditMonitors === '1';
  if (!canEditMonitors || !monitorStream) return;
  
  const enableBtn = document.getElementById('enableAlmBtn') as HTMLButtonElement | null;
  const forceBtn = document.getElementById('forceAlmBtn') as HTMLButtonElement | null;
  const enableBtnText = document.getElementById('enableAlmBtnText');
  const forceBtnText = document.getElementById('forceAlmBtnText');
  
  if (!enableBtn || !forceBtn) return;
  
  let isEnabled = true;
  let isForced = false;
  
  const updateButtonStates = (enabled: boolean, forced: boolean) => {
    isEnabled = enabled;
    isForced = forced;
    
    enableBtn.disabled = false;
    
    if (enabled) {
      enableBtn.classList.remove('btn-primary');
      enableBtn.classList.add('btn-ghost');
      enableBtn.title = 'Disable Alarms';
      if (enableBtnText) enableBtnText.textContent = 'Disable';
      
      forceBtn.disabled = false;
      if (forced) {
        forceBtn.classList.remove('btn-error');
        forceBtn.classList.add('btn-warning');
        forceBtn.title = 'Cancel Forced Alarm';
        if (forceBtnText) forceBtnText.textContent = 'Cancel Force';
      } else {
        forceBtn.classList.remove('btn-warning');
        forceBtn.classList.add('btn-error');
        forceBtn.title = 'Force Alarm';
        if (forceBtnText) forceBtnText.textContent = 'Force';
      }
    } else {
      enableBtn.classList.remove('btn-ghost');
      enableBtn.classList.add('btn-primary');
      enableBtn.title = 'Enable Alarms';
      if (enableBtnText) enableBtnText.textContent = 'Enable';
      
      forceBtn.disabled = true;
      forceBtn.title = 'Force Alarm (alarms disabled)';
    }
  };
  
  monitorStream.onAlarmStateChange(updateButtonStates);
  
  enableBtn.addEventListener('click', async () => {
    enableBtn.disabled = true;
    if (isEnabled) {
      await monitorStream?.disableAlarms();
    } else {
      await monitorStream?.enableAlarms();
    }
  });
  
  forceBtn.addEventListener('click', async () => {
    forceBtn.disabled = true;
    if (isForced) {
      await monitorStream?.cancelForcedAlarm();
    } else {
      await monitorStream?.forceAlarm();
    }
  });
}
