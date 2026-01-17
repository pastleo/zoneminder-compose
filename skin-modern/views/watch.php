<?php
//
// ZoneMinder web watch feed view file
// Copyright (C) 2001-2008 Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

if ( !canView('Stream') ) {
  $view = 'error';
  return;
}

if ( !isset($_REQUEST['mid']) ) {
  $view = 'error';
  return;
}

// This is for input sanitation
$mid = intval($_REQUEST['mid']); 
if ( !visibleMonitor($mid) ) {
  $view = 'error';
  return;
}

require_once('includes/Monitor.php');
foreach ( getSkinIncludes('includes/video-controls.php') as $includeFile )
  require_once $includeFile;
$monitor = new ZM\Monitor($mid);

#Whether to show the controls button
$showPtzControls = ( ZM_OPT_CONTROL && $monitor->Controllable() && canView('Control') && $monitor->Type() != 'WebSite' );

if ( isset($_REQUEST['scale']) ) {
  $scale = validInt($_REQUEST['scale']);
} else if ( isset($_COOKIE['zmWatchScale'.$mid]) ) {
  $scale = $_COOKIE['zmWatchScale'.$mid];
  if ($scale == 'auto') $scale = '0';
} else {
  $scale = $monitor->DefaultScale();
}

$connkey = generateConnKey();

$streamMode = getStreamMode();

$popup = ((isset($_REQUEST['popup'])) && ($_REQUEST['popup'] == 1));

noCacheHeaders();
xhtmlHeaders(__FILE__, $monitor->Name().' - '.translate('Feed'));
?>
<body 
  data-view="watch"
  data-monitor-id="<?php echo $monitor->Id() ?>"
  data-connkey="<?php echo $connkey ?>"
  data-monitor-width="<?php echo $monitor->ViewWidth() ?>"
  data-monitor-height="<?php echo $monitor->ViewHeight() ?>"
  data-monitor-type="<?php echo $monitor->Type() ?>"
  data-stream-mode="<?php echo $streamMode ?>"
  data-monitor-url="<?php echo htmlspecialchars($monitor->UrlToIndex()) ?>"
  data-stream-replay-buffer="<?php echo $monitor->StreamReplayBuffer() ?>"
  data-status-refresh="<?php echo ZM_WEB_REFRESH_STATUS ?>"
  data-can-edit-monitors="<?php echo canEdit('Monitors') ? '1' : '0' ?>"
>
<noscript>
<div class="alert alert-error">
<?php echo validHtmlStr(ZM_WEB_TITLE) ?> requires Javascript. Please enable Javascript in your browser for this site.
</div>
</noscript>
<?php
global $error_message;
if ( $error_message ) {
  echo '<div class="alert alert-error">'.$error_message.'</div>';
}
echo getNavBarHTML();
?>

    <main class="p-3 md:p-4 lg:p-6 min-h-screen bg-base-100">
      <!-- Bento Grid Layout -->
      <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4 auto-rows-min">
        
        <!-- HERO: Video Stream Cell - spans 2 cols on md, 3 cols on lg -->
        <div class="card bg-base-200 shadow-xl md:col-span-2 lg:col-span-3 md:row-span-2 order-1">
          <div class="card-body p-3 md:p-4">
            <!-- Stream Container with YouTube-style overlay controls -->
            <div class="monitor rounded-lg overflow-hidden bg-black/50 relative group" id="monitor<?php echo $monitor->Id() ?>">
              <div id="imageFeed<?php echo $monitor->Id() ?>"
                class="relative w-full flex items-center justify-center"
              >
<?php
if ( $monitor->Status() != 'Connected' and $monitor->Type() != 'WebSite' ) {
?>
                <div class="absolute inset-0 flex items-center justify-center bg-base-300/80 z-10">
                  <div class="alert alert-warning max-w-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span><?php echo translate('Monitor is not capturing') ?></span>
                  </div>
                </div>
<?php
}
echo getStreamHTML($monitor, array('scale'=>$scale, 'mode'=>'single'));
?>
              </div>

