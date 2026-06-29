<?php
/**
 * Main plugin orchestrator.
 *
 * @package SoundtrackYourBrand
 */

namespace SoundtrackYourBrand;

use SoundtrackYourBrand\Admin\Admin;
use SoundtrackYourBrand\Api\Client;
use SoundtrackYourBrand\Cache\NowPlayingCache;
use SoundtrackYourBrand\Frontend\Renderer;
use SoundtrackYourBrand\Frontend\Shortcode;

defined( 'ABSPATH' ) || exit;

/**
 * Bootstraps all plugin components.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * API client instance.
	 *
	 * @var Client
	 */
	private Client $api_client;

	/**
	 * Cache instance.
	 *
	 * @var NowPlayingCache
	 */
	private NowPlayingCache $cache;

	/**
	 * Renderer instance.
	 *
	 * @var Renderer
	 */
	private Renderer $renderer;

	/**
	 * Get singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->api_client = new Client();
		$this->cache      = new NowPlayingCache( $this->api_client );
		$this->renderer   = new Renderer();

		if ( is_admin() ) {
			new Admin( $this->api_client );
		}

		new Shortcode( $this->cache, $this->renderer );
	}

	/**
	 * Get the API client.
	 *
	 * @return Client
	 */
	public function api_client(): Client {
		return $this->api_client;
	}

	/**
	 * Get the cache handler.
	 *
	 * @return NowPlayingCache
	 */
	public function cache(): NowPlayingCache {
		return $this->cache;
	}

	/**
	 * Get display settings with defaults merged.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_display_settings(): array {
		$defaults = Activator::default_display_settings();
		$stored   = get_option( 'soundtrack_display_settings', array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return wp_parse_args( $stored, $defaults );
	}

	/**
	 * Get update interval in seconds (clamped 10–120).
	 *
	 * @return int
	 */
	public static function get_update_interval(): int {
		$interval = (int) get_option( 'soundtrack_update_interval', 30 );

		return max( 10, min( 120, $interval ) );
	}
}