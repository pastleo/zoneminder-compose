<?php
//
// ZoneMinder web monitor view file, $Date$, $Revision$
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

require_once('includes/Server.php');
require_once('includes/Storage.php');

if ( !canEdit('Monitors', empty($_REQUEST['mid'])?0:$_REQUEST['mid']) ) {
  $view = 'error';
  return;
}

$Server = null;
if ( defined('ZM_SERVER_ID') ) {
  $Server = dbFetchOne('SELECT * FROM Servers WHERE Id=?', NULL, array(ZM_SERVER_ID));
}
if ( !$Server ) {
  $Server = array('Id' => '');
}
$mid = null;
$monitor = null;
if ( !empty($_REQUEST['mid']) ) {
  $mid = validInt($_REQUEST['mid']);
  $monitor = new ZM\Monitor($mid);
  if ( $monitor and ZM_OPT_X10 )
    $x10Monitor = dbFetchOne('SELECT * FROM TriggersX10 WHERE MonitorId = ?', NULL, array($mid));
}

if ( !$monitor ) {
  $monitor = new ZM\Monitor();
  $monitor->Name(translate('Monitor').'-'.getTableAutoInc('Monitors'));
  $monitor->WebColour(random_colour());
} # end if $_REQUEST['mid']

if ( isset($_REQUEST['dupId']) ) {
  $monitor = new ZM\Monitor($_REQUEST['dupId']);
  $monitor->GroupIds(); // have to load before we change the Id
  if ( ZM_OPT_X10 )
    $x10Monitor = dbFetchOne('SELECT * FROM TriggersX10 WHERE MonitorId = ?', NULL, array($_REQUEST['dupId']));
  $clonedName = $monitor->Name();
  $monitor->Name('Clone of '.$monitor->Name());
  $monitor->Id($mid);
}

if ( ZM_OPT_X10 && empty($x10Monitor) ) {
  $x10Monitor = array(
      'Activation' => '',
      'AlarmInput' => '',
      'AlarmOutput' => '',
      );
}

function fourcc($a, $b, $c, $d) {
  return ord($a) | (ord($b) << 8) | (ord($c) << 16) | (ord($d) << 24);
}
if ( isset($_REQUEST['newMonitor']) ) {
  # Update the monitor object with whatever has been set so far.
  $monitor->set($_REQUEST['newMonitor']);

  if ( ZM_OPT_X10 )
    $newX10Monitor = $_REQUEST['newX10Monitor'];
} else {
  if ( ZM_OPT_X10 )
    $newX10Monitor = $x10Monitor;
}

# What if it has less zeros?  This is not robust code.
if ( $monitor->AnalysisFPSLimit() == '0.00' )
  $monitor->AnalysisFPSLimit('');
if ( $monitor->MaxFPS() == '0.00' )
  $monitor->MaxFPS('');
if ( $monitor->AlarmMaxFPS() == '0.00' )
  $monitor->AlarmMaxFPS('');

if ( !empty($_REQUEST['preset']) ) {
  $preset = dbFetchOne( 'SELECT Type, Device, Channel, Format, Protocol, Method, Host, Port, Path, Width, Height, Palette, MaxFPS, Controllable, ControlId, ControlDevice, ControlAddress, DefaultRate, DefaultScale FROM MonitorPresets WHERE Id = ?', NULL, array($_REQUEST['preset']) );
  foreach ( $preset as $name=>$value ) {
    # Does isset handle NULL's?  I don't think this code is correct.
    # Icon: It does, but this means we can't set a null value.
    if ( isset($value) ) {
      $monitor->$name($value);
    }
  }
} # end if preset

