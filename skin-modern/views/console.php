<?php
//
// ZoneMinder web console file, $Date$, $Revision$
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

if ( $running == null ) 
  $running = daemonCheck();

$eventCounts = array(
  'Total'=>  array(
    'title' => translate('Events'),
    'filter' => array(
      'Query' => array(
        'terms' => array()
      )
    ),
    'totalevents' => 0,
    'totaldiskspace' => 0,
  ),
  'Hour'=>array(
    'title' => translate('Hour'),
    'filter' => array(
      'Query' => array(
        'terms' => array(
          array( 'attr' => 'StartDateTime', 'op' => '>=', 'val' => '-1 hour' ),
        )
      )
    ),
    'totalevents' => 0,
    'totaldiskspace' => 0,
  ),
  'Day'=>array(
    'title' => translate('Day'),
    'filter' => array(
      'Query' => array(
        'terms' => array(
          array( 'attr' => 'StartDateTime', 'op' => '>=', 'val' => '-1 day' ),
        )
      )
    ),
    'totalevents' => 0,
    'totaldiskspace' => 0,
  ),
  'Week'=>array(
    'title' => translate('Week'),
    'filter' => array(
      'Query' => array(
        'terms' => array(
          array( 'attr' => 'StartDateTime', 'op' => '>=', 'val' => '-7 day' ),
        )
      )
    ),
    'totalevents' => 0,
    'totaldiskspace' => 0,
  ),
  'Month'=>array(
    'title' => translate('Month'),
    'filter' => array(
      'Query' => array(
        'terms' => array(
          array( 'attr' => 'StartDateTime', 'op' => '>=', 'val' => '-1 month' ),
        )
      )
    ),
    'totalevents' => 0,
    'totaldiskspace' => 0,
  ),
  'Archived'=>array(
    'title' => translate('Archived'),
    'filter' => array(
      'Query' => array(
        'terms' => array(
          array( 'attr' => 'Archived', 'op' => '=', 'val' => '1' ),
        )
      )
    ),
    'totalevents' => 0,
    'totaldiskspace' => 0,
  ),
);

require_once('includes/Group_Monitor.php');
foreach ( getSkinIncludes('includes/monitor-cards.php') as $includeFile )
  require_once $includeFile;

$navbar = getNavBarHTML();
ob_start();
include('_monitor_filters.php');
$filterbar = ob_get_contents();
ob_end_clean();

$show_storage_areas = (count($storage_areas) > 1) and (canEdit('System') ? 1 : 0);
$maxWidth = 0;
$maxHeight = 0;
$zoneCount = 0;
$total_capturing_bandwidth = 0;

$group_ids_by_monitor_id = array();
foreach ( ZM\Group_Monitor::find(array('MonitorId'=>$selected_monitor_ids)) as $GM ) {
  if ( !isset($group_ids_by_monitor_id[$GM->MonitorId()]) )
    $group_ids_by_monitor_id[$GM->MonitorId()] = array();
  $group_ids_by_monitor_id[$GM->MonitorId()][] = $GM->GroupId();
}

$status_counts = array();
for ( $i = 0; $i < count($displayMonitors); $i++ ) {
  $monitor = &$displayMonitors[$i];
  if ( !$monitor['Status'] ) {
    if ( $monitor['Type'] == 'WebSite' )
     $monitor['Status'] = 'Running';
    else
     $monitor['Status'] = 'NotRunning';
  }
  if ( !isset($status_counts[$monitor['Status']]) )
    $status_counts[$monitor['Status']] = 0;
  $status_counts[$monitor['Status']] += 1;

  if ( $monitor['Function'] != 'None' ) {
    $scaleWidth = reScale($monitor['Width'], $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE);
    $scaleHeight = reScale($monitor['Height'], $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE);
    if ( $maxWidth < $scaleWidth ) $maxWidth = $scaleWidth;
    if ( $maxHeight < $scaleHeight ) $maxHeight = $scaleHeight;
  } else {
     $monitor['Status'] = 'NotRunning';
  }
  $zoneCount += $monitor['ZoneCount'];
  $total_capturing_bandwidth += $monitor['CaptureBandwidth'];

  $counts = array();
  foreach ( array_keys($eventCounts) as $j ) {
    $filter = addFilterTerm(
      $eventCounts[$j]['filter'],
      count($eventCounts[$j]['filter']['Query']['terms']),
      array('cnj'=>'and', 'attr'=>'MonitorId', 'op'=>'=', 'val'=>$monitor['Id'])
    );
    parseFilter($filter);
    $monitor['eventCounts'][$j]['filter'] = $filter;
    $eventCounts[$j]['totalevents'] += $monitor[$j.'Events'];
    $eventCounts[$j]['totaldiskspace'] += $monitor[$j.'EventDiskSpace'];
  }
  unset($monitor);
}
$cycleWidth = $maxWidth;
$cycleHeight = $maxHeight;

