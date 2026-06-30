# Soundtrack Your Brand – Now Playing

[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2+-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![PHP 8.0+](https://img.shields.io/badge/PHP-8.0%2B-777BB4.svg?logo=php&logoColor=white)](https://php.net/)
[![WordPress 6.2+](https://img.shields.io/badge/WordPress-6.2%2B-21759B.svg?logo=wordpress&logoColor=white)](https://wordpress.org/)

A WordPress plugin that displays currently playing tracks from [Soundtrack Your Brand](https://www.soundtrackyourbrand.com) sound zones via shortcode — with live refresh, customizable templates, and lazy on-demand caching.

## Overview

Connect your WordPress site to the [Soundtrack Your Brand API](https://api.soundtrackyourbrand.com/v2/docs) and show what's playing in your sound zones. Tracks update in real time without page reloads, and the plugin is built with performance and security in mind.

### Key Features

- **Live Refresh** — Widget updates automatically when the track changes (no page reload)
- **Four Templates** — Classic, Compact, Modern Card, and Minimal (BEM-style CSS classes)
- **Lazy Caching** — On-demand transients, no WP-Cron or background polling
- **Secure** — Nonces, capability checks, sanitized input, escaped output, encrypted API tokens (AES-256-CBC)
- **Customizable** — Colors, fonts, alignment, image sizes, and per-shortcode overrides
- **In-Request Deduplication** — Multiple shortcodes for the same zone trigger only one API call
- **Composer Autoloading** — PSR-4 structure with no third-party runtime dependencies

## Requirements

- PHP 8.0+
- WordPress 6.2+
- A [Soundtrack Your Brand API token](https://www.soundtrackyourbrand.com/our-api/apply)

## Installation

1. Upload the `soundtrack-your-brand` folder to `wp-content/plugins/`
2. Install the Composer autoloader:

   ```bash
   cd wp-content/plugins/soundtrack-your-brand
   composer install --no-dev
   ```

3. Activate **Soundtrack Your Brand – Now Playing** in the WordPress admin
4. Navigate to **Settings → Soundtrack Your Brand**

## Setup

### 1. API Configuration

| Setting     | Description                                                                                                                                                                         |
|-------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| API Base URL | Default: `https://api.soundtrackyourbrand.com/v2`                                                                                                                                   |
| API Token   | Encrypted at rest (AES-256-CBC). Write-only in settings — enter a new value to replace; the saved token cannot be viewed. Sent as `Authorization: Basic <token>` to the Soundtrack API. |

See the [Soundtrack API Documentation](https://api.soundtrackyourbrand.com/v2/docs) for full details.

### 2. SoundZone Mapping

1. Click **Fetch / Refresh SoundZones from API**
2. Assign a unique slug to each zone (lowercase, alphanumeric, hyphens/underscores)
3. Click **Save All Mappings**

**Example:** zone "Nagold" → slug `nagold`

Mappings are stored as `slug => zone_id` in the `soundtrack_mappings` option.

### 3. Display Settings

Choose a default template, toggle image/artist visibility, and customize colors, font sizes, weights, and alignment.

## Usage

### Basic Shortcode

```
[syb_nowplaying slug="nagold"]
```

### Optional Attributes

| Attribute    | Description                | Values                             |
|--------------|----------------------------|------------------------------------|
| `slug`       | **Required.** Zone slug    | `nagold`                           |
| `design`     | Template override          | `classic`, `compact`, `modern`, `minimal` |
| `show_image` | Show/hide album art        | `true`, `false`                    |
| `show_artist`| Show/hide artist name      | `true`, `false`                    |
| `class`      | Additional CSS class       | `my-widget`                        |

### Examples

```
[syb_nowplaying slug="lobby" design="modern" show_image="false"]
[syb_nowplaying slug="bar" design="minimal" class="header-now-playing"]
```

## Caching

The plugin uses **lazy, on-demand caching** — no WP-Cron or background polling.

```
Visitor loads page with shortcode
        │
        ▼
Is transient syb_nowplaying_{md5(zone_id)} valid?
   ├── Yes → render cached data
   └── No  → call GraphQL nowPlaying API → store transient → render
```

- **Transient TTL:** Configurable Update Interval (10–120 seconds, default 30)
- **In-Request Deduplication:** Multiple shortcodes for the same zone on one page → one API call
- **Live Refresh:** Frontend JavaScript polls at the update interval and updates the widget when the track changes (no page reload)
- **Admin Zone Fetch:** Separate from frontend cache; triggered manually via AJAX

## Templates

| Template     | CSS Class                | Description                                |
|--------------|--------------------------|--------------------------------------------|
| Classic      | `syb-nowplaying--classic` | Album image left, song above artist       |
| Compact      | `syb-nowplaying--compact` | Inline text                                |
| Modern Card  | `syb-nowplaying--modern`  | Centered card with shadow                  |
| Minimal      | `syb-nowplaying--minimal` | `Artist – Song`, small footprint           |

All templates use BEM-style classes for easy custom CSS overrides. Inline CSS custom properties (`--syb-song-color`, etc.) are set on the root element.

## Frequently Asked Questions

### How do I get an API token?

Request API credentials at [soundtrackyourbrand.com/our-api/apply](https://www.soundtrackyourbrand.com/our-api/apply).

### How does caching work?

Now playing data is stored in WordPress transients. Data is fetched from the API when a visitor loads a page containing the shortcode, or when the cache expires during live refresh. The frontend polls at the configured interval and updates the widget when a new track is detected. No WP-Cron is used.

### Can I override display settings per shortcode?

Yes. Use optional attributes:

```
[syb_nowplaying slug="lobby" design="modern" show_image="false" show_artist="true" class="my-class"]
```

### What happens when nothing is playing?

The widget shows your configured fallback text (default: "No music playback at the moment.").

### Will I hit rate limits?

The Soundtrack API uses a token bucket (3600 max, 50 tokens/second refill). This plugin minimizes API calls via transients and in-request deduplication. Monitor the `x-ratelimiting-*` response headers if needed.

## Troubleshooting

### "API token is not configured"

Enter your token in **Settings → Soundtrack Your Brand → API Configuration**.

### "Unknown slug: ..."

The slug is not in your saved mappings. Assign and save it in the SoundZone Mapping table.

### "No track playing" / fallback text

The zone may be paused or between tracks. This is normal — not an error.

### Rate limits

The Soundtrack API enforces rate limiting (3600 token bucket, 50 tokens/second refill). This plugin minimizes calls via transients. If you see HTTP 429 errors:

- Increase the Update Interval
- Reduce the number of unique zones on high-traffic pages
- Check `x-ratelimiting-cost` and `x-ratelimiting-tokens-available` response headers

### API errors with HTTP 200

GraphQL can return HTTP 200 with an `errors` array. The plugin surfaces these messages in the widget fallback state.

## Uninstall

Deleting the plugin removes all options and cached transients via `uninstall.php`.

## Development

```bash
composer install
```

The plugin uses PSR-4 autoloading via Composer (`SoundtrackYourBrand\` → `src/`). No third-party runtime dependencies are required.

## File Structure

```
soundtrack-your-brand/
├── soundtrack-your-brand.php    # Bootstrap
├── composer.json                # PSR-4 autoloading
├── uninstall.php
├── src/
│   ├── Plugin.php               # Orchestrator
│   ├── Activator.php
│   ├── Api/
│   │   └── Client.php           # GraphQL client
│   ├── Cache/
│   │   └── NowPlayingCache.php  # Transient cache
│   ├── Frontend/
│   │   ├── Renderer.php         # HTML output
│   │   └── Shortcode.php        # [syb_nowplaying]
│   └── Admin/
│       ├── Admin.php            # AJAX, assets
│       └── Settings.php         # Settings page
└── assets/
    ├── css/admin.css
    ├── css/frontend.css
    └── js/admin.js
```

## License

GPL-2.0-or-later. See [Soundtrack API Terms of Use](https://www.soundtrackyourbrand.com/legal/api-terms-of-use).