<?php
//
// ZoneMinder web options view file
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

if (!canView('System')) {
  $view = 'error';
  return;
}

$canEdit = canEdit('System');

$tabs = array();
$tabs['skins'] = translate('Display');
$tabs['system'] = translate('System');
$tabs['config'] = translate('Config');
$tabs['API'] = translate('API');
$tabs['servers'] = translate('Servers');
$tabs['storage'] = translate('Storage');
$tabs['web'] = translate('Web');
$tabs['images'] = translate('Images');
$tabs['logging'] = translate('Logging');
$tabs['network'] = translate('Network');
$tabs['mail'] = translate('Email');
$tabs['upload'] = translate('Upload');
$tabs['x10'] = translate('X10');
$tabs['highband'] = translate('HighBW');
$tabs['medband'] = translate('MediumBW');
$tabs['lowband'] = translate('LowBW');
$tabs['users'] = translate('Users');
$tabs['control'] = translate('Control');
$tabs['privacy'] = translate('Privacy');

$supported_tabs = ['skins', 'system', 'users', 'servers', 'storage', 'API', 'config', 'web', 'images', 'logging', 'network', 'mail', 'upload', 'x10', 'highband', 'medband', 'lowband'];

if (isset($_REQUEST['tab']))
  $tab = validHtmlStr($_REQUEST['tab']);
else
  $tab = 'system';

if ($tab == 'control') {
  if (canView('Control')) {
    header('Location: ?view=controlcaps');
  } else {
    header('Location: ?view=error');
  }
  exit;
}
if ($tab == 'privacy') {
  if (canView('System')) {
    header('Location: ?view=privacy');
  } else {
    header('Location: ?view=error');
  }
  exit;
}

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Options'));
getBodyTopHTML();
echo getNavBarHTML();

global $restartWarning;
?>

<?php if (!empty($restartWarning)) : ?>
<script>
  window.addEventListener('DOMContentLoaded', function() {
    alert('<?php echo translate('OptionRestartWarning') ?>');
  });
</script>
<?php endif; ?>

<div class="drawer lg:drawer-open">
  <input id="options-drawer" type="checkbox" class="drawer-toggle" />
  
  <div class="drawer-content flex flex-col">
    <!-- Mobile header for drawer toggle -->
    <div class="lg:hidden flex items-center gap-2 p-4 bg-base-200 border-b border-base-300">
      <label for="options-drawer" class="btn btn-square btn-ghost">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-6 h-6 stroke-current">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
      </label>
      <h1 class="text-xl font-bold"><?php echo translate('Options') ?> - <?php echo $tabs[$tab] ?></h1>
    </div>
    
    <!-- Main content area -->
    <main class="flex-1 p-4 lg:p-6 bg-base-100 min-h-screen">
      <h1 class="hidden lg:block text-2xl font-bold mb-6"><?php echo translate('Options') ?> - <?php echo $tabs[$tab] ?></h1>
      
      <div id="options-content">
