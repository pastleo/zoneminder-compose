<?php
//
// ZoneMinder Modern Skin - Shared Monitor Cards Component
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
 * Renders a single monitor card
 * 
 * @param ZM\Monitor $Monitor The monitor object
 * @param array $monitor The monitor array data
 * @param array $eventCounts Event count configuration
 * @param bool $running Whether ZM daemon is running
 * @param bool $canEditMonitors Whether user can edit monitors
 */
function renderMonitorCard($Monitor, $monitor, $eventCounts, $running, $canEditMonitors) {
  if ( (!$monitor['Status'] || $monitor['Status'] == 'NotRunning') && $monitor['Type']!='WebSite' ) {
    $status_class = 'badge-error';
  } else {
    if ( $monitor['CaptureFPS'] == '0.00' ) {
      $status_class = 'badge-error';
    } else if ( (!$monitor['AnalysisFPS']) && ($monitor['Function']!='Monitor') && ($monitor['Function'] != 'Nodect') ) {
      $status_class = 'badge-warning';
    } else {
      $status_class = 'badge-success';
    }
  }
  
  $stream_available = canView('Stream') and $monitor['Type']=='WebSite' or ($monitor['CaptureFPS'] && $monitor['Function'] != 'None');
  
  $fps_string = '';
  if ( isset($monitor['CaptureFPS']) ) $fps_string .= $monitor['CaptureFPS'];
  if ( isset($monitor['AnalysisFPS']) and ( $monitor['Function'] == 'Mocord' or $monitor['Function'] == 'Modect' ) ) {
    $fps_string .= '/' . $monitor['AnalysisFPS'];
  }
  if ($fps_string) $fps_string .= ' fps';

  $thumbHtml = '';
  if (ZM_WEB_LIST_THUMBS && $monitor['Function'] != 'None' && ($monitor['Status'] == 'Connected') && $running && canView('Stream')) {
    $thumb_options = array(
      'scale' => 0,
      'mode' => 'single',
    );
    $stillSrc = $Monitor->getStreamSrc($thumb_options);
    $streamSrc = $Monitor->getStreamSrc(array('scale' => 0));
    $thumbHtml = '<a href="?view=watch&amp;mid='.$monitor['Id'].'" class="block rounded overflow-hidden bg-base-300 mt-2">'
      . '<img id="thumbnail'.$Monitor->Id().'" src="'.$stillSrc.'" stream_src="'.$streamSrc.'" still_src="'.$stillSrc.'" '
      . 'class="w-full object-cover" loading="lazy" alt="'.validHtmlStr($monitor['Name']).'" />'
      . '</a>';
  }
?>
        <div id="monitor_id-<?php echo $monitor['Id'] ?>" class="card bg-base-200 shadow-md hover:shadow-lg transition-shadow monitor-card" data-mid="<?php echo $monitor['Id'] ?>" <?php if ($canEditMonitors) echo 'draggable="true"'; ?>>
          <div class="card-body p-4">
            <div class="flex items-center justify-between">
<?php if ( $canEditMonitors ) : ?>
              <label class="cursor-pointer flex items-center gap-1">
                <span class="drag-handle cursor-grab active:cursor-grabbing opacity-40 hover:opacity-100 mr-1" title="Drag to reorder">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-8a2 2 0 1 0-.001-4.001A2 2 0 0 0 13 6zm0 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z"/></svg>
                </span>
                <input type="checkbox" name="markMids[]" value="<?php echo $monitor['Id'] ?>" class="checkbox checkbox-sm checkbox-primary monitor-checkbox" />
              </label>
<?php endif; ?>
              <h2 class="card-title text-base flex-1 ml-2">
                <div class="badge <?php echo $status_class ?> badge-xs"></div>
<?php if ($stream_available) : ?>
                <a href="?view=watch&amp;mid=<?php echo $monitor['Id'] ?>" class="link link-hover"><?php echo validHtmlStr($monitor['Name']) ?></a>
<?php else : ?>
                <?php echo validHtmlStr($monitor['Name']) ?>
<?php endif; ?>
              </h2>
<?php if ( ZM_WEB_ID_ON_CONSOLE ) : ?>
              <span class="text-xs opacity-60">#<?php echo $monitor['Id'] ?></span>
<?php endif; ?>
            </div>
<?php echo $thumbHtml; ?>

            <div class="flex flex-wrap gap-1 mt-1">
<?php if ($canEditMonitors) : ?>
              <button type="button" class="badge badge-outline badge-sm cursor-pointer hover:badge-primary function-btn"
                data-mid="<?php echo $monitor['Id'] ?>"
                data-name="<?php echo htmlspecialchars($monitor['Name']) ?>"
                data-function="<?php echo $monitor['Function'] ?>"
                data-enabled="<?php echo $monitor['Enabled'] ?>"
                data-decoding="<?php echo $monitor['DecodingEnabled'] ?>"
              ><?php echo translate('Fn'.$monitor['Function']) ?></button>
<?php else : ?>
              <div class="badge badge-outline badge-sm"><?php echo translate('Fn'.$monitor['Function']) ?></div>
<?php endif; ?>
              <div class="badge badge-outline badge-sm"><?php echo translate('Status'.$monitor['Status']) ?></div>
            </div>

<?php if ($fps_string || $monitor['CaptureBandwidth']) : ?>
            <div class="text-xs opacity-60 mt-1">
              <?php echo $fps_string ?> <?php echo human_filesize($monitor['CaptureBandwidth']) ?>/s
            </div>
<?php endif; ?>

<?php if ($canEditMonitors) : ?>
            <div class="text-xs mt-1">
              <a href="?view=monitor&amp;mid=<?php echo $monitor['Id'] ?>" class="link link-hover text-info"><?php echo validHtmlStr($Monitor->Source()) ?></a>
            </div>
<?php endif; ?>

            <div class="divider my-2"></div>

            <div class="flex flex-wrap gap-1 text-xs">
<?php 
$eventPeriods = array(
  'Hour' => 'H',
  'Day' => 'D', 
  'Week' => 'W',
  'Month' => 'M',
  'Total' => 'All'
);
foreach ( $eventPeriods as $i => $abbrev ) : ?>
<?php if (canView('Events')) : ?>
              <a href="?view=<?php echo ZM_WEB_EVENTS_VIEW ?>&amp;page=1<?php echo $monitor['eventCounts'][$i]['filter']['querystring'] ?>" class="badge badge-sm badge-outline hover:badge-primary gap-0.5" title="<?php echo $eventCounts[$i]['title'] ?>">
                <span class="opacity-60"><?php echo $abbrev ?>:</span><?php echo $monitor[$i.'Events'] ?>
              </a>
<?php else : ?>
              <span class="badge badge-sm badge-ghost gap-0.5" title="<?php echo $eventCounts[$i]['title'] ?>">
                <span class="opacity-60"><?php echo $abbrev ?>:</span><?php echo $monitor[$i.'Events'] ?>
              </span>
<?php endif; ?>
<?php endforeach; ?>
            </div>

            <div class="card-actions justify-end mt-2">
<?php if (canView('Monitors')) : ?>
              <a href="?view=zones&amp;mid=<?php echo $monitor['Id'] ?>" class="btn btn-ghost btn-xs"><?php echo $monitor['ZoneCount'] ?> <?php echo translate('Zones') ?></a>
<?php endif; ?>
<?php if ($stream_available) : ?>
              <a href="?view=watch&amp;mid=<?php echo $monitor['Id'] ?>" class="btn btn-primary btn-xs"><?php echo translate('Watch') ?></a>
<?php endif; ?>
            </div>
          </div>
        </div>
<?php
}

