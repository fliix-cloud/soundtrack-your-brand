<?php
/**
 * Plugin Name:       fliix – Now Playing for Soundtrack Your Brand
 * Description:       Display currently playing tracks from Soundtrack Your Brand sound zones via shortcode.
 * Version:           1.1.0
 * Requires at least: 6.2
 * Requires PHP:      8.0
 * Author:            fliix - Marc Werner
 * Author URI:        https://www.fliix.cloud
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       fliix-now-playing-for-soundtrack-your-brand
 * Domain Path:       /languages
 *
 * Independent third-party extension for Soundtrack Your Brand. Not affiliated with Soundtrack Your Brand.
 *
 * @package SoundtrackYourBrand
 */

use Fliix\SoundtrackYourBrand\Activator;
use Fliix\SoundtrackYourBrand\Autoloader;
use Fliix\SoundtrackYourBrand\Plugin;

defined( 'ABSPATH' ) || exit;

if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: required PHP version, 2: current PHP version */
						__( 'fliix – Now Playing for Soundtrack Your Brand requires PHP %1$s or higher. You are running PHP %2$s.', 'fliix-now-playing-for-soundtrack-your-brand' ),
						'8.0',
						PHP_VERSION
					)
				)
			);
		}
	);
	return;
}

const FLIIX_NP_SYB_VERSION = '1.1.0';
const FLIIX_NP_SYB_FILE    = __FILE__;
define( 'FLIIX_NP_SYB_DIR', plugin_dir_path( FLIIX_NP_SYB_FILE ) );
define( 'FLIIX_NP_SYB_URL', plugin_dir_url( FLIIX_NP_SYB_FILE ) );
define( 'FLIIX_NP_SYB_BASENAME', plugin_basename( FLIIX_NP_SYB_FILE ) );

require_once FLIIX_NP_SYB_DIR . 'src/Autoloader.php';

Autoloader::register(
	prefix: 'Fliix\\SoundtrackYourBrand\\',
	base_dir: FLIIX_NP_SYB_DIR . 'src/'
);

register_activation_hook( __FILE__, array( Activator::class, 'activate' ) );

/**
 * Initialize the plugin.
 *
 * @return Plugin
 */
function fliix_np_syb_plugin(): Plugin {
	return Plugin::instance();
}

fliix_np_syb_plugin();