<?php
//
// ZoneMinder Modern Skin - Monitor Source Tab
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

$resolutions = array(
  ''=>translate('Custom'),
  '176x120'=>'176x120 QCIF',
  '176x144'=>'176x144',
  '320x240'=>'320x240',
  '320x200'=>'320x200',
  '352x240'=>'352x240 CIF',
  '352x480'=>'352x480',
  '640x360'=>'640x360',
  '640x400'=>'640x400',
  '640x480'=>'640x480',
  '704x240'=>'704x240 2CIF',
  '704x480'=>'704x480 4CIF',
  '704x576'=>'704x576 D1 PAL',
  '720x480'=>'720x480 Full D1 NTSC',
  '720x576'=>'720x576 Full D1 PAL',
  '1280x720'=>'1280x720 720p',
  '1280x800'=>'1280x800',
  '1280x960'=>'1280x960 960p',
  '1280x1024'=>'1280x1024 1MP',
  '1600x1200'=>'1600x1200 2MP',
  '1920x1080'=>'1920x1080 1080p',
  '2048x1536'=>'2048x1536 3MP',
  '2560x1440'=>'2560x1440 1440p QHD WQHD',
  '2592x1944'=>'2592x1944 5MP',
  '2688x1520'=>'2688x1520 4MP',
  '3072x2048'=>'3072x2048 6MP',
  '3840x2160'=>'3840x2160 4K UHD',
);
$selectedResolution = '';
if ( $monitor->Width() && $monitor->Height() ) {
  $selectedResolution = $monitor->Width().'x'.$monitor->Height();
  if ( !isset($resolutions[$selectedResolution]) ) {
    $resolutions[$selectedResolution] = $selectedResolution;
  }
}
?>
<div class="space-y-4">
<?php if ( ZM_HAS_V4L && $monitor->Type() == 'Local' ) : ?>
  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('DevicePath') ?></span></label>
    <input type="text" name="newMonitor[Device]" value="<?php echo validHtmlStr($monitor->Device()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('CaptureMethod') ?></span></label>
    <select name="newMonitor[Method]" class="select select-bordered w-full max-w-lg" data-tab-name="<?php echo $tab ?>">
