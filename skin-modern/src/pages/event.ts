/**
 * ZoneMinder Modern Skin - Event Page
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

import { EventStream, getEventConfigFromPage, formatTime, formatRate, PLAYBACK_RATES, type FrameData } from '../lib/event-stream.ts';
import { confirm, createModal, showModal, closeModal } from '../lib/modal.ts';

let eventStream: EventStream | null = null;

export function init(): void {
  const config = getEventConfigFromPage();
  if (!config) {
    console.log('[event] No event config found');
    return;
  }

  eventStream = new EventStream(config);
  
  eventStream.onPlay(handlePlay);
  eventStream.onPause(handlePause);
  eventStream.onProgress(handleProgress);
  eventStream.onRateChange(handleRateChange);
  eventStream.onNearEvents(handleNearEvents);
  eventStream.onFrames(handleFrames);
  eventStream.onEnd(handleEnd);

  eventStream.init();

  initDvrControls();
  initToolbarButtons();
  initSettingsControls();
  initProgressBar();
  initFullscreen();
}

function handlePlay(): void {
  const playIcon = document.getElementById('playIcon');
  const pauseIcon = document.getElementById('pauseIcon');
  playIcon?.classList.add('hidden');
  pauseIcon?.classList.remove('hidden');
  setText('modeValue', 'Playing');
}

function handlePause(): void {
  const playIcon = document.getElementById('playIcon');
  const pauseIcon = document.getElementById('pauseIcon');
  playIcon?.classList.remove('hidden');
  pauseIcon?.classList.add('hidden');
  setText('modeValue', 'Paused');
}

function handleProgress(progress: number, duration: number): void {
  setText('currentTime', formatTime(progress));
  setText('totalTime', formatTime(duration));
  setText('progressValue', formatTime(progress));
  
  const progressFill = document.getElementById('progressFill');
  if (progressFill && duration > 0) {
    const percent = (progress / duration) * 100;
    progressFill.style.width = `${percent}%`;
  }
}

function handleRateChange(rate: number): void {
  const rateSelect = document.getElementById('rate') as HTMLSelectElement | null;
  if (rateSelect) {
    rateSelect.value = String(rate);
  }
}

function handleNearEvents(near: { PrevEventId: number; NextEventId: number }): void {
  const prevBtn = document.getElementById('prevBtn') as HTMLButtonElement | null;
  const nextBtn = document.getElementById('nextBtn') as HTMLButtonElement | null;
  
  if (prevBtn) {
    prevBtn.disabled = near.PrevEventId === 0;
  }
  if (nextBtn) {
    nextBtn.disabled = near.NextEventId === 0;
  }
}

function handleFrames(frames: FrameData[]): void {
  renderAlarmCues(frames);
}

function handleEnd(): void {
  const body = document.body;
  const replayMode = (document.getElementById('replayMode') as HTMLSelectElement)?.value || 'none';
  
  switch (replayMode) {
    case 'single':
      eventStream?.seek(0);
      eventStream?.play();
      break;
    case 'all':
    case 'gapless':
      if (eventStream?.hasNext()) {
        eventStream.navigateNext();
      }
      break;
  }
}

function renderAlarmCues(frames: FrameData[]): void {
  const container = document.getElementById('alarmCue');
  if (!container || !frames.length) return;

  const lastFrame = frames[frames.length - 1];
  if (!lastFrame) return;
  
  const totalDelta = lastFrame.Delta || 1;
  let html = '';
  let inAlarm = false;
  let segmentStart = 0;

  for (const frame of frames) {
    const isAlarm = frame.Type === 'Alarm';

    if (isAlarm && !inAlarm) {
      const width = ((frame.Delta - segmentStart) / totalDelta) * 100;
      if (width > 0) {
        html += `<span class="bg-transparent" style="width:${width}%"></span>`;
      }
      segmentStart = frame.Delta;
      inAlarm = true;
    } else if (!isAlarm && inAlarm) {
      const width = ((frame.Delta - segmentStart) / totalDelta) * 100;
      html += `<span class="bg-error" style="width:${width}%"></span>`;
      segmentStart = frame.Delta;
      inAlarm = false;
    }
  }

  if (inAlarm) {
    const width = ((totalDelta - segmentStart) / totalDelta) * 100;
    html += `<span class="bg-error" style="width:${width}%"></span>`;
  } else {
    const remaining = ((totalDelta - segmentStart) / totalDelta) * 100;
    if (remaining > 0) {
      html += `<span class="bg-transparent" style="width:${remaining}%"></span>`;
    }
  }

  container.innerHTML = html;
}

function initDvrControls(): void {
  document.getElementById('playPauseBtn')?.addEventListener('click', () => {
    eventStream?.togglePlayPause();
  });

  document.getElementById('stepRevBtn')?.addEventListener('click', () => {
    eventStream?.stepBackward();
  });

  document.getElementById('stepFwdBtn')?.addEventListener('click', () => {
    eventStream?.stepForward();
  });

  document.getElementById('prevBtn')?.addEventListener('click', () => {
    eventStream?.navigatePrev();
  });

  document.getElementById('nextBtn')?.addEventListener('click', () => {
    eventStream?.navigateNext();
  });

  const videoPlayer = document.getElementById('videoPlayer');
  const imageFeed = document.getElementById('imageFeed');
  const clickTarget = videoPlayer || imageFeed;
  
  clickTarget?.addEventListener('click', (e) => {
    if ((e.target as HTMLElement).closest('button, a')) return;
    eventStream?.togglePlayPause();
  });
}

function initToolbarButtons(): void {
  document.getElementById('editBtn')?.addEventListener('click', handleEdit);
  document.getElementById('renameBtn')?.addEventListener('click', handleRename);
  document.getElementById('archiveBtn')?.addEventListener('click', () => handleArchive(true));
  document.getElementById('unarchiveBtn')?.addEventListener('click', () => handleArchive(false));
  document.getElementById('videoBtn')?.addEventListener('click', handleGenerateVideo);
  document.getElementById('deleteBtn')?.addEventListener('click', handleDelete);
}

async function handleRename(): Promise<void> {
  const eventName = document.getElementById('eventName')?.textContent || '';
  const newName = prompt('Enter new name:', eventName);
  
  if (newName && newName !== eventName) {
    const eventId = document.body.dataset.eventId;
    try {
      const response = await fetch(`?view=request&request=event&action=rename&id=${eventId}&eventName=${encodeURIComponent(newName)}`, {
        credentials: 'include',
      });
      const data = await response.json();
      if (data.result === 'Ok') {
        document.getElementById('eventName')!.textContent = newName;
      }
    } catch (err) {
      console.error('[event] Rename failed:', err);
    }
  }
}

async function handleArchive(archive: boolean): Promise<void> {
  const eventId = document.body.dataset.eventId;
  const task = archive ? 'archive' : 'unarchive';
  
  try {
    await fetch(`?request=events&task=${task}&eids[]=${eventId}`, {
      credentials: 'include',
    });
    window.location.reload();
  } catch (err) {
    console.error(`[event] ${task} failed:`, err);
  }
}

async function handleDelete(): Promise<void> {
  const confirmed = await confirm('Are you sure you want to delete this event?', 'Delete Event');
  if (!confirmed) return;

  const eventId = document.body.dataset.eventId;
  
  try {
    eventStream?.pause();
    await fetch(`?request=event&action=delete&id=${eventId}`, {
      credentials: 'include',
    });
    
    if (eventStream?.hasNext()) {
      eventStream.navigateNext();
    } else if (eventStream?.hasPrev()) {
      eventStream.navigatePrev();
    } else {
      window.history.back();
    }
  } catch (err) {
    console.error('[event] Delete failed:', err);
  }
}

async function handleEdit(): Promise<void> {
  const eventId = document.body.dataset.eventId;
  if (!eventId) return;

  try {
    const response = await fetch(`?view=request&request=status&entity=event&id=${eventId}`, {
      credentials: 'include',
    });
    const data = await response.json();
    
    if (!data.event) {
      console.error('[event] Failed to fetch event data');
      return;
    }

    const event = data.event;
    
    createModal({
      id: 'edit-event-modal',
      title: `Event ${eventId}`,
      content: `
        <form id="editEventForm" class="space-y-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text">${translate('Cause')}</span>
            </label>
            <input type="text" name="cause" class="input input-bordered w-full" value="${escapeHtml(event.Cause || '')}" />
          </div>
          <div class="form-control">
            <label class="label">
              <span class="label-text">${translate('Notes')}</span>
            </label>
            <textarea name="notes" class="textarea textarea-bordered w-full h-32">${escapeHtml(event.Notes || '')}</textarea>
          </div>
        </form>
      `,
      actions: [
        {
          label: translate('Cancel'),
          className: 'btn btn-ghost',
        },
        {
          label: translate('Save'),
          className: 'btn btn-primary',
          closeOnClick: false,
          onClick: async (_e, modal) => {
            const form = modal.querySelector('#editEventForm') as HTMLFormElement;
            const cause = (form.querySelector('[name="cause"]') as HTMLInputElement).value;
            const notes = (form.querySelector('[name="notes"]') as HTMLTextAreaElement).value;
            
            await saveEventDetails(eventId, cause, notes);
            closeModal('edit-event-modal');
            window.location.reload();
          },
        },
      ],
    });
    
    showModal('edit-event-modal');
  } catch (err) {
    console.error('[event] Edit failed:', err);
  }
}

async function saveEventDetails(eventId: string, cause: string, notes: string): Promise<void> {
  // Get CSRF token from global variable (injected by ZoneMinder's csrf-magic.js)
  const csrfToken = (window as unknown as { csrfMagicToken?: string }).csrfMagicToken || '';

  const formData = new FormData();
  formData.append('__csrf_magic', csrfToken);
  formData.append('view', 'eventdetail');
  formData.append('action', 'eventdetail');
  formData.append('markEids[]', eventId);
  formData.append('newEvent[Cause]', cause);
  formData.append('newEvent[Notes]', notes);

  await fetch('?view=eventdetail&action=eventdetail', {
    method: 'POST',
    credentials: 'include',
    body: formData,
  });
}

function translate(key: string): string {
  const translations: Record<string, string> = {
    'Cause': 'Cause',
    'Notes': 'Notes',
    'Cancel': 'Cancel',
    'Save': 'Save',
  };
  return translations[key] || key;
}

function escapeHtml(text: string): string {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function handleGenerateVideo(): void {
  const eventId = document.body.dataset.eventId;
  window.location.assign(`?view=video&eid=${eventId}`);
}

function initSettingsControls(): void {
  const codecSelect = document.getElementById('codec') as HTMLSelectElement | null;
  codecSelect?.addEventListener('change', () => {
    const url = new URL(window.location.href);
    url.searchParams.set('codec', codecSelect.value);
    window.location.href = url.toString();
  });

  const replaySelect = document.getElementById('replayMode') as HTMLSelectElement | null;
  replaySelect?.addEventListener('change', () => {
    document.cookie = `replayMode=${replaySelect.value};path=/;max-age=3600`;
  });

  const rateSelect = document.getElementById('rate') as HTMLSelectElement | null;
  rateSelect?.addEventListener('change', () => {
    const rate = parseInt(rateSelect.value, 10);
    if (!isNaN(rate)) {
      eventStream?.setRate(rate);
      document.cookie = `zmEventRate=${rate};path=/;max-age=3600`;
    }
  });
}

function initProgressBar(): void {
  const progressBar = document.getElementById('progressBar');
  if (!progressBar) return;

  progressBar.addEventListener('click', (e) => {
    const rect = progressBar.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const percent = (x / rect.width) * 100;
    eventStream?.seekPercent(percent);
  });
}

function initFullscreen(): void {
  const eventVideo = document.getElementById('eventVideo');
  const fullscreenBtn = document.getElementById('FullscreenBtn');
  const enterIcon = document.getElementById('EnterFsIcon');
  const exitIcon = document.getElementById('ExitFsIcon');

  if (!eventVideo || !fullscreenBtn) return;

  fullscreenBtn.addEventListener('click', async () => {
    try {
      if (document.fullscreenElement) {
        await document.exitFullscreen();
      } else {
        await eventVideo.requestFullscreen();
      }
    } catch (err) {
      console.error('[event] Fullscreen error:', err);
    }
  });

  document.addEventListener('fullscreenchange', () => {
    const isFullscreen = !!document.fullscreenElement;
    enterIcon?.classList.toggle('hidden', isFullscreen);
    exitIcon?.classList.toggle('hidden', !isFullscreen);
  });
}

function setText(id: string, text: string): void {
  const el = document.getElementById(id);
  if (el) el.textContent = text;
}