if ( !empty($_REQUEST['probe']) ) {
  $probe = json_decode(base64_decode($_REQUEST['probe']));
  foreach ( $probe as $name=>$value ) {
    if ( isset($value) ) {
      # Does isset handle NULL's?  I don't think this code is correct.
      $monitor->$name = urldecode($value);
    }
  }
  if ( ZM_HAS_V4L && $monitor->Type() == 'Local' ) {
    $monitor->Palette( fourCC( substr($monitor->Palette,0,1), substr($monitor->Palette,1,1), substr($monitor->Palette,2,1), substr($monitor->Palette,3,1) ) );
    if ( $monitor->Format() == 'PAL' )
      $monitor->Format( 0x000000ff );
    elseif ( $monitor->Format() == 'NTSC' )
      $monitor->Format( 0x0000b000 );
  }
} # end if apply probe settings

$sourceTypes = array(
    'Local'  => translate('Local'),
    'Remote' => translate('Remote'),
    'File'   => translate('File'),
    'Ffmpeg' => translate('Ffmpeg'),
    'Libvlc' => translate('Libvlc'),
    'cURL'   => 'cURL (HTTP(S) only)',
    'WebSite'=> 'Web Site',
    'NVSocket'	=> translate('NVSocket'),
    'VNC' => translate('VNC'),
    );
if ( !ZM_HAS_V4L )
  unset($sourceTypes['Local']);

$localMethods = array(
    'v4l2' => 'Video For Linux version 2',
    'v4l1' => 'Video For Linux version 1',
    );

if ( !ZM_HAS_V4L2 )
  unset($localMethods['v4l2']);
if ( !ZM_HAS_V4L1 )
  unset($localMethods['v4l1']);

$remoteProtocols = array(
    'http' => 'HTTP',
    'rtsp' => 'RTSP'
    );

$rtspMethods = array(
    'rtpUni'      => 'RTP/Unicast',
    'rtpMulti'    => 'RTP/Multicast',
    'rtpRtsp'     => 'RTP/RTSP',
    'rtpRtspHttp' => 'RTP/RTSP/HTTP'
    );

$rtspFFMpegMethods = array(
    'rtpRtsp'     => 'TCP',
    'rtpUni'      => 'UDP',
    'rtpMulti'    => 'UDP Multicast',
    'rtpRtspHttp' => 'HTTP Tunnel'
    );

$httpMethods = array(
    'simple'   => 'Simple',
    'regexp'   => 'Regexp',
    'jpegTags' => 'JPEG Tags'
    );

if ( !ZM_PCRE )
  unset($httpMethods['regexp']);
  // Currently unsupported
unset($httpMethods['jpegTags']);

if ( ZM_HAS_V4L1 ) {
  $v4l1DeviceFormats = array(
      0 => 'PAL',
      1 => 'NTSC',
      2 => 'SECAM',
      3 => 'AUTO',
      4 => 'FMT4',
      5 => 'FMT5',
      6 => 'FMT6',
      7 => 'FMT7'
      );

  $v4l1MaxChannels = 15;
  $v4l1DeviceChannels = array();
  for ( $i = 0; $i <= $v4l1MaxChannels; $i++ )
    $v4l1DeviceChannels[$i] = $i;

  $v4l1LocalPalettes = array(
      1  => translate('Grey'),
      5  => 'BGR32',
      4  => 'BGR24',
      8  => '*YUYV',
      3  => '*RGB565',
      6  => '*RGB555',
      7  => '*YUV422',
      13 => '*YUV422P',
      15 => '*YUV420P',
      );
}

