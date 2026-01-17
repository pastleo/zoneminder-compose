<?php
//
// ZoneMinder Modern Skin - Monitor General Tab
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

if ( $monitor->Type() == 'WebSite' ) {
  $showAdvancedFields = false;
} else {
  $showAdvancedFields = true;
}
?>
<div class="space-y-4">
  <div class="form-control w-full">
    <label class="label">
      <span class="label-text font-medium"><?php echo translate('Name') ?></span>
    </label>
    <input type="text" name="newMonitor[Name]" value="<?php echo validHtmlStr($monitor->Name()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label">
      <span class="label-text font-medium"><?php echo translate('Notes') ?></span>
    </label>
    <textarea name="newMonitor[Notes]" rows="4" class="textarea textarea-bordered w-full max-w-lg"><?php echo validHtmlStr($monitor->Notes()) ?></textarea>
  </div>

  <div class="form-control w-full">
    <label class="label">
      <span class="label-text font-medium"><?php echo translate('Server') ?></span>
    </label>
    <select name="newMonitor[ServerId]" class="select select-bordered w-full max-w-lg">
<?php
$servers = array(''=>'None','auto'=>'Auto');
foreach ( ZM\Server::find(NULL, array('order'=>'lower(Name)')) as $Server ) {
  $servers[$Server->Id()] = $Server->Name();
}
foreach ($servers as $val => $label) :
?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->ServerId() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>

  <div class="form-control w-full">
    <label class="label">
      <span class="label-text font-medium"><?php echo translate('SourceType') ?></span>
    </label>
    <select name="newMonitor[Type]" class="select select-bordered w-full max-w-lg">