<?php
if ($tab == 'skins') {
  $skin_options = array_map('basename', glob('skins/*', GLOB_ONLYDIR));
  $css_options = array_map('basename', glob('skins/'.$skin.'/css/*', GLOB_ONLYDIR));
?>
        <div class="card bg-base-200 shadow-lg max-w-2xl">
          <div class="card-body">
            <h2 class="card-title"><?php echo translate('Display') ?></h2>
            
            <form name="optionsForm" method="get" action="?">
              <input type="hidden" name="view" value="<?php echo $view ?>"/>
              <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
              
              <div class="form-control w-full mb-4">
                <label class="label">
                  <span class="label-text font-medium"><?php echo translate('Skin') ?></span>
                </label>
                <select name="skin" class="select select-bordered w-full">
<?php foreach ($skin_options as $dir) : ?>
                  <option value="<?php echo $dir ?>" <?php echo ($skin == $dir) ? 'selected' : '' ?>><?php echo $dir ?></option>
<?php endforeach; ?>
                </select>
                <label class="label">
                  <span class="label-text-alt opacity-70"><?php echo translate('SkinDescription') ?></span>
                </label>
              </div>
              
              <div class="form-control w-full mb-6">
                <label class="label">
                  <span class="label-text font-medium">CSS</span>
                </label>
                <select name="css" class="select select-bordered w-full">
<?php foreach ($css_options as $dir) : ?>
                  <option value="<?php echo $dir ?>" <?php echo ($css == $dir) ? 'selected' : '' ?>><?php echo $dir ?></option>
<?php endforeach; ?>
                </select>
                <label class="label">
                  <span class="label-text-alt opacity-70"><?php echo translate('CSSDescription') ?></span>
                </label>
              </div>
              
              <div class="card-actions justify-end">
                <button type="submit" class="btn btn-primary"><?php echo translate('Save') ?></button>
              </div>
            </form>
          </div>
        </div>
<?php
}
else if ($tab == 'users') {
  $sql = 'SELECT * FROM Monitors ORDER BY Sequence ASC';
  $monitors = array();
  foreach (dbFetchAll($sql) as $monitor) {
    $monitors[$monitor['Id']] = $monitor;
  }
  
  $sql = 'SELECT * FROM Users ORDER BY Username';
  $users_list = dbFetchAll($sql);
?>
        <div class="card bg-base-200 shadow-lg">
          <div class="card-body">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
              <h2 class="card-title"><?php echo translate('Users') ?></h2>
<?php if ($canEdit) : ?>
              <a href="?view=user&amp;uid=0" class="btn btn-primary btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                <?php echo translate('AddNewUser') ?>
              </a>
<?php endif; ?>
            </div>
            
            <form name="userForm" method="post" action="?">
              <input type="hidden" name="view" value="<?php echo $view ?>"/>
              <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
              <input type="hidden" name="action" value="delete"/>
              
              <div class="overflow-x-auto">
                <table class="table table-zebra table-sm">
                  <thead>
                    <tr>
                      <th><?php echo translate('Username') ?></th>
                      <th class="hidden sm:table-cell"><?php echo translate('Language') ?></th>
                      <th><?php echo translate('Enabled') ?></th>
                      <th class="hidden md:table-cell"><?php echo translate('Stream') ?></th>
                      <th class="hidden md:table-cell"><?php echo translate('Events') ?></th>
                      <th class="hidden lg:table-cell"><?php echo translate('Control') ?></th>
                      <th class="hidden lg:table-cell"><?php echo translate('Monitors') ?></th>
                      <th class="hidden xl:table-cell"><?php echo translate('System') ?></th>
<?php if (ZM_OPT_USE_API) : ?>
                      <th class="hidden xl:table-cell"><?php echo translate('APIEnabled') ?></th>
<?php endif; ?>
<?php if ($canEdit) : ?>
                      <th><?php echo translate('Mark') ?></th>
<?php endif; ?>
                    </tr>
                  </thead>
                  <tbody>
<?php foreach ($users_list as $user_row) :
  $userMonitors = array();
  if (!empty($user_row['MonitorIds'])) {
    foreach (explode(',', $user_row['MonitorIds']) as $monitorId) {
      if (!isset($monitors[$monitorId])) continue;
      $userMonitors[] = $monitors[$monitorId]['Name'];
    }
  }
  $isCurrentUser = ($user['Username'] == $user_row['Username']);
?>
                    <tr class="hover">
                      <td>
<?php if ($canEdit) : ?>
                        <a href="?view=user&amp;uid=<?php echo $user_row['Id'] ?>" class="link link-primary">
                          <?php echo validHtmlStr($user_row['Username']) ?><?php echo $isCurrentUser ? '*' : '' ?>
                        </a>
<?php else : ?>
                        <?php echo validHtmlStr($user_row['Username']) ?><?php echo $isCurrentUser ? '*' : '' ?>
<?php endif; ?>
                      </td>
                      <td class="hidden sm:table-cell"><?php echo $user_row['Language'] ? validHtmlStr($user_row['Language']) : 'default' ?></td>
                      <td>
                        <span class="badge <?php echo $user_row['Enabled'] ? 'badge-success' : 'badge-error' ?> badge-sm">
                          <?php echo translate($user_row['Enabled'] ? 'Yes' : 'No') ?>
                        </span>
                      </td>
                      <td class="hidden md:table-cell"><?php echo validHtmlStr($user_row['Stream']) ?></td>
                      <td class="hidden md:table-cell"><?php echo validHtmlStr($user_row['Events']) ?></td>
                      <td class="hidden lg:table-cell"><?php echo validHtmlStr($user_row['Control']) ?></td>
                      <td class="hidden lg:table-cell"><?php echo validHtmlStr($user_row['Monitors']) ?></td>
                      <td class="hidden xl:table-cell"><?php echo validHtmlStr($user_row['System']) ?></td>
<?php if (ZM_OPT_USE_API) : ?>
                      <td class="hidden xl:table-cell">
                        <span class="badge <?php echo $user_row['APIEnabled'] ? 'badge-success' : 'badge-ghost' ?> badge-sm">
                          <?php echo translate($user_row['APIEnabled'] ? 'Yes' : 'No') ?>
                        </span>
                      </td>
<?php endif; ?>
<?php if ($canEdit) : ?>
                      <td>
                        <input type="checkbox" name="markUids[]" value="<?php echo $user_row['Id'] ?>" class="checkbox checkbox-sm checkbox-error" data-on-click-this="configureDeleteButton" />
                      </td>
<?php endif; ?>
                    </tr>
<?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              
<?php if ($canEdit) : ?>
              <div class="card-actions justify-end mt-4">
                <button type="submit" name="deleteBtn" class="btn btn-error btn-sm" disabled>
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                  <?php echo translate('Delete') ?>
                </button>
              </div>
<?php endif; ?>
            </form>
          </div>
        </div>
<?php
}
else if ($tab == 'servers') {
  $monitor_counts = dbFetchAssoc('SELECT Id,(SELECT COUNT(Id) FROM Monitors WHERE ServerId=Servers.Id) AS MonitorCount FROM Servers', 'Id', 'MonitorCount');
  $servers = ZM\Server::find();
?>
        <div class="card bg-base-200 shadow-lg">
          <div class="card-body">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
              <h2 class="card-title"><?php echo translate('Servers') ?></h2>
<?php if ($canEdit) : ?>
              <button type="button" id="NewServerBtn" class="btn btn-primary btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                <?php echo translate('AddNewServer') ?>
              </button>
<?php endif; ?>
            </div>
            
            <form name="serversForm" method="post" action="?">
              <input type="hidden" name="view" value="<?php echo $view ?>"/>
              <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="object" value="server"/>
              
              <div class="overflow-x-auto">
                <table class="table table-zebra table-sm">
                  <thead>
                    <tr>
                      <th><?php echo translate('Name') ?></th>
                      <th class="hidden md:table-cell"><?php echo translate('Url') ?></th>
                      <th class="hidden lg:table-cell"><?php echo translate('Status') ?></th>
                      <th class="hidden sm:table-cell"><?php echo translate('Monitors') ?></th>
                      <th class="hidden lg:table-cell"><?php echo translate('CpuLoad') ?></th>
                      <th class="hidden xl:table-cell"><?php echo translate('Memory') ?></th>
                      <th class="hidden xl:table-cell"><?php echo translate('RunStats') ?></th>
                      <th class="hidden xl:table-cell"><?php echo translate('RunAudit') ?></th>
<?php if ($canEdit) : ?>
                      <th><?php echo translate('Mark') ?></th>
<?php endif; ?>
                    </tr>
                  </thead>
                  <tbody>
<?php foreach ($servers as $Server) : 
  $statusClass = ($Server->Status() == 'NotRunning') ? 'badge-error' : 'badge-success';
  $cpuClass = ($Server->CpuLoad() > 5) ? 'text-error' : '';
  $memWarning = (!$Server->TotalMem()) || ($Server->FreeMem()/$Server->TotalMem() < .1);
?>
                    <tr class="hover">
                      <td>
<?php if ($canEdit) : ?>
                        <a href="#" class="link link-primary serverCol" data-sid="<?php echo $Server->Id() ?>">
                          <?php echo validHtmlStr($Server->Name()) ?>
                        </a>
<?php else : ?>
                        <?php echo validHtmlStr($Server->Name()) ?>
<?php endif; ?>
                      </td>
                      <td class="hidden md:table-cell"><?php echo validHtmlStr($Server->Url()) ?></td>
                      <td class="hidden lg:table-cell">
                        <span class="badge <?php echo $statusClass ?> badge-sm"><?php echo validHtmlStr($Server->Status()) ?></span>
                      </td>
                      <td class="hidden sm:table-cell"><?php echo isset($monitor_counts[$Server->Id()]) ? $monitor_counts[$Server->Id()] : 0 ?></td>
                      <td class="hidden lg:table-cell <?php echo $cpuClass ?>"><?php echo $Server->CpuLoad() ?></td>
                      <td class="hidden xl:table-cell <?php echo $memWarning ? 'text-error' : '' ?>">
                        <?php echo human_filesize($Server->FreeMem()) ?> / <?php echo human_filesize($Server->TotalMem()) ?>
                      </td>
                      <td class="hidden xl:table-cell">
                        <span class="badge <?php echo $Server->zmstats() ? 'badge-success' : 'badge-ghost' ?> badge-sm">
                          <?php echo $Server->zmstats() ? 'yes' : 'no' ?>
                        </span>
                      </td>
                      <td class="hidden xl:table-cell">
                        <span class="badge <?php echo $Server->zmaudit() ? 'badge-success' : 'badge-ghost' ?> badge-sm">
                          <?php echo $Server->zmaudit() ? 'yes' : 'no' ?>
                        </span>
                      </td>
<?php if ($canEdit) : ?>
                      <td>
                        <input type="checkbox" name="markIds[]" value="<?php echo $Server->Id() ?>" class="checkbox checkbox-sm checkbox-error" data-on-click-this="configureDeleteButton" />
                      </td>
<?php endif; ?>
                    </tr>
<?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              
<?php if ($canEdit) : ?>
              <div class="card-actions justify-end mt-4">
                <button type="submit" name="deleteBtn" class="btn btn-error btn-sm" disabled>
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                  <?php echo translate('Delete') ?>
                </button>
              </div>
<?php endif; ?>
            </form>
          </div>
        </div>
<?php
}
else if ($tab == 'storage') {
  $storages = ZM\Storage::find(null, array('order'=>'lower(Name)'));
?>
        <div class="card bg-base-200 shadow-lg">
          <div class="card-body">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
              <h2 class="card-title"><?php echo translate('Storage') ?></h2>
<?php if ($canEdit) : ?>
              <button type="button" id="NewStorageBtn" class="btn btn-primary btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                <?php echo translate('AddNewStorage') ?>
              </button>
<?php endif; ?>
            </div>
            
            <form name="storageForm" method="post" action="?">
              <input type="hidden" name="view" value="<?php echo $view ?>"/>
              <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="object" value="storage"/>
              
              <div class="overflow-x-auto">
                <table class="table table-zebra table-sm">
                  <thead>
                    <tr>
                      <th><?php echo translate('Id') ?></th>
                      <th><?php echo translate('Name') ?></th>
                      <th class="hidden sm:table-cell"><?php echo translate('Path') ?></th>
                      <th class="hidden md:table-cell"><?php echo translate('Type') ?></th>
                      <th class="hidden lg:table-cell"><?php echo translate('StorageScheme') ?></th>
                      <th class="hidden lg:table-cell"><?php echo translate('Server') ?></th>
                      <th class="hidden md:table-cell"><?php echo translate('DiskSpace') ?></th>
                      <th class="hidden sm:table-cell"><?php echo translate('Events') ?></th>
<?php if ($canEdit) : ?>
                      <th><?php echo translate('Mark') ?></th>
<?php endif; ?>
                    </tr>
                  </thead>
                  <tbody>
<?php foreach ($storages as $Storage) : 
  $filter = new ZM\Filter();
  $filter->addTerm(array('attr'=>'StorageId','op'=>'=','val'=>$Storage->Id()));
  if ($user['MonitorIds']) {
    $filter = $filter->addTerm(array('cnj'=>'and', 'attr'=>'MonitorId', 'op'=>'IN', 'val'=>$user['MonitorIds']));
  }
  $diskTotal = $Storage->disk_total_space();
  $diskUsed = $Storage->disk_used_space();
  $diskPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100) : 0;
  $diskClass = $diskPercent > 90 ? 'text-error' : ($diskPercent > 75 ? 'text-warning' : '');
