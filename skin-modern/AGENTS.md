# ZoneMinder Modern Skin Development Guide

## Project Goal

Rebuild **all features** from ZoneMinder's classic skin with a modern, mobile-friendly UI using daisyUI (Tailwind CSS component library). This is a complete feature parity migration - every feature in the classic skin should work in the modern skin.

**Key principle**: Use the classic skin as the source of truth for functionality. The modern skin should support all the same features, just with better UI/UX.

## Tech Stack

- **Tailwind CSS v4** - Utility-first CSS framework
- **daisyUI v5** - Component library built on Tailwind
- **PHP templates** - ZoneMinder's existing view system (no changes to backend)

## Build System

```bash
cd skin-modern
bun install        # First time setup
bun run build      # Production build (CSS + JS)
bun run watch      # Development (auto-rebuild on file changes)

# Individual builds
bun run build:css  # CSS only
bun run build:js   # JS only
```

Output: `dist/` directory (CSS + JS bundles), loaded via `includes/functions.php`.

## How It Works

**CSS:**
1. Tailwind scans PHP files in `views/` and `includes/` for class names
2. Only used classes are included in the output CSS

**JS:**
1. `src/main.ts` is the entry point, loaded on every page as ES module
2. Page-specific code lives in `src/pages/<view>.ts` (e.g., `src/pages/console.ts`)
3. Bun's `--splitting` flag enables automatic code splitting
4. Pages are loaded dynamically based on `<body data-view="...">` attribute

## JavaScript Architecture

```
src/
├── main.ts              # Entry point (always loaded)
├── lib/                 # Shared utilities (import statically in pages)
│   └── modal.ts         # Native <dialog> modal utility
└── pages/               # Page-specific modules (loaded dynamically)
    └── console.ts
```

**Shared utilities (`src/lib/`):**

| Module | Purpose |
|--------|---------|
| `modal.ts` | Native `<dialog>` modal with daisyUI styling |

Usage:
```ts
import { createModal, showModal, confirm } from '../lib/modal.ts';

// Quick confirm dialog
if (await confirm('Delete this monitor?')) { ... }

// Custom modal
createModal({
  id: 'my-modal',
  title: 'Edit Function',
  content: '<form>...</form>',
  actions: [
    { label: 'Cancel', className: 'btn btn-ghost' },
    { label: 'Save', className: 'btn btn-primary', onClick: handleSave },
  ],
});
showModal('my-modal');
```

**Adding a new page module:**

1. Create `src/pages/<view>.ts` with an `init()` export:
   ```ts
   import { someUtil } from '../lib/utils.ts';  // shared code (bundled with page)

   export function init(): void {
     // Page-specific initialization
   }
   ```

2. Register in `src/main.ts`:
   ```ts
   const pages = {
     console: () => import('./pages/console.ts'),
     watch: () => import('./pages/watch.ts'),  // add here
   };
   ```

3. Run `bun run build` - Bun automatically creates separate chunks

**Benefits:**
- Only loads JS needed for current page
- Shared code is deduplicated into common chunks
- No manual script loading in PHP

## Development Workflow

1. Edit PHP templates in `views/`
2. Use daisyUI component classes (btn, card, navbar, etc.)
3. Use Tailwind utility classes for custom styling
4. Run `bun run build`
5. Refresh browser at `http://localhost:8000/?skin=modern`

> if http://localhost:8000 is not running zoneminder, ask user to run it, there is a ../compose.example.yml

## daisyUI Components Reference

Commonly used components for this project:

| Component | Use Case |
|-----------|----------|
| `navbar` | Top navigation bar |
| `drawer` | Mobile sidebar menu |
| `btn` | Buttons (btn-primary, btn-ghost, etc.) |
| `card` | Content containers |
| `table` | Data tables |
| `badge` | Status indicators |
| `alert` | Notifications |
| `modal` | Dialogs |
| `menu` | Dropdown menus |
| `tabs` | Tabbed navigation |
| `form-control` | Form inputs |
| `stat` | Statistics display |

Full docs: https://daisyui.com/components/

## Theme

Dark theme is default. Configured in `src/main.css`:
```css
@plugin "daisyui" {
  themes: dark --default, light;
}
```

## File Structure