noCacheHeaders();

$eventsWindow = 'zm'.ucfirst(ZM_WEB_EVENTS_VIEW);

$monitors = array();
for( $monitor_i = 0; $monitor_i < count($displayMonitors); $monitor_i += 1 ) {
  $monitor = $displayMonitors[$monitor_i];
  $Monitor = new ZM\Monitor($monitor);
  $monitors[] = $Monitor;
  $Monitor->GroupIds(isset($group_ids_by_monitor_id[$Monitor->Id()]) ? $group_ids_by_monitor_id[$Monitor->Id()] : array());
}

$Functions = ZM\GetMonitorFunctionTypes();
$status_options = array(
  'Unknown' => translate('StatusUnknown'),
  'NotRunning' => translate('StatusNotRunning'),
  'Running' => translate('StatusRunning'),
  'Connected' => translate('StatusConnected'),
);

xhtmlHeaders(__FILE__, translate('Console'));
getBodyTopHTML();
echo $navbar;
?>

    <main class="p-4 min-h-screen bg-base-100" data-refresh="<?php echo ZM_WEB_REFRESH_MAIN ?>">
      <div class="flex justify-center mb-6">
        <div class="hidden md:flex stats stats-horizontal shadow-lg bg-base-200 border border-base-300">
<?php foreach ( array_keys($eventCounts) as $i ) :
  $filter = addFilterTerm(
    $eventCounts[$i]['filter'],
    count($eventCounts[$i]['filter']['Query']['terms']),
    array(
      'cnj'=>'and',
      'attr'=>'MonitorId',
      'op'=>'IN',
      'val'=>implode(',',array_map(function($m){return $m['Id'];}, $displayMonitors))
    )
  );
  parseFilter($filter);
?>
          <div class="stat py-3 px-4">
            <div class="stat-title text-xs"><?php echo $eventCounts[$i]['title'] ?></div>
<?php if ( canView('Events') ) : ?>
            <a href="?view=<?php echo ZM_WEB_EVENTS_VIEW ?>&amp;page=1<?php echo $filter['querystring'] ?>" class="stat-value text-xl text-primary link link-hover"><?php echo $eventCounts[$i]['totalevents'] ?></a>
<?php else : ?>
            <div class="stat-value text-xl text-primary"><?php echo $eventCounts[$i]['totalevents'] ?></div>
<?php endif; ?>
            <div class="stat-desc text-xs"><?php echo human_filesize($eventCounts[$i]['totaldiskspace']) ?></div>
          </div>
<?php endforeach; ?>
          <div class="stat py-3 px-4">
            <div class="stat-title text-xs"><?php echo translate('Zones') ?></div>
            <div class="stat-value text-xl text-secondary"><?php echo $zoneCount ?></div>
            <div class="stat-desc text-xs">&nbsp;</div>
          </div>
          <div class="stat py-3 px-4">
            <div class="stat-title text-xs"><?php echo translate('Bandwidth') ?></div>
            <div class="stat-value text-xl text-accent"><?php echo human_filesize($total_capturing_bandwidth) ?>/s</div>
            <div class="stat-desc text-xs"><?php echo count($displayMonitors) ?> <?php echo translate('Monitors') ?></div>
          </div>
          <div class="stat py-3 px-4">
            <div class="stat-title text-xs"><?php echo translate('Capturing') ?></div>
            <div class="stat-value text-xl text-success"><?php echo isset($status_counts['Connected']) ? round(100*($status_counts['Connected']/count($displayMonitors)),1) : 0 ?>%</div>
            <div class="stat-desc text-xs"><?php echo isset($status_counts['Connected']) ? $status_counts['Connected'] : 0 ?> <?php echo translate('Monitors') ?></div>
          </div>
        </div>
        <div class="md:hidden grid grid-cols-3 gap-2 w-full bg-base-200 rounded-xl p-3 border border-base-300">
