<?php
//
// ZoneMinder Modern Skin - Monitor Misc Tab
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

$importance_options = array(
  'Not'=>translate('Not important'),
  'Less'=>translate('Less important'),
  'Normal'=>translate('Normal')
);
?>
<div class="space-y-4">
  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('EventPrefix') ?></span></label>
    <input type="text" name="newMonitor[EventPrefix]" value="<?php echo validHtmlStr($monitor->EventPrefix()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('Sectionlength') ?></span></label>
    <div class="flex items-center gap-2">
      <input type="number" name="newMonitor[SectionLength]" value="<?php echo validHtmlStr($monitor->SectionLength()) ?>" min="0" class="input input-bordered w-full max-w-xs"/>
      <span class="text-base-content/70"><?php echo translate('seconds') ?></span>
    </div>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('MinSectionlength') ?></span></label>
    <div class="flex items-center gap-2">
      <input type="number" name="newMonitor[MinSectionLength]" value="<?php echo validHtmlStr($monitor->MinSectionLength()) ?>" min="0" class="input input-bordered w-full max-w-xs"/>
      <span class="text-base-content/70"><?php echo translate('seconds') ?></span>
    </div>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('FrameSkip') ?></span></label>
    <div class="flex items-center gap-2">
      <input type="number" name="newMonitor[FrameSkip]" value="<?php echo validHtmlStr($monitor->FrameSkip()) ?>" min="0" class="input input-bordered w-full max-w-xs"/>
      <span class="text-base-content/70"><?php echo translate('frames') ?></span>
    </div>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('MotionFrameSkip') ?></span></label>
    <div class="flex items-center gap-2">
      <input type="number" name="newMonitor[MotionFrameSkip]" value="<?php echo validHtmlStr($monitor->MotionFrameSkip()) ?>" min="0" class="input input-bordered w-full max-w-xs"/>
      <span class="text-base-content/70"><?php echo translate('frames') ?></span>
    </div>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('AnalysisUpdateDelay') ?></span></label>
    <div class="flex items-center gap-2">
      <input type="number" name="newMonitor[AnalysisUpdateDelay]" value="<?php echo validHtmlStr($monitor->AnalysisUpdateDelay()) ?>" min="0" class="input input-bordered w-full max-w-xs"/>
      <span class="text-base-content/70"><?php echo translate('seconds') ?></span>
    </div>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('FPSReportInterval') ?></span></label>
    <div class="flex items-center gap-2">
      <input type="number" name="newMonitor[FPSReportInterval]" value="<?php echo validHtmlStr($monitor->FPSReportInterval()) ?>" min="0" class="input input-bordered w-full max-w-xs"/>
      <span class="text-base-content/70"><?php echo translate('frames') ?></span>
    </div>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('DefaultRate') ?></span></label>
    <select name="newMonitor[DefaultRate]" class="select select-bordered w-full max-w-lg">
<?php foreach ($rates as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->DefaultRate() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('DefaultScale') ?></span></label>
    <select name="newMonitor[DefaultScale]" class="select select-bordered w-full max-w-lg">
<?php foreach ($scales as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->DefaultScale() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('DefaultCodec') ?></span></label>
    <select name="newMonitor[DefaultCodec]" class="select select-bordered w-full max-w-lg">
<?php foreach ($codecs as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->DefaultCodec() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('SignalCheckPoints') ?></span></label>
    <input type="number" name="newMonitor[SignalCheckPoints]" value="<?php echo validInt($monitor->SignalCheckPoints()) ?>" min="0" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('SignalCheckColour') ?></span></label>
    <div class="flex items-center gap-2">
      <input type="color" name="newMonitor[SignalCheckColour]" value="<?php echo validHtmlStr($monitor->SignalCheckColour()) ?>" class="w-12 h-10 rounded cursor-pointer"/>
      <span id="SignalCheckSwatch" class="w-8 h-8 rounded border border-base-300" style="background-color: <?php echo validHtmlStr($monitor->SignalCheckColour()) ?>;"></span>
    </div>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('WebColour') ?></span></label>
    <div class="flex items-center gap-2">
      <input type="color" name="newMonitor[WebColour]" value="<?php echo validHtmlStr($monitor->WebColour()) ?>" class="w-12 h-10 rounded cursor-pointer"/>
      <span id="WebSwatch" class="w-8 h-8 rounded border border-base-300" style="background-color: <?php echo validHtmlStr($monitor->WebColour()) ?>;"></span>
      <button type="button" data-on-click="random_WebColour" class="btn btn-ghost btn-sm btn-circle">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
      </button>
    </div>
  </div>

  <div class="form-control w-full">
    <label class="label cursor-pointer justify-start gap-4">
      <input type="checkbox" name="newMonitor[Exif]" value="1" class="checkbox checkbox-primary" <?php echo $monitor->Exif() ? 'checked' : '' ?>/>
      <span class="label-text font-medium"><?php echo translate('Exif') ?></span>
    </label>
  </div>

  <div class="form-control w-full">
    <label class="label cursor-pointer justify-start gap-4">
      <input type="checkbox" name="newMonitor[RTSPServer]" value="1" class="checkbox checkbox-primary" <?php echo $monitor->RTSPServer() ? 'checked' : '' ?>/>
      <span class="label-text font-medium"><?php echo translate('RTSPServer') ?></span>
    </label>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('RTSPStreamName') ?></span></label>
    <input type="text" name="newMonitor[RTSPStreamName]" value="<?php echo validHtmlStr($monitor->RTSPStreamName()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('Importance') ?></span></label>
    <select name="newMonitor[Importance]" class="select select-bordered w-full max-w-lg">
<?php foreach ($importance_options as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->Importance() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>
</div>