?>
                    <tr class="hover">
                      <td><?php echo $Storage->Id() ?></td>
                      <td>
<?php if ($canEdit) : ?>
                        <a href="#" class="link link-primary storageCol" data-sid="<?php echo $Storage->Id() ?>">
                          <?php echo validHtmlStr($Storage->Name()) ?>
                        </a>
<?php else : ?>
                        <?php echo validHtmlStr($Storage->Name()) ?>
<?php endif; ?>
                      </td>
                      <td class="hidden sm:table-cell font-mono text-xs"><?php echo validHtmlStr($Storage->Path()) ?></td>
                      <td class="hidden md:table-cell"><?php echo validHtmlStr($Storage->Type()) ?></td>
                      <td class="hidden lg:table-cell"><?php echo validHtmlStr($Storage->Scheme()) ?></td>
                      <td class="hidden lg:table-cell"><?php echo validHtmlStr($Storage->Server()->Name()) ?></td>
                      <td class="hidden md:table-cell <?php echo $diskClass ?>">
                        <?php echo human_filesize($diskUsed) ?> / <?php echo human_filesize($diskTotal) ?>
                        <span class="text-xs opacity-70">(<?php echo $diskPercent ?>%)</span>
                      </td>
                      <td class="hidden sm:table-cell">
                        <a href="?view=events<?php echo $filter->querystring() ?>" class="link link-primary">
                          <?php echo $Storage->EventCount() ?> (<?php echo human_filesize($Storage->event_disk_space()) ?>)
                        </a>
                      </td>