```
skin-modern/
├── src/                  # Source files
│   ├── main.css          # Tailwind input (global styles)
│   ├── main.ts           # JS entry point (page loader)
│   ├── lib/              # Shared JS utilities
│   └── pages/            # Page-specific JS modules
│       └── console.ts
├── dist/                 # Build output (do not edit)
│   ├── main.css          # Built CSS
│   ├── main.js           # JS entry chunk
│   └── *.js              # JS page/shared chunks (auto-generated)
├── views/                # PHP templates (main work area)
├── includes/
│   └── functions.php     # Loads CSS/JS, renders HTML head/footer
├── package.json
├── bun.lock
└── AGENTS.md
```

## Code Reference

| Path | Description |
|------|-------------|
| `../zoneminder/` | Full ZoneMinder source (read-only reference) |
| `../zoneminder/web/skins/classic/` | Stock classic skin - the source of truth for features |

When implementing a view, reference the classic skin's PHP to understand:
- Data fetching patterns and PHP logic
- Available variables and includes
- JS interactions to replicate

> can ask user to `git clone https://github.com/ZoneMinder/zoneminder.git` at `../` if not found

**Live comparison** - open both skins side-by-side:
- Modern: `http://localhost:8000/?skin=modern&view=console`
- Classic: `http://localhost:8000/?skin=classic&view=console`

## Per-View Fallback System

The modern skin uses a **whitelist-based fallback** to classic skin. This allows gradual migration - only completed views use modern skin, others transparently fall back to classic.

**How it works:**
1. User visits `?skin=modern` → cookie set to "modern"
2. For each page load, `skin.php` checks if view is in `$MODERN_READY_VIEWS`
3. If YES → render with modern skin
4. If NO → temporarily switch to classic skin for this request (cookie unchanged)
5. Next page load checks whitelist again

**Configuration** (`skin.php`):
```php
$MODERN_READY_VIEWS = [
  'console',
  // Add views here as they are completed
];
```

**Adding a new modernized view:**
1. Create/update `views/<name>.php` with daisyUI markup
2. Create `src/pages/<name>.ts` if JS needed, register in `src/main.ts`
3. Add `'<name>'` to `$MODERN_READY_VIEWS` array in `skin.php`
4. Run `bun run build`

**Notes:**
- Login, logout, postlogin use classic skin (no plans to modernize)
- The fallback is transparent - users don't need to do anything
- Cookie preference is preserved across fallback views

## Migration Strategy

### CSS: Bootstrap → daisyUI

We're progressively replacing Bootstrap classes with daisyUI equivalents:

| Bootstrap | daisyUI |
|-----------|---------|
| `btn btn-primary` | `btn btn-primary` (same) |
| `navbar navbar-dark bg-dark` | `navbar bg-base-200` |
| `table table-striped` | `table table-zebra` |
| `alert alert-info` | `alert alert-info` (same) |
| `form-control` | `input input-bordered` |
| `card` | `card bg-base-100 shadow` |

### JS: Legacy → Modern TypeScript

The classic skin uses legacy JS dependencies loaded via PHP:
- `jquery.min.js` + `$j = jQuery.noConflict()`
- `ajaxQueue.js`
- `moment.min.js`
- `logger.js`

**Goal**: The modern skin does NOT load any legacy JS. All interactive behavior must be implemented in modern TypeScript (`src/pages/*.ts`).

**Common patterns to replace**:

| Legacy Pattern | Modern Replacement |
|----------------|-------------------|
| `data-on-click-this="functionName"` | `addEventListener('change/click', ...)` in page module |
| `$j(selector)` | `document.querySelector()` or `querySelectorAll()` |
| `moment(date).format()` | Native `Intl.DateTimeFormat` or npm `date-fns` |
| `$.ajax()` | Native `fetch()` API |

**Adding JS to a view**:
1. Identify all JS interactions in the PHP template (search for `data-on-click`, `onclick`, etc.)
2. Implement equivalent logic in `src/pages/<view>.ts`
3. Register the page in `src/main.ts`
4. Run `bun run build`

## Key Views to Revamp

1. **Layout** (`functions.php`) - Navbar, drawer for mobile
2. **Console** (`views/console.php`) - Main dashboard
3. **Watch** (`views/watch.php`) - Live camera view
4. **Events** (`views/event.php`) - Event playback
5. **Montage** (`views/montage.php`) - Multi-camera grid
6. **Options** (`views/options.php`) - Settings pages