<?php if ( $monitor->Type() != 'WebSite' && $streamMode == 'jpeg' ) { ?>
              <!-- Video Player Overlay Controls -->
              <div id="videoOverlay" class="absolute inset-0 flex flex-col justify-end opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none z-20" data-auto-hide="true">
                <!-- Gradient backdrop for controls visibility -->
                <div class="absolute bottom-0 left-0 right-0 h-24 bg-gradient-to-t from-black/80 to-transparent pointer-events-none"></div>
                
<?php if ( $monitor->StreamReplayBuffer() != 0 ) { ?>
                <!-- Buffer Progress Bar -->
                <div class="relative h-1 bg-white/20 mx-3 mb-1 rounded-full overflow-hidden pointer-events-auto cursor-pointer" id="bufferBar">
                  <div id="bufferProgress" class="absolute inset-y-0 left-0 bg-primary/60 rounded-full transition-all" style="width: 0%"></div>
                  <div id="playbackProgress" class="absolute inset-y-0 left-0 bg-primary rounded-full transition-all" style="width: 0%"></div>
                </div>
<?php } ?>
                
                <!-- Controls Bar -->
                <div class="relative flex items-center gap-1 px-3 pb-3 pt-2 pointer-events-auto">
                  <!-- Left: Playback controls -->
                  <div class="flex items-center gap-1">
<?php if ( $monitor->StreamReplayBuffer() != 0 ) { ?>
                    <button type="button" id="overlayFastRevBtn" title="<?php echo translate('Rewind') ?>" class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20" disabled>
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M11 18V6l-8.5 6 8.5 6zm.5-6l8.5 6V6l-8.5 6z"/></svg>
                    </button>
                    <button type="button" id="overlaySlowRevBtn" title="<?php echo translate('StepBack') ?>" class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20" disabled>
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
                    </button>
<?php } ?>
                    <button type="button" id="overlayPlayPauseBtn" title="<?php echo translate('Play') ?>/<?php echo translate('Pause') ?>" class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20">
                      <svg id="overlayPlayIcon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 hidden" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                      <svg id="overlayPauseIcon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                    </button>
<?php if ( $monitor->StreamReplayBuffer() != 0 ) { ?>
                    <button type="button" id="overlaySlowFwdBtn" title="<?php echo translate('StepForward') ?>" class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20" disabled>
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
                    </button>
                    <button type="button" id="overlayFastFwdBtn" title="<?php echo translate('FastForward') ?>" class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20" disabled>
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M4 18l8.5-6L4 6v12zm9-12v12l8.5-6L13 6z"/></svg>
                    </button>
<?php } ?>
                  </div>
                  
                  <!-- Center: Status badges (compact) -->
                  <div class="flex-1 flex items-center justify-center gap-2 text-white/80 text-xs">
<?php if ( $monitor->StreamReplayBuffer() != 0 ) { ?>
                    <span id="overlayRate" class="hidden">
                      <span id="overlayRateValue">1</span>x
                    </span>
                    <span id="overlayDelay" class="hidden">
                      -<span id="overlayDelayValue">0</span>s
                    </span>
<?php } ?>
                  </div>
                  
                  <!-- Right: Utility controls -->
                  <div class="flex items-center gap-1">
                    <?php echo getFullscreenButtonHTML(['prefix' => 'overlay']); ?>
                  </div>
                </div>
              </div>
<?php } ?>
            </div>

