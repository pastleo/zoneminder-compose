/**
 * ZoneMinder Modern Skin - Event Stream Controller
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

export const PLAYBACK_RATES = [25, 50, 100, 150, 200, 400, 800, 1600];
export const REVERSE_RATES = [-50, -100, -200, -400, -800];

export interface EventData {
  Id: number;
  MonitorId: number;
  Name: string;
  StartDateTime: string;
  EndDateTime: string | null;
  Length: number;
  Frames: number;
  AlarmFrames: number;
  Width: number;
  Height: number;
  Archived: boolean;
  Emailed: boolean;
  DefaultVideo: string | null;
  MaxScore: number;
  Notes: string;
  Orientation: string;
  MonitorName?: string;
  Cause?: string;
}

export interface NearEvents {
  PrevEventId: number;
  NextEventId: number;
  PrevEventStartTime: string | null;
  NextEventStartTime: string | null;
  PrevEventDefVideoPath: string | null;
  NextEventDefVideoPath: string | null;
}

export interface FrameData {
  FrameId: number;
  EventId: number;
  Type: 'Normal' | 'Alarm' | 'Bulk';
  Delta: number;
  Score: number;
}

interface StreamStatus {
  event: number;
  progress: number;
  rate: number;
  zoom: string;
  paused: boolean;
  duration?: number;
  auth?: string;
}

interface StreamResponse {
  result: 'Ok' | 'Error';
  status?: StreamStatus;
  message?: string;
}

interface EventStreamConfig {
  eventId: number;
  monitorId: number;
  connKey: string;
  mode: 'video' | 'mjpeg';
  videoSrc?: string;
  mjpegSrc?: string;
  initialRate?: number;
  initialScale?: number;
  filterQuery?: string;
  sortQuery?: string;
}

type EventCallback = () => void;
type ProgressCallback = (progress: number, duration: number) => void;
type RateCallback = (rate: number) => void;
type ZoomCallback = (zoom: number) => void;
type EventDataCallback = (event: EventData) => void;
type NearEventsCallback = (near: NearEvents) => void;
type FramesCallback = (frames: FrameData[]) => void;

export class EventStream {
  private config: EventStreamConfig;
  private eventData: EventData | null = null;
  private nearEvents: NearEvents | null = null;
  private frames: FrameData[] = [];
  
  private videoElement: HTMLVideoElement | null = null;
  private mjpegElement: HTMLImageElement | null = null;
  
  private isPaused = false;
  private currentRate = 100;
  private currentZoom = 1;
  private currentProgress = 0;
  private streamCmdTimer: ReturnType<typeof setTimeout> | null = null;
  private reverseInterval: ReturnType<typeof setInterval> | null = null;
  
  private onPlayCallback: EventCallback | null = null;
  private onPauseCallback: EventCallback | null = null;
  private onProgressCallback: ProgressCallback | null = null;
  private onRateChangeCallback: RateCallback | null = null;
  private onZoomChangeCallback: ZoomCallback | null = null;
  private onEventLoadCallback: EventDataCallback | null = null;
  private onNearEventsCallback: NearEventsCallback | null = null;
  private onFramesCallback: FramesCallback | null = null;
  private onEndCallback: EventCallback | null = null;
  
  constructor(config: EventStreamConfig) {
    this.config = config;
    this.currentRate = config.initialRate || 100;
  }
  
  async init(): Promise<void> {
    await this.loadEventData();
    await this.loadNearEvents();
    await this.loadFrames();
    
    if (this.config.mode === 'video') {
      this.initVideoElement();
    } else {
      this.initMjpegElement();
    }
  }
  
  private initVideoElement(): void {
    this.videoElement = document.getElementById('videoPlayer') as HTMLVideoElement;
    if (!this.videoElement) {
      console.error('[EventStream] Video element #videoPlayer not found');
      return;
    }
    
    this.videoElement.addEventListener('play', () => {
      this.isPaused = false;
      this.stopReversePlayback();
      this.onPlayCallback?.();
    });
    
    this.videoElement.addEventListener('pause', () => {
      this.isPaused = true;
      this.onPauseCallback?.();
    });
    
    this.videoElement.addEventListener('timeupdate', () => {
      this.currentProgress = this.videoElement!.currentTime;
      this.onProgressCallback?.(this.currentProgress, this.getDuration());
    });
    
    this.videoElement.addEventListener('ratechange', () => {
      this.currentRate = Math.round(this.videoElement!.playbackRate * 100);
      this.onRateChangeCallback?.(this.currentRate);
    });
    
    this.videoElement.addEventListener('ended', () => {
      this.onEndCallback?.();
    });
    
    if (this.currentRate > 0 && this.currentRate !== 100) {
      this.videoElement.playbackRate = this.currentRate / 100;
    }
  }
  
  private initMjpegElement(): void {
    this.mjpegElement = document.getElementById('evtStream') as HTMLImageElement;
    if (!this.mjpegElement) {
      console.error('[EventStream] MJPEG element #evtStream not found');
      return;
    }
    
    this.startStatusPolling();
  }
  
  private async loadEventData(): Promise<void> {
    try {
      const params = new URLSearchParams({
        view: 'request',
        request: 'status',
        entity: 'event',
        id: String(this.config.eventId),
      });
      
      const response = await fetch(`?${params.toString()}`, { credentials: 'include' });
      const data = await response.json();
      
      if (data.event) {
        this.eventData = data.event;
        if (this.eventData) {
          this.onEventLoadCallback?.(this.eventData);
        }
      }
    } catch (err) {
      console.error('[EventStream] Failed to load event data:', err);
    }
  }
  
  private async loadNearEvents(): Promise<void> {
    try {
      // Build base URL with required params
      let url = `?view=request&request=status&entity=nearevents&id=${this.config.eventId}`;
      
      // Append filterQuery and sortQuery directly - they contain pre-formatted query strings
      // like "&filter[Query][terms][0][attr]=MonitorId&..."
      if (this.config.filterQuery) {
        url += this.config.filterQuery;
      }
      if (this.config.sortQuery) {
        url += this.config.sortQuery;
      }
      
      const response = await fetch(url, { credentials: 'include' });
      const data = await response.json();
      
      if (data.nearevents) {
        this.nearEvents = data.nearevents;
        if (this.nearEvents) {
          this.onNearEventsCallback?.(this.nearEvents);
        }
      }
    } catch (err) {
      console.error('[EventStream] Failed to load near events:', err);
    }
  }
  
  private async loadFrames(): Promise<void> {
    try {
      const params = new URLSearchParams({
        view: 'request',
        request: 'status',
        entity: 'frames',
        id: String(this.config.eventId),
      });
      
      const response = await fetch(`?${params.toString()}`, { credentials: 'include' });
      const data = await response.json();
      
      if (data.frames) {
        this.frames = data.frames;
        this.onFramesCallback?.(this.frames);
      }
    } catch (err) {
      console.error('[EventStream] Failed to load frames:', err);
    }
  }
  
  play(): void {
    if (this.config.mode === 'video' && this.videoElement) {
      this.stopReversePlayback();
      this.videoElement.play();
    } else {
      this.sendCommand(CMD_PLAY);
    }
    this.isPaused = false;
    this.onPlayCallback?.();
  }
  
  pause(): void {
    if (this.config.mode === 'video' && this.videoElement) {
      this.stopReversePlayback();
      this.videoElement.pause();
    } else {
      this.sendCommand(CMD_PAUSE);
    }
    this.isPaused = true;
    this.onPauseCallback?.();
  }
  
  togglePlayPause(): void {
    if (this.isPaused) {
      this.play();
    } else {
      this.pause();
    }
  }
  
  setRate(rate: number): void {
    this.currentRate = rate;
    
    if (this.config.mode === 'video' && this.videoElement) {
      if (rate <= 0) {
        this.videoElement.pause();
        this.startReversePlayback(Math.abs(rate) / 100);
      } else {
        this.stopReversePlayback();
        this.videoElement.playbackRate = rate / 100;
        if (this.isPaused) {
          this.videoElement.play();
        }
      }
    } else {
      this.sendCommand(CMD_VARPLAY, { rate });
    }
    
    this.onRateChangeCallback?.(rate);
  }
  
  private startReversePlayback(speed: number): void {
    this.stopReversePlayback();
    
    if (!this.videoElement) return;
    
    const INTERVAL_MS = 500;
    const stepBack = speed * (INTERVAL_MS / 1000);
    
    this.reverseInterval = setInterval(() => {
      if (!this.videoElement) return;
      
      const newTime = this.videoElement.currentTime - stepBack;
      if (newTime <= 0) {
        this.videoElement.currentTime = 0;
        this.stopReversePlayback();
        this.pause();
      } else {
        this.videoElement.currentTime = newTime;
      }
    }, INTERVAL_MS);
  }
  
  private stopReversePlayback(): void {
    if (this.reverseInterval) {
      clearInterval(this.reverseInterval);
      this.reverseInterval = null;
    }
  }
  
  fastForward(): void {
    const currentIndex = PLAYBACK_RATES.indexOf(this.currentRate);
    const nextIndex = Math.min(currentIndex + 1, PLAYBACK_RATES.length - 1);
    const nextRate = PLAYBACK_RATES[nextIndex];
    if (nextRate !== undefined) {
      this.setRate(nextRate);
    }
  }
  
  fastReverse(): void {
    if (this.config.mode === 'video') {
      const absRate = Math.abs(this.currentRate);
      const currentIndex = PLAYBACK_RATES.indexOf(absRate);
      const prevIndex = currentIndex > 0 ? currentIndex - 1 : 0;
      const nextRate = PLAYBACK_RATES[prevIndex];
      if (nextRate !== undefined) {
        this.setRate(-nextRate);
      }
    } else {
      this.sendCommand(CMD_FASTREV);
    }
  }
  
  stepForward(): void {
    if (this.config.mode === 'video' && this.videoElement) {
      this.videoElement.pause();
      const duration = this.getDuration();
      const frameTime = duration / (this.eventData?.Frames || 100);
      this.videoElement.currentTime = Math.min(
        this.videoElement.currentTime + frameTime,
        duration
      );
    } else {
      this.sendCommand(CMD_SLOWFWD);
    }
  }
  
  stepBackward(): void {
    if (this.config.mode === 'video' && this.videoElement) {
      this.videoElement.pause();
      const duration = this.getDuration();
      const frameTime = duration / (this.eventData?.Frames || 100);
      this.videoElement.currentTime = Math.max(
        this.videoElement.currentTime - frameTime,
        0
      );
    } else {
      this.sendCommand(CMD_SLOWREV);
    }
  }
  
  seek(time: number): void {
    if (this.config.mode === 'video' && this.videoElement) {
      this.videoElement.currentTime = time;
    } else {
      this.sendCommand(CMD_SEEK, { offset: time });
    }
    this.currentProgress = time;
    this.onProgressCallback?.(time, this.getDuration());
  }
  
  seekPercent(percent: number): void {
    const duration = this.getDuration();
    this.seek((percent / 100) * duration);
  }
  
  zoomIn(x?: number, y?: number): void {
    if (this.config.mode === 'video') {
      this.currentZoom = Math.min(this.currentZoom + 0.5, 4);
      this.applyVideoZoom();
    } else {
      const params: Record<string, number> = {};
      if (x !== undefined) params.x = x;
      if (y !== undefined) params.y = y;
      this.sendCommand(CMD_ZOOMIN, params);
    }
    this.onZoomChangeCallback?.(this.currentZoom);
  }
  
  zoomOut(): void {
    if (this.config.mode === 'video') {
      this.currentZoom = Math.max(this.currentZoom - 0.5, 1);
      this.applyVideoZoom();
    } else {
      this.sendCommand(CMD_ZOOMOUT);
    }
    this.onZoomChangeCallback?.(this.currentZoom);
  }
  
  private applyVideoZoom(): void {
    if (!this.videoElement) return;
    this.videoElement.style.transform = `scale(${this.currentZoom})`;
  }
  
  hasPrev(): boolean {
    return (this.nearEvents?.PrevEventId ?? 0) > 0;
  }
  
  hasNext(): boolean {
    return (this.nearEvents?.NextEventId ?? 0) > 0;
  }
  
  getPrevEventId(): number {
    return this.nearEvents?.PrevEventId ?? 0;
  }
  
  getNextEventId(): number {
    return this.nearEvents?.NextEventId ?? 0;
  }
  
  navigatePrev(): void {
    if (!this.hasPrev()) return;
    this.navigateToEvent(this.nearEvents!.PrevEventId);
  }
  
  navigateNext(): void {
    if (!this.hasNext()) return;
    this.navigateToEvent(this.nearEvents!.NextEventId);
  }
  
  private navigateToEvent(eventId: number): void {
    let url = `?view=event&eid=${eventId}`;
    if (this.config.filterQuery) url += this.config.filterQuery;
    if (this.config.sortQuery) url += this.config.sortQuery;
    window.location.href = url;
  }
  
  private async sendCommand(command: number, params: Record<string, number> = {}): Promise<void> {
    const searchParams = new URLSearchParams({
      view: 'request',
      request: 'stream',
      connkey: this.config.connKey,
      command: String(command),
    });
    
    for (const [key, value] of Object.entries(params)) {
      searchParams.set(key, String(value));
    }
    
    try {
      const response = await fetch(`?${searchParams.toString()}`, { credentials: 'include' });
      const data = await response.json() as StreamResponse;
      this.handleStreamResponse(data);
    } catch (err) {
      console.error('[EventStream] Command failed:', err);
    }
  }
  
  private handleStreamResponse(resp: StreamResponse): void {
    if (resp.result !== 'Ok' || !resp.status) {
      if (resp.message) console.error('[EventStream]', resp.message);
      return;
    }
    
    const status = resp.status;
    
    this.currentProgress = status.progress;
    this.currentRate = status.rate * 100;
    this.currentZoom = parseFloat(status.zoom) || 1;
    this.isPaused = status.paused;
    
    if (status.duration && this.eventData) {
      this.eventData.Length = status.duration;
    }
    
    this.onProgressCallback?.(this.currentProgress, this.getDuration());
    this.onRateChangeCallback?.(this.currentRate);
    this.onZoomChangeCallback?.(this.currentZoom);
    
    if (status.paused) {
      this.onPauseCallback?.();
    } else {
      this.onPlayCallback?.();
    }
  }
  
  private startStatusPolling(): void {
    if (this.streamCmdTimer) return;
    
    const poll = async () => {
      await this.sendCommand(CMD_QUERY);
      this.streamCmdTimer = setTimeout(poll, 1000);
    };
    
    this.streamCmdTimer = setTimeout(poll, 500);
  }
  
  getEventData(): EventData | null {
    return this.eventData;
  }
  
  getNearEvents(): NearEvents | null {
    return this.nearEvents;
  }
  
  getFrames(): FrameData[] {
    return this.frames;
  }
  
  getDuration(): number {
    if (this.config.mode === 'video' && this.videoElement) {
      return this.videoElement.duration || this.eventData?.Length || 0;
    }
    return this.eventData?.Length || 0;
  }
  
  getProgress(): number {
    return this.currentProgress;
  }
  
  getRate(): number {
    return this.currentRate;
  }
  
  getZoom(): number {
    return this.currentZoom;
  }
  
  isPausedState(): boolean {
    return this.isPaused;
  }
  
  isVideoMode(): boolean {
    return this.config.mode === 'video';
  }
  
  onPlay(callback: EventCallback): void {
    this.onPlayCallback = callback;
  }
  
  onPause(callback: EventCallback): void {
    this.onPauseCallback = callback;
  }
  
  onProgress(callback: ProgressCallback): void {
    this.onProgressCallback = callback;
  }
  
  onRateChange(callback: RateCallback): void {
    this.onRateChangeCallback = callback;
  }
  
  onZoomChange(callback: ZoomCallback): void {
    this.onZoomChangeCallback = callback;
  }
  
  onEventLoad(callback: EventDataCallback): void {
    this.onEventLoadCallback = callback;
  }
  
  onNearEvents(callback: NearEventsCallback): void {
    this.onNearEventsCallback = callback;
  }
  
  onFrames(callback: FramesCallback): void {
    this.onFramesCallback = callback;
  }
  
  onEnd(callback: EventCallback): void {
    this.onEndCallback = callback;
  }
  
  destroy(): void {
    this.stopReversePlayback();
    
    if (this.streamCmdTimer) {
      clearTimeout(this.streamCmdTimer);
      this.streamCmdTimer = null;
    }
    
    if (this.config.mode === 'mjpeg') {
      this.sendCommand(CMD_QUIT);
    }
  }
}

export function getEventConfigFromPage(): EventStreamConfig | null {
  const body = document.body;
  
  const eventId = parseInt(body.dataset.eventId || '0', 10);
  const monitorId = parseInt(body.dataset.monitorId || '0', 10);
  const connKey = body.dataset.connkey || '';
  const mode = (body.dataset.streamMode === 'video' ? 'video' : 'mjpeg') as 'video' | 'mjpeg';
  
  if (!eventId) return null;
  
  return {
    eventId,
    monitorId,
    connKey,
    mode,
    videoSrc: body.dataset.videoSrc,
    mjpegSrc: body.dataset.mjpegSrc,
    initialRate: parseInt(body.dataset.rate || '100', 10),
    initialScale: parseInt(body.dataset.scale || '100', 10),
    filterQuery: body.dataset.filterQuery,
    sortQuery: body.dataset.sortQuery,
  };
}

export function formatTime(seconds: number): string {
  if (!isFinite(seconds)) return '0:00';
  
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  const s = Math.floor(seconds % 60);
  
  if (h > 0) {
    return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
  }
  return `${m}:${String(s).padStart(2, '0')}`;
}

export function formatRate(rate: number): string {
  const absRate = Math.abs(rate);
  const multiplier = absRate / 100;
  const sign = rate < 0 ? '-' : '';
  return `${sign}${multiplier}x`;
}
