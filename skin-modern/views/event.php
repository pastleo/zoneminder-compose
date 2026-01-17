<?php
if ( !canView('Events') ) {
  $view = 'error';
  return;
}

require_once('includes/Event.php');
require_once('includes/Filter.php');
foreach ( getSkinIncludes('includes/video-controls.php') as $includeFile )
  require_once $includeFile;

$eid = validInt($_REQUEST['eid']);
$fid = !empty($_REQUEST['fid']) ? validInt($_REQUEST['fid']) : 1;

$Event = new ZM\Event($eid);
if ( $user['MonitorIds'] ) {
  $monitor_ids = explode(',', $user['MonitorIds']);
  if ( count($monitor_ids) and ! in_array($Event->MonitorId(), $monitor_ids) ) {
    $view = 'error';
    return;
  }
}
$Monitor = $Event->Monitor();

if (isset($_REQUEST['rate'])) {
  $rate = validInt($_REQUEST['rate']);
} else if (isset($_COOKIE['zmEventRate'])) {
  $rate = validInt($_COOKIE['zmEventRate']);
} else {
  $rate = reScale(RATE_BASE, $Monitor->DefaultRate(), ZM_WEB_DEFAULT_RATE);
}

if ( isset($_REQUEST['scale']) ) {
  $scale = validInt($_REQUEST['scale']);
} else if ( isset($_COOKIE['zmEventScale'.$Event->MonitorId()]) ) {
  $scale = $_COOKIE['zmEventScale'.$Event->MonitorId()];
} else {
  $scale = $Monitor->DefaultScale();
}

$codec = 'auto';
if ( isset($_REQUEST['codec']) ) {
  $codec = $_REQUEST['codec'];
  zm_session_start();
  $_SESSION['zmEventCodec'.$Event->MonitorId()] = $codec;
  session_write_close();
} else if ( isset($_SESSION['zmEventCodec'.$Event->MonitorId()]) ) {
  $codec = $_SESSION['zmEventCodec'.$Event->MonitorId()];
} else {
  $codec = $Monitor->DefaultCodec();
}
$hasVideo = $Event->DefaultVideo() ? true : false;
if ($hasVideo) {
  $codecs = array(
    'auto'  => translate('Auto'),
    'MP4'   => translate('MP4'),
    'MJPEG' => translate('MJPEG'),
  );
} else {
  $codecs = array(
    'MJPEG' => translate('MJPEG'),
  );
  $codec = 'MJPEG';
}

$replayModes = array(
  'none'    => translate('None'),
  'single'  => translate('ReplaySingle'),
  'all'     => translate('ReplayAll'),
  'gapless' => translate('ReplayGapless'),
);

$rates = array(
  50    => '0.5x',
  100   => '1x',
  200   => '2x',
  400   => '4x',
  800   => '8x',
  1600  => '16x',
);

if ( isset($_REQUEST['streamMode']) )
  $streamMode = validHtmlStr($_REQUEST['streamMode']);
else
  $streamMode = 'video';

$replayMode = '';
if ( isset($_REQUEST['replayMode']) )
  $replayMode = validHtmlStr($_REQUEST['replayMode']);
if ( isset($_COOKIE['replayMode']) && preg_match('#^[a-z]+$#', $_COOKIE['replayMode']) )
  $replayMode = validHtmlStr($_COOKIE['replayMode']);

if ( ( !$replayMode ) or ( !$replayModes[$replayMode] ) ) {
  $replayMode = 'none';
}

$useVideoTag = false;
if ( $Event->DefaultVideo() and ( $codec == 'MP4' or $codec == 'auto' ) ) {
  $useVideoTag = true;
}

$Zoom = 1;
$Rotation = 0;
if ( $Monitor->VideoWriter() == '2' ) {
  $Rotation = $Event->Orientation();
  if ( in_array($Event->Orientation(), array('90','270')) )
    $Zoom = $Event->Height()/$Event->Width();
}

if ( !isset($_REQUEST['filter']) ) {
  $_REQUEST['filter'] = array(
    'Query'=>array(
      'terms'=>array(
        array('attr'=>'MonitorId', 'op'=>'=', 'val'=>$Event->MonitorId())
      )
    )
  );
}
parseSort();
$filter = ZM\Filter::parse($_REQUEST['filter']);
$filterQuery = $filter->querystring();

$connkey = generateConnKey();

$canEdit = canEdit('Events');

