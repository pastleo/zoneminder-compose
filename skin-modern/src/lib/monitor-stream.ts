/**
 * ZoneMinder Modern Skin - Monitor Stream Controller
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
 *
 * Stream command constants - values defined in PHP (includes/config.php) and exported
 * to JS via skins/classic/js/skin.js.php. Do NOT assume sequential numbering!
 * 
 * CMD_QUERY = 99 (not 15!) - Using wrong value causes status polls to send
 * unintended commands. For example, 15 = CMD_VARPLAY which resumes playback,
 * breaking pause functionality.
 * 
 * To verify current values, check browser console on classic skin:
 *   Object.keys(window).filter(k => k.startsWith('CMD_')).map(k => `${k}=${window[k]}`)
 */
const CMD_NONE = 0;
const CMD_PAUSE = 1;
const CMD_PLAY = 2;
const CMD_STOP = 3;
const CMD_FASTFWD = 4;
const CMD_SLOWFWD = 5;
const CMD_SLOWREV = 6;
const CMD_FASTREV = 7;
const CMD_ZOOMIN = 8;
const CMD_ZOOMOUT = 9;
const CMD_PAN = 10;
const CMD_SCALE = 11;
const CMD_PREV = 12;
const CMD_NEXT = 13;
const CMD_SEEK = 14;
const CMD_VARPLAY = 15;
const CMD_QUERY = 99;
const CMD_QUIT = 17;
const CMD_ANALYZE_ON = 18;
const CMD_ANALYZE_OFF = 19;

const STATE_IDLE = 0;
const STATE_ALARM = 2;
const STATE_ALERT = 3;

const STATE_STRINGS = ['Idle', 'Prealarm', 'Alarm', 'Alert', 'Tape'];

interface MonitorData {
  id: number;
  connKey: string;
  url: string;
  url_to_zms: string;
  width: number;
  height: number;
  type: string;
  refresh?: number;
}

interface StreamStatus {
  fps: number;
  capturefps: number;
  analysisfps: number;
  state: number;
  level: number;
  paused: boolean;
  delayed: boolean;
  rate: number;
  delay: number;
  zoom: string;
  enabled: boolean;
  forced: boolean;
  auth?: string;
}

interface StreamResponse {
  result: 'Ok' | 'Error';
  status?: StreamStatus;
  message?: string;
}

export class MonitorStream {
  private id: number;
  private connKey: string;
  private url: string;
  private urlToZms: string;
  private width: number;
  private height: number;
  private type: string;
  
  private element: HTMLImageElement | null = null;
  private frame: HTMLElement | null = null;
  private streamCmdTimer: ReturnType<typeof setTimeout> | null = null;
  private statusRefreshTimeout: number;
  private lastAlarmState = STATE_IDLE;
  /**
   * PITFALL: Must track previous pause state to avoid UI flicker.
   * 
   * Status polling runs every few seconds. Without state tracking, onPause/onPlay
   * callbacks fire on EVERY poll response, causing button states to flicker or
   * revert unexpectedly. Only fire callbacks when state actually changes.
   * 
   * null = initial state (prevents onPlay firing on page load before any user action)
   */
  private lastPausedState: boolean | null = null;
  
  status: StreamStatus | null = null;
  
  private onPauseCallback: (() => void) | null = null;
  private onPlayCallback: (() => void) | null = null;
  private onAlarmCallback: (() => void) | null = null;
  private onZoomChangeCallback: ((zoom: number) => void) | null = null;
  private onAlarmStateChangeCallback: ((enabled: boolean, forced: boolean) => void) | null = null;
  
  constructor(data: MonitorData) {
    this.id = data.id;
    this.connKey = data.connKey;
    this.url = data.url;
    this.urlToZms = data.url_to_zms;
    this.width = data.width;
    this.height = data.height;
    this.type = data.type;
    
    const refreshAttr = document.body.dataset.statusRefresh;
    this.statusRefreshTimeout = refreshAttr ? parseInt(refreshAttr, 10) * 1000 : 5000;
  }
  
  getElement(): HTMLImageElement | null {
    if (this.element) return this.element;
    this.element = document.getElementById(`liveStream${this.id}`) as HTMLImageElement;
    return this.element;
  }
  
  getFrame(): HTMLElement | null {
    if (this.frame) return this.frame;
    this.frame = document.getElementById(`imageFeed${this.id}`);
    return this.frame;
  }
  