<?php if ($canEdit) : ?>
                      <td>
                        <input type="checkbox" name="markIds[]" value="<?php echo $Storage->Id() ?>" 
                          class="checkbox checkbox-sm checkbox-error" 
                          data-on-click-this="configureDeleteButton"
                          <?php echo ($Storage->EventCount() || !$canEdit) ? 'disabled' : '' ?>
                          <?php echo $Storage->EventCount() ? 'title="Can\'t delete while events are stored here."' : '' ?> />
                      </td>
<?php endif; ?>
                    </tr>
<?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              
<?php if ($canEdit) : ?>
              <div class="card-actions justify-end mt-4">
                <button type="submit" name="deleteBtn" class="btn btn-error btn-sm" disabled>
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                  <?php echo translate('Delete') ?>
                </button>
              </div>
<?php endif; ?>
            </form>
          </div>
        </div>
<?php
}
else if ($tab == 'API') {
  $apiEnabled = dbFetchOne('SELECT Value FROM Config WHERE Name=\'ZM_OPT_USE_API\'');
  
  if (array_key_exists('revokeAllTokens', $_POST)) {
    $minTokenTime = time();
    dbQuery('UPDATE `Users` SET `TokenMinExpiry`=?', array($minTokenTime));
    $apiMessage = translate('AllTokensRevoked');
  }
  
  if (array_key_exists('updateSelected', $_POST)) {
    dbQuery('UPDATE `Users` SET `APIEnabled`=0');
    if (isset($_REQUEST['tokenUids'])) {
      foreach ($_REQUEST['tokenUids'] as $markUid) {
        $minTime = time();
        dbQuery('UPDATE `Users` SET `TokenMinExpiry`=? WHERE `Id`=?', array($minTime, $markUid));
      }
    }
    if (isset($_REQUEST['apiUids'])) {
      foreach ($_REQUEST['apiUids'] as $markUid) {
        dbQuery('UPDATE `Users` SET `APIEnabled`=1 WHERE `Id`=?', array($markUid));
      }
    }
    $apiMessage = translate('Updated');
  }
  
  $sql = 'SELECT * FROM Users ORDER BY Username';
  $api_users = dbFetchAll($sql);
?>
        <div class="card bg-base-200 shadow-lg">
          <div class="card-body">
            <h2 class="card-title mb-4"><?php echo translate('API') ?></h2>
            
<?php if ($apiEnabled['Value'] != '1') : ?>
            <div class="alert alert-error">
              <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>APIs are disabled. To enable, please turn on OPT_USE_API in Options &rarr; System</span>
            </div>
<?php else : ?>
<?php if (isset($apiMessage)) : ?>
            <div class="alert alert-success mb-4">
              <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span><?php echo $apiMessage ?></span>
            </div>
<?php endif; ?>
            
            <form name="apiForm" method="post" action="?">
              <input type="hidden" name="view" value="<?php echo $view ?>"/>
              <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
              
              <div class="flex flex-wrap gap-2 mb-4">
                <button type="submit" name="updateSelected" class="btn btn-primary btn-sm">
                  <?php echo translate('Update') ?>
                </button>
                <button type="submit" name="revokeAllTokens" class="btn btn-error btn-sm">
                  <?php echo translate('RevokeAllTokens') ?>
                </button>
              </div>
              
              <div class="overflow-x-auto">
                <table class="table table-zebra table-sm">
                  <thead>
                    <tr>
                      <th><?php echo translate('Username') ?></th>
                      <th><?php echo translate('Revoke Token') ?></th>
                      <th><?php echo translate('API Enabled') ?></th>
                    </tr>
                  </thead>
                  <tbody>
<?php foreach ($api_users as $api_user) : ?>
                    <tr class="hover">
                      <td><?php echo validHtmlStr($api_user['Username']) ?></td>
                      <td>
                        <input type="checkbox" name="tokenUids[]" value="<?php echo $api_user['Id'] ?>" class="checkbox checkbox-sm checkbox-warning" />
                      </td>
                      <td>
                        <input type="checkbox" name="apiUids[]" value="<?php echo $api_user['Id'] ?>" class="checkbox checkbox-sm checkbox-primary" <?php echo $api_user['APIEnabled'] ? 'checked' : '' ?> />
                      </td>
                    </tr>
<?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </form>
<?php endif; ?>
          </div>
        </div>
<?php
}
else if (in_array($tab, $supported_tabs) || !in_array($tab, $supported_tabs)) {
  $config = array();
  $configCat = array();
  $configCats = array();

  $result = $dbConn->query('SELECT * FROM `Config` ORDER BY `Id` ASC');
  if ($result) {
    while ($row = dbFetchNext($result)) {
      $config[$row['Name']] = $row;
      if (!($configCat = &$configCats[$row['Category']])) {
        $configCats[$row['Category']] = array();
        $configCat = &$configCats[$row['Category']];
      }
      $configCat[$row['Name']] = $row;
    }
  }

  if ($tab == 'system' && isset($configCats[$tab])) {
    $configCats[$tab]['ZM_LANG_DEFAULT']['Hint'] = join('|', getLanguages());
    $configCats[$tab]['ZM_SKIN_DEFAULT']['Hint'] = join('|', array_map('basename', glob('skins/*', GLOB_ONLYDIR)));
    $configCats[$tab]['ZM_CSS_DEFAULT']['Hint'] = join('|', array_map('basename', glob('skins/'.ZM_SKIN_DEFAULT.'/css/*', GLOB_ONLYDIR)));
    $configCats[$tab]['ZM_BANDWIDTH_DEFAULT']['Hint'] = $bandwidth_options;

    function timezone_list() {
      static $timezones = null;
      if ($timezones === null) {
        $timezones = [];
        $offsets = [];
        $now = new DateTime('now', new DateTimeZone('UTC'));
        foreach (DateTimeZone::listIdentifiers() as $timezone) {
          $now->setTimezone(new DateTimeZone($timezone));
          $offsets[] = $offset = $now->getOffset();
          $timezones[$timezone] = '(' . format_GMT_offset($offset) . ') ' . format_timezone_name($timezone);
        }
        array_multisort($offsets, $timezones);
      }
      return $timezones;
    }

    function format_GMT_offset($offset) {
      $hours = intval($offset / 3600);
      $minutes = abs(intval($offset % 3600 / 60));
      return 'GMT' . ($offset ? sprintf('%+03d:%02d', $hours, $minutes) : '');
    }

    function format_timezone_name($name) {
      $name = str_replace('/', ', ', $name);
      $name = str_replace('_', ' ', $name);
      $name = str_replace('St ', 'St. ', $name);
      return $name;
    }

    $configCats[$tab]['ZM_TIMEZONE']['Hint'] = array('' => translate('TZUnset')) + timezone_list();
    $configCats[$tab]['ZM_LOCALE_DEFAULT']['Hint'] = array('' => translate('System Default'));
    $locales = ResourceBundle::getLocales('');
    if ($locales) {
      foreach ($locales as $locale) {
        $configCats[$tab]['ZM_LOCALE_DEFAULT']['Hint'][$locale] = $locale;
      }
    }
  }

  if (isset($configCats[$tab])) {
    $configCat = $configCats[$tab];
?>
        <div class="card bg-base-200 shadow-lg">
          <div class="card-body">
            <h2 class="card-title mb-4"><?php echo $tabs[$tab] ?> <?php echo translate('Config') ?></h2>
            
            <form name="optionsForm" method="post" action="?">
              <input type="hidden" name="view" value="<?php echo $view ?>"/>
              <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
              <input type="hidden" name="action" value="options"/>
              
              <div class="space-y-4">
<?php foreach ($configCat as $name => $value) :
  $shortName = preg_replace('/^ZM_/', '', $name);
  $optionPromptText = !empty($OLANG[$shortName]) ? $OLANG[$shortName]['Prompt'] : $value['Prompt'];
  $optionCanEdit = $canEdit && !$value['System'];
?>
                <div class="form-control w-full">
                  <label class="label">
                    <span class="label-text font-medium"><?php echo $shortName ?></span>
<?php if (!$optionCanEdit) : ?>
                    <span class="label-text-alt badge badge-ghost badge-sm"><?php echo translate('ReadOnly') ?></span>
<?php endif; ?>
                  </label>
                  
<?php
  if ($value['Type'] == 'boolean') {
?>
                  <input type="checkbox" id="<?php echo $name ?>" name="newConfig[<?php echo $name ?>]" value="1" 
                    class="toggle toggle-primary" <?php echo $value['Value'] ? 'checked' : '' ?> <?php echo $optionCanEdit ? '' : 'disabled' ?> />
<?php
  }
  else if (is_array($value['Hint'])) {
?>
                  <select name="newConfig[<?php echo $name ?>]" class="select select-bordered w-full max-w-lg" <?php echo $optionCanEdit ? '' : 'disabled' ?>>
<?php foreach ($value['Hint'] as $optVal => $optLabel) : ?>
                    <option value="<?php echo $optVal ?>" <?php echo ($value['Value'] == $optVal) ? 'selected' : '' ?>><?php echo htmlspecialchars($optLabel) ?></option>
<?php endforeach; ?>
                  </select>
<?php
  }
  else if (preg_match('/\|/', $value['Hint'])) {
    $options = explode('|', $value['Hint']);
    if (count($options) > 3) {
?>
                  <select name="newConfig[<?php echo $name ?>]" class="select select-bordered w-full max-w-lg" <?php echo $optionCanEdit ? '' : 'disabled' ?>>
<?php foreach ($options as $option) :
  if (preg_match('/^([^=]+)=(.+)$/', $option, $matches)) {
    $optionLabel = $matches[1];
    $optionValue = $matches[2];
  } else {
    $optionLabel = $optionValue = $option;
  }
?>
                    <option value="<?php echo $optionValue ?>" <?php echo ($value['Value'] == $optionValue) ? 'selected' : '' ?>><?php echo htmlspecialchars($optionLabel) ?></option>
<?php endforeach; ?>
                  </select>
<?php
    } else {
?>
                  <div class="flex flex-wrap gap-4">
<?php foreach ($options as $option) :
  if (preg_match('/^([^=]+)=(.+)$/', $option, $matches)) {
    $optionLabel = $matches[1];
    $optionValue = $matches[2];
  } else {
    $optionLabel = $optionValue = $option;
  }
?>
                    <label class="label cursor-pointer gap-2">
                      <input type="radio" name="newConfig[<?php echo $name ?>]" value="<?php echo $optionValue ?>" 
                        class="radio radio-primary" <?php echo ($value['Value'] == $optionValue) ? 'checked' : '' ?> <?php echo $optionCanEdit ? '' : 'disabled' ?> />
                      <span class="label-text"><?php echo htmlspecialchars($optionLabel) ?></span>
                    </label>
<?php endforeach; ?>
                  </div>
<?php
    }
  }
  else if ($value['Type'] == 'text') {
?>
                  <textarea name="newConfig[<?php echo $name ?>]" class="textarea textarea-bordered w-full max-w-lg" rows="4" <?php echo $optionCanEdit ? '' : 'disabled' ?>><?php echo validHtmlStr($value['Value']) ?></textarea>
<?php
  }
  else if ($value['Type'] == 'integer') {
?>
                  <input type="number" name="newConfig[<?php echo $name ?>]" value="<?php echo validHtmlStr($value['Value']) ?>" 
                    class="input input-bordered w-full max-w-lg" <?php echo $optionCanEdit ? '' : 'disabled' ?> />
<?php
  }
  else {
?>
                  <input type="text" name="newConfig[<?php echo $name ?>]" value="<?php echo validHtmlStr($value['Value']) ?>" 
                    class="input input-bordered w-full max-w-lg" <?php echo $optionCanEdit ? '' : 'disabled' ?> />
<?php
  }

  if ($value['Value'] != constant($name)) {
?>
                  <div class="alert alert-warning mt-2 py-2 text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>Overridden in config files. Active value: <code class="bg-base-300 px-1 rounded"><?php echo constant($name) ?></code></span>
                  </div>
<?php
  }
?>
                  <label class="label">
                    <span class="label-text-alt opacity-70"><?php echo validHtmlStr($optionPromptText) ?></span>
                  </label>
                </div>
<?php endforeach; ?>
              </div>
              
              <div class="card-actions justify-end mt-6">
                <button type="submit" class="btn btn-primary" <?php echo $canEdit ? '' : 'disabled' ?>>
                  <?php echo translate('Save') ?>
                </button>
              </div>
            </form>
          </div>
        </div>
<?php
  } else {
?>
        <div class="alert alert-info max-w-2xl">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <div>
            <h3 class="font-bold"><?php echo $tabs[$tab] ?></h3>
            <p>This tab is not yet available in the modern skin. It will fall back to the classic skin.</p>
            <a href="?view=options&tab=<?php echo $tab ?>&skin=classic" class="btn btn-sm btn-ghost mt-2">Open in Classic Skin</a>
          </div>
        </div>
<?php
  }
}
?>
      </div>
    </main>
  </div>
  
  <!-- Sidebar -->
  <div class="drawer-side z-20">
    <label for="options-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
    <aside class="bg-base-200 min-h-full w-64 border-r border-base-300">
      <div class="p-4 border-b border-base-300">
        <h2 class="text-lg font-bold"><?php echo translate('Options') ?></h2>
      </div>
      <ul class="menu p-4 gap-1">
