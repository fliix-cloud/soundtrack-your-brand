# Soundtrack Your Brand – Now Playing

A WordPress plugin that displays currently playing tracks from [Soundtrack Your Brand](https://www.soundtrackyourbrand.com) sound zones via shortcode.

## Requirements

- PHP 8.0+
- WordPress 6.2+
- Soundtrack API token ([apply here](https://www.soundtrackyourbrand.com/our-api/apply))

## Installation

1. Copy the `soundtrack-your-brand` folder into `wp-content/plugins/`
2. Install the Composer autoloader:

   ```bash
   cd wp-content/plugins/soundtrack-your-brand
   composer install --no-dev
   ```

3. Activate **Soundtrack Your Brand – Now Playing** in the WordPress admin
4. Open **Settings → Soundtrack Your Brand**

## Setup

### 1. API Configuration

| Setting | Description |
|---|---|
| API Base URL | Default: `https://api.soundtrackyourbrand.com/v2` |
| API Token | Encrypted at rest (AES-256-CBC). Write-only in settings — enter a new value to replace; the saved token cannot be viewed. Sent as `Authorization: Basic <token>` |

See the [Soundtrack API Documentation](https://api.soundtrackyourbrand.com/v2/docs) for details.

### 2. SoundZone Mapping

1. Click **Fetch / Refresh SoundZones from API**
2. Assign a unique slug to each zone (lowercase, alphanumeric, hyphens/underscores)
3. Click **Save All Mappings**

Example: zone "MC Shape Nersingen" → slug `mc-shape-nersingen`

Mappings are stored as `slug => zone_id` in the `soundtrack_mappings` option.

### 3. Display Settings

Choose a default template, toggle image/artist visibility, customize colors, font sizes, weights, and alignment.

## Shortcode

```
[syb_nowplaying slug="mc-shape-nersingen"]
```

### Optional Attributes

| Attribute | Description | Example |
|---|---|---|
| `slug` | **Required.** Mapped zone slug | `mc-shape-nersingen` |
| `design` | Template override | `classic`, `compact`, `modern`, `minimal` |
| `show_image` | Show/hide album art | `true`, `false` |
| `show_artist` | Show/hide artist name | `true`, `false` |
| `class` | Additional CSS class | `my-widget` |

```
[syb_nowplaying slug="lobby" design="modern" show_image="false"]
[syb_nowplaying slug="bar" design="minimal" class="header-now-playing"]
```

## Caching Behavior

The plugin uses **lazy, on-demand caching** — no WP-Cron or background polling.

```
Visitor loads page with shortcode
        ↓
Is transient syb_nowplaying_{md5(zone_id)} valid?
   ├── Yes → render cached data
   └── No  → call GraphQL nowPlaying API → store transient → render
```

- **Transient TTL:** Update Interval setting (10–120 seconds, default 30)
- **In-request deduplication:** Multiple shortcodes for the same zone on one page → one API call
- **Live refresh:** Frontend JavaScript polls every update interval and updates the widget when the track changes (no page reload needed)
- **Admin zone fetch:** Separate from frontend cache; only triggered manually via AJAX

## Templates

| Template | CSS Class | Description |
|---|---|---|
| Classic | `syb-nowplaying--classic` | Album image left, song above artist |
| Compact | `syb-nowplaying--compact` | Inline text |
| Modern Card | `syb-nowplaying--modern` | Centered card with shadow |
| Minimal | `syb-nowplaying--minimal` | `Artist – Song`, small footprint |

All templates use BEM-style classes for easy custom CSS overrides. Inline CSS custom properties (`--syb-song-color`, etc.) are set on the root element.

## Troubleshooting

### "API token is not configured"
Enter your token in Settings → Soundtrack Your Brand → API Configuration.

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

## Development

```bash
composer install
```

The plugin uses PSR-4 autoloading via Composer (`SoundtrackYourBrand\` → `src/`). No third-party runtime dependencies are required.

## License

GPL-2.0-or-later. See [Soundtrack API Terms of Use](https://www.soundtrackyourbrand.com/legal/api-terms-of-use).