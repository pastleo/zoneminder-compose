<?php
//
// ZoneMinder web function library
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

function getDataTheme() {
  global $css;
  if ($css === 'light') return 'light';
  if ($css === 'dark') return 'dark';
  return null;
}

function xhtmlHeaders($file, $title) {
  ob_start();

  global $skin;
  global $view;
  global $basename;
  global $css;

  $basename = basename($file, '.php');
  $dataTheme = getDataTheme();

  function output_cache_busted_stylesheet_links($files) {
    $html = array();
    foreach ( $files as $file ) {
        $html[] = '<link rel="stylesheet" href="'.cache_bust($file).'" type="text/css"/>';
    }
    if ( ! count($html) ) {
      ZM\Warning("No files found for $files");
    }
    $html[] = '';
    return implode(PHP_EOL, $html);
  }
?>
<!DOCTYPE html>
<html lang="en"<?php if ($dataTheme) echo ' data-theme="' . $dataTheme . '"'; ?>>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo validHtmlStr(ZM_WEB_TITLE_PREFIX) . ' - ' . validHtmlStr($title) ?></title>
<?php
if ( file_exists("skins/$skin/css/graphics/favicon.ico") ) {
  echo "
  <link rel=\"icon\" type=\"image/ico\" href=\"skins/$skin/css/graphics/favicon.ico\"/>
  <link rel=\"shortcut icon\" href=\"skins/$skin/css/graphics/favicon.ico\"/>
";
} else {
  echo '
  <link rel="icon" type="image/ico" href="graphics/favicon.ico"/>
  <link rel="shortcut icon" href="graphics/favicon.ico"/>
';
}

echo output_cache_busted_stylesheet_links(array(
  'skins/'.$skin.'/dist/main.css',
));
?>
</head>
<?php
  echo ob_get_clean();
} // end function xhtmlHeaders( $file, $title )

// Outputs an opening body tag, and any additional content that should go at the very top, like warnings and error messages.
function getBodyTopHTML() {
  global $view;
  echo '
<body data-view="'.htmlspecialchars($view).'">
<noscript>
<div class="alert alert-error">
'. validHtmlStr(ZM_WEB_TITLE) .' requires Javascript. Please enable Javascript in your browser for this site.
</div>
</noscript>
';
  global $error_message;
  if ( $error_message ) {
   echo '<div class="alert alert-error">'.$error_message.'</div>';
  }
} // end function getBodyTopHTML

function getNavBarHTML() {
  if ( isset($_REQUEST['navbar']) and $_REQUEST['navbar'] == '0' )
    return '';

  global $running;
  global $user;
  global $view;

  ob_start();
  echo getModernNavBarHTML($running, $user, $view);
  return ob_get_clean();
}

function getModernNavBarHTML($running, $user, $view) {
  $status = runtimeStatus($running);
  $menuItems = array();
  
  if ( canView('Monitors') ) $menuItems[] = array('view' => 'console', 'label' => translate('Console'));
  if ( canView('System') ) $menuItems[] = array('view' => 'options', 'label' => translate('Options'));
  if ( canView('System') && ZM\logToDatabase() > ZM\Logger::NOLOG ) $menuItems[] = array('view' => 'log', 'label' => translate('Log'));
  if ( canView('Groups') ) $menuItems[] = array('view' => 'groups', 'label' => translate('Groups'));
  if ( canView('Events') ) $menuItems[] = array('view' => 'filter', 'label' => translate('Filters'));
  if ( canView('Stream') ) $menuItems[] = array('view' => 'cycle', 'label' => translate('Cycle'));
  if ( canView('Stream') ) $menuItems[] = array('view' => 'montage', 'label' => translate('Montage'));
  if ( canView('Events') ) $menuItems[] = array('view' => 'montagereview', 'label' => translate('MontageReview'));
  if ( defined('ZM_FEATURES_SNAPSHOTS') && ZM_FEATURES_SNAPSHOTS && canView('Snapshots') ) $menuItems[] = array('view' => 'snapshots', 'label' => translate('Snapshots'));
?>
<div class="drawer">
  <input id="nav-drawer" type="checkbox" class="drawer-toggle" />
  <div class="drawer-content flex flex-col">
    <div class="navbar bg-base-200 w-full">
      <div class="flex-none lg:hidden">
        <label for="nav-drawer" aria-label="open sidebar" class="btn btn-square btn-ghost">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block h-6 w-6 stroke-current">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
        </label>
      </div>
      <div class="mx-2 flex-1 px-2">
        <a href="?view=console" class="btn btn-ghost text-xl">ZoneMinder</a>
      </div>
      <div class="hidden flex-none lg:block">
        <ul class="menu menu-horizontal px-1">
<?php foreach ( $menuItems as $item ) : ?>
          <li><a href="?view=<?php echo $item['view'] ?>" class="<?php echo ($view == $item['view']) ? 'active' : '' ?>"><?php echo $item['label'] ?></a></li>
<?php endforeach; ?>
        </ul>
      </div>
      <div class="flex-none gap-2">
<?php if ( canEdit('System') ) : ?>
        <div class="badge badge-<?php echo $running ? 'success' : 'error' ?> gap-1">
          <?php echo $status ?>
        </div>
<?php endif; ?>
<?php if ( ZM_OPT_USE_AUTH && $user ) : ?>
        <div class="dropdown dropdown-end">
          <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar placeholder">
            <div class="bg-neutral text-neutral-content w-10 rounded-full">
              <span><?php echo strtoupper(substr($user['Username'], 0, 2)) ?></span>
            </div>
          </div>
          <ul tabindex="0" class="menu menu-sm dropdown-content bg-base-100 rounded-box z-[1] mt-3 w-52 p-2 shadow">
            <li class="menu-title"><?php echo validHtmlStr($user['Username']) ?></li>
            <li><a href="?view=logout"><?php echo translate('Logout') ?></a></li>
          </ul>
        </div>
<?php endif; ?>
      </div>
    </div>
<?php
}

