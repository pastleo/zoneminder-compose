<?php
//
// ZoneMinder web events view file
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

if ( !canView('Events') || (!empty($_REQUEST['execute']) && !canEdit('Events')) ) {
  $view = 'error';
  return;
}

require_once('includes/Filter.php');

$filter = isset($_REQUEST['filter_id']) ? new ZM\Filter($_REQUEST['filter_id']) : new ZM\Filter();
if ( isset($_REQUEST['filter'])) {
  $filter->set($_REQUEST['filter']);
}

parseSort();
$filterQuery = $filter->querystring();

foreach ( getSkinIncludes('includes/event-cards.php') as $includeFile )
  require_once $includeFile;

noCacheHeaders();
xhtmlHeaders(__FILE__, translate('Events'));
?>
<body data-view="events">
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
  <div class="card bg-base-200 shadow-xl">
    <div class="card-body p-4 md:p-6">
      <?php echo getEventCardsHTML([
        'id' => 'eventCards',
        'title' => translate('Events'),
        'showToolbar' => canEdit('Events'),
        'showRefresh' => !canEdit('Events'),
        'showPagination' => true,
      ]); ?>
    </div>
  </div>
</main>

<?php xhtmlFooter() ?>