<?php foreach ( array_keys($eventCounts) as $i ) :
  $filter = addFilterTerm(
    $eventCounts[$i]['filter'],
    count($eventCounts[$i]['filter']['Query']['terms']),
    array(
      'cnj'=>'and',
      'attr'=>'MonitorId',
      'op'=>'IN',
      'val'=>implode(',',array_map(function($m){return $m['Id'];}, $displayMonitors))
    )
  );
  parseFilter($filter);
?>
          <div class="text-center">
            <div class="text-xs opacity-60"><?php echo $eventCounts[$i]['title'] ?></div>
<?php if ( canView('Events') ) : ?>
            <a href="?view=<?php echo ZM_WEB_EVENTS_VIEW ?>&amp;page=1<?php echo $filter['querystring'] ?>" class="text-lg font-bold text-primary link link-hover"><?php echo $eventCounts[$i]['totalevents'] ?></a>
<?php else : ?>
            <div class="text-lg font-bold text-primary"><?php echo $eventCounts[$i]['totalevents'] ?></div>
<?php endif; ?>
          </div>
<?php endforeach; ?>
          <div class="text-center">
            <div class="text-xs opacity-60"><?php echo translate('Zones') ?></div>
            <div class="text-lg font-bold text-secondary"><?php echo $zoneCount ?></div>
          </div>
          <div class="text-center">
            <div class="text-xs opacity-60"><?php echo translate('Bandwidth') ?></div>
            <div class="text-lg font-bold text-accent"><?php echo human_filesize($total_capturing_bandwidth) ?>/s</div>
          </div>
          <div class="text-center">
            <div class="text-xs opacity-60"><?php echo translate('Capturing') ?></div>
            <div class="text-lg font-bold text-success"><?php echo isset($status_counts['Connected']) ? round(100*($status_counts['Connected']/count($displayMonitors)),1) : 0 ?>%</div>
          </div>
        </div>
      </div>

      <!-- Filters Section -->
      <form name="monitorForm" method="get" action="?" class="mb-6">
        <input type="hidden" name="view" value="console"/>
        <input type="hidden" name="skin" value="modern"/>
        <input type="hidden" name="filtering" value="1"/>
        
        <div class="hidden lg:flex bg-base-200/50 rounded-xl p-4 border border-base-300 flex-wrap items-end gap-3">
          <div class="form-control flex-1 min-w-[140px]">
            <label class="label py-1"><span class="label-text text-xs opacity-70"><?php echo translate('Name') ?></span></label>
            <input type="text" name="MonitorName" value="<?php echo isset($_SESSION['MonitorName']) ? validHtmlStr($_SESSION['MonitorName']) : '' ?>" placeholder="<?php echo translate('Name') ?>" class="input input-bordered input-sm w-full bg-base-100"/>
          </div>
          
          <div class="form-control flex-1 min-w-[140px]">
            <label class="label py-1"><span class="label-text text-xs opacity-70"><?php echo translate('Function') ?></span></label>
            <div class="dropdown w-full">
              <div tabindex="0" role="button" class="btn btn-sm w-full justify-between bg-base-100 border-base-300 font-normal">
                <span class="truncate"><?php echo isset($_SESSION['Function']) ? implode(', ', array_map(function($f) use ($Functions) { return $Functions[$f] ?? $f; }, (array)$_SESSION['Function'])) : translate('All') ?></span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
              </div>
              <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-10 w-full p-2 shadow-lg border border-base-300 mt-1">
                <li><label class="label cursor-pointer justify-start gap-2 py-1"><input type="checkbox" class="checkbox checkbox-sm checkbox-primary" data-select-all="Function[]" <?php echo !isset($_SESSION['Function']) ? 'checked' : '' ?>/><span class="label-text font-medium"><?php echo translate('All') ?></span></label></li>
                <li class="divider my-0 h-px"></li>
