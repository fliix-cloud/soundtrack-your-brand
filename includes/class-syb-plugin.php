<?php
/**
 * Main plugin orchestrator.
 *
 * @package SoundtrackYourBrand
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bootstraps all plugin components.
 */
class SYB_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var SYB_Plugin|null
	 */
	private static ?SYB_Plugin $instance = null;

	/**
	 * API client instance.
	 *
	 * @var SYB_Api_Client
	 */
	private SYB_Api_Client $api_client;

	/**
	 * Cache instance.
	 *
	 * @var SYB_Cache
	 */
	private SYB_Cache $cache;

	/**
	 * Renderer instance.
	 *
	 * @var SYB_Renderer
	 */
	private SYB_Renderer $renderer;

	/**
	 * Get singleton instance.
	 *
	 * @return SYB_Plugin
	 */
	public static function instance(): SYB_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->api_client = new SYB_Api_Client();
		$this->cache      = new SYB_Cache( $this->api_client );
		$this->renderer   = new SYB_Renderer();

		if ( is_admin() ) {
			new SYB_Admin( $this->api_client );
		}

		new SYB_Shortcode( $this->cache, $this->renderer );
	}

	/**
	 * Get the API client.
	 *
	 * @return SYB_Api_Client
	 */
	public function api_client(): SYB_Api_Client {
		return $this->api_client;
	}

	/**
	 * Get the cache handler.
	 *
	 * @return SYB_Cache
	 */
	public function cache(): SYB_Cache {
		return $this->cache;
	}

	/**
	 * Get display settings with defaults merged.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_display_settings(): array {
		$defaults = SYB_Activator::default_display_settings();
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