<?php
//
// ZoneMinder Modern Skin - Monitor Storage Tab
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

$storage_areas = array(0=>'Default');
foreach ( ZM\Storage::find(array('Enabled'=>true), array('order'=>'lower(Name)')) as $Storage ) {
  $storage_areas[$Storage->Id()] = $Storage->Name();
}

$savejpegopts = array(
  0 => translate('Disabled'),
  1 => translate('Frames only'),
  2 => translate('Analysis images only (if available)'),
  3 => translate('Frames + Analysis images (if available)'),
);

$videowriteropts = array(
  0 => translate('Disabled'),
  1 => translate('Encode'),
);
if ( $monitor->Type() == 'Ffmpeg' ) {
  $videowriteropts[2] = translate('Camera Passthrough');
} else {
  $videowriteropts[2] = translate('Camera Passthrough - only for FFMPEG');
}

$videowriter_codecs = array(
  '0' => translate('Auto'),
  '27' => 'h264',
  '173' => 'h265/hevc',
  '226' => 'av1',
);

$videowriter_encoders = array(
  'auto' => translate('Auto'),
  'libx264' => 'libx264',
  'h264' => 'h264',
  'h264_nvenc' => 'h264_nvenc',
  'h264_omx' => 'h264_omx',
  'h264_vaapi' => 'h264_vaapi',
  'libx265' => 'libx265',
  'hevc_nvenc' => 'hevc_nvenc',
  'hevc_vaapi' => 'hevc_vaapi',
  'libaom-av1' => 'libaom-av1',
);

$videowriter_containers = array(
  '' => translate('Auto'),
  'mp4' => 'mp4',
  'mkv' => 'mkv',
);
?>
<div class="space-y-4">
  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('StorageArea') ?></span></label>
    <select name="newMonitor[StorageId]" class="select select-bordered w-full max-w-lg">
<?php foreach ($storage_areas as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->StorageId() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('SaveJPEGs') ?></span></label>
    <select name="newMonitor[SaveJPEGs]" class="select select-bordered w-full max-w-lg">
<?php foreach ($savejpegopts as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->SaveJPEGs() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('VideoWriter') ?></span></label>
    <select name="newMonitor[VideoWriter]" class="select select-bordered w-full max-w-lg">
<?php foreach ($videowriteropts as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->VideoWriter() == $val) ? 'selected' : '' ?> <?php echo ($val == 2 && $monitor->Type() != 'Ffmpeg') ? 'disabled' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>

  <div class="form-control w-full OutputCodec">
    <label class="label"><span class="label-text font-medium"><?php echo translate('OutputCodec') ?></span></label>
    <select name="newMonitor[OutputCodec]" class="select select-bordered w-full max-w-lg">
<?php foreach ($videowriter_codecs as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->OutputCodec() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>

  <div class="form-control w-full Encoder">
    <label class="label"><span class="label-text font-medium"><?php echo translate('Encoder') ?></span></label>
    <select name="newMonitor[Encoder]" class="select select-bordered w-full max-w-lg">
<?php foreach ($videowriter_encoders as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->Encoder() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>

  <div class="form-control w-full OutputContainer">
    <label class="label"><span class="label-text font-medium"><?php echo translate('OutputContainer') ?></span></label>
    <select name="newMonitor[OutputContainer]" class="select select-bordered w-full max-w-lg">
<?php foreach ($videowriter_containers as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->OutputContainer() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>

  <div class="form-control w-full OptionalEncoderParam">
    <label class="label"><span class="label-text font-medium"><?php echo translate('OptionalEncoderParam') ?></span></label>
    <textarea name="newMonitor[EncoderParameters]" rows="<?php echo count(explode("\n", $monitor->EncoderParameters()))+2; ?>" class="textarea textarea-bordered w-full max-w-lg"><?php echo validHtmlStr($monitor->EncoderParameters()) ?></textarea>
  </div>

  <div class="form-control w-full RecordAudio">
    <label class="label cursor-pointer justify-start gap-4">
<?php if ( $monitor->Type() == 'Ffmpeg' ) : ?>
      <input type="checkbox" name="newMonitor[RecordAudio]" value="1" class="checkbox checkbox-primary" <?php echo $monitor->RecordAudio() ? 'checked' : '' ?>/>
      <span class="label-text font-medium"><?php echo translate('RecordAudio') ?></span>
<?php else : ?>
      <input type="hidden" name="newMonitor[RecordAudio]" value="<?php echo $monitor->RecordAudio() ? 1 : 0 ?>"/>
      <span class="label-text opacity-70"><?php echo translate('Audio recording only available with FFMPEG') ?></span>
<?php endif; ?>
    </label>
  </div>
</div>