  start(): void {
    const stream = this.getElement();
    if (!stream?.src) {
      console.log(`[MonitorStream] No src for #liveStream${this.id}`);
      return;
    }
    
    if (stream.getAttribute('loading') === 'lazy') {
      stream.setAttribute('loading', 'eager');
    }
    
    let src = stream.src.replace(/mode=single/i, 'mode=jpeg');
    if (!src.includes('connkey')) {
      src += `&connkey=${this.connKey}`;
    }
    
    if (stream.src !== src) {
      console.log(`[MonitorStream] Starting stream: ${src}`);
      stream.src = '';
      stream.src = src;
    }
    
    stream.onerror = () => this.onImageError();
    stream.onload = () => this.onImageLoad();
  }
  
  private onImageError(): void {
    console.log('[MonitorStream] Image stream error, stopping status polling');
    if (this.streamCmdTimer) {
      clearTimeout(this.streamCmdTimer);
      this.streamCmdTimer = null;
    }
  }
  
  private onImageLoad(): void {
    if (!this.streamCmdTimer) {
      console.log(`[MonitorStream] Image loaded, starting status polling for ${this.connKey}`);
      this.streamCmdTimer = setTimeout(() => this.queryStatus(), this.statusRefreshTimeout);
    }
  }
  
  stop(): void {
    this.sendCommand(CMD_STOP);
    if (this.streamCmdTimer) {
      clearTimeout(this.streamCmdTimer);
      this.streamCmdTimer = null;
    }
  }
  
  pause(): void {
    this.sendCommand(CMD_PAUSE);
  }
  
  play(): void {
    this.sendCommand(CMD_PLAY);
  }
  
  zoomOut(): void {
    this.sendCommand(CMD_ZOOMOUT);
  }
  
  /**
   * PITFALL: Coordinates must be in monitor's NATIVE resolution, not display size.
   * 
   * If display is 400x300 but monitor is 1280x720, clicking at display (100, 75)
   * must be converted: nativeX = 100 * (1280/400) = 320, nativeY = 75 * (720/300) = 180
   * 
   * The server zooms into the point specified, making it the new center of view.
   */
  zoomIn(x: number, y: number): void {
    this.sendCommandWithParams(CMD_ZOOMIN, { x, y });
  }
  
  /**
   * PITFALL: Pan coordinates specify WHERE TO CENTER THE VIEW, not a delta/offset.
   * 
   * - pan(640, 360) on a 1280x720 monitor centers the view (resets pan)
   * - pan(320, 360) centers on the left quarter of the image
   * 
   * For drag-to-pan UX, you must track currentPanX/Y and calculate:
   *   newPanX = currentPanX - (dragDeltaX * scaleX)
   * Then update currentPanX after drag ends, otherwise consecutive drags reset.
   * 
   * Coordinates must be in monitor's NATIVE resolution (see zoomIn).
   */
  pan(x: number, y: number): void {
    this.sendCommandWithParams(CMD_PAN, { x, y });
  }
  
  getZoom(): number {
    return parseFloat(this.status?.zoom || '1') || 1;
  }
  
  fastForward(): void {
    this.sendCommand(CMD_FASTFWD);
  }
  
  fastReverse(): void {
    this.sendCommand(CMD_FASTREV);
  }
  
  slowForward(): void {
    this.sendCommand(CMD_SLOWFWD);
  }
  
  slowReverse(): void {
    this.sendCommand(CMD_SLOWREV);
  }
  
  async alarmCommand(command: 'disableAlarms' | 'enableAlarms' | 'forceAlarm' | 'cancelForcedAlarm'): Promise<void> {
    const params = new URLSearchParams({
      view: 'request',
      request: 'alarm',
      id: String(this.id),
      command: command,
    });
    
    try {
      const response = await fetch(`${this.url}?${params.toString()}`, {
        credentials: 'include',
      });
      const text = await response.text();
      console.log(`[MonitorStream] Alarm command '${command}' response:`, text);
    } catch (err) {
      console.error('[MonitorStream] Alarm command failed:', err);
    }
  }
  
  enableAlarms(): Promise<void> {
    return this.alarmCommand('enableAlarms');
  }
  
  disableAlarms(): Promise<void> {
    return this.alarmCommand('disableAlarms');
  }
  
  forceAlarm(): Promise<void> {
    return this.alarmCommand('forceAlarm');
  }
  
  cancelForcedAlarm(): Promise<void> {
    return this.alarmCommand('cancelForcedAlarm');
  }
  
  isEnabled(): boolean {
    return this.status?.enabled ?? true;
  }
  