if ( ZM_HAS_V4L2 ) {
  $v4l2DeviceFormats = array(
    0x000000ff => 'PAL',
    0x0000b000 => 'NTSC',
    0x00000001 => 'PAL B',
    0x00000002 => 'PAL B1',
    0x00000004 => 'PAL G',
    0x00000008 => 'PAL H',
    0x00000010 => 'PAL I',
    0x00000020 => 'PAL D',
    0x00000040 => 'PAL D1',
    0x00000080 => 'PAL K',
    0x00000100 => 'PAL M',
    0x00000200 => 'PAL N',
    0x00000400 => 'PAL Nc',
    0x00000800 => 'PAL 60',
    0x00001000 => 'NTSC M',
    0x00002000 => 'NTSC M JP',
    0x00004000 => 'NTSC 443',
    0x00008000 => 'NTSC M KR',
    0x00010000 => 'SECAM B',
    0x00020000 => 'SECAM D',
    0x00040000 => 'SECAM G',
    0x00080000 => 'SECAM H',
    0x00100000 => 'SECAM K',
    0x00200000 => 'SECAM K1',
    0x00400000 => 'SECAM L',
    0x00800000 => 'SECAM LC',
    0x01000000 => 'ATSC 8 VSB',
    0x02000000 => 'ATSC 16 VSB',
      );

  $v4l2MaxChannels = 31;
  $v4l2DeviceChannels = array();
  for ( $i = 0; $i <= $v4l2MaxChannels; $i++ )
    $v4l2DeviceChannels[$i] = $i;

  $v4l2LocalPalettes = array(
      0 => 'Auto', /* Automatic palette selection */

      /*  FOURCC              =>  Pixel format         depth  Description  */
      fourcc('G','R','E','Y') =>  translate('Grey'), /*  8  Greyscale     */
      fourcc('B','G','R','4') => 'BGR32', /* 32  BGR-8-8-8-8   */
      fourcc('R','G','B','4') => 'RGB32', /* 32  RGB-8-8-8-8   */
      fourcc('B','G','R','3') => 'BGR24', /* 24  BGR-8-8-8     */
      fourcc('R','G','B','3') => 'RGB24', /* 24  RGB-8-8-8     */
      fourcc('Y','U','Y','V') => '*YUYV', /* 16  YUV 4:2:2     */

      /* compressed formats */
      fourcc('J','P','E','G') => '*JPEG',  /* JFIF JPEG     */
      fourcc('M','J','P','G') => '*MJPEG', /* Motion-JPEG   */
      // fourcc('d','v','s','d') => 'DV',  /* 1394          */
      // fourcc('M','P','E','G') => 'MPEG', /* MPEG-1/2/4    */

      //
      fourcc('R','G','B','1') =>  'RGB332', /*  8  RGB-3-3-2     */
      fourcc('R','4','4','4') => '*RGB444', /* 16  xxxxrrrr ggggbbbb */
      fourcc('R','G','B','O') => '*RGB555', /* 16  RGB-5-5-5     */
      fourcc('R','G','B','P') => '*RGB565', /* 16  RGB-5-6-5     */
      // fourcc('R','G','B','Q') => 'RGB555X', /* 16  RGB-5-5-5 BE  */
      // fourcc('R','G','B','R') => 'RGB565X', /* 16  RGB-5-6-5 BE  */
      // fourcc('Y','1','6','')  => 'Y16',     /* 16  Greyscale     */
      // fourcc('P','A','L','8') => 'PAL8',    /*  8  8-bit palette */
      // fourcc('Y','V','U','9') => 'YVU410',  /*  9  YVU 4:1:0     */
      // fourcc('Y','V','1','2') => 'YVU420',  /* 12  YVU 4:2:0     */

      fourcc('U','Y','V','Y') => '*UYVY',      /* 16  YUV 4:2:2     */
      fourcc('4','2','2','P') => '*YUV422P',   /* 16  YVU422 planar */
      fourcc('4','1','1','P') => '*YUV411P',   /* 16  YVU411 planar */
      // fourcc('Y','4','1','P') => 'Y41P',    /* 12  YUV 4:1:1     */
      fourcc('Y','4','4','4') => '*YUV444',    /* 16  xxxxyyyy uuuuvvvv */
      // fourcc('Y','U','V','O') => 'YUV555',  /* 16  YUV-5-5-5     */
      // fourcc('Y','U','V','P') => 'YUV565',  /* 16  YUV-5-6-5     */
      // fourcc('Y','U','V','4') => 'YUV32',   /* 32  YUV-8-8-8-8   */

      /* two planes -- one Y, one Cr + Cb interleaved  */
      fourcc('N','V','1','2') => 'NV12', /* 12  Y/CbCr 4:2:0  */
      // fourcc('N','V','2','1') => 'NV21', /* 12  Y/CrCb 4:2:0  */

      /*  The following formats are not defined in the V4L2 specification */
      fourcc('Y','U','V','9') => '*YUV410', /*  9  YUV 4:1:0     */
      fourcc('Y','U','1','2') => '*YUV420', /* 12  YUV 4:2:0     */
      // fourcc('Y','Y','U','V') => 'YYUV', /* 16  YUV 4:2:2     */
      // fourcc('H','I','2','4') => 'HI240',   /*  8  8-bit color   */
      // fourcc('H','M','1','2') => 'HM12',  /*  8  YUV 4:2:0 16x16 macroblocks */

      /* see http://www.siliconimaging.com/RGB%20Bayer.htm */
      // fourcc('B','A','8','1') => 'SBGGR8', /*  8  BGBG.. GRGR.. */
      // fourcc('G','B','R','G') => 'SGBRG8', /*  8  GBGB.. RGRG.. */
      // fourcc('B','Y','R','2') => 'SBGGR16', /* 16  BGBG.. GRGR.. */

      /*  Vendor-specific formats   */
      //'WNVA' =>     fourcc('W','N','V','A'), /* Winnov hw compress */
      //'SN9C10X' =>  fourcc('S','9','1','0'), /* SN9C10x compression */
      //'PWC1' =>     fourcc('P','W','C','1'), /* pwc older webcam */
      //'PWC2' =>     fourcc('P','W','C','2'), /* pwc newer webcam */
      //'ET61X251' => fourcc('E','6','2','5'), /* ET61X251 compression */
      //'SPCA501' =>  fourcc('S','5','0','1'), /* YUYV per line */
      //'SPCA505' =>  fourcc('S','5','0','5'), /* YYUV per line */
      //'SPCA508' =>  fourcc('S','5','0','8'), /* YUVY per line */
      //'SPCA561' =>  fourcc('S','5','6','1'), /* compressed GBRG bayer */
      //'PAC207' =>   fourcc('P','2','0','7'), /* compressed BGGR bayer */
      //'PJPG' =>     fourcc('P','J','P','G'), /* Pixart 73xx JPEG */
      //'YVYU' =>     fourcc('Y','V','Y','U'), /* 16  YVU 4:2:2     */
      );
}

