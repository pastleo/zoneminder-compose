/**
 * ZoneMinder Modern Skin - Monitor Page
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

declare const ZM_OPT_CONTROL: number;
declare const ZM_HAS_ONVIF: number;
declare const ZM_DEFAULT_ASPECT_RATIO: string;
declare const ZM_OPT_USE_GEOLOCATION: number;
declare const ZM_OPT_GEOLOCATION_TILE_PROVIDER: string;
declare const ZM_OPT_GEOLOCATION_ACCESS_TOKEN: string;
declare const controlOptions: Record<number, string[]>;
declare const monitorNames: Record<string, boolean>;
declare const rtspStreamNames: Record<string, boolean>;
declare const L: any;

function humanFilesize(bytes: number): string {
  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  let i = 0;
  while (bytes >= 1024 && i < units.length - 1) {
    bytes /= 1024;
    i++;
  }
  return bytes.toFixed(1) + ' ' + units[i];
}

function updateMonitorDimensions(element: HTMLInputElement | HTMLSelectElement): void {
  const form = element.form;
  if (!form) return;

  const defaultAspectRatio = (window as any).ZM_DEFAULT_ASPECT_RATIO || '4:3';
  const widthFactor = parseInt(defaultAspectRatio.replace(/:.*$/, ''));
  const heightFactor = parseInt(defaultAspectRatio.replace(/^.*:/, ''));

  const widthInput = form.elements.namedItem('newMonitor[Width]') as HTMLInputElement;
  const heightInput = form.elements.namedItem('newMonitor[Height]') as HTMLInputElement;
  const preserveAspect = form.elements.namedItem('preserveAspectRatio') as HTMLInputElement;
  const dimensionsSelect = form.elements.namedItem('dimensions_select') as HTMLSelectElement;

  if (!widthInput || !heightInput) return;

  if (element instanceof HTMLInputElement && element.type === 'number') {
    let monitorWidth = parseInt(widthInput.value) || 0;
    let monitorHeight = parseInt(heightInput.value) || 0;

    if (preserveAspect?.checked) {
      if (element.name === 'newMonitor[Width]') {
        if (monitorWidth >= 0) {
          heightInput.value = String(Math.round((monitorWidth * heightFactor) / widthFactor));
        } else {
          heightInput.value = '';
        }
        monitorHeight = parseInt(heightInput.value) || 0;
      } else if (element.name === 'newMonitor[Height]') {
        if (monitorHeight >= 0) {
          widthInput.value = String(Math.round((monitorHeight * widthFactor) / heightFactor));
        } else {
          widthInput.value = '';
        }
        monitorWidth = parseInt(widthInput.value) || 0;
      }
    }

    if (dimensionsSelect) {
      const matchingOption = dimensionsSelect.querySelector(`option[value="${monitorWidth}x${monitorHeight}"]`);
      dimensionsSelect.value = matchingOption ? `${monitorWidth}x${monitorHeight}` : '';
    }
  } else if (dimensionsSelect) {
    const value = dimensionsSelect.value;
    if (value !== '') {
      const dimensions = value.split('x');
      widthInput.value = dimensions[0] || '';
      heightInput.value = dimensions[1] || '';
    }
  }
  updateEstimatedRamUse();
}

function updateEstimatedRamUse(): void {
  const widthInput = document.querySelector('input[name="newMonitor[Width]"]') as HTMLInputElement;
  const heightInput = document.querySelector('input[name="newMonitor[Height]"]') as HTMLInputElement;
  const coloursSelect = document.querySelector('select[name="newMonitor[Colours]"]') as HTMLSelectElement;
  const imageBufferInput = document.querySelector('input[name="newMonitor[ImageBufferCount]"]') as HTMLInputElement;
  const preEventInput = document.getElementById('newMonitor[PreEventCount]') as HTMLInputElement;
  const maxImageBufferInput = document.getElementById('newMonitor[MaxImageBufferCount]') as HTMLInputElement;
  const estimatedRamEl = document.getElementById('estimated_ram_use');

  if (!widthInput || !heightInput || !estimatedRamEl) return;

  const width = parseInt(widthInput.value) || 0;
  const height = parseInt(heightInput.value) || 0;
  const colours = parseInt(coloursSelect?.value || '3');

  let minBufferCount = parseInt(imageBufferInput?.value || '0');
  minBufferCount += parseInt(preEventInput?.value || '0');
  const minBufferSize = minBufferCount * width * height * colours;
  let result = 'Min: ' + humanFilesize(minBufferSize);

  const maxBufferCount = parseInt(maxImageBufferInput?.value || '0');
  if (maxBufferCount) {
    const maxBufferSize = (minBufferCount + maxBufferCount) * width * height * colours;
    result += ' Max: ' + humanFilesize(maxBufferSize);
  } else {
    result += ' Max: Unlimited';
  }

  estimatedRamEl.textContent = result;
}

function bufferSettingOnInput(this: HTMLInputElement): void {
  const maxImageBufferCount = document.getElementById('newMonitor[MaxImageBufferCount]') as HTMLInputElement;
  const preEventCount = document.getElementById('newMonitor[PreEventCount]') as HTMLInputElement;

  if (!maxImageBufferCount || !preEventCount) return;

  if (parseInt(preEventCount.value) > parseInt(maxImageBufferCount.value)) {
    if (this.id === 'newMonitor[PreEventCount]') {
      maxImageBufferCount.value = preEventCount.value;
    } else {
      preEventCount.value = maxImageBufferCount.value;
    }
  }
  updateEstimatedRamUse();
}

function updateFunctionVisibility(): void {
  const functionSelect = document.querySelector('select[name="newMonitor[Function]"]') as HTMLSelectElement;
  if (!functionSelect) return;

  const value = functionSelect.value;
  const functionEnabled = document.getElementById('FunctionEnabled');
  const decodingEnabled = document.getElementById('FunctionDecodingEnabled');
  const functionHelp = document.getElementById('function_help');

  if (functionHelp) {
    functionHelp.querySelectorAll('div').forEach(el => el.classList.add('hidden'));
    const helpEl = document.getElementById(value + 'Help');
    if (helpEl) helpEl.classList.remove('hidden');
  }

  if (functionEnabled) {
    if (value === 'Monitor' || value === 'None') {
      functionEnabled.classList.add('hidden');
    } else {
      functionEnabled.classList.remove('hidden');
    }
  }

  if (decodingEnabled) {
    if (value === 'Record' || value === 'Nodect') {
      decodingEnabled.classList.remove('hidden');
    } else {
      decodingEnabled.classList.add('hidden');
    }
  }
}

function updateVideoWriterVisibility(): void {
  const videoWriterSelect = document.querySelector('select[name="newMonitor[VideoWriter]"]') as HTMLSelectElement;
  if (!videoWriterSelect) return;

  const value = videoWriterSelect.value;
  const outputCodec = document.querySelector('.OutputCodec') as HTMLElement;
  const encoder = document.querySelector('.Encoder') as HTMLElement;

  if (value === '1') {
    outputCodec?.classList.remove('hidden');
    encoder?.classList.remove('hidden');
  } else {
    outputCodec?.classList.add('hidden');
    encoder?.classList.add('hidden');
  }
}

function updateOutputCodecVisibility(): void {
  const codecSelect = document.querySelector('select[name="newMonitor[OutputCodec]"]') as HTMLSelectElement;
  const encoderSelect = document.querySelector('select[name="newMonitor[Encoder]"]') as HTMLSelectElement;

  if (!codecSelect || !encoderSelect) return;

  const value = codecSelect.value;
  for (let i = 0; i < encoderSelect.options.length; i++) {
    const option = encoderSelect.options[i];
    if (!option) continue;
    
    if (value === '27') {
      option.disabled = !option.value.includes('264');
    } else if (value === '173') {
      option.disabled = !(option.value.includes('hevc') || option.value.includes('265'));
    } else if (value === '226') {
      option.disabled = !option.value.includes('av1');
    } else {
      option.disabled = false;
    }

    if (option.disabled && option.selected) {
      const firstOption = encoderSelect.options[0];
      if (firstOption) firstOption.selected = true;
      option.selected = false;
    }
  }
}

function changeWebColour(): void {
  const input = document.querySelector('input[name="newMonitor[WebColour]"]') as HTMLInputElement;
  const swatch = document.getElementById('WebSwatch');
  if (input && swatch) {
    swatch.style.backgroundColor = input.value;
  }
}

function randomWebColour(): void {
  const letters = '0123456789ABCDEF';
  let colour = '#';
  for (let i = 0; i < 6; i++) {
    colour += letters[Math.floor(Math.random() * 16)];
  }
  const input = document.querySelector('input[name="newMonitor[WebColour]"]') as HTMLInputElement;
  const swatch = document.getElementById('WebSwatch');
  if (input) input.value = colour;
  if (swatch) swatch.style.backgroundColor = colour;
}

function getLocation(): void {
  if ('geolocation' in navigator) {
    navigator.geolocation.getCurrentPosition((position) => {
      const form = document.getElementById('contentForm') as HTMLFormElement;
      if (!form) return;
      const latInput = form.elements.namedItem('newMonitor[Latitude]') as HTMLInputElement;
      const lonInput = form.elements.namedItem('newMonitor[Longitude]') as HTMLInputElement;
      if (latInput) latInput.value = String(position.coords.latitude);
      if (lonInput) lonInput.value = String(position.coords.longitude);
    });
  } else {
    console.log('Geolocation not available');
  }
}

function validateForm(): boolean {
  const form = document.getElementById('contentForm') as HTMLFormElement;
  if (!form) return false;

  const errors: string[] = [];
  const warnings: string[] = [];
  const elements = form.elements;

  for (let i = 0; i < elements.length; i++) {
    const el = elements[i] as HTMLInputElement;
    if (el.nodeName !== 'SELECT' && el.value) {
      el.value = el.value.trim();
    }
  }

  const nameInput = form.elements.namedItem('newMonitor[Name]') as HTMLInputElement | null;
  if (nameInput) {
    if (nameInput.value.search(/[^\w\-\.\(\)\:\/ ]/) >= 0) {
      errors.push('Monitor name contains invalid characters');
    } else if ((window as any).monitorNames?.[nameInput.value]) {
      errors.push('A monitor with this name already exists');
    }
  }

  const typeSelect = form.elements.namedItem('newMonitor[Type]') as HTMLSelectElement | null;
  if (typeSelect && typeSelect.value !== 'WebSite') {
    const analysisFPS = form.elements.namedItem('newMonitor[AnalysisFPSLimit]') as HTMLInputElement;
    if (analysisFPS?.value && !(parseFloat(analysisFPS.value) > 0)) {
      errors.push('Invalid Analysis FPS value');
    }

    const maxFPS = form.elements.namedItem('newMonitor[MaxFPS]') as HTMLInputElement;
    if (maxFPS?.value && !(parseFloat(maxFPS.value) > 0)) {
      errors.push('Invalid Maximum FPS value');
    }

    const alarmMaxFPS = form.elements.namedItem('newMonitor[AlarmMaxFPS]') as HTMLInputElement;
    if (alarmMaxFPS?.value && !(parseFloat(alarmMaxFPS.value) > 0)) {
      errors.push('Invalid Alarm Maximum FPS value');
    }
  }

  if (errors.length) {
    alert(errors.join('\n'));
    return false;
  }

  if (warnings.length) {
    if (!confirm(warnings.join('\n'))) {
      return false;
    }
  }

  const actionInput = form.elements.namedItem('action') as HTMLInputElement;
  if (actionInput) actionInput.value = 'save';
  form.submit();
  return true;
}

export function init(): void {
  const form = document.getElementById('contentForm') as HTMLFormElement;
  if (!form) return;

  form.addEventListener('keyup', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
    }
  });

  document.querySelectorAll('input[name="newMonitor[SignalCheckColour]"]').forEach((el) => {
    (el as HTMLInputElement).addEventListener('input', (e) => {
      const swatch = document.getElementById('SignalCheckSwatch');
      if (swatch) swatch.style.backgroundColor = (e.target as HTMLInputElement).value;
    });
  });

  document.querySelectorAll('input[name="newMonitor[WebColour]"]').forEach((el) => {
    (el as HTMLInputElement).addEventListener('input', (e) => {
      const swatch = document.getElementById('WebSwatch');
      if (swatch) swatch.style.backgroundColor = (e.target as HTMLInputElement).value;
    });
    (el as HTMLInputElement).addEventListener('change', changeWebColour);
  });

  document.querySelectorAll('input[name="newMonitor[MaxFPS]"]').forEach((el) => {
    const handler = (e: Event) => {
      const warning = document.getElementById('newMonitor[MaxFPS]');
      if (warning) {
        warning.classList.toggle('hidden', !(e.target as HTMLInputElement).value);
      }
    };
    el.addEventListener('input', handler);
    el.addEventListener('click', handler);
  });

  document.querySelectorAll('input[name="newMonitor[AlarmMaxFPS]"]').forEach((el) => {
    const handler = (e: Event) => {
      const warning = document.getElementById('newMonitor[AlarmMaxFPS]');
      if (warning) {
        warning.classList.toggle('hidden', !(e.target as HTMLInputElement).value);
      }
    };
    el.addEventListener('input', handler);
    el.addEventListener('click', handler);
  });

  document.querySelectorAll('input[name="newMonitor[Width]"], input[name="newMonitor[Height]"]').forEach((el) => {
    el.addEventListener('input', () => updateMonitorDimensions(el as HTMLInputElement));
  });

  document.querySelectorAll('select[name="dimensions_select"]').forEach((el) => {
    el.addEventListener('change', () => updateMonitorDimensions(el as HTMLSelectElement));
  });

  document.querySelectorAll('select[name="newMonitor[Type]"]').forEach((el) => {
    el.addEventListener('change', () => {
      const tabInput = form.elements.namedItem('tab') as HTMLInputElement;
      if (tabInput) tabInput.value = 'general';
      form.submit();
    });
  });

  document.querySelectorAll('input[name="newMonitor[ImageBufferCount]"], input[name="newMonitor[Width]"], input[name="newMonitor[Height]"]').forEach((el) => {
    el.addEventListener('input', bufferSettingOnInput.bind(el as HTMLInputElement));
  });

  const maxImageBuffer = document.getElementById('newMonitor[MaxImageBufferCount]');
  const preEventCount = document.getElementById('newMonitor[PreEventCount]');
  if (maxImageBuffer) maxImageBuffer.addEventListener('input', bufferSettingOnInput.bind(maxImageBuffer as HTMLInputElement));
  if (preEventCount) preEventCount.addEventListener('input', bufferSettingOnInput.bind(preEventCount as HTMLInputElement));

  document.querySelectorAll('select[name="newMonitor[Function]"]').forEach((el) => {
    el.addEventListener('change', updateFunctionVisibility);
  });
  updateFunctionVisibility();

  document.querySelectorAll('select[name="newMonitor[VideoWriter]"]').forEach((el) => {
    el.addEventListener('change', updateVideoWriterVisibility);
  });
  updateVideoWriterVisibility();

  document.querySelectorAll('select[name="newMonitor[OutputCodec]"]').forEach((el) => {
    el.addEventListener('change', updateOutputCodecVisibility);
  });
  updateOutputCodecVisibility();

  const probeBtn = document.getElementById('probeBtn');
  const probeBtnMobile = document.getElementById('probeBtnMobile');
  [probeBtn, probeBtnMobile].forEach((btn) => {
    btn?.addEventListener('click', (e) => {
      e.preventDefault();
      const mid = (e.currentTarget as HTMLElement).getAttribute('data-mid') || '';
      window.location.assign('?view=monitorprobe&mid=' + mid);
    });
  });

  const onvifBtn = document.getElementById('onvifBtn');
  const onvifBtnMobile = document.getElementById('onvifBtnMobile');
  [onvifBtn, onvifBtnMobile].forEach((btn) => {
    btn?.addEventListener('click', (e) => {
      e.preventDefault();
      const mid = (e.currentTarget as HTMLElement).getAttribute('data-mid') || '';
      window.location.assign('?view=onvifprobe&mid=' + mid);
    });
  });

  const presetBtn = document.getElementById('presetBtn');
  const presetBtnMobile = document.getElementById('presetBtnMobile');
  [presetBtn, presetBtnMobile].forEach((btn) => {
    btn?.addEventListener('click', (e) => {
      e.preventDefault();
      const mid = (e.currentTarget as HTMLElement).getAttribute('data-mid') || '';
      window.location.assign('?view=monitorpreset&mid=' + mid);
    });
  });

  const cancelBtn = document.getElementById('cancelBtn');
  cancelBtn?.addEventListener('click', (e) => {
    e.preventDefault();
    window.location.assign('?view=console');
  });

  const saveBtn = document.getElementById('saveBtn');
  saveBtn?.addEventListener('click', (e) => {
    e.preventDefault();
    validateForm();
  });

  updateEstimatedRamUse();

  (window as any).validateForm = validateForm;
  (window as any).random_WebColour = randomWebColour;
  (window as any).getLocation = getLocation;
}