  isForced(): boolean {
    return this.status?.forced ?? false;
  }
  
  setScale(scale: number): void {
    const img = this.getElement();
    if (!img?.src) return;
    
    let newScale = scale;
    if (newScale > 100) newScale = 100;
    if (newScale <= 0) newScale = 100;
    
    const oldSrc = img.src;
    const newSrc = oldSrc.replace(/scale=\d+/i, `scale=${newScale}`);
    
    if (newSrc !== oldSrc) {
      if (this.streamCmdTimer) {
        clearTimeout(this.streamCmdTimer);
        this.streamCmdTimer = null;
      }
      
      if (oldSrc.includes('connkey') && oldSrc.includes('mode=single')) {
        this.sendCommand(CMD_QUIT);
      }
      
      console.log(`[MonitorStream] Changing scale to ${newScale}`);
      img.src = '';
      img.src = newSrc;
    }
  }
  
  onPause(callback: () => void): void {
    this.onPauseCallback = callback;
  }
  
  onPlay(callback: () => void): void {
    this.onPlayCallback = callback;
  }
  
  onAlarm(callback: () => void): void {
    this.onAlarmCallback = callback;
  }
  
  onZoomChange(callback: (zoom: number) => void): void {
    this.onZoomChangeCallback = callback;
  }
  
  onAlarmStateChange(callback: (enabled: boolean, forced: boolean) => void): void {
    this.onAlarmStateChangeCallback = callback;
  }
  
  private async sendCommand(command: number): Promise<void> {
    this.sendCommandWithParams(command, {});
  }
  
  private async sendCommandWithParams(command: number, extra: Record<string, number>): Promise<void> {
    const params = new URLSearchParams({
      view: 'request',
      request: 'stream',
      connkey: this.connKey,
      command: String(command),
    });
    
    for (const [key, value] of Object.entries(extra)) {
      params.set(key, String(value));
    }
    
    try {
      const response = await fetch(`${this.url}?${params.toString()}`, {
        credentials: 'include',
      });
      const data = await response.json() as StreamResponse;
      this.handleResponse(data);
    } catch (err) {
      console.error('[MonitorStream] Command failed:', err);
    }
  }
  
  private async queryStatus(): Promise<void> {
    if (this.type === 'WebSite') return;
    
    const params = new URLSearchParams({
      view: 'request',
      request: 'stream',
      connkey: this.connKey,
      command: String(CMD_QUERY),
    });
    
    try {
      const response = await fetch(`${this.url}?${params.toString()}`, {
        credentials: 'include',
      });
      const data = await response.json() as StreamResponse;
      this.handleResponse(data);
    } catch (err) {
      console.error('[MonitorStream] Status query failed:', err);
    }
    
    this.streamCmdTimer = setTimeout(() => this.queryStatus(), this.statusRefreshTimeout);
  }
  
  private handleResponse(resp: StreamResponse): void {
    if (resp.result !== 'Ok' || !resp.status) {
      if (resp.message) console.error('[MonitorStream]', resp.message);
      return;
    }
    
    this.status = resp.status;
    this.updateUI();
    this.handleAlarmState(resp.status.state);
  }
  
  private updateUI(): void {
    if (!this.status) return;
    
    const formatFps = (fps: number) => fps.toLocaleString(undefined, { 
      minimumFractionDigits: 1, 
      maximumFractionDigits: 1 
    });
    
    this.setText(`viewingFPSValue${this.id}`, formatFps(this.status.fps));
    this.setText(`captureFPSValue${this.id}`, formatFps(this.status.capturefps));
    this.setText(`analysisFPSValue${this.id}`, formatFps(this.status.analysisfps));
    this.setText(`stateValue${this.id}`, STATE_STRINGS[this.status.state] || 'Unknown');
    this.setText(`zoomValue${this.id}`, this.status.zoom);
    this.setText(`levelValue${this.id}`, String(this.status.level));
    this.setText(`rateValue${this.id}`, String(this.status.rate));
    this.setText(`delayValue${this.id}`, this.secsToTime(this.status.delay));
    
    this.updateOverlayStatus();
    
    this.onAlarmStateChangeCallback?.(this.status.enabled, this.status.forced);
    
    const isPaused = this.status.paused;
    const isDelayed = this.status.delayed;
    
    if (isPaused) {
      this.setText(`modeValue${this.id}`, 'Paused');
    } else if (isDelayed) {
      this.setText(`modeValue${this.id}`, 'Replay');
    } else {
      this.setText(`modeValue${this.id}`, 'Live');
    }
    
    // Only fire callbacks when pause state actually changes (not on every poll)
    if (this.lastPausedState !== isPaused) {
      if (isPaused) {
        this.onPauseCallback?.();
      } else if (this.lastPausedState !== null) {
        // Only call onPlay if we were previously paused (not on initial load)
        this.onPlayCallback?.();
      }
      this.lastPausedState = isPaused;
    }
    
    const stateEl = document.getElementById(`stateValue${this.id}`);
    if (stateEl) {
      stateEl.className = 'badge';
      if (this.status.state === STATE_ALARM) {
        stateEl.classList.add('badge-error');
      } else if (this.status.state === STATE_ALERT) {
        stateEl.classList.add('badge-warning');
      } else {
        stateEl.classList.add('badge-info');
      }
    }
  }
  