$Colours = array(
    '1' => translate('8BitGrey'),
    '3' => translate('24BitColour'),
    '4' => translate('32BitColour')
    );

$orientations = array(
    'ROTATE_0' => translate('Normal'),
    'ROTATE_90' => translate('RotateRight'),
    'ROTATE_180' => translate('Inverted'),
    'ROTATE_270' => translate('RotateLeft'),
    'FLIP_HORI' => translate('FlippedHori'),
    'FLIP_VERT' => translate('FlippedVert')
    );

$deinterlaceopts = array(
  0x00000000 => translate('Disabled'),
  0x00001E04 => translate('Four field motion adaptive - Soft'), /* 30 change */
  0x00001404 => translate('Four field motion adaptive - Medium'), /* 20 change */
  0x00000A04 => translate('Four field motion adaptive - Hard'), /* 10 change */
  0x00000001 => translate('Discard'),
  0x00000002 => translate('Linear'),
  0x00000003 => translate('Blend'),
  0x00000205 => translate('Blend (25%)'),
);

$deinterlaceopts_v4l2 = array(
  0x00000000 => 'Disabled',
  0x00001E04 => 'Four field motion adaptive - Soft',   /* 30 change */
  0x00001404 => 'Four field motion adaptive - Medium', /* 20 change */
  0x00000A04 => 'Four field motion adaptive - Hard',   /* 10 change */
  0x00000001 => 'Discard',
  0x00000002 => 'Linear',
  0x00000003 => 'Blend',
  0x00000205 => 'Blend (25%)',
  0x02000000 => 'V4L2: Capture top field only',
  0x03000000 => 'V4L2: Capture bottom field only',
  0x07000000 => 'V4L2: Alternate fields (Bob)',
  0x01000000 => 'V4L2: Progressive',
  0x04000000 => 'V4L2: Interlaced',
);

