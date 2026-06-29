<?php
/**
 * Plugin Name:       Soundtrack Your Brand – Now Playing
 * Plugin URI:        https://github.com/soundtrackyourbrand/soundtrack_api-example_app
 * Description:       Display currently playing tracks from Soundtrack Your Brand sound zones via shortcode.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      8.0
 * Author:            Soundtrack Your Brand
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       soundtrack-your-brand
 *
 * @package SoundtrackYourBrand
 */

defined( 'ABSPATH' ) || exit;

define( 'SYB_VERSION', '1.0.0' );
define( 'SYB_PLUGIN_FILE', __FILE__ );
define( 'SYB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SYB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SYB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoload plugin classes.
 *
 * @param string $class_name Class name to load.
 */
function syb_autoload( string $class_name ): void {
	if ( strpos( $class_name, 'SYB_' ) !== 0 ) {
		return;
	}

	$file = strtolower( str_replace( '_', '-', $class_name ) );
	$paths = array(
		SYB_PLUGIN_DIR . 'includes/class-' . $file . '.php',
		SYB_PLUGIN_DIR . 'admin/class-' . $file . '.php',
	);

	foreach ( $paths as $path ) {
		if ( file_exists( $path ) ) {
			require_once $path;
			return;
		}
	}
}

spl_autoload_register( 'syb_autoload' );

register_activation_hook( __FILE__, array( 'SYB_Activator', 'activate' ) );

/**
 * Initialize the plugin.
 *
 * @return SYB_Plugin
 */
function syb_plugin(): SYB_Plugin {
	return SYB_Plugin::instance();
}

syb_plugin();