<?php foreach ($Functions as $fn_key => $fn_label) : ?>
                <li><label class="label cursor-pointer justify-start gap-2 py-1"><input type="checkbox" name="Function[]" value="<?php echo $fn_key ?>" <?php echo (isset($_SESSION['Function']) && in_array($fn_key, (array)$_SESSION['Function'])) ? 'checked' : '' ?> class="checkbox checkbox-sm checkbox-primary"/><span class="label-text"><?php echo $fn_label ?></span></label></li>
<?php endforeach; ?>
              </ul>
            </div>
          </div>
          
          <div class="form-control flex-1 min-w-[140px]">
            <label class="label py-1"><span class="label-text text-xs opacity-70"><?php echo translate('Status') ?></span></label>
            <div class="dropdown w-full">
              <div tabindex="0" role="button" class="btn btn-sm w-full justify-between bg-base-100 border-base-300 font-normal">
                <span class="truncate"><?php echo isset($_SESSION['Status']) ? implode(', ', array_map(function($s) use ($status_options) { return $status_options[$s] ?? $s; }, (array)$_SESSION['Status'])) : translate('All') ?></span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
              </div>
              <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-10 w-full p-2 shadow-lg border border-base-300 mt-1">
                <li><label class="label cursor-pointer justify-start gap-2 py-1"><input type="checkbox" class="checkbox checkbox-sm checkbox-primary" data-select-all="Status[]" <?php echo !isset($_SESSION['Status']) ? 'checked' : '' ?>/><span class="label-text font-medium"><?php echo translate('All') ?></span></label></li>
                <li class="divider my-0 h-px"></li>
<?php foreach ($status_options as $st_key => $st_label) : ?>
                <li><label class="label cursor-pointer justify-start gap-2 py-1"><input type="checkbox" name="Status[]" value="<?php echo $st_key ?>" <?php echo (isset($_SESSION['Status']) && in_array($st_key, (array)$_SESSION['Status'])) ? 'checked' : '' ?> class="checkbox checkbox-sm checkbox-primary"/><span class="label-text"><?php echo $st_label ?></span></label></li>
<?php endforeach; ?>
              </ul>
            </div>
          </div>
          
          <div class="form-control flex-1 min-w-[140px]">
            <label class="label py-1"><span class="label-text text-xs opacity-70"><?php echo translate('Source') ?></span></label>
            <input type="text" name="Source" value="<?php echo isset($_SESSION['Source']) ? validHtmlStr($_SESSION['Source']) : '' ?>" placeholder="<?php echo translate('Source') ?>" class="input input-bordered input-sm w-full bg-base-100"/>
          </div>
          
          <div class="form-control flex-1 min-w-[140px]">
            <label class="label py-1"><span class="label-text text-xs opacity-70"><?php echo translate('Monitor') ?></span></label>
            <div class="dropdown w-full">
              <div tabindex="0" role="button" class="btn btn-sm w-full justify-between bg-base-100 border-base-300 font-normal">
                <span class="truncate"><?php echo isset($_SESSION['MonitorId']) ? implode(', ', array_map(function($m) use ($monitors_dropdown) { return $monitors_dropdown[$m] ?? $m; }, (array)$_SESSION['MonitorId'])) : translate('All') ?></span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
              </div>
              <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-10 w-full p-2 shadow-lg border border-base-300 mt-1 max-h-60 overflow-y-auto">
                <li><label class="label cursor-pointer justify-start gap-2 py-1"><input type="checkbox" class="checkbox checkbox-sm checkbox-primary" data-select-all="MonitorId[]" <?php echo !isset($_SESSION['MonitorId']) ? 'checked' : '' ?>/><span class="label-text font-medium"><?php echo translate('All') ?></span></label></li>
                <li class="divider my-0 h-px"></li>
<?php foreach ($monitors_dropdown as $m_id => $m_label) : ?>
                <li><label class="label cursor-pointer justify-start gap-2 py-1"><input type="checkbox" name="MonitorId[]" value="<?php echo $m_id ?>" <?php echo (isset($_SESSION['MonitorId']) && in_array($m_id, (array)$_SESSION['MonitorId'])) ? 'checked' : '' ?> class="checkbox checkbox-sm checkbox-primary"/><span class="label-text"><?php echo validHtmlStr($m_label) ?></span></label></li>