  private setText(id: string, text: string): void {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
  }
  
  private secsToTime(secs: number): string {
    const h = Math.floor(secs / 3600);
    const m = Math.floor((secs % 3600) / 60);
    const s = Math.floor(secs % 60);
    if (h > 0) return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    return `${m}:${String(s).padStart(2, '0')}`;
  }
  
  private updateOverlayStatus(): void {
    if (!this.status) return;
    
    const zoom = parseFloat(this.status.zoom) || 1;
    const zoomEl = document.getElementById('overlayZoom');
    const zoomVal = document.getElementById('overlayZoomValue');
    if (zoomEl && zoomVal) {
      zoomVal.textContent = String(zoom);
      zoomEl.classList.toggle('hidden', zoom <= 1);
    }
    
    this.onZoomChangeCallback?.(zoom);
    
    const rate = this.status.rate || 1;
    const rateEl = document.getElementById('overlayRate');
    const rateVal = document.getElementById('overlayRateValue');
    if (rateEl && rateVal) {
      rateVal.textContent = String(rate);
      rateEl.classList.toggle('hidden', rate === 1);
    }
    
    const delay = this.status.delay || 0;
    const delayEl = document.getElementById('overlayDelay');
    const delayVal = document.getElementById('overlayDelayValue');
    if (delayEl && delayVal) {
      delayVal.textContent = this.secsToTime(delay);
      delayEl.classList.toggle('hidden', delay === 0);
    }
    
    const level = this.status.level || 0;
    const bufferProgress = document.getElementById('bufferProgress');
    if (bufferProgress) {
      bufferProgress.style.width = `${level}%`;
    }
  }
  
  private handleAlarmState(state: number): void {
    const isAlarmed = state === STATE_ALARM || state === STATE_ALERT;
    const wasAlarmed = this.lastAlarmState === STATE_ALARM || this.lastAlarmState === STATE_ALERT;
    
    const newAlarm = isAlarmed && !wasAlarmed;
    const endAlarm = !isAlarmed && wasAlarmed;
    
    const monitorFrame = document.getElementById(`monitor${this.id}`);
    if (monitorFrame) {
      monitorFrame.classList.remove('alarm', 'alert', 'idle');
      if (state === STATE_ALARM) {
        monitorFrame.classList.add('alarm');
      } else if (state === STATE_ALERT) {
        monitorFrame.classList.add('alert');
      } else {
        monitorFrame.classList.add('idle');
      }
    }
    
    if (newAlarm || endAlarm) {
      this.onAlarmCallback?.();
      
      const alarmSound = document.getElementById('alarmSound');
      if (alarmSound) {
        alarmSound.classList.toggle('hidden', !newAlarm);
      }
    }
    
    this.lastAlarmState = state;
  }
  
  destroy(): void {
    if (this.streamCmdTimer) {
      clearTimeout(this.streamCmdTimer);
      this.streamCmdTimer = null;
    }
    
    const stream = this.getElement();
    if (stream) {
      stream.onerror = null;
      stream.onload = null;
    }
  }
}

export function getMonitorDataFromPage(): MonitorData | null {
  const body = document.body;
  const mid = body.dataset.monitorId;
  const connkey = body.dataset.connkey;
  
  if (!mid || !connkey) return null;
  
  return {
    id: parseInt(mid, 10),
    connKey: connkey,
    url: body.dataset.monitorUrl || window.location.pathname,
    url_to_zms: '/cgi-bin/nph-zms',
    width: parseInt(body.dataset.monitorWidth || '640', 10),
    height: parseInt(body.dataset.monitorHeight || '480', 10),
    type: body.dataset.monitorType || 'Local',
  };
}
