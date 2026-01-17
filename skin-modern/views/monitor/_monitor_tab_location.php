<?php
//
// ZoneMinder Modern Skin - Monitor Location Tab
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
?>
<div class="space-y-4">
  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('Latitude') ?></span></label>
    <input type="number" name="newMonitor[Latitude]" step="any" value="<?php echo $monitor->Latitude() ?>" min="-90" max="90" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('Longitude') ?></span></label>
    <input type="number" name="newMonitor[Longitude]" step="any" value="<?php echo $monitor->Longitude() ?>" min="-180" max="180" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <button type="button" data-on-click="getLocation" class="btn btn-ghost btn-sm w-fit">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
      <?php echo translate('GetCurrentLocation') ?>
    </button>
  </div>

  <div id="LocationMap" class="w-full h-96 max-w-2xl bg-base-300 rounded-lg"></div>
</div>