$fastblendopts = array(
    0  => translate ('No blending'),
    1  => '1.5625%',
    3  => '3.125%',
    6  => translate('6.25% (Indoor)'),
    12 => translate('12.5% (Outdoor)'),
    25 => '25%',
    50 => '50%',
    );

$fastblendopts_alarm = array(
    0  => translate('No blending (Alarm lasts forever)'),
    1  => '1.5625%',
    3  => '3.125%',
    6  => '6.25%',
    12 => '12.5%',
    25 => '25%',
    50 => translate('50% (Alarm lasts a moment)'),
    );

$label_size = array(
    1 => translate('Small'),
    2 => translate('Default'),
    3 => translate('Large'),
    4 => translate('Extra Large'),
    );

$codecs = array(
  'auto'  => translate('Auto'),
  'MP4'  => translate('MP4'),
  'MJPEG' => translate('MJPEG'),
);

$controls = ZM\Control::find(null, array('order'=>'lower(Name)'));

// Build tabs array
$tabs = array();
$tabs['general'] = translate('General');
$tabs['source'] = translate('Source');
$tabs['onvif'] = translate('ONVIF');
if ( $monitor->Type() != 'WebSite' ) {
  $tabs['storage'] = translate('Storage');
  $tabs['timestamp'] = translate('Timestamp');
  $tabs['buffers'] = translate('Buffers');
  if ( ZM_OPT_CONTROL && canView('Control') )
    $tabs['control'] = translate('Control');
  if ( ZM_OPT_X10 )
    $tabs['x10'] = translate('X10');
  $tabs['misc'] = translate('Misc');
  if (defined('ZM_OPT_USE_GEOLOCATION') and ZM_OPT_USE_GEOLOCATION)
    $tabs['location'] = translate('Location');
}

if ( isset($_REQUEST['tab']) )
  $tab = validHtmlStr($_REQUEST['tab']);
else
  $tab = 'general';

xhtmlHeaders(__FILE__, translate('Monitor').' - '.validHtmlStr($monitor->Name()));
getBodyTopHTML();
echo getNavBarHTML();
?>

<div class="drawer lg:drawer-open">
  <input id="monitor-drawer" type="checkbox" class="drawer-toggle" />
  
  <div class="drawer-content flex flex-col">
    <!-- Mobile header for drawer toggle -->
    <div class="lg:hidden flex items-center gap-2 p-4 bg-base-200 border-b border-base-300">
      <label for="monitor-drawer" class="btn btn-square btn-ghost">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-6 h-6 stroke-current">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
      </label>
      <h1 class="text-xl font-bold"><?php echo translate('Monitor') ?> - <?php echo validHtmlStr($monitor->Name()) ?></h1>
    </div>
    
    <!-- Main content area -->
    <main class="flex-1 p-4 lg:p-6 bg-base-100 min-h-screen">
      <!-- Desktop header with title and action buttons -->
      <div class="hidden lg:flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">
          <?php echo translate('Monitor') ?> - <?php echo validHtmlStr($monitor->Name()) ?>
          <?php if ( $monitor->Id() ) { ?><span class="text-base-content/60 text-lg">(<?php echo $monitor->Id()?>)</span><?php } ?>
        </h1>
        
<?php if ( canEdit('Monitors') ) : ?>
        <div class="flex gap-2">
          <button id="probeBtn" class="btn btn-sm btn-ghost" data-mid="<?php echo $monitor->Id() ?>" title="<?php echo translate('Probe') ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
            </svg>
          </button>
          <button id="onvifBtn" class="btn btn-sm btn-ghost" data-mid="<?php echo $monitor->Id() ?>" title="<?php echo translate('OnvifProbe') ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
          </button>
          <button id="presetBtn" class="btn btn-sm btn-ghost" data-mid="<?php echo $monitor->Id() ?>" title="<?php echo translate('Presets') ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
            </svg>
          </button>
        </div>
<?php endif; ?>
      </div>