## Mobile-First Approach

- Use responsive prefixes: `sm:`, `md:`, `lg:`
- Test at 375px width (mobile) first
- daisyUI's `drawer` component handles mobile navigation
- Tables should be scrollable or card-based on mobile

## Verification with Playwright

Use Playwright to verify changes work correctly in the browser.

**URL**: `http://localhost:8000/?skin=modern`

**Basic verification flow**:
1. Navigate to the page with `browser_navigate`
2. Use `browser_snapshot` to get page structure (preferred over screenshot for accessibility tree)
3. Use `browser_take_screenshot` for visual verification
4. Test mobile viewport with `browser_resize` (e.g., 375x667 for iPhone SE)

**Testing responsive design**:
```
browser_resize width=375 height=667    # Mobile
browser_resize width=768 height=1024   # Tablet
browser_resize width=1280 height=800   # Desktop
```

**Key things to verify**:
- Navigation elements are accessible
- Buttons and interactive elements are clickable
- Layout doesn't break at different viewport sizes
- daisyUI components render with correct styling
- Mobile drawer opens/closes properly

**After each view change**:
1. Rebuild CSS: `bun run build`
2. Navigate to the changed view
3. Take snapshot to verify structure
4. Take screenshot for visual check
5. Test at mobile width to ensure responsive behavior

## Testing Monitor Manipulation via Playwright

When testing features that require monitors (delete, batch actions, sorting, etc.), you may need to create test monitors via Playwright.

**Test RTSP streams** (720p):
- Still stream: `rtsp://mediamtx-still:8554/test` (blue background with timestamp)
- Motion stream: `rtsp://mediamtx-motion:8554/test` (moving test pattern with timestamp)

> provided by `mediamtx-still`, `test-stream-still`, `mediamtx-motion`, and `test-stream-motion` containers defined in ../compose.example.yml

**Creating a test monitor** (via classic skin for reliability):
```
1. browser_navigate to http://localhost:8000/?skin=classic&view=monitor
2. Fill in Name: "Test-Monitor"
3. Select Function: "Monitor" (no recording) or "Modect" (motion detection)
4. If using Modect and please prevent writing to disk: uncheck "Analysis Enabled"
5. Click "Source" tab
6. Fill Source Path: rtsp://mediamtx-still:8554/test (or mediamtx-motion for motion detection testing)
7. Select Capture Resolution: 1280x720 720p
8. Click "Storage" tab
9. Select Video Writer: Camera Passthrough
10. Click "Save"
```

**Recommended test monitor configurations**:
| Name | Function | Analysis | Source | Use Case |
|------|----------|----------|--------|----------|
| Test-Monitor | Monitor | N/A | mediamtx-still | Basic streaming, no recording |
| Motion-Monitor | Modect | Disabled | mediamtx-motion | Motion detection UI testing without disk writes |

**Creating a test event** (to test event playback features):
```
1. Navigate to watch page: ?view=watch&mid=2 (or any monitor with recording enabled)
2. Wait for stream to load and status to show (State: Idle/Prealarm)
3. Click "Force" button in Alarm section - yellow border appears, recording starts
4. Wait 10+ seconds for sufficient video length
5. Click "Cancel Force" button to stop recording
6. New event appears in Recent Events list
```

Events created this way will have MP4 video files (if Video Writer is enabled), allowing testing of:
- Video playback with native HTML5 player
- Playback rate control (0.5x to 16x)
- Seeking and progress bar
- Prev/Next navigation between events

**Deleting test monitors**:
- Use the modern skin's batch selection (checkboxes on cards)
- Or navigate to `?skin=classic&view=console` and use the classic interface
- The delete action requires `markMids[]` form field (handled by our implementation)

**Known backend issues**:
- Monitor sorting (`ajax/console.php`) has a bug where `LOCK TABLES` causes an implicit commit, breaking the transaction. This affects both classic and modern skins - sorting fails to persist to database.

## Viewing ZoneMinder Server Logs

The ZoneMinder dev container runs via `../compose.yml` (should be copied from `../compose.example.yml`). To view server logs:

```bash
cd .. && docker-compose logs zoneminder
# or
cd .. && podman-compose logs zoneminder
```
