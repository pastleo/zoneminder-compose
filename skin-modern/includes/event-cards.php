<?php
//
// ZoneMinder Modern Skin - Shared Event Cards Component
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
 * Renders the event cards container markup
 * 
 * @param array $options Configuration options:
 *   - 'id' (string): Container ID (default: 'eventCards')
 *   - 'title' (string|null): Section title (null to hide header)
 *   - 'showToolbar' (bool): Show action toolbar (default: false)
 *   - 'showRefresh' (bool): Show refresh button in header (default: true)
 *   - 'showPagination' (bool): Show pagination controls (default: false)
 *   - 'cardClass' (string): Additional classes for card container
 */
function getEventCardsHTML($options = []) {
  $id = $options['id'] ?? 'eventCards';
  $title = array_key_exists('title', $options) ? $options['title'] : translate('Recent Events');
  $showToolbar = $options['showToolbar'] ?? false;
  $showRefresh = $options['showRefresh'] ?? true;
  $showPagination = $options['showPagination'] ?? false;
  $cardClass = $options['cardClass'] ?? '';

  ob_start();
?>
<div id="<?php echo htmlspecialchars($id) ?>" class="event-cards-container <?php echo htmlspecialchars($cardClass) ?>">
<?php if ($title !== null || $showToolbar) : ?>
  <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
<?php if ($title !== null) : ?>
    <h3 class="font-medium text-sm opacity-60 flex items-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
        <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
      </svg>
      <?php echo htmlspecialchars($title) ?>
    </h3>
<?php endif; ?>

<?php if ($showToolbar) : ?>
    <!-- Toolbar for batch actions -->
    <div id="<?php echo $id ?>Toolbar" class="flex flex-wrap items-center gap-2">
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" id="<?php echo $id ?>SelectAll" class="checkbox checkbox-sm" />
        <span class="text-sm"><?php echo translate('All') ?></span>
      </label>
      <div class="divider divider-horizontal mx-0"></div>
      <button type="button" id="<?php echo $id ?>ArchiveBtn" class="btn btn-sm btn-ghost gap-1" disabled title="<?php echo translate('Archive') ?>">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M20.54 5.23l-1.39-1.68C18.88 3.21 18.47 3 18 3H6c-.47 0-.88.21-1.16.55L3.46 5.23C3.17 5.57 3 6.02 3 6.5V19c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6.5c0-.48-.17-.93-.46-1.27zM12 17.5L6.5 12H10v-2h4v2h3.5L12 17.5zM5.12 5l.81-1h12l.94 1H5.12z"/></svg>
        <span class="hidden sm:inline"><?php echo translate('Archive') ?></span>
      </button>
      <button type="button" id="<?php echo $id ?>UnarchiveBtn" class="btn btn-sm btn-ghost gap-1" disabled title="<?php echo translate('Unarchive') ?>">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M20.54 5.23l-1.39-1.68C18.88 3.21 18.47 3 18 3H6c-.47 0-.88.21-1.16.55L3.46 5.23C3.17 5.57 3 6.02 3 6.5V19c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6.5c0-.48-.17-.93-.46-1.27zM12 6.5l5.5 5.5H14v2h-4v-2H6.5L12 6.5zM5.12 5l.81-1h12l.94 1H5.12z"/></svg>
        <span class="hidden sm:inline"><?php echo translate('Unarchive') ?></span>
      </button>
      <button type="button" id="<?php echo $id ?>ExportBtn" class="btn btn-sm btn-ghost gap-1" disabled title="<?php echo translate('Export') ?>">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7h-2zm-6 .67l2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2v9.67z"/></svg>
        <span class="hidden sm:inline"><?php echo translate('Export') ?></span>
      </button>
      <button type="button" id="<?php echo $id ?>DeleteBtn" class="btn btn-sm btn-error btn-ghost gap-1" disabled title="<?php echo translate('Delete') ?>">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
        <span class="hidden sm:inline"><?php echo translate('Delete') ?></span>
      </button>
    </div>
<?php elseif ($showRefresh) : ?>
    <button type="button" id="<?php echo $id ?>RefreshBtn" class="btn btn-ghost btn-xs" title="<?php echo translate('Refresh') ?>">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
    </button>
<?php endif; ?>
  </div>
<?php endif; ?>

  <!-- Event Cards Grid -->
  <div id="<?php echo $id ?>Grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
    <!-- Event cards populated via JS -->
    <div id="<?php echo $id ?>Loading" class="col-span-full flex justify-center py-8">
      <span class="loading loading-spinner loading-lg"></span>
    </div>
    <div id="<?php echo $id ?>Empty" class="col-span-full hidden">
      <div class="alert">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span><?php echo translate('NoEvents') ?></span>
      </div>
    </div>
  </div>

<?php if ($showPagination) : ?>
  <!-- Pagination -->
  <div id="<?php echo $id ?>Pagination" class="flex flex-wrap items-center justify-between gap-4 mt-4">
    <div class="flex items-center gap-2">
      <span class="text-sm opacity-60"><?php echo translate('Show') ?>:</span>
      <select id="<?php echo $id ?>PageSize" class="select select-sm select-bordered w-20">
        <option value="12">12</option>
        <option value="24" selected>24</option>
        <option value="48">48</option>
        <option value="96">96</option>
      </select>
    </div>
    <div class="join">
      <button type="button" id="<?php echo $id ?>PrevPage" class="join-item btn btn-sm" disabled>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
      </button>
      <span id="<?php echo $id ?>PageInfo" class="join-item btn btn-sm btn-disabled">1 / 1</span>
      <button type="button" id="<?php echo $id ?>NextPage" class="join-item btn btn-sm" disabled>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
      </button>
    </div>
    <div id="<?php echo $id ?>Total" class="text-sm opacity-60">
      <!-- Total count populated via JS -->
    </div>
  </div>
<?php endif; ?>
</div>
<?php
  return ob_get_clean();
}
?>
