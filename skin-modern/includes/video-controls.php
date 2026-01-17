<?php
//
// ZoneMinder Modern Skin - Shared Video Controls Component
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

/**
 * Renders fullscreen toggle button.
 * 
 * @param array $options Configuration:
 *   - 'prefix' (string): ID prefix for elements (default: '')
 */
function getFullscreenButtonHTML($options = []) {
  $prefix = $options['prefix'] ?? '';
  
  ob_start();
?>
<button type="button" id="<?php echo $prefix ?>FullscreenBtn" title="<?php echo translate('Fullscreen') ?>" class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20">
  <svg id="<?php echo $prefix ?>EnterFsIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></svg>
  <svg id="<?php echo $prefix ?>ExitFsIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" viewBox="0 0 24 24" fill="currentColor"><path d="M5 16h3v3h2v-5H5v2zm3-8H5v2h5V5H8v3zm6 11h2v-3h3v-2h-5v5zm2-11V5h-2v5h5V8h-3z"/></svg>
</button>
<?php
  return ob_get_clean();
}

/**
 * Renders play/pause button.
 * 
 * @param array $options Configuration:
 *   - 'prefix' (string): ID prefix for elements (default: '')
 *   - 'initialPaused' (bool): Initial state is paused (default: false)
 */
function getPlayPauseButtonHTML($options = []) {
  $prefix = $options['prefix'] ?? '';
  $initialPaused = $options['initialPaused'] ?? false;
  
  ob_start();
?>
<button type="button" id="<?php echo $prefix ?>playPauseBtn" title="<?php echo translate('Play') ?>/<?php echo translate('Pause') ?>" class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20">
  <svg id="<?php echo $prefix ?>playIcon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6<?php echo $initialPaused ? '' : ' hidden' ?>" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
  <svg id="<?php echo $prefix ?>pauseIcon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6<?php echo $initialPaused ? ' hidden' : '' ?>" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
</button>
<?php
  return ob_get_clean();
}

/**
 * Renders step forward/backward buttons.
 * 
 * @param array $options Configuration:
 *   - 'prefix' (string): ID prefix for elements (default: '')
 */
function getStepButtonsHTML($options = []) {
  $prefix = $options['prefix'] ?? '';
  
  ob_start();
?>
<button type="button" id="<?php echo $prefix ?>stepRevBtn" title="<?php echo translate('StepBack') ?>" class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
</button>
<button type="button" id="<?php echo $prefix ?>stepFwdBtn" title="<?php echo translate('StepForward') ?>" class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
</button>
<?php
  return ob_get_clean();
}

/**
 * Renders prev/next event navigation buttons.
 * 
 * @param array $options Configuration:
 *   - 'prefix' (string): ID prefix for elements (default: '')
 */
function getPrevNextButtonsHTML($options = []) {
  $prefix = $options['prefix'] ?? '';
  
  ob_start();
?>
<button type="button" id="<?php echo $prefix ?>prevBtn" title="<?php echo translate('Prev') ?>" class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20" disabled>
  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/></svg>
</button>
<?php
  return ob_get_clean();
}

function getNextButtonHTML($options = []) {
  $prefix = $options['prefix'] ?? '';
  
  ob_start();
?>
<button type="button" id="<?php echo $prefix ?>nextBtn" title="<?php echo translate('Next') ?>" class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20" disabled>
  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg>
</button>
<?php
  return ob_get_clean();
}