/**
 * Renders the monitor cards grid
 * 
 * @param array $monitors Array of ZM\Monitor objects
 * @param array $displayMonitors Array of monitor data arrays
 * @param array $eventCounts Event count configuration
 * @param bool $running Whether ZM daemon is running
 * @param array $options Configuration options:
 *   - 'id' (string): Grid container ID (default: 'monitorsGrid')
 *   - 'showBatchToolbar' (bool): Show batch action toolbar (default: true if canEdit)
 *   - 'gridClass' (string): Additional classes for grid container
 */
function getMonitorCardsHTML($monitors, $displayMonitors, $eventCounts, $running, $options = []) {
  $id = $options['id'] ?? 'monitorsGrid';
  $gridClass = $options['gridClass'] ?? '';
  $canEditMonitors = canEdit('Monitors');
  $showBatchToolbar = $options['showBatchToolbar'] ?? $canEditMonitors;

  ob_start();
?>
<?php if ( $canEditMonitors ) : ?>
      <form id="monitorActionsForm" method="post" action="?view=console">
        <input type="hidden" name="action" value="" />

<?php if ($showBatchToolbar) : ?>
        <!-- Batch action toolbar (hidden by default) -->
        <div id="batchToolbar" class="hidden sticky top-0 z-10 mb-4 p-3 bg-base-300 rounded-lg shadow-lg flex flex-wrap items-center gap-2">
          <span class="text-sm"><span id="selectedCount">0</span> selected</span>
          <div class="divider divider-horizontal mx-1"></div>
          <button type="button" id="editBtn" class="btn btn-sm btn-ghost"><?php echo translate('Edit') ?></button>
          <button type="button" id="cloneBtn" class="btn btn-sm btn-ghost hidden"><?php echo translate('Clone') ?></button>
          <button type="button" id="deleteBtn" class="btn btn-sm btn-error btn-ghost"><?php echo translate('Delete') ?></button>
          <button type="button" id="cancelSelectBtn" class="btn btn-sm btn-ghost ml-auto"><?php echo translate('Cancel') ?></button>
        </div>
<?php endif; ?>

        <div id="<?php echo htmlspecialchars($id) ?>" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 <?php echo htmlspecialchars($gridClass) ?>">
<?php else : ?>
      <div id="<?php echo htmlspecialchars($id) ?>" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 <?php echo htmlspecialchars($gridClass) ?>">
<?php endif; ?>
<?php 
for( $monitor_i = 0; $monitor_i < count($displayMonitors); $monitor_i += 1 ) :
  $monitor = $displayMonitors[$monitor_i];
  $Monitor = $monitors[$monitor_i];
  renderMonitorCard($Monitor, $monitor, $eventCounts, $running, $canEditMonitors);
endfor;
?>
<?php if ( $canEditMonitors ) : ?>
        </div>
      </form>
<?php else : ?>
      </div>
<?php endif; ?>

<?php if (count($displayMonitors) == 0) : ?>
      <div class="alert alert-info">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span><?php echo translate('NoMonitors') ?></span>
      </div>
<?php endif; ?>
<?php
  return ob_get_clean();
}
?>