<?php if ( isset($_REQUEST['dupId']) ) : ?>
      <div class="alert alert-info mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span>Configuration cloned from Monitor: <?php echo validHtmlStr($clonedName) ?></span>
      </div>
<?php endif; ?>

      <!-- Form content -->
      <div class="card bg-base-200 shadow-lg">
        <div class="card-body">
          <form name="contentForm" id="contentForm" method="post" action="?view=monitor">
            <input type="hidden" name="tab" value="<?php echo $tab?>"/>
            <input type="hidden" name="mid" value="<?php echo $monitor->Id() ? $monitor->Id() : $mid ?>"/>
            <input type="hidden" name="origMethod" value="<?php echo (null !== $monitor->Method())?validHtmlStr($monitor->Method()):'' ?>"/>
            <input type="hidden" name="action"/>
            
            <div id="monitor-content">
<?php
// Render current tab content
switch ( $tab ) {
  case 'general':
    include('monitor/_monitor_tab_general.php');
    break;
  case 'source':
    include('monitor/_monitor_tab_source.php');
    break;
  case 'onvif':
    include('monitor/_monitor_tab_onvif.php');
    break;
  case 'storage':
    include('monitor/_monitor_tab_storage.php');
    break;
  case 'timestamp':
    include('monitor/_monitor_tab_timestamp.php');
    break;
  case 'buffers':
    include('monitor/_monitor_tab_buffers.php');
    break;
  case 'control':
    include('monitor/_monitor_tab_control.php');
    break;
  case 'x10':
    include('monitor/_monitor_tab_x10.php');
    break;
  case 'misc':
    include('monitor/_monitor_tab_misc.php');
    break;
  case 'location':
    include('monitor/_monitor_tab_location.php');
    break;
  default:
    ZM\Error("Unknown tab $tab");
}
?>
            </div>
            
            <div class="card-actions justify-end mt-6 pt-4 border-t border-base-300">
              <button type="button" id="cancelBtn" class="btn btn-ghost"><?php echo translate('Cancel') ?></button>
              <button type="button" id="saveBtn" class="btn btn-primary" <?php echo canEdit('Monitors') ? '' : 'disabled' ?>>
                <?php echo translate('Save') ?>
              </button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
  
  <!-- Sidebar -->
  <div class="drawer-side z-20">
    <label for="monitor-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
    <aside class="bg-base-200 min-h-full w-64 border-r border-base-300">
      <div class="p-4 border-b border-base-300">
        <h2 class="text-lg font-bold"><?php echo translate('Monitor') ?></h2>
        <p class="text-sm text-base-content/70"><?php echo validHtmlStr($monitor->Name()) ?></p>
      </div>
      
<?php if ( canEdit('Monitors') ) : ?>
      <!-- Mobile action buttons -->
      <div class="lg:hidden p-4 border-b border-base-300 flex gap-2">
        <button id="probeBtnMobile" class="btn btn-sm btn-ghost flex-1" data-mid="<?php echo $monitor->Id() ?>">
          <?php echo translate('Probe') ?>
        </button>
        <button id="onvifBtnMobile" class="btn btn-sm btn-ghost flex-1" data-mid="<?php echo $monitor->Id() ?>">
          ONVIF
        </button>
        <button id="presetBtnMobile" class="btn btn-sm btn-ghost flex-1" data-mid="<?php echo $monitor->Id() ?>">
          <?php echo translate('Presets') ?>
        </button>
      </div>
<?php endif; ?>
      
      <ul class="menu p-4 gap-1">
<?php foreach ( $tabs as $name => $value ) : ?>
        <li>
          <a href="?view=<?php echo $view ?>&amp;mid=<?php echo $monitor->Id() ?>&amp;tab=<?php echo $name ?>" 
             class="<?php echo $tab == $name ? 'active' : '' ?>">
            <?php echo $value ?>
          </a>
        </li>
<?php endforeach; ?>
      </ul>
    </aside>
  </div>
</div>

<?php xhtmlFooter() ?>
