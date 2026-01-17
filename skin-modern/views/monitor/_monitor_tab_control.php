<?php
//
// ZoneMinder Modern Skin - Monitor Control Tab
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

$controlTypes = array(''=>translate('None'));
foreach ( $controls as $control ) {
  $controlTypes[$control->Id()] = $control->Name();
}

$return_options = array(
  '-1' => translate('None'),
  '0' => translate('Home'),
  '1' => translate('Preset').' 1',
);
?>
<div class="space-y-4">
  <div class="form-control w-full">
    <label class="label cursor-pointer justify-start gap-4">
      <input type="checkbox" name="newMonitor[Controllable]" value="1" class="checkbox checkbox-primary" <?php echo $monitor->Controllable() ? 'checked' : '' ?>/>
      <span class="label-text font-medium"><?php echo translate('Controllable') ?></span>
    </label>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('ControlType') ?></span></label>
    <div class="flex gap-2 items-center">
      <select name="newMonitor[ControlId]" class="select select-bordered flex-1 max-w-lg">
<?php foreach ($controlTypes as $val => $label) : ?>
        <option value="<?php echo $val ?>" <?php echo ($monitor->ControlId() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
      </select>
<?php if ( canEdit('Control') ) : ?>
      <a href="?view=controlcaps" class="btn btn-ghost btn-sm"><?php echo translate('Edit') ?></a>
<?php endif; ?>
    </div>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('ControlDevice') ?></span></label>
    <input type="text" name="newMonitor[ControlDevice]" value="<?php echo validHtmlStr($monitor->ControlDevice()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('ControlAddress') ?></span></label>
    <input type="text" name="newMonitor[ControlAddress]" value="<?php echo validHtmlStr($monitor->ControlAddress()) ?: 'user:port@ip' ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label cursor-pointer justify-start gap-4">
      <input type="checkbox" name="newMonitor[ModectDuringPTZ]" value="1" class="checkbox checkbox-primary" <?php echo $monitor->ModectDuringPTZ() ? 'checked' : '' ?>/>
      <span class="label-text font-medium"><?php echo translate('ModectDuringPTZ') ?></span>
    </label>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('AutoStopTimeout') ?></span></label>
    <input type="number" name="newMonitor[AutoStopTimeout]" value="<?php echo validHtmlStr($monitor->AutoStopTimeout()) ?>" min="0" step="any" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label cursor-pointer justify-start gap-4">
      <input type="checkbox" name="newMonitor[TrackMotion]" value="1" class="checkbox checkbox-primary" <?php echo $monitor->TrackMotion() ? 'checked' : '' ?>/>
      <span class="label-text font-medium"><?php echo translate('TrackMotion') ?></span>
    </label>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('TrackDelay') ?></span></label>
    <input type="number" name="newMonitor[TrackDelay]" value="<?php echo validHtmlStr($monitor->TrackDelay()) ?>" min="0" step="any" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('ReturnLocation') ?></span></label>
    <select name="newMonitor[ReturnLocation]" class="select select-bordered w-full max-w-lg">
<?php foreach ($return_options as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->ReturnLocation() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('ReturnDelay') ?></span></label>
    <input type="number" name="newMonitor[ReturnDelay]" value="<?php echo validHtmlStr($monitor->ReturnDelay()) ?>" min="0" step="any" class="input input-bordered w-full max-w-lg"/>
  </div>
</div>