function getModernNavBarFooterHTML() {
  global $user;
  global $view;
  
  $menuItems = array();
  if ( canView('Monitors') ) $menuItems[] = array('view' => 'console', 'label' => translate('Console'));
  if ( canView('System') ) $menuItems[] = array('view' => 'options', 'label' => translate('Options'));
  if ( canView('System') && ZM\logToDatabase() > ZM\Logger::NOLOG ) $menuItems[] = array('view' => 'log', 'label' => translate('Log'));
  if ( canView('Groups') ) $menuItems[] = array('view' => 'groups', 'label' => translate('Groups'));
  if ( canView('Events') ) $menuItems[] = array('view' => 'filter', 'label' => translate('Filters'));
  if ( canView('Stream') ) $menuItems[] = array('view' => 'cycle', 'label' => translate('Cycle'));
  if ( canView('Stream') ) $menuItems[] = array('view' => 'montage', 'label' => translate('Montage'));
  if ( canView('Events') ) $menuItems[] = array('view' => 'montagereview', 'label' => translate('MontageReview'));
  if ( defined('ZM_FEATURES_SNAPSHOTS') && ZM_FEATURES_SNAPSHOTS && canView('Snapshots') ) $menuItems[] = array('view' => 'snapshots', 'label' => translate('Snapshots'));
?>
  </div>
  <div class="drawer-side">
    <label for="nav-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
    <ul class="menu bg-base-200 min-h-full w-80 p-4">
      <li class="menu-title">ZoneMinder</li>
<?php foreach ( $menuItems as $item ) : ?>
      <li><a href="?view=<?php echo $item['view'] ?>" class="<?php echo ($view == $item['view']) ? 'active' : '' ?>"><?php echo $item['label'] ?></a></li>
<?php endforeach; ?>
<?php if ( ZM_OPT_USE_AUTH && $user ) : ?>
      <li class="mt-auto"><a href="?view=logout"><?php echo translate('Logout') ?></a></li>
<?php endif; ?>
    </ul>
  </div>
</div>
<?php
}

function runtimeStatus($running=null) {
  if ( $running == null )
    $running = daemonCheck();
  if ( $running ) {
    $state = dbFetchOne('SELECT Name FROM States WHERE isActive=1', 'Name');
    if ( $state == 'default' )
      $state = '';
  }

  return $running ? ($state ? $state : translate('Running')) : translate('Stopped');
}

