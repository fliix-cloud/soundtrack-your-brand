<?php
/**
 * Plugin Name:       Soundtrack Your Brand – Now Playing
 * Plugin URI:        https://github.com/fliix-cloud/soundtrack-your-brand
 * Description:       Display currently playing tracks from Soundtrack Your Brand sound zones via shortcode.
 * Version:           1.0.2
 * Requires at least: 6.2
 * Requires PHP:      8.0
 * Author:            fliix - Marc Werner
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       soundtrack-your-brand
 *
 * @package SoundtrackYourBrand
 */

defined( 'ABSPATH' ) || exit;

define( 'SYB_VERSION', '1.0.2' );
define( 'SYB_PLUGIN_FILE', __FILE__ );
define( 'SYB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SYB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SYB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

$syb_autoloader = SYB_PLUGIN_DIR . 'vendor/autoload.php';

if ( ! file_exists( $syb_autoloader ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__(
					'Soundtrack Your Brand – Now Playing: Composer autoloader not found. Run "composer install" in the plugin directory.',
					'soundtrack-your-brand'
				)
			);
		}
	);
	return;
}

require $syb_autoloader;

register_activation_hook( __FILE__, array( \SoundtrackYourBrand\Activator::class, 'activate' ) );

/**
 * Initialize the plugin.
 *
 * @return \SoundtrackYourBrand\Plugin
 */
function syb_plugin(): \SoundtrackYourBrand\Plugin {
	return \SoundtrackYourBrand\Plugin::instance();
}

syb_plugin();