noCacheHeaders();
xhtmlHeaders(__FILE__, translate('Event').' '.$Event->Id());
?>
<body
  data-view="event"
  data-event-id="<?php echo $Event->Id() ?>"
  data-monitor-id="<?php echo $Monitor->Id() ?>"
  data-connkey="<?php echo $connkey ?>"
  data-stream-mode="<?php echo $useVideoTag ? 'video' : 'mjpeg' ?>"
  data-rate="<?php echo $rate ?>"
  data-scale="<?php echo $scale ?>"
  data-filter-query="<?php echo htmlspecialchars(htmlspecialchars_decode($filterQuery)) ?>"
  data-can-edit="<?php echo $canEdit ? '1' : '0' ?>"
  data-event-archived="<?php echo $Event->Archived() ? '1' : '0' ?>"
  data-event-width="<?php echo $Event->Width() ?>"
  data-event-height="<?php echo $Event->Height() ?>"
  data-event-length="<?php echo $Event->Length() ?>"
  data-event-frames="<?php echo $Event->Frames() ?>"
>
<?php echo getNavBarHTML() ?>

<main class="p-3 md:p-4 lg:p-6 min-h-screen bg-base-100">
<?php if ( !$Event->Id() ) { ?>
  <div class="alert alert-error mb-4">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    <span><?php echo translate('Event') ?> was not found.</span>
  </div>
<?php } else if ( !file_exists($Event->Path()) ) { ?>
  <div class="alert alert-warning mb-4">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
    </svg>
    <span><?php echo translate('Event') ?> files not found at <?php echo $Event->Path() ?>. Playback may fail.</span>
  </div>
<?php } ?>

