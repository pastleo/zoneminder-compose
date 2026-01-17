<?php
//
// ZoneMinder Modern Skin - Monitor Timestamp Tab
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
    <label class="label"><span class="label-text font-medium"><?php echo translate('TimestampLabelFormat') ?></span></label>
    <input type="text" name="newMonitor[LabelFormat]" value="<?php echo validHtmlStr($monitor->LabelFormat()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('TimestampLabelX') ?></span></label>
    <input type="number" name="newMonitor[LabelX]" value="<?php echo validHtmlStr($monitor->LabelX()) ?>" min="0" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('TimestampLabelY') ?></span></label>
    <input type="number" name="newMonitor[LabelY]" value="<?php echo validHtmlStr($monitor->LabelY()) ?>" min="0" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('TimestampLabelSize') ?></span></label>
    <select name="newMonitor[LabelSize]" class="select select-bordered w-full max-w-lg">
<?php foreach ($label_size as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->LabelSize() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>
</div>
