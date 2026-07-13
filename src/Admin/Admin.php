<?php
/**
 * Admin bootstrap and AJAX handlers.
 *
 * @package SoundtrackYourBrand
 */

namespace Fliix\SoundtrackYourBrand\Admin;

use Fliix\SoundtrackYourBrand\Api\Client;

defined( 'ABSPATH' ) || exit;

/**
 * Registers admin hooks, assets, and AJAX endpoints.
 */
class Admin {

	/**
	 * Settings page slug under Settings → General.
	 */
	private const SETTINGS_PAGE = 'fliix-now-playing-for-soundtrack-your-brand';

	/**
	 * Settings page hook suffix.
	 *
	 * @var string
	 */
	private string $page_hook = '';

	/**
	 * API client instance.
	 *
	 * @var Client
	 */
	private Client $api_client;

	/**
	 * Constructor.
	 *
	 * @param Client $api_client API client instance.
	 */
	public function __construct( Client $api_client ) {
		$this->api_client = $api_client;

		new Settings();

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . FLIIX_NP_SYB_BASENAME, array( $this, 'add_plugin_action_links' ) );

		add_action( 'wp_ajax_fliix_fetch_soundzones', array( $this, 'ajax_fetch_soundzones' ) );
		add_action( 'wp_ajax_fliix_save_mappings', array( $this, 'ajax_save_mappings' ) );
	}

	/**
	 * Register the settings submenu page.
	 */
	public function register_menu(): void {
		$this->page_hook = (string) add_options_page(
			__( 'Soundtrack Your Brand', 'fliix-now-playing-for-soundtrack-your-brand' ),
			__( 'Soundtrack Your Brand', 'fliix-now-playing-for-soundtrack-your-brand' ),
			'manage_options',
			self::SETTINGS_PAGE,
			array( Settings::class, 'render_page' )
		);
	}

	/**
	 * Add a Settings link on the Plugins list screen.
	 *
	 * @param array<int, string> $links Existing action links.
	 * @return array<int, string>
	 */
	public function add_plugin_action_links( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=' . self::SETTINGS_PAGE ) ),
			esc_html__( 'Settings', 'fliix-now-playing-for-soundtrack-your-brand' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Enqueue admin assets on the settings page.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( $hook_suffix !== $this->page_hook ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		wp_enqueue_style(
			'fliix-admin',
			FLIIX_NP_SYB_URL . 'assets/css/admin.css',
			array(),
			FLIIX_NP_SYB_VERSION
		);

		wp_enqueue_script(
			'fliix-admin',
			FLIIX_NP_SYB_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			FLIIX_NP_SYB_VERSION,
			true
		);

		$mappings = get_option( 'fliix_soundtrack_mappings', array() );
		if ( ! is_array( $mappings ) ) {
			$mappings = array();
		}

		$zone_id_to_slug = array_flip( $mappings );

		wp_localize_script(
			'fliix-admin',
			'fliixAdmin',
			array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'fliix_admin_nonce' ),
				'zoneIdToSlug'   => $zone_id_to_slug,
				'i18n'           => array(
					'fetching'       => __( 'Fetching sound zones…', 'fliix-now-playing-for-soundtrack-your-brand' ),
					'fetchSuccess'   => __( 'Sound zones refreshed successfully.', 'fliix-now-playing-for-soundtrack-your-brand' ),
					'fetchError'     => __( 'Failed to fetch sound zones.', 'fliix-now-playing-for-soundtrack-your-brand' ),
					'saving'         => __( 'Saving mappings…', 'fliix-now-playing-for-soundtrack-your-brand' ),
					'saveSuccess'    => __( 'Mappings saved successfully.', 'fliix-now-playing-for-soundtrack-your-brand' ),
					'saveError'      => __( 'Failed to save mappings.', 'fliix-now-playing-for-soundtrack-your-brand' ),
					'copied'         => __( 'Copied!', 'fliix-now-playing-for-soundtrack-your-brand' ),
					'copyFailed'     => __( 'Copy failed.', 'fliix-now-playing-for-soundtrack-your-brand' ),
					'slugInvalid'    => __( 'Use lowercase letters, numbers, hyphens, and underscores only.', 'fliix-now-playing-for-soundtrack-your-brand' ),
					'slugDuplicate'  => __( 'This slug is already used.', 'fliix-now-playing-for-soundtrack-your-brand' ),
					'noZones'        => __( 'No sound zones loaded. Click "Fetch / Refresh SoundZones from API" first.', 'fliix-now-playing-for-soundtrack-your-brand' ),
					'paired'         => __( 'Paired', 'fliix-now-playing-for-soundtrack-your-brand' ),
					'unpaired'       => __( 'Unpaired', 'fliix-now-playing-for-soundtrack-your-brand' ),
					'copy'           => __( 'Copy', 'fliix-now-playing-for-soundtrack-your-brand' ),
				),
			)
		);
	}

	/**
	 * AJAX: fetch sound zones from API.
	 */
	public function ajax_fetch_soundzones(): void {
		check_ajax_referer( 'fliix_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'fliix-now-playing-for-soundtrack-your-brand' ) ), 403 );
		}

		$zones = $this->api_client->fetch_sound_zones();

		if ( is_wp_error( $zones ) ) {
			wp_send_json_error( array( 'message' => $zones->get_error_message() ) );
		}

		$cache = $this->api_client->build_zones_cache( $zones );
		update_option( 'fliix_zones_cache', $cache );

		wp_send_json_success(
			array(
				'zones'   => $zones,
				'message' => sprintf(
					/* translators: %d: number of zones */
					__( 'Fetched %d sound zones.', 'fliix-now-playing-for-soundtrack-your-brand' ),
					count( $zones )
				),
			)
		);
	}

	/**
	 * AJAX: save slug mappings.
	 */
	public function ajax_save_mappings(): void {
		check_ajax_referer( 'fliix_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'fliix-now-playing-for-soundtrack-your-brand' ) ), 403 );
		}

		$raw_mappings = isset( $_POST['mappings'] ) ? sanitize_textarea_field( wp_unslash( $_POST['mappings'] ) ) : '';
		$decoded      = json_decode( $raw_mappings, true );

		if ( ! is_array( $decoded ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid mapping data.', 'fliix-now-playing-for-soundtrack-your-brand' ) ) );
		}

		$validation = $this->validate_mappings( $decoded );

		if ( ! empty( $validation['errors'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please fix validation errors before saving.', 'fliix-now-playing-for-soundtrack-your-brand' ),
					'errors'  => $validation['errors'],
				)
			);
		}

		update_option( 'fliix_soundtrack_mappings', $validation['mappings'] );

		wp_send_json_success(
			array(
				'mappings' => $validation['mappings'],
				'message'  => __( 'Mappings saved successfully.', 'fliix-now-playing-for-soundtrack-your-brand' ),
			)
		);
	}

	/**
	 * Validate and sanitize slug mappings.
	 *
	 * @param array<string, string> $raw_mappings Zone ID to slug map from POST.
	 * @return array{mappings: array<string, string>, errors: array<string, string>}
	 */
	private function validate_mappings( array $raw_mappings ): array {
		$mappings      = array();
		$errors        = array();
		$seen_slugs    = array();
		$slug_pattern  = '/^[a-z0-9_-]+$/';

		foreach ( $raw_mappings as $zone_id => $slug ) {
			$zone_id = sanitize_text_field( (string) $zone_id );
			$slug    = sanitize_title( (string) $slug );

			if ( empty( $slug ) ) {
				continue;
			}

			if ( ! preg_match( $slug_pattern, $slug ) ) {
				$errors[ $zone_id ] = __( 'Invalid slug format.', 'fliix-now-playing-for-soundtrack-your-brand' );
				continue;
			}

			if ( isset( $seen_slugs[ $slug ] ) ) {
				$errors[ $zone_id ] = __( 'Duplicate slug.', 'fliix-now-playing-for-soundtrack-your-brand' );
				continue;
			}

			$seen_slugs[ $slug ] = true;
			$mappings[ $slug ]   = $zone_id;
		}

		return array(
			'mappings' => $mappings,
			'errors'   => $errors,
		);
	}
}