<?php foreach ($localMethods as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->Method() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>

<?php if ( ZM_HAS_V4L1 && $monitor->Method() == 'v4l1' ) : ?>
  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('DeviceChannel') ?></span></label>
    <select name="newMonitor[Channel]" class="select select-bordered w-full max-w-lg">
<?php foreach ($v4l1DeviceChannels as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->Channel() == $val) ? 'selected' : '' ?>><?php echo $label ?></option>
<?php endforeach; ?>
    </select>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('DeviceFormat') ?></span></label>
    <select name="newMonitor[Format]" class="select select-bordered w-full max-w-lg">
<?php foreach ($v4l1DeviceFormats as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->Format() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('CapturePalette') ?></span></label>
    <select name="newMonitor[Palette]" class="select select-bordered w-full max-w-lg">
<?php foreach ($v4l1LocalPalettes as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->Palette() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>
<?php else : ?>
  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('DeviceChannel') ?></span></label>
    <select name="newMonitor[Channel]" class="select select-bordered w-full max-w-lg">
<?php foreach ($v4l2DeviceChannels as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->Channel() == $val) ? 'selected' : '' ?>><?php echo $label ?></option>
<?php endforeach; ?>
    </select>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('DeviceFormat') ?></span></label>
    <select name="newMonitor[Format]" class="select select-bordered w-full max-w-lg">
<?php foreach ($v4l2DeviceFormats as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->Format() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('CapturePalette') ?></span></label>
    <select name="newMonitor[Palette]" class="select select-bordered w-full max-w-lg">
<?php foreach ($v4l2LocalPalettes as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->Palette() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>
<?php endif; ?>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('V4LMultiBuffer') ?></span></label>
    <div class="flex flex-wrap gap-4">
      <label class="label cursor-pointer gap-2">
        <input type="radio" name="newMonitor[V4LMultiBuffer]" value="1" class="radio radio-primary" <?php echo ($monitor->V4LMultiBuffer() == '1') ? 'checked' : '' ?>/>
        <span class="label-text">Yes</span>
      </label>
      <label class="label cursor-pointer gap-2">
        <input type="radio" name="newMonitor[V4LMultiBuffer]" value="0" class="radio radio-primary" <?php echo ($monitor->V4LMultiBuffer() == '0') ? 'checked' : '' ?>/>
        <span class="label-text">No</span>
      </label>
      <label class="label cursor-pointer gap-2">
        <input type="radio" name="newMonitor[V4LMultiBuffer]" value="" class="radio radio-primary" <?php echo ($monitor->V4LMultiBuffer() == '') ? 'checked' : '' ?>/>
        <span class="label-text">Use Config Value</span>
      </label>
    </div>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('V4LCapturesPerFrame') ?></span></label>
    <input type="number" name="newMonitor[V4LCapturesPerFrame]" value="<?php echo validHtmlStr($monitor->V4LCapturesPerFrame()) ?>" min="1" class="input input-bordered w-full max-w-lg"/>
  </div>

<?php elseif ( $monitor->Type() == 'NVSocket' ) : ?>
  <?php include('_monitor_source_nvsocket.php'); ?>

<?php elseif ( $monitor->Type() == 'VNC' ) : ?>
  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('RemoteHostName') ?></span></label>
    <input type="text" name="newMonitor[Host]" value="<?php echo validHtmlStr($monitor->Host()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('RemoteHostPort') ?></span></label>
    <input type="number" name="newMonitor[Port]" value="<?php echo validHtmlStr($monitor->Port()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('Username') ?></span></label>
    <input type="text" name="newMonitor[User]" value="<?php echo validHtmlStr($monitor->User()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('Password') ?></span></label>
    <input type="text" name="newMonitor[Pass]" value="<?php echo validHtmlStr($monitor->Pass()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

<?php elseif ( $monitor->Type() == 'Remote' ) : ?>
  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('RemoteProtocol') ?></span></label>
    <select name="newMonitor[Protocol]" id="newMonitorProtocol" class="select select-bordered w-full max-w-lg">
<?php foreach ($remoteProtocols as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->Protocol() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('RemoteMethod') ?></span></label>
<?php if ( !$monitor->Protocol() || $monitor->Protocol() == 'http' ) : ?>
    <select name="newMonitor[Method]" class="select select-bordered w-full max-w-lg">
<?php foreach ($httpMethods as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->Method() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
<?php else : ?>
    <select name="newMonitor[Method]" class="select select-bordered w-full max-w-lg">
<?php foreach ($rtspMethods as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->Method() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
<?php endif; ?>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('RemoteHostName') ?></span></label>
    <input type="text" name="newMonitor[Host]" value="<?php echo validHtmlStr($monitor->Host()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('RemoteHostPort') ?></span></label>
    <input type="number" name="newMonitor[Port]" value="<?php echo validHtmlStr($monitor->Port()) ?>" min="0" max="65535" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('RemoteHostPath') ?></span></label>
    <input type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($monitor->Path()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

<?php elseif ( $monitor->Type() == 'File' ) : ?>
  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('SourcePath') ?></span></label>
    <input type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($monitor->Path()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

<?php elseif ( $monitor->Type() == 'cURL' ) : ?>
  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium">URL</span></label>
    <input type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($monitor->Path()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium">Username</span></label>
    <input type="text" name="newMonitor[User]" value="<?php echo validHtmlStr($monitor->User()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium">Password</span></label>
    <input type="text" name="newMonitor[Pass]" value="<?php echo validHtmlStr($monitor->Pass()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

<?php elseif ( $monitor->Type() == 'WebSite' ) : ?>
  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('WebSiteUrl') ?></span></label>
    <input type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($monitor->Path()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('Width') ?> (<?php echo translate('Pixels') ?>)</span></label>
    <input type="number" name="newMonitor[Width]" value="<?php echo validHtmlStr($monitor->Width()) ?>" min="1" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('Height') ?> (<?php echo translate('Pixels') ?>)</span></label>
    <input type="number" name="newMonitor[Height]" value="<?php echo validHtmlStr($monitor->Height()) ?>" min="1" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium">Web Site Refresh (Optional)</span></label>
    <input type="number" name="newMonitor[Refresh]" value="<?php echo validHtmlStr($monitor->Refresh()) ?>" min="1" class="input input-bordered w-full max-w-lg"/>
  </div>

<?php elseif ( $monitor->Type() == 'Ffmpeg' || $monitor->Type() == 'Libvlc' ) : ?>
  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('SourcePath') ?></span></label>
    <input type="text" name="newMonitor[Path]" value="<?php echo validHtmlStr($monitor->Path()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('RemoteMethod') ?></span></label>
    <select name="newMonitor[Method]" class="select select-bordered w-full max-w-lg">
<?php foreach ($rtspFFMpegMethods as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->Method() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('Options') ?></span></label>
    <input type="text" name="newMonitor[Options]" value="<?php echo validHtmlStr($monitor->Options()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>
<?php endif; ?>

<?php if ( $monitor->Type() == 'Ffmpeg' ) : ?>
  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('SourceSecondPath') ?></span></label>
    <input type="text" name="newMonitor[SecondPath]" value="<?php echo validHtmlStr($monitor->SecondPath()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('DecoderHWAccelName') ?></span></label>
    <input type="text" name="newMonitor[DecoderHWAccelName]" value="<?php echo validHtmlStr($monitor->DecoderHWAccelName()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('DecoderHWAccelDevice') ?></span></label>
    <input type="text" name="newMonitor[DecoderHWAccelDevice]" value="<?php echo validHtmlStr($monitor->DecoderHWAccelDevice()) ?>" class="input input-bordered w-full max-w-lg"/>
  </div>
<?php endif; ?>

<?php if ( $monitor->Type() != 'NVSocket' && $monitor->Type() != 'WebSite' ) : ?>
  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('TargetColorspace') ?></span></label>
    <select name="newMonitor[Colours]" class="select select-bordered w-full max-w-lg">
<?php foreach ($Colours as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->Colours() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('CaptureResolution') ?> (<?php echo translate('Pixels') ?>)</span></label>
    <div class="flex flex-wrap gap-2 items-center">
      <input type="number" name="newMonitor[Width]" value="<?php echo validHtmlStr($monitor->Width()) ?>" min="1" class="input input-bordered w-24"/>
      <span>x</span>
      <input type="number" name="newMonitor[Height]" value="<?php echo validHtmlStr($monitor->Height()) ?>" min="1" class="input input-bordered w-24"/>
      <select name="dimensions_select" class="select select-bordered flex-1 max-w-xs">
<?php foreach ($resolutions as $val => $label) : ?>
        <option value="<?php echo $val ?>" <?php echo ($selectedResolution == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="form-control w-full">
    <label class="label cursor-pointer justify-start gap-4">
      <input type="checkbox" name="preserveAspectRatio" value="1" class="checkbox checkbox-primary"/>
      <span class="label-text font-medium"><?php echo translate('PreserveAspect') ?></span>
    </label>
  </div>

  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('Orientation') ?></span></label>
    <select name="newMonitor[Orientation]" class="select select-bordered w-full max-w-lg">
<?php foreach ($orientations as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->Orientation() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>
<?php endif; ?>

<?php if ( $monitor->Type() == 'Local' ) : ?>
  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('Deinterlacing') ?></span></label>
    <select name="newMonitor[Deinterlacing]" class="select select-bordered w-full max-w-lg">
<?php foreach ($deinterlaceopts_v4l2 as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->Deinterlacing() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>
<?php elseif ( $monitor->Type() != 'WebSite' ) : ?>
  <div class="form-control w-full">
    <label class="label"><span class="label-text font-medium"><?php echo translate('Deinterlacing') ?></span></label>
    <select name="newMonitor[Deinterlacing]" class="select select-bordered w-full max-w-lg">
<?php foreach ($deinterlaceopts as $val => $label) : ?>
      <option value="<?php echo $val ?>" <?php echo ($monitor->Deinterlacing() == $val) ? 'selected' : '' ?>><?php echo htmlspecialchars($label) ?></option>
<?php endforeach; ?>
    </select>
  </div>
<?php endif; ?>

<?php if ( $monitor->Type() == 'Remote' ) : ?>
  <div class="form-control w-full" id="RTSPDescribe" <?php if ($monitor->Protocol() != 'rtsp') echo 'style="display:none;"' ?>>
    <label class="label cursor-pointer justify-start gap-4">
      <input type="checkbox" name="newMonitor[RTSPDescribe]" value="1" class="checkbox checkbox-primary" <?php echo $monitor->RTSPDescribe() ? 'checked' : '' ?>/>
      <span class="label-text font-medium"><?php echo translate('RTSPDescribe') ?></span>
    </label>
  </div>
<?php endif; ?>
</div>