<?php endforeach; ?>
              </ul>
            </div>
          </div>
          
          <div class="join">
            <button type="submit" class="btn btn-primary btn-sm join-item">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" /></svg>
              <?php echo translate('Filter') ?>
            </button>
            <a href="?view=console&skin=modern&filtering=1" class="btn btn-ghost btn-sm join-item"><?php echo translate('Clear') ?></a>
          </div>
        </div>
        
        <div class="lg:hidden collapse collapse-arrow bg-base-200 rounded-xl border border-base-300">
          <input type="checkbox" <?php echo (isset($_SESSION['MonitorName']) || isset($_SESSION['Function']) || isset($_SESSION['Status']) || isset($_SESSION['Source']) || isset($_SESSION['MonitorId'])) ? 'checked' : '' ?> />
          <div class="collapse-title font-medium flex items-center gap-2 text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" /></svg>
            <?php echo translate('Filters') ?>
          </div>
          <div class="collapse-content">
            <div class="grid grid-cols-2 gap-3 pt-2">
              <div class="form-control col-span-2">
                <label class="label py-1"><span class="label-text text-xs"><?php echo translate('Name') ?></span></label>
                <input type="text" name="MonitorName" value="<?php echo isset($_SESSION['MonitorName']) ? validHtmlStr($_SESSION['MonitorName']) : '' ?>" placeholder="<?php echo translate('Name') ?>" class="input input-bordered input-sm w-full"/>
              </div>
              
              <div class="form-control">
                <label class="label py-1"><span class="label-text text-xs"><?php echo translate('Function') ?></span></label>
                <div class="dropdown w-full">
                  <div tabindex="0" role="button" class="btn btn-sm w-full justify-between bg-base-100 border-base-300 font-normal">
                    <span class="truncate"><?php echo isset($_SESSION['Function']) ? implode(', ', array_map(function($f) use ($Functions) { return $Functions[$f] ?? $f; }, (array)$_SESSION['Function'])) : translate('All') ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                  </div>
                  <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-10 w-full p-2 shadow-lg border border-base-300 mt-1">
                    <li><label class="label cursor-pointer justify-start gap-2 py-1"><input type="checkbox" class="checkbox checkbox-sm checkbox-primary" data-select-all="Function[]" <?php echo !isset($_SESSION['Function']) ? 'checked' : '' ?>/><span class="label-text font-medium"><?php echo translate('All') ?></span></label></li>
                    <li class="divider my-0 h-px"></li>
<?php foreach ($Functions as $fn_key => $fn_label) : ?>
                    <li><label class="label cursor-pointer justify-start gap-2 py-1"><input type="checkbox" name="Function[]" value="<?php echo $fn_key ?>" <?php echo (isset($_SESSION['Function']) && in_array($fn_key, (array)$_SESSION['Function'])) ? 'checked' : '' ?> class="checkbox checkbox-sm checkbox-primary"/><span class="label-text"><?php echo $fn_label ?></span></label></li>
<?php endforeach; ?>
                  </ul>
                </div>
              </div>
              
              <div class="form-control">
                <label class="label py-1"><span class="label-text text-xs"><?php echo translate('Status') ?></span></label>
                <div class="dropdown w-full">
                  <div tabindex="0" role="button" class="btn btn-sm w-full justify-between bg-base-100 border-base-300 font-normal">
                    <span class="truncate"><?php echo isset($_SESSION['Status']) ? implode(', ', array_map(function($s) use ($status_options) { return $status_options[$s] ?? $s; }, (array)$_SESSION['Status'])) : translate('All') ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                  </div>
                  <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-10 w-full p-2 shadow-lg border border-base-300 mt-1">
                    <li><label class="label cursor-pointer justify-start gap-2 py-1"><input type="checkbox" class="checkbox checkbox-sm checkbox-primary" data-select-all="Status[]" <?php echo !isset($_SESSION['Status']) ? 'checked' : '' ?>/><span class="label-text font-medium"><?php echo translate('All') ?></span></label></li>
                    <li class="divider my-0 h-px"></li>
