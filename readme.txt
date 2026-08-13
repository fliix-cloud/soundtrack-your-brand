=== fliix – Now Playing for Soundtrack Your Brand ===
Contributors: fliix
Tags: soundtrack, music, now playing, shortcode, api
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display currently playing tracks from Soundtrack Your Brand sound zones on your WordPress site via shortcode.

== Description ==

**fliix – Now Playing for Soundtrack Your Brand** connects your WordPress site to the [Soundtrack Your Brand API](https://api.soundtrackyourbrand.com/v2/docs) and shows what is playing in your sound zones. Tracks update in real time without page reloads.

= Features =

* Admin settings under **Settings → Soundtrack Your Brand**
* SoundZone mapping with friendly slugs (e.g. `nagold`)
* Lazy, on-demand caching — no WP-Cron; API called when visitors load pages with the shortcode
* Four display templates: Classic, Compact, Modern Card, Minimal
* Customizable colors, fonts, alignment, and artwork options
* Live frontend refresh at a configurable interval (10–120 seconds)
* Secure: nonces, capability checks, sanitized input, escaped output, encrypted API tokens (AES-256-CBC)

== External services ==

This plugin relies on the **Soundtrack Your Brand API** to retrieve your sound zones and now-playing track information. It connects to `https://api.soundtrackyourbrand.com/v2` by default, or to a custom API base URL if you configure one. You must supply your own API token from [Soundtrack Your Brand](https://www.soundtrackyourbrand.com/our-api/apply).

The plugin sends data when an administrator fetches sound zones and whenever a page containing the shortcode loads or refreshes after its local cache expires. Each API request sends:

* Your API token (as `Authorization: Basic` header)
* A GraphQL query describing the requested data
* The selected sound zone ID when requesting now-playing information

When album artwork display is enabled, the visitor's browser may load cover images from image URLs returned by the Soundtrack API (typically Soundtrack CDN hosts). No additional credentials are sent with those image requests.

No visitor personal data is collected or transmitted by this plugin.

The service is provided by **Soundtrack Technologies Sweden AB**:

* [General Terms and Conditions](https://www.soundtrack.io/legal/general-terms-and-conditions/)
* [Privacy Policy](https://www.soundtrack.io/legal/privacy-policy/)

= Third-party extension =

This is an independent plugin for the Soundtrack Your Brand platform. It is **not** an official Soundtrack Your Brand product.

= Translations =

The plugin is fully internationalized (text domain `fliix-now-playing-for-soundtrack-your-brand`). Translations are managed on [translate.wordpress.org](https://translate.wordpress.org/) and delivered as language packs — no translation files are bundled in the plugin package.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install from the WordPress.org plugin directory.
2. Activate **fliix – Now Playing for Soundtrack Your Brand**.
3. Open **Settings → Soundtrack Your Brand**.
4. Enter your API token (encrypted at rest; sent as `Authorization: Basic <token>`).
5. Click **Fetch / Refresh SoundZones from API**, assign slugs, and save mappings.
6. Add `[fliix_np_syb slug="your-slug"]` to any page or post.

== Frequently Asked Questions ==

= Does this require a Soundtrack Your Brand account? =

Yes. You need an API token from [soundtrackyourbrand.com/our-api/apply](https://www.soundtrackyourbrand.com/our-api/apply).

= How does caching work? =

Now playing data is stored in WordPress transients. Data is fetched when a visitor loads a page containing the shortcode, or when the cache expires during live refresh. The frontend polls at the configured interval and updates the widget when a new track is detected. No WP-Cron is used.

= Can I override display settings per shortcode? =

Yes. Use optional attributes:

`[fliix_np_syb slug="lobby" design="modern" show_image="false" show_artist="true" class="my-class"]`

= What happens when nothing is playing? =

The widget shows your configured fallback text (default: "No music playback at the moment.").

= Will I hit rate limits? =

The Soundtrack API uses a token bucket (3600 max, 50 tokens/second refill). This plugin minimizes API calls via transients and in-request deduplication.

= Does it collect personal data? =

No. The plugin stores your API token (encrypted), zone mappings, display settings, and cached track metadata locally. It does not collect visitor personal data.

= How do I report a security issue? =

Contact the maintainer privately (see Author URI) rather than posting exploit details in public forums.

== Screenshots ==

1. Admin settings page with API configuration and SoundZone mapping
2. Display settings with template picker and typography options
3. Classic template showing now playing on the frontend
4. Modern Card template on a live page

== Changelog ==

= 1.1.2 =

* Remove bundled locale translation files and the languages folder for WordPress.org language pack workflow
* Drop Domain Path header; translations via translate.wordpress.org only
* Point translation contributions to translate.wordpress.org

= 1.1.1 =

* Use plugin-specific fliix_np_syb prefix for options, shortcodes, AJAX, scripts, and related identifiers
* Align version metadata across plugin header and readme

= 1.1.0 =

* Prefix plugin identifiers to avoid naming collisions
* Document Soundtrack Your Brand as an external service with terms and privacy links
* Improve API token sanitization (trim and control-character rejection only)

= 1.0.9 =

* Stable release

= 1.0.8 =

* Version bump and German translation refresh

= 1.0.7 =

* Widget improvements and translation updates

= 1.0.5 =

* Animated music waves artwork mode and display label enhancements

= 1.0.3 =

* Internationalization support with German translations

= 1.0.0 =

* Initial release

== Upgrade Notice ==

= 1.1.2 =
Translations are now delivered via WordPress.org language packs instead of files bundled with the plugin.

= 1.1.1 =
Uses a more unique fliix_np_syb prefix for all plugin identifiers. Re-save settings and update shortcodes to [fliix_np_syb] after upgrading.