<?php foreach ($tabs as $name => $value) : ?>
        <li>
          <a href="?view=<?php echo $view ?>&amp;tab=<?php echo $name ?>" 
             class="<?php echo $tab == $name ? 'active' : '' ?>">
            <?php echo $value ?>
          </a>
        </li>
<?php endforeach; ?>
      </ul>
    </aside>
  </div>
</div>

<?php if ($tab == 'servers' && $canEdit) : ?>
<!-- Server Modal -->
<dialog id="serverModal" class="modal">
  <div class="modal-box w-11/12 max-w-2xl">
    <h3 class="font-bold text-lg mb-4" id="serverModalTitle"><?php echo translate('Server') ?></h3>
    <form id="serverModalForm" method="post" action="?view=server">
      <?php echo getCSRFinputHTML(); ?>
      <input type="hidden" name="object" value="server"/>
      <input type="hidden" name="id" id="serverModalId" value="0"/>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="form-control w-full">
          <label class="label"><span class="label-text"><?php echo translate('Name') ?></span></label>
          <input type="text" name="newServer[Name]" id="serverName" class="input input-bordered w-full" />
        </div>
        
        <div class="form-control w-full">
          <label class="label"><span class="label-text"><?php echo translate('Protocol') ?></span></label>
          <input type="text" name="newServer[Protocol]" id="serverProtocol" class="input input-bordered w-full" placeholder="https" />
        </div>
        
        <div class="form-control w-full">
          <label class="label"><span class="label-text"><?php echo translate('Hostname') ?></span></label>
          <input type="text" name="newServer[Hostname]" id="serverHostname" class="input input-bordered w-full" />
        </div>
        
        <div class="form-control w-full">
          <label class="label"><span class="label-text"><?php echo translate('Port') ?></span></label>
          <input type="number" name="newServer[Port]" id="serverPort" class="input input-bordered w-full" />
        </div>
        
        <div class="form-control w-full md:col-span-2">
          <label class="label"><span class="label-text"><?php echo translate('PathToIndex') ?></span></label>
          <input type="text" name="newServer[PathToIndex]" id="serverPathToIndex" class="input input-bordered w-full" placeholder="/zm/index.php" />
        </div>
        
        <div class="form-control w-full md:col-span-2">
          <label class="label"><span class="label-text"><?php echo translate('PathToZMS') ?></span></label>
          <input type="text" name="newServer[PathToZMS]" id="serverPathToZMS" class="input input-bordered w-full" placeholder="/zm/cgi-bin/nph-zms" />
        </div>
        
        <div class="form-control w-full md:col-span-2">
          <label class="label"><span class="label-text"><?php echo translate('PathToApi') ?></span></label>
          <input type="text" name="newServer[PathToApi]" id="serverPathToApi" class="input input-bordered w-full" placeholder="/zm/api" />
        </div>
      </div>
      
      <div class="divider"></div>
      
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="form-control">
          <label class="label cursor-pointer justify-start gap-2">
            <input type="checkbox" name="newServer[zmstats]" id="serverZmstats" value="1" class="checkbox checkbox-primary" />
            <span class="label-text"><?php echo translate('RunStats') ?></span>
          </label>
        </div>
        
        <div class="form-control">
          <label class="label cursor-pointer justify-start gap-2">
            <input type="checkbox" name="newServer[zmaudit]" id="serverZmaudit" value="1" class="checkbox checkbox-primary" />
            <span class="label-text"><?php echo translate('RunAudit') ?></span>
          </label>
        </div>
        
        <div class="form-control">
          <label class="label cursor-pointer justify-start gap-2">
            <input type="checkbox" name="newServer[zmtrigger]" id="serverZmtrigger" value="1" class="checkbox checkbox-primary" />
            <span class="label-text"><?php echo translate('RunTrigger') ?></span>
          </label>
        </div>
        
        <div class="form-control">
          <label class="label cursor-pointer justify-start gap-2">
            <input type="checkbox" name="newServer[zmeventnotification]" id="serverZmeventnotification" value="1" class="checkbox checkbox-primary" />
            <span class="label-text"><?php echo translate('RunEventNotification') ?></span>
          </label>
        </div>
      </div>
      
      <div class="modal-action">
        <button type="button" class="btn btn-ghost" data-close-modal="serverModal"><?php echo translate('Cancel') ?></button>
        <button type="submit" name="action" value="save" class="btn btn-primary"><?php echo translate('Save') ?></button>
      </div>
    </form>
  </div>
  <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<!-- Server data for JS -->
