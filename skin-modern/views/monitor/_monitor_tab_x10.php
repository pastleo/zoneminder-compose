<?php
//
// ZoneMinder Modern Skin - Monitor X10 Tab
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
    <label class="label"><span class="label-text font-medium"><?php echo translate('X10ActivationString') ?></span></label>
    <input type="text" name="newX10Monitor[Activation]" value="<?php echo validHtmlStr($newX10Monitor['Activation']) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('X10InputAlarmString') ?></span></label>
    <input type="text" name="newX10Monitor[AlarmInput]" value="<?php echo validHtmlStr($newX10Monitor['AlarmInput']) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('X10OutputAlarmString') ?></span></label>
    <input type="text" name="newX10Monitor[AlarmOutput]" value="<?php echo validHtmlStr($newX10Monitor['AlarmOutput']) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>
</div>