<?php if ( $Event->Id() ) { ?>
  <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 auto-rows-min">

    <!-- Video Player Cell (spans 3 cols on lg) -->
    <div class="card bg-base-200 shadow-xl lg:col-span-3 lg:row-span-2 order-1">
      <div class="card-body p-3 md:p-4">
        
        <!-- Video Container - aspect ratio based on event dimensions -->
        <div id="eventVideo" class="rounded-lg overflow-hidden bg-black relative group" style="aspect-ratio: <?php echo $Event->Width() ?> / <?php echo $Event->Height() ?>;">
<?php if ( $useVideoTag ) { ?>
          <video
            id="videoPlayer"
            class="w-full h-full object-contain"
            autoplay
            preload="auto"
          >
            <source src="<?php echo $Event->getStreamSrc(array('mode'=>'mpeg','format'=>'h264'),'&amp;'); ?>" type="video/mp4">
            <?php echo translate('YourBrowserDoesntSupportVideo') ?>
          </video>
<?php } else { ?>
          <div id="imageFeed" class="relative w-full h-full flex items-center justify-center [&>img]:w-full [&>img]:!h-full [&>img]:object-contain">
<?php
  $streamSrc = $Event->getStreamSrc(array('mode'=>'jpeg', 'frame'=>$fid, 'scale'=>$scale, 'rate'=>$rate, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS, 'replay'=>$replayMode, 'connkey'=>$connkey),'&amp;');
  if ( canStreamNative() ) {
    outputImageStream('evtStream', $streamSrc, reScale($Event->Width(), $scale).'px', reScale($Event->Height(), $scale).'px', validHtmlStr($Event->Name()));
  } else {
    outputHelperStream('evtStream', $streamSrc, reScale($Event->Width(), $scale).'px', reScale($Event->Height(), $scale).'px' );
  }
?>
          </div>
<?php } ?>

          <!-- Alarm Cue Track (red marks for alarm frames) -->
          <div id="alarmCue" class="absolute bottom-0 left-0 right-0 h-1 flex"></div>

          <!-- Video Overlay Controls (shows on hover) -->
          <div id="videoOverlay" class="absolute inset-0 flex flex-col justify-end opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none z-20">
            <div class="absolute bottom-0 left-0 right-0 h-24 bg-gradient-to-t from-black/80 to-transparent pointer-events-none"></div>
            
            <!-- Progress Bar -->
            <div class="relative h-1 bg-white/20 mx-3 mb-1 rounded-full overflow-hidden pointer-events-auto cursor-pointer" id="progressBar">
              <div id="progressFill" class="absolute inset-y-0 left-0 bg-primary rounded-full transition-all" style="width: 0%"></div>
            </div>
            
            <!-- Controls Bar -->
            <div class="relative flex items-center gap-1 px-3 pb-3 pt-2 pointer-events-auto">
              <!-- Left: Playback controls + Time display -->
              <div class="flex items-center gap-1">
                <button type="button" id="prevBtn" title="<?php echo translate('Prev') ?>" class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20" disabled>
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/></svg>
                </button>

                <button type="button" id="stepRevBtn" title="<?php echo translate('StepBack') ?>" class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
                </button>
                <button type="button" id="playPauseBtn" title="<?php echo translate('Play') ?>/<?php echo translate('Pause') ?>" class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20">
                  <svg id="playIcon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 hidden" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                  <svg id="pauseIcon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                </button>
                <button type="button" id="stepFwdBtn" title="<?php echo translate('StepForward') ?>" class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
                </button>

                <button type="button" id="nextBtn" title="<?php echo translate('Next') ?>" class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20" disabled>
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg>
                </button>

                <!-- Time display -->
                <div class="flex items-center text-white text-sm font-mono ml-2">
                  <span id="currentTime">0:00</span>
                  <span class="opacity-50 mx-1">/</span>
                  <span id="totalTime">0:00</span>
                </div>
              </div>

              <!-- Spacer -->
              <div class="flex-1"></div>

              <!-- Right: Utility controls -->
              <div class="flex items-center gap-1">
                <?php echo getFullscreenButtonHTML(['prefix' => '']); ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Playback Settings (below video) -->
        <div class="flex flex-wrap items-center gap-4 mt-3 pt-3 border-t border-base-300">
          <!-- Codec -->
          <div class="form-control">
            <label class="label py-0">
              <span class="label-text text-xs opacity-60"><?php echo translate('Codec') ?></span>
            </label>
            <?php echo htmlSelect('codec', $codecs, $codec, array('id'=>'codec', 'class'=>'select select-bordered select-sm')); ?>
          </div>
          
          <!-- Replay Mode -->
          <div class="form-control">
            <label class="label py-0">
              <span class="label-text text-xs opacity-60"><?php echo translate('Replay') ?></span>
            </label>
            <?php echo htmlSelect('replayMode', $replayModes, $replayMode, array('id'=>'replayMode', 'class'=>'select select-bordered select-sm')); ?>
          </div>
          
<?php if ( $useVideoTag ) { ?>
          <!-- Rate (video mode only) -->
          <div class="form-control">
            <label class="label py-0">
              <span class="label-text text-xs opacity-60"><?php echo translate('Rate') ?></span>
            </label>
            <?php echo htmlSelect('rate', $rates, $rate, array('id'=>'rate', 'class'=>'select select-bordered select-sm')); ?>
          </div>
<?php } ?>
        </div>
        
      </div>
    </div>

    <!-- Event Info Card -->
    <div class="card bg-base-200 shadow-lg order-2">
      <div class="card-body p-4">
        <div class="flex items-center justify-between mb-3">
          <h2 class="card-title text-lg">
            <span id="eventName"><?php echo validHtmlStr($Event->Name()) ?></span>
          </h2>
          <span class="badge badge-sm opacity-60">#<?php echo $Event->Id() ?></span>
        </div>

        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="opacity-70"><?php echo translate('Monitor') ?></span>
            <a href="?view=watch&mid=<?php echo $Monitor->Id() ?>" class="link link-hover link-primary"><?php echo validHtmlStr($Monitor->Name()) ?></a>
          </div>
          <div class="flex justify-between">
            <span class="opacity-70"><?php echo translate('Cause') ?></span>
            <span><?php echo validHtmlStr($Event->Cause()) ?></span>
          </div>
<?php if ( $Event->Notes() ) { ?>
          <div class="flex justify-between">
            <span class="opacity-70"><?php echo translate('Notes') ?></span>
            <span class="text-right max-w-[60%] break-words"><?php echo validHtmlStr($Event->Notes()) ?></span>
          </div>
<?php } ?>
          <div class="flex justify-between">
            <span class="opacity-70"><?php echo translate('AttrStartTime') ?></span>
            <span><?php echo date('M j, g:i A', strtotime($Event->StartDateTime())) ?></span>
          </div>
<?php if ( $Event->EndDateTime() ) { ?>
          <div class="flex justify-between">
            <span class="opacity-70"><?php echo translate('AttrEndTime') ?></span>
            <span><?php echo date('M j, g:i A', strtotime($Event->EndDateTime())) ?></span>
          </div>
<?php } ?>
          <div class="flex justify-between">
            <span class="opacity-70"><?php echo translate('Duration') ?></span>
            <span><?php echo gmdate('H:i:s', intval($Event->Length())) ?></span>
          </div>
          <div class="flex justify-between">
            <span class="opacity-70"><?php echo translate('Frames') ?></span>
            <a href="?view=frames&eid=<?php echo $Event->Id() ?>" class="link link-hover"><?php echo $Event->Frames() ?></a>
          </div>
          <div class="flex justify-between">
            <span class="opacity-70"><?php echo translate('AlarmFrames') ?></span>
            <span><?php echo $Event->AlarmFrames() ?></span>
          </div>
          <div class="flex justify-between">
            <span class="opacity-70"><?php echo translate('AttrTotalScore') ?></span>
            <span><?php echo $Event->TotScore() ?></span>
          </div>
          <div class="flex justify-between">
            <span class="opacity-70"><?php echo translate('AttrAvgScore') ?></span>
            <span><?php echo $Event->AvgScore() ?></span>
          </div>
          <div class="flex justify-between">
            <span class="opacity-70"><?php echo translate('AttrMaxScore') ?></span>
            <a href="?view=frame&eid=<?php echo $Event->Id() ?>&fid=0" class="link link-hover"><?php echo $Event->MaxScore() ?></a>
          </div>
<?php if ( $Event->DiskSpace(null) ) { ?>
          <div class="flex justify-between">
            <span class="opacity-70"><?php echo translate('DiskSpace') ?></span>
            <span><?php echo human_filesize($Event->DiskSpace(null)) ?></span>
          </div>
<?php } ?>
          <div class="flex justify-between">
            <span class="opacity-70"><?php echo translate('Storage') ?></span>
            <span><?php echo validHtmlStr($Event->Storage()->Name()) . ($Event->SecondaryStorageId() ? ', ' . validHtmlStr($Event->SecondaryStorage()->Name()) : '') ?></span>
          </div>
        </div>

        <!-- Status badges -->
        <div class="flex flex-wrap gap-2 mt-3">
<?php if ( $Event->Archived() ) { ?>
          <span class="badge badge-warning gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"/><path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
            <?php echo translate('Archived') ?>
          </span>
<?php } ?>
<?php if ( $Event->Emailed() ) { ?>
          <span class="badge badge-info gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
            <?php echo translate('Emailed') ?>
          </span>
<?php } ?>
        </div>
      </div>
    </div>

    <!-- Actions Card -->
    <div class="card bg-base-200 shadow-lg order-3">
      <div class="card-body p-4">
        <h3 class="font-medium text-sm opacity-60 mb-3"><?php echo translate('Actions') ?></h3>
        
<?php if ( $canEdit ) { ?>
        <div class="flex flex-wrap gap-2">
          <button id="editBtn" class="btn btn-sm btn-ghost gap-1" title="<?php echo translate('Edit') ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
            <?php echo translate('Edit') ?>
          </button>
          
          <button id="renameBtn" class="btn btn-sm btn-ghost gap-1" title="<?php echo translate('Rename') ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"/><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"/></svg>
            <?php echo translate('Rename') ?>
          </button>
          
<?php if ( !$Event->Archived() ) { ?>
          <button id="archiveBtn" class="btn btn-sm btn-ghost gap-1" title="<?php echo translate('Archive') ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"/><path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
            <?php echo translate('Archive') ?>
          </button>
<?php } else { ?>
          <button id="unarchiveBtn" class="btn btn-sm btn-ghost gap-1" title="<?php echo translate('Unarchive') ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"/><path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
            <?php echo translate('Unarchive') ?>
          </button>
<?php } ?>
        </div>

        <div class="divider my-2"></div>
<?php } ?>
        
        <div class="flex flex-wrap gap-2">
          <a href="?view=export&eids[]=<?php echo $Event->Id() ?>" class="btn btn-sm btn-ghost gap-1" title="<?php echo translate('Export') ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            <?php echo translate('Export') ?>
          </a>
          
<?php if ( $Event->DefaultVideo() ) { ?>
          <a href="<?php echo $Event->getStreamSrc(array('mode'=>'mp4'),'&amp;')?>" download class="btn btn-sm btn-ghost gap-1" title="<?php echo translate('Download') ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            <?php echo translate('Download') ?>
          </a>
<?php } ?>

          <button id="videoBtn" class="btn btn-sm btn-ghost gap-1" title="<?php echo translate('GenerateVideo') ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm3 2h6v4H7V5zm8 8v2h1v-2h-1zm-2-2H7v4h6v-4zm2 0h1V9h-1v2zm1-4V5h-1v2h1zM5 5v2H4V5h1zm0 4H4v2h1V9zm-1 4h1v2H4v-2z" clip-rule="evenodd"/></svg>
            <?php echo translate('GenerateVideo') ?>
          </button>

          <a href="?view=frames&eid=<?php echo $Event->Id() ?>" class="btn btn-sm btn-ghost gap-1" title="<?php echo translate('Frames') ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>
            <?php echo translate('Frames') ?>
          </a>

          <a href="?view=montagereview&live=0&current=<?php echo urlencode($Event->StartDateTime()) ?>" class="btn btn-sm btn-ghost gap-1" title="<?php echo translate('MontageReview') ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            <?php echo translate('MontageReview') ?>
          </a>
        </div>

<?php if ( $canEdit && !$Event->Archived() ) { ?>
        <div class="divider my-2"></div>
        
        <button id="deleteBtn" class="btn btn-sm btn-error gap-1 w-full" title="<?php echo translate('Delete') ?>">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
          <?php echo translate('Delete') ?>
        </button>
<?php } ?>
      </div>
    </div>

  </div>
<?php } ?>
</main>

<?php xhtmlFooter() ?>
