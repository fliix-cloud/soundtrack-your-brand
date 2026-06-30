=== Soundtrack Your Brand – Now Playing ===
Contributors: soundtrackyourbrand
Tags: soundtrack, music, now playing, shortcode, graphql
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display currently playing tracks from Soundtrack Your Brand sound zones on your WordPress site.

== Description ==

This plugin connects to the [Soundtrack Your Brand API](https://api.soundtrackyourbrand.com/v2/docs) and displays what's currently playing in your sound zones via a simple shortcode.

**Features:**

* Admin settings page under Settings → Soundtrack Your Brand
* SoundZone mapping with friendly slugs (e.g. `mc-shape-nersingen`)
* Lazy, on-demand caching — no WP-Cron, API called only when visitors load pages with the shortcode
* Four display templates: Classic, Compact, Modern Card, Minimal
* Customizable colors, fonts, alignment, and image sizes
* Secure: nonces, capability checks, sanitized input, escaped output

== Installation ==

1. Upload the `soundtrack-your-brand` folder to `/wp-content/plugins/`
2. Run `composer install --no-dev` in the plugin directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Settings → Soundtrack Your Brand
5. Enter your API token (encrypted at rest; sent as `Authorization: Basic <token>`)
6. Click "Fetch / Refresh SoundZones from API"
7. Assign slugs to your zones and click "Save All Mappings"
8. Add `[syb_nowplaying slug="your-slug"]` to any page or post

== Frequently Asked Questions ==

= How do I get an API token? =

Request API credentials at [soundtrackyourbrand.com/our-api/apply](https://www.soundtrackyourbrand.com/our-api/apply).

= How does caching work? =

The plugin stores now playing data in WordPress transients. Data is fetched from the API when a visitor loads a page containing the shortcode, or when the cache expires during live refresh. The frontend polls at the configured interval and updates the widget when a new track is detected. No WP-Cron is used.

= Can I override display settings per shortcode? =

Yes. Use optional attributes:

`[syb_nowplaying slug="lobby" design="modern" show_image="false" show_artist="true" class="my-class"]`

= What happens when nothing is playing? =

The widget shows your configured fallback text (default: "No music playback at the moment.").

= Will I hit rate limits? =

The Soundtrack API uses a token bucket (3600 max, 50 tokens/second refill). This plugin minimizes API calls via transients and in-request deduplication. Monitor the `x-ratelimiting-*` response headers if needed.

== Screenshots ==

1. Admin settings page with SoundZone mapping table
2. Classic template displaying now playing on the frontend

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release.