<?php if ( $monitor->Type() != 'WebSite' ) { ?>
            <!-- Replay Status Bar (conditionally shown) -->
            <div id="replayStatus" class="mt-3 flex flex-wrap gap-3 text-sm opacity-70<?php echo $streamMode=="single" ? ' hidden' : '' ?>">
              <span id="mode<?php echo $monitor->Id() ?>" class="badge badge-outline gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" /></svg>
                <?php echo translate('Mode') ?>: <span id="modeValue<?php echo $monitor->Id() ?>">-</span>
              </span>
              <span id="rate<?php echo $monitor->Id() ?>" class="badge badge-outline">
                <?php echo translate('Rate') ?>: <span id="rateValue<?php echo $monitor->Id() ?>">1</span>x
              </span>
              <span id="delay<?php echo $monitor->Id() ?>" class="badge badge-outline">
                <?php echo translate('Delay') ?>: <span id="delayValue<?php echo $monitor->Id() ?>">0</span>s
              </span>
              <span id="level<?php echo $monitor->Id() ?>" class="badge badge-outline">
                <?php echo translate('Buffer') ?>: <span id="levelValue<?php echo $monitor->Id() ?>">0</span>%
              </span>
            </div>
<?php } ?>
          </div>
        </div>

        <!-- INFO Cell: Monitor Name, Function, Scale -->
        <div class="card bg-base-200 shadow-lg order-2">
          <div class="card-body p-4">
            <div class="flex items-center justify-between mb-2">
              <h2 class="card-title text-lg truncate">
<?php if ( canEdit('Monitors') ) { ?>
                <a href="?view=monitor&amp;mid=<?php echo $monitor->Id() ?>" class="link link-hover link-primary">
                  <?php echo validHtmlStr($monitor->Name()) ?>
                </a>
<?php } else { ?>
                <?php echo validHtmlStr($monitor->Name()) ?>
<?php } ?>
              </h2>
              <span class="badge badge-sm opacity-60">#<?php echo $monitor->Id() ?></span>
            </div>
            
            <div class="flex flex-wrap gap-2">
              <span class="badge badge-primary badge-outline"><?php echo translate('Fn'.$monitor->Function()) ?></span>
              <span class="badge badge-secondary badge-outline"><?php echo $monitor->Width() ?>x<?php echo $monitor->Height() ?></span>
            </div>
          </div>
        </div>

<?php if ( $monitor->Type() != 'WebSite' ) { ?>
        <!-- STATUS Cell: State + FPS Stats -->
        <div class="card bg-base-200 shadow-lg order-3">
          <div class="card-body p-4">
            <h3 class="font-medium text-sm opacity-60 mb-3 flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
              </svg>
              <?php echo translate('Status') ?>
            </h3>
            
            <div id="monitorStatus">
              <div id="monitorState" class="space-y-2">
                <!-- State -->
                <div class="flex items-center justify-between">
                  <span class="text-sm opacity-70"><?php echo translate('State') ?></span>
                  <span id="stateValue<?php echo $monitor->Id() ?>" class="badge badge-info">-</span>
                </div>
                
                <!-- Viewing FPS -->
                <div class="flex items-center justify-between" title="<?php echo translate('Viewing FPS') ?>">
                  <span class="text-sm opacity-70"><?php echo translate('Viewing') ?></span>
                  <span class="font-mono text-sm"><span id="viewingFPSValue<?php echo $monitor->Id() ?>">-</span> fps</span>
                </div>
                
                <!-- Capture FPS -->
                <div class="flex items-center justify-between" title="<?php echo translate('Capturing FPS') ?>">
                  <span class="text-sm opacity-70"><?php echo translate('Capture') ?></span>
                  <span class="font-mono text-sm"><span id="captureFPSValue<?php echo $monitor->Id() ?>">-</span> fps</span>
                </div>
                
<?php if ( $monitor->Function() == 'Modect' or $monitor->Function() == 'Mocord' ) { ?>
                <!-- Analysis FPS -->
                <div class="flex items-center justify-between" title="<?php echo translate('Analysis FPS') ?>">
                  <span class="text-sm opacity-70"><?php echo translate('Analysis') ?></span>
                  <span class="font-mono text-sm"><span id="analysisFPSValue<?php echo $monitor->Id() ?>">-</span> fps</span>
                </div>
<?php } ?>
              </div>
            </div>

<?php if ( canEdit('Monitors') && in_array($monitor->Function(), array('Modect', 'Mocord', 'Nodect')) ) { ?>
            <!-- Alarm Controls -->
            <div class="divider my-2"></div>
            <h3 class="font-medium text-sm opacity-60 mb-2 flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
              </svg>
              <?php echo translate('Alarm') ?>
            </h3>
            <div class="flex gap-2">
              <!-- Enable/Disable Alarm Button -->
              <button type="button" id="enableAlmBtn" class="btn btn-sm flex-1 gap-1" 
                title="<?php echo translate('DisableAlarms') ?>" disabled>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                </svg>
                <span id="enableAlmBtnText"><?php echo translate('DisableAlarms') ?></span>
              </button>
              <!-- Force Alarm Button -->
              <button type="button" id="forceAlmBtn" class="btn btn-sm btn-error flex-1 gap-1" 
                title="<?php echo translate('ForceAlarm') ?>" disabled>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
                <span id="forceAlmBtnText"><?php echo translate('ForceAlarm') ?></span>
              </button>
            </div>
<?php } ?>
          </div>
        </div>
<?php } ?>