function getStatsTableHTML($eid, $fid, $row='') {
  if ( !canView('Events') ) return;
  $result = '';
  
  $sql = 'SELECT S.*,E.*,Z.Name AS ZoneName,Z.Units,Z.Area,M.Name AS MonitorName FROM Stats AS S LEFT JOIN Events AS E ON S.EventId = E.Id LEFT JOIN Zones AS Z ON S.ZoneId = Z.Id LEFT JOIN Monitors AS M ON E.MonitorId = M.Id WHERE S.EventId = ? AND S.FrameId = ? ORDER BY S.ZoneId';
  $stats = dbFetchAll( $sql, NULL, array( $eid, $fid ) );
  
  $result .= '<table class="table table-zebra table-sm">'.PHP_EOL;
    $result .= '<caption class="text-base-content/70">' .translate('Stats'). ' - ' .$eid. ' - ' .$fid. '</caption>'.PHP_EOL;
    $result .= '<thead>'.PHP_EOL;
      $result .= '<tr>'.PHP_EOL;
        $result .= '<th>' .translate('Zone'). '</th>'.PHP_EOL;
        $result .= '<th>' .translate('PixelDiff'). '</th>'.PHP_EOL;
        $result .= '<th>' .translate('AlarmPx'). '</th>'.PHP_EOL;
        $result .= '<th>' .translate('FilterPx'). '</th>'.PHP_EOL;
        $result .= '<th>' .translate('BlobPx'). '</th>'.PHP_EOL;
        $result .= '<th>' .translate('Blobs'). '</th>'.PHP_EOL;
        $result .= '<th>' .translate('BlobSizes'). '</th>'.PHP_EOL;
        $result .= '<th>' .translate('AlarmLimits'). '</th>'.PHP_EOL;
        $result .= '<th>' .translate('Score'). '</th>'.PHP_EOL;
      $result .= '</tr>'.PHP_EOL;
    $result .= '</thead>'.PHP_EOL;

    $result .= '<tbody>'.PHP_EOL;
    
    if ( count($stats) ) {
      foreach ( $stats as $stat ) {
        $result .= '<tr>'.PHP_EOL;
          $result .= '<td>' .validHtmlStr($stat['ZoneName']). '</td>'.PHP_EOL;
          $result .= '<td>' .validHtmlStr($stat['PixelDiff']). '</td>'.PHP_EOL;
          $result .= '<td>' .sprintf( "%d (%d%%)", $stat['AlarmPixels'], (100*$stat['AlarmPixels']/$stat['Area']) ). '</td>'.PHP_EOL;
          $result .= '<td>' .sprintf( "%d (%d%%)", $stat['FilterPixels'], (100*$stat['FilterPixels']/$stat['Area']) ).'</td>'.PHP_EOL;
          $result .= '<td>' .sprintf( "%d (%d%%)", $stat['BlobPixels'], (100*$stat['BlobPixels']/$stat['Area']) ). '</td>'.PHP_EOL;
          $result .= '<td>' .validHtmlStr($stat['Blobs']). '</td>'.PHP_EOL;
          
          if ( $stat['Blobs'] > 1 ) {
            $result .= '<td>' .sprintf( "%d-%d (%d%%-%d%%)", $stat['MinBlobSize'], $stat['MaxBlobSize'], (100*$stat['MinBlobSize']/$stat['Area']), (100*$stat['MaxBlobSize']/$stat['Area']) ). '</td>'.PHP_EOL;
          } else {
            $result .= '<td>' .sprintf( "%d (%d%%)", $stat['MinBlobSize'], 100*$stat['MinBlobSize']/$stat['Area'] ). '</td>'.PHP_EOL;
          }
          
          $result .= '<td>' .validHtmlStr($stat['MinX'].",".$stat['MinY']."-".$stat['MaxX'].",".$stat['MaxY']). '</td>'.PHP_EOL;
          $result .= '<td>' .$stat['Score']. '</td>'.PHP_EOL;
      }
    } else {
      $result .= '<tr>'.PHP_EOL;
        $result .= '<td colspan="9" class="text-center text-base-content/50">' .translate('NoStatisticsRecorded'). '</td>'.PHP_EOL;
      $result .= '</tr>'.PHP_EOL;
    }

    $result .= '</tbody>'.PHP_EOL;
  $result .= '</table>'.PHP_EOL;
  
  return $result;
}

// Use this function to manually insert the csrf key into the form when using a modal generated via ajax call
function getCSRFinputHTML() {
  if ( isset($GLOBALS['csrf']['key']) ) {
    $result = '<input type="hidden" name="__csrf_magic" value="key:' .csrf_hash($GLOBALS['csrf']['key']). '" />'.PHP_EOL;
  } else {
    $result = '';
  }
  
  return $result;
}

function xhtmlFooter() {
  global $skin;

  getModernNavBarFooterHTML();
?>
  <script type="module" src="<?php echo cache_bust('skins/'.$skin.'/dist/main.js'); ?>"></script>
  </body>
</html>
<?php
}
?>