<?php foreach ($status_options as $st_key => $st_label) : ?>
                    <li><label class="label cursor-pointer justify-start gap-2 py-1"><input type="checkbox" name="Status[]" value="<?php echo $st_key ?>" <?php echo (isset($_SESSION['Status']) && in_array($st_key, (array)$_SESSION['Status'])) ? 'checked' : '' ?> class="checkbox checkbox-sm checkbox-primary"/><span class="label-text"><?php echo $st_label ?></span></label></li>
<?php endforeach; ?>
                  </ul>
                </div>
              </div>
              
              <div class="form-control col-span-2">
                <label class="label py-1"><span class="label-text text-xs"><?php echo translate('Source') ?></span></label>
                <input type="text" name="Source" value="<?php echo isset($_SESSION['Source']) ? validHtmlStr($_SESSION['Source']) : '' ?>" placeholder="<?php echo translate('Source') ?>" class="input input-bordered input-sm w-full"/>
              </div>
              
              <div class="form-control col-span-2">
                <label class="label py-1"><span class="label-text text-xs"><?php echo translate('Monitor') ?></span></label>
                <div class="dropdown w-full">
                  <div tabindex="0" role="button" class="btn btn-sm w-full justify-between bg-base-100 border-base-300 font-normal">
                    <span class="truncate"><?php echo isset($_SESSION['MonitorId']) ? implode(', ', array_map(function($m) use ($monitors_dropdown) { return $monitors_dropdown[$m] ?? $m; }, (array)$_SESSION['MonitorId'])) : translate('All') ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                  </div>
                  <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-10 w-full p-2 shadow-lg border border-base-300 mt-1 max-h-48 overflow-y-auto">
                    <li><label class="label cursor-pointer justify-start gap-2 py-1"><input type="checkbox" class="checkbox checkbox-sm checkbox-primary" data-select-all="MonitorId[]" <?php echo !isset($_SESSION['MonitorId']) ? 'checked' : '' ?>/><span class="label-text font-medium"><?php echo translate('All') ?></span></label></li>
                    <li class="divider my-0 h-px"></li>
<?php foreach ($monitors_dropdown as $m_id => $m_label) : ?>
                    <li><label class="label cursor-pointer justify-start gap-2 py-1"><input type="checkbox" name="MonitorId[]" value="<?php echo $m_id ?>" <?php echo (isset($_SESSION['MonitorId']) && in_array($m_id, (array)$_SESSION['MonitorId'])) ? 'checked' : '' ?> class="checkbox checkbox-sm checkbox-primary"/><span class="label-text"><?php echo validHtmlStr($m_label) ?></span></label></li>
<?php endforeach; ?>
                  </ul>
                </div>
              </div>
            </div>
            <div class="flex gap-2 mt-4">
              <button type="submit" class="btn btn-primary btn-sm flex-1"><?php echo translate('Filter') ?></button>
              <a href="?view=console&skin=modern&filtering=1" class="btn btn-ghost btn-sm"><?php echo translate('Clear') ?></a>
            </div>
          </div>
        </div>
      </form>

      <div class="flex flex-wrap gap-2 mb-4 items-center justify-between">
        <div class="flex gap-2 flex-wrap">
<?php foreach ( array_keys($status_counts) as $status ) : ?>
<?php if ( $status == 'Connected' ) continue; ?>
          <div class="badge badge-lg gap-1 <?php
            echo $status == 'Running' ? 'badge-info' :
                ($status == 'NotRunning' ? 'badge-error' : 'badge-warning');
          ?>">
            <?php echo translate('Status'.$status) ?>: <?php echo round(100*($status_counts[$status]/count($displayMonitors)),1) ?>%
          </div>
<?php endforeach; ?>
        </div>
        
        <div class="flex gap-2 flex-wrap">
<?php if ( canEdit('Monitors') && !$user['MonitorIds'] ) : ?>
          <a href="?view=monitor" class="btn btn-primary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            <?php echo translate('AddNewMonitor') ?>
          </a>
<?php endif; ?>
        </div>
      </div>

<?php echo getMonitorCardsHTML($monitors, $displayMonitors, $eventCounts, $running); ?>
    </main>

<?php xhtmlFooter(); ?>