<?php
if ( $showPtzControls ) {
    foreach ( getSkinIncludes('includes/control_functions.php') as $includeFile )
        require_once $includeFile;
?>
        <!-- PTZ CONTROLS Cell (only shown for PTZ-capable monitors) -->
        <div class="card bg-base-200 shadow-lg order-4">
          <div class="card-body p-4">
            <h3 class="font-medium text-sm opacity-60 mb-3 flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M15 3l2.3 2.3-2.89 2.87 1.42 1.42L18.7 6.7 21 9V3zM3 9l2.3-2.3 2.87 2.89 1.42-1.42L6.7 5.3 9 3H3zm6 12l-2.3-2.3 2.89-2.87-1.42-1.42L5.3 17.3 3 15v6zm12-6l-2.3 2.3-2.87-2.89-1.42 1.42 2.89 2.87L15 21h6z"/>
              </svg>
              PTZ <?php echo translate('Control') ?>
            </h3>
            <div id="ptzControls" class="ptzControls">
              <?php echo ptzControls($monitor) ?>
            </div>
          </div>
        </div>
<?php
}
?>

<?php
if ( canView('Events') && ($monitor->Type() != 'WebSite') ) {
  foreach ( getSkinIncludes('includes/event-cards.php') as $includeFile )
    require_once $includeFile;
?>
        <!-- EVENTS Cell - Full width at bottom -->
        <div class="card bg-base-200 shadow-lg order-6 col-span-full">
          <div class="card-body p-4">
            <?php echo getEventCardsHTML([
              'id' => 'events',
              'title' => translate('Recent Events'),
              'showToolbar' => canEdit('Events'),
              'showRefresh' => !canEdit('Events'),
              'showPagination' => true,
            ]); ?>
          </div>
        </div>
<?php } ?>

      </div><!-- End Bento Grid -->

<?php
if ( ZM_WEB_SOUND_ON_ALARM ) {
    $soundSrc = ZM_DIR_SOUNDS.'/'.ZM_WEB_ALARM_SOUND;
?>
      <div id="alarmSound" class="hidden">
<?php
    if ( ZM_WEB_USE_OBJECT_TAGS && isWindows() ) {
?>
        <object id="MediaPlayer" width="0" height="0"
          classid="CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95"
          codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,0,02,902">
          <param name="FileName" value="<?php echo $soundSrc ?>"/>
          <param name="autoStart" value="0"/>
          <param name="loop" value="1"/>
          <param name="hidden" value="1"/>
          <param name="showControls" value="0"/>
          <embed src="<?php echo $soundSrc ?>"
            autostart="true"
            loop="true"
            hidden="true">
          </embed>
        </object>
<?php
    } else {
?>
        <embed src="<?php echo $soundSrc ?>"
          autostart="true"
          loop="true"
          hidden="true">
        </embed>
<?php
    }
?>
      </div>
<?php
}
?>
    </main>

<?php xhtmlFooter() ?>