<?php foreach ($sourceTypes as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->Type() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>

<?php if ( $showAdvancedFields ) : ?>
  <div class="form-control w-full">
    <label class="label">
      <span class="label-text font-medium"><?php echo translate('Function') ?></span>
    </label>
    <select name="newMonitor[Function]" class="select select-bordered w-full max-w-lg">
<?php
$function_options = array();
foreach ( getEnumValues('Monitors', 'Function') as $f ) {
  $function_options[$f] = translate("Fn$f");
}
foreach ($function_options as $val => $label) :
?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->Function() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
    <div id="function_help" class="mt-2">
<?php
foreach ( ZM\getMonitorFunctionTypes() as $fn => $translated ) {
  if ( isset($OLANG['FUNCTION_'.strtoupper($fn)]) ) {
    echo '<div class="text-sm opacity-70 hidden" id="'.$fn.'Help">'.$OLANG['FUNCTION_'.strtoupper($fn)]['Help'].'</div>';
  }
}
?>
    </div>
  </div>

  <div class="form-control w-full" id="FunctionEnabled">
    <label class="label cursor-pointer justify-start gap-4">
      <input type="checkbox" name="newMonitor[Enabled]" value="1" class="checkbox checkbox-primary" <?php echo $monitor->Enabled() ? 'checked' : '' ?>/>
      <span class="label-text font-medium"><?php echo translate('Analysis Enabled') ?></span>
    </label>
<?php if ( isset($OLANG['FUNCTION_ANALYSIS_ENABLED']) ) : ?>
    <label class="label"><span class="label-text-alt opacity-70"><?php echo $OLANG['FUNCTION_ANALYSIS_ENABLED']['Help'] ?></span></label>
<?php endif; ?>
  </div>

  <div class="form-control w-full" id="FunctionDecodingEnabled">
    <label class="label cursor-pointer justify-start gap-4">
      <input type="checkbox" name="newMonitor[DecodingEnabled]" value="1" class="checkbox checkbox-primary" <?php echo $monitor->DecodingEnabled() ? 'checked' : '' ?>/>
      <span class="label-text font-medium"><?php echo translate('Decoding Enabled') ?></span>
    </label>
<?php if ( isset($OLANG['FUNCTION_DECODING_ENABLED']) ) : ?>
    <label class="label"><span class="label-text-alt opacity-70"><?php echo $OLANG['FUNCTION_DECODING_ENABLED']['Help'] ?></span></label>
<?php endif; ?>
  </div>

  <div class="form-control w-full">
    <label class="label">
      <span class="label-text font-medium"><?php echo translate('LinkedMonitors') ?></span>
    </label>
<?php
$monitors = dbFetchAll('SELECT Id, Name FROM Monitors ORDER BY Name,Sequence ASC');
$monitor_options = array();
foreach ( $monitors as $linked_monitor ) {
  if ( (!$monitor->Id() || ($monitor->Id()!= $linked_monitor['Id'])) && visibleMonitor($linked_monitor['Id']) ) {
    $monitor_options[$linked_monitor['Id']] = validHtmlStr($linked_monitor['Name']);
  }
}
$selectedLinked = $monitor->LinkedMonitors() ? explode(',', $monitor->LinkedMonitors()) : array();
?>
    <select name="newMonitor[LinkedMonitors][]" multiple class="select select-bordered w-full max-w-lg h-32">
<?php foreach ($monitor_options as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo in_array($val, $selectedLinked) ? 'selected' : '' ?>><?php echo $label ?></option>
<?php endforeach; ?>
    </select>
    <label class="label"><span class="label-text-alt opacity-70">Hold Ctrl/Cmd to select multiple</span></label>
  </div>

  <div class="form-control w-full">
    <label class="label">
      <span class="label-text font-medium"><?php echo translate('Groups') ?></span>
    </label>
    <select name="newMonitor[GroupIds][]" multiple class="select select-bordered w-full max-w-lg h-32">
<?php 
$groupOptions = ZM\Group::get_dropdown_options();
$selectedGroups = $monitor->GroupIds();
foreach ($groupOptions as $val => $label) : 
?>
      <option value="<?php echo $val ?>" <?php echo in_array($val, $selectedGroups) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>

  <div class="form-control w-full">
    <label class="label">
      <span class="label-text font-medium"><?php echo translate('AnalysisFPS') ?></span>
    </label>
    <input type="number" name="newMonitor[AnalysisFPSLimit]" value="<?php echo validHtmlStr($monitor->AnalysisFPSLimit()) ?>" min="0" step="any" class="input input-bordered w-full max-w-lg"/>
  </div>

<?php if ( $monitor->Type() != 'Local' && $monitor->Type() != 'File' && $monitor->Type() != 'NVSocket' ) : ?>
  <div class="form-control w-full">
    <label class="label">
      <span class="label-text font-medium"><?php echo translate('MaximumFPS') ?></span>
    </label>
    <input type="number" name="newMonitor[MaxFPS]" value="<?php echo validHtmlStr($monitor->MaxFPS()) ?>" min="0" step="any" class="input input-bordered w-full max-w-lg"/>
    <span id="newMonitor[MaxFPS]" class="text-error text-sm mt-1 <?php echo $monitor->MaxFPS() ? '' : 'hidden' ?>">CAUTION: See the help text</span>
  </div>

  <div class="form-control w-full">
    <label class="label">
      <span class="label-text font-medium"><?php echo translate('AlarmMaximumFPS') ?></span>
    </label>
    <input type="number" name="newMonitor[AlarmMaxFPS]" value="<?php echo validHtmlStr($monitor->AlarmMaxFPS()) ?>" min="0" step="any" class="input input-bordered w-full max-w-lg"/>
    <span id="newMonitor[AlarmMaxFPS]" class="text-error text-sm mt-1 <?php echo $monitor->AlarmMaxFPS() ? '' : 'hidden' ?>">CAUTION: See the help text</span>
  </div>
<?php else : ?>
  <div class="form-control w-full">
    <label class="label">
      <span class="label-text font-medium"><?php echo translate('MaximumFPS') ?></span>
    </label>
    <input type="number" name="newMonitor[MaxFPS]" value="<?php echo validHtmlStr($monitor->MaxFPS()) ?>" min="0" step="any" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label">
      <span class="label-text font-medium"><?php echo translate('AlarmMaximumFPS') ?></span>
    </label>
    <input type="number" name="newMonitor[AlarmMaxFPS]" value="<?php echo validHtmlStr($monitor->AlarmMaxFPS()) ?>" min="0" step="any" class="input input-bordered w-full max-w-lg"/>
  </div>
<?php endif; ?>

<?php if ( ZM_FAST_IMAGE_BLENDS ) : ?>
  <div class="form-control w-full">
    <label class="label">
      <span class="label-text font-medium"><?php echo translate('RefImageBlendPct') ?></span>
    </label>
    <select name="newMonitor[RefBlendPerc]" class="select select-bordered w-full max-w-lg">
<?php foreach ($fastblendopts as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->RefBlendPerc() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>

  <div class="form-control w-full">
    <label class="label">
      <span class="label-text font-medium"><?php echo translate('AlarmRefImageBlendPct') ?></span>
    </label>
    <select name="newMonitor[AlarmRefBlendPerc]" class="select select-bordered w-full max-w-lg">
<?php foreach ($fastblendopts_alarm as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->AlarmRefBlendPerc() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>
<?php else : ?>
  <div class="form-control w-full">
    <label class="label">
      <span class="label-text font-medium"><?php echo translate('RefImageBlendPct') ?></span>
    </label>
    <input type="text" name="newMonitor[RefBlendPerc]" value="<?php echo validHtmlStr($monitor->RefBlendPerc()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label">
      <span class="label-text font-medium"><?php echo translate('AlarmRefImageBlendPct') ?></span>
    </label>
    <input type="text" name="newMonitor[AlarmRefBlendPerc]" value="<?php echo validHtmlStr($monitor->AlarmRefBlendPerc()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>
<?php endif; ?>

  <div class="form-control w-full">
    <label class="label">
      <span class="label-text font-medium"><?php echo translate('Triggers') ?></span>
    </label>
    <div class="flex flex-wrap gap-4">
<?php
$optTriggers = getSetValues('Monitors', 'Triggers');
$currentTriggers = ('' !== $monitor->Triggers()) ? $monitor->Triggers() : array();
$hasOptions = false;
foreach ( $optTriggers as $optTrigger ) {
  if ( $optTrigger == 'X10' and !ZM_OPT_X10 ) continue;
  $hasOptions = true;
?>
      <label class="label cursor-pointer gap-2">
        <input type="checkbox" name="newMonitor[Triggers][]" value="<?php echo $optTrigger ?>" class="checkbox checkbox-primary checkbox-sm" <?php echo in_array($optTrigger, $currentTriggers) ? 'checked' : '' ?>/>
        <span class="label-text"><?php echo $optTrigger ?></span>
      </label>
<?php
}
if ( !$hasOptions ) {
  echo '<em class="opacity-70">'.translate('NoneAvailable').'</em>';
}
?>
    </div>
  </div>
<?php endif; ?>
</div>