<script type="application/json" id="serversData">
<?php
$serverData = [];
foreach ($servers as $Server) {
  $serverData[$Server->Id()] = [
    'Id' => $Server->Id(),
    'Name' => $Server->Name(),
    'Protocol' => $Server->Protocol(),
    'Hostname' => $Server->Hostname(),
    'Port' => $Server->Port(),
    'PathToIndex' => $Server->PathToIndex(),
    'PathToZMS' => $Server->PathToZMS(),
    'PathToApi' => $Server->PathToApi(),
    'zmstats' => $Server->zmstats(),
    'zmaudit' => $Server->zmaudit(),
    'zmtrigger' => $Server->zmtrigger(),
    'zmeventnotification' => $Server->zmeventnotification(),
  ];
}
echo json_encode($serverData);
?>
</script>
<?php endif; ?>

<?php if ($tab == 'storage' && $canEdit) : 
  $allServers = ZM\Server::find();
?>
<!-- Storage Modal -->
<dialog id="storageModal" class="modal">
  <div class="modal-box w-11/12 max-w-2xl">
    <h3 class="font-bold text-lg mb-4" id="storageModalTitle"><?php echo translate('Storage') ?></h3>
    <form id="storageModalForm" method="post" action="?view=storage&action=save">
      <?php echo getCSRFinputHTML(); ?>
      <input type="hidden" name="object" value="storage"/>
      <input type="hidden" name="id" id="storageModalId" value="0"/>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="form-control w-full">
          <label class="label"><span class="label-text"><?php echo translate('Name') ?></span></label>
          <input type="text" name="newStorage[Name]" id="storageName" class="input input-bordered w-full" />
        </div>
        
        <div class="form-control w-full">
          <label class="label"><span class="label-text"><?php echo translate('Path') ?></span></label>
          <input type="text" name="newStorage[Path]" id="storagePath" class="input input-bordered w-full" />
        </div>
        
        <div class="form-control w-full md:col-span-2">
          <label class="label"><span class="label-text"><?php echo translate('Url') ?></span></label>
          <input type="text" name="newStorage[Url]" id="storageUrl" class="input input-bordered w-full" placeholder="Optional URL for remote access" />
        </div>
        
        <div class="form-control w-full">
          <label class="label"><span class="label-text"><?php echo translate('Server') ?></span></label>
          <select name="newStorage[ServerId]" id="storageServerId" class="select select-bordered w-full">
            <option value="">Remote / No Specific Server</option>
