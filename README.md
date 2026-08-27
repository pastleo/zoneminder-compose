# ZoneMinder Container with Modern Dark Theme

This repository contains Docker Compose configuration for running ZoneMinder using [zoneminder-base container](https://github.com/zoneminder-containers/zoneminder-base) [builds](https://github.com/zoneminder-containers/zoneminder-base/pkgs/container/zoneminder-base)

## Features

- **ZoneMinder CCTV System**: Full-featured video surveillance system
- **MariaDB Database**: Persistent storage for events and configuration
- **OAuth2 Proxy**: Google OAuth authentication (optional)
- **Tapo Talkback**: On-demand two-way audio through go2rtc (optional)

## Quick Start

1. **Copy configuration files**:
   ```bash
   cp .env.example .env
   cp compose.example.yml compose.yml
   cp go2rtc.example.yaml go2rtc.yaml
   ```

2. **Edit `.env` file**:
   - Set database passwords
   - Configure timezone (TZ)

3. **Edit `compose.yml` file**:
   - Update the ZoneMinder image tag to your desired version
   - Configure port mappings if needed

4. **Configure talkback** (optional):
   - Set each WebRTC candidate to an address clients can use to reach this host on port 8555.
   - Configure each Tapo camera as an `Ffmpeg` monitor with an `rtsp://` or `rtsps://` source in ZoneMinder.
   - For cameras configured with `tapo_onboard.sh`, use `rtsp://tapoadmin:<new_password>@CAMERA_IP:554/stream1`. The password is the same value passed to the onboarding script.
   - Set the capture resolution to the camera stream's actual dimensions, such as `1920x1080` for a Tapo C100 primary stream. ZoneMinder cannot create the monitor when width or height is blank.

5. **Start the services**:
   ```bash
   docker compose up -d
   ```

## Tapo Talkback

ZoneMinder continues to capture and record every monitor from its configured RTSP URL. For control-authorized `Ffmpeg` monitors with an RTSP source, the Talk button extracts only the source hostname and asks go2rtc to open `tapo://unused@HOST` on demand. No monitor-to-stream mapping or camera credential is stored in go2rtc. The viewer and microphone tracks are destroyed when the dialog closes.

The one-shot `zoneminder-config` service sets ZoneMinder's system default skin to `modern` after the database schema is ready. Individual users can still select another skin, and views not yet implemented by the modern skin continue to fall back to classic.

The Compose build applies `go2rtc/cloudless-tapo.patch` to go2rtc `v1.9.14`. Cameras initialized without the Tapo cloud app can return an already accepted stream response with `Key-Exchange username="none"`; upstream's decryptor supports this mode, but its connection setup rejects the initial HTTP `200`. The patch accepts only that specific response and leaves the normal Digest authentication path unchanged.

For a camera confirmed to use `username="none"`, the credential portion of the Tapo URL is ignored. The generated URL therefore uses the non-secret placeholder `unused`.

### Network And Security

- Serve ZoneMinder over HTTPS. Browsers allow microphone capture only in a secure context (with `localhost` as a development exception).
- Keep cloudlessly initialized cameras on an isolated network. Their native port 8800 stream can use the vendor's unauthenticated `username="none"` mode even when RTSP and the camera API have passwords.
- Allow client devices to reach this Compose host on TCP and UDP port `8555`. Set `webrtc.candidates` in `go2rtc.yaml` to the host's reachable LAN address, public address, or both as appropriate.
- Do not publish port `1984`. The Compose example leaves it on the private Compose network.
- go2rtc shares ZoneMinder's network namespace so the internal Nginx proxy can use stable loopback address `127.0.0.1:1984`; port `8555` is therefore published by the ZoneMinder service.
- The mounted Nginx include exposes only `/go2rtc/webrtc.html` and `/go2rtc/api/ws`; go2rtc's configuration and REST API are not routed.
- The signaling endpoints accept arbitrary source URLs and must be protected by OAuth2 Proxy. An authenticated user can direct go2rtc to attempt connections to internal addresses.
- Do not expose the ZoneMinder `8000` port where it can bypass OAuth2 Proxy. In production, publish only OAuth2 Proxy's port and remove the ZoneMinder `ports` entry.
- Any external HTTPS reverse proxy must forward `/go2rtc/` on the same origin as ZoneMinder and support WebSocket upgrades. No separate route to go2rtc is needed because ZoneMinder's internal Nginx handles it.

The Talk button appears for RTSP-backed `Ffmpeg` monitors when the user has ZoneMinder `Control` permission. The embedded viewer requests `video+audio+microphone`; use its audio control to unmute camera audio if desired.