<?php foreach ($allServers as $S) : ?>
            <option value="<?php echo $S->Id() ?>"><?php echo validHtmlStr($S->Name()) ?></option>
<?php endforeach; ?>
          </select>
        </div>
        
        <div class="form-control w-full">
          <label class="label"><span class="label-text"><?php echo translate('Type') ?></span></label>
          <select name="newStorage[Type]" id="storageType" class="select select-bordered w-full">
            <option value="local"><?php echo translate('Local') ?></option>
            <option value="s3fs"><?php echo translate('s3fs') ?></option>
          </select>
        </div>
        
        <div class="form-control w-full">
          <label class="label"><span class="label-text"><?php echo translate('StorageScheme') ?></span></label>
          <select name="newStorage[Scheme]" id="storageScheme" class="select select-bordered w-full">
            <option value="Deep"><?php echo translate('Deep') ?></option>
            <option value="Medium"><?php echo translate('Medium') ?></option>
            <option value="Shallow"><?php echo translate('Shallow') ?></option>
          </select>
        </div>
      </div>
      
      <div class="divider"></div>
      
      <div class="grid grid-cols-2 gap-4">
        <div class="form-control">
          <label class="label cursor-pointer justify-start gap-2">
            <input type="checkbox" name="newStorage[DoDelete]" id="storageDoDelete" value="1" class="checkbox checkbox-primary" checked />
            <span class="label-text"><?php echo translate('StorageDoDelete') ?></span>
          </label>
        </div>
        
        <div class="form-control">
          <label class="label cursor-pointer justify-start gap-2">
            <input type="checkbox" name="newStorage[Enabled]" id="storageEnabled" value="1" class="checkbox checkbox-primary" checked />
            <span class="label-text"><?php echo translate('Enabled') ?></span>
          </label>
        </div>
      </div>
      
      <div class="modal-action">
        <button type="button" class="btn btn-ghost" data-close-modal="storageModal"><?php echo translate('Cancel') ?></button>
        <button type="submit" name="action" value="save" class="btn btn-primary"><?php echo translate('Save') ?></button>
      </div>
    </form>
  </div>
  <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<!-- Storage data for JS -->
<script type="application/json" id="storageData">
<?php
$storageDataArr = [];
foreach ($storages as $Storage) {
  $storageDataArr[$Storage->Id()] = [
    'Id' => $Storage->Id(),
    'Name' => $Storage->Name(),
    'Path' => $Storage->Path(),
    'Url' => $Storage->Url(),
    'ServerId' => $Storage->ServerId(),
    'Type' => $Storage->Type(),
    'Scheme' => $Storage->Scheme(),
    'DoDelete' => $Storage->DoDelete(),
    'Enabled' => $Storage->Enabled(),
  ];
}
echo json_encode($storageDataArr);
?>
</script>
<?php endif; ?>

<?php xhtmlFooter() ?>
