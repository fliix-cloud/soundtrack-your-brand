<?php
/**
 * Now playing shortcode handler.
 *
 * @package SoundtrackYourBrand
 */

namespace SoundtrackYourBrand\Frontend;

use SoundtrackYourBrand\Cache\NowPlayingCache;
use SoundtrackYourBrand\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and handles the [syb_nowplaying] shortcode.
 */
class Shortcode {

	/**
	 * Cache handler.
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
	 * Whether frontend assets have been enqueued this request.
	 *
	 * @var bool
	 */
	private static bool $assets_enqueued = false;

	/**
	 * Constructor.
	 *
	 * @param NowPlayingCache $cache    Cache handler.
	 * @param Renderer        $renderer Renderer instance.
	 */
	public function __construct( NowPlayingCache $cache, Renderer $renderer ) {
		$this->cache    = $cache;
		$this->renderer = $renderer;

		add_shortcode( 'syb_nowplaying', array( $this, 'render_shortcode' ) );

		add_action( 'wp_ajax_syb_refresh_nowplaying', array( $this, 'ajax_refresh_nowplaying' ) );
		add_action( 'wp_ajax_nopriv_syb_refresh_nowplaying', array( $this, 'ajax_refresh_nowplaying' ) );
	}

	/**
	 * Render the shortcode output.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ): string {
		$atts = $this->normalize_atts( $atts );

		$this->enqueue_assets();

		$result = $this->render_widget( $atts['slug'], $atts, true );

		return $result['html'];
	}

	/**
	 * AJAX: return refreshed widget HTML for live updates.
	 */
	public function ajax_refresh_nowplaying(): void {
		$slug = sanitize_title( wp_unslash( $_POST['slug'] ?? '' ) );

		if ( empty( $slug ) ) {
			wp_send_json_error( array( 'message' => __( 'No slug specified.', 'soundtrack-your-brand' ) ) );
		}

		$atts = $this->decode_refresh_atts( wp_unslash( $_POST['atts'] ?? '' ) );
		$result = $this->render_widget( $slug, $atts, true );

		wp_send_json_success(
			array(
				'html'      => $result['html'],
				'state'     => $result['state'],
				'track_key' => $result['track_key'],
			)
		);
	}

	/**
	 * Build widget output for a mapped slug.
	 *
	 * @param string               $slug         Sound zone slug.
	 * @param array<string, string> $atts         Shortcode attributes.
	 * @param bool                 $live_refresh Whether to enable live refresh attributes.
	 * @return array{html: string, state: string, track_key: string}
	 */
	public function render_widget( string $slug, array $atts = array(), bool $live_refresh = false ): array {
		$settings = $this->build_settings( $atts );
		$slug     = sanitize_title( $slug );

		if ( empty( $slug ) ) {
			$settings['error_text'] = __( 'No slug specified.', 'soundtrack-your-brand' );

			return $this->build_widget_result(
				$this->renderer->render( null, $settings, 'error' ),
				'error',
				null
			);
		}

		$zone_id = $this->resolve_zone_id( $slug );

		if ( null === $zone_id ) {
			$settings['error_text'] = sprintf(
				/* translators: %s: slug name */
				__( 'Unknown slug: %s', 'soundtrack-your-brand' ),
				$slug
			);

			return $this->build_widget_result(
				$this->renderer->render( null, $settings, 'error' ),
				'error',
				null
			);
		}

		if ( $live_refresh ) {
			$settings['live_refresh']  = true;
			$settings['slug']            = $slug;
			$settings['refresh_atts']    = $this->encode_refresh_atts( $atts );
		}

		$result = $this->cache->get_now_playing( $zone_id );

		if ( null !== $result['error'] ) {
			$settings['error_text'] = $result['error']->get_error_message();

			return $this->build_widget_result(
				$this->renderer->render( null, $settings, 'error' ),
				'error',
				null
			);
		}

		$data = $result['data'];

		if ( empty( $data ) || empty( $data['has_track'] ) ) {
			return $this->build_widget_result(
				$this->renderer->render( $data, $settings, 'empty' ),
				'empty',
				$data
			);
		}

		return $this->build_widget_result(
			$this->renderer->render( $data, $settings, 'track' ),
			'track',
			$data
		);
	}

	/**
	 * Build a normalized widget result payload.
	 *
	 * @param string                    $html  Rendered HTML.
	 * @param string                    $state Render state.
	 * @param array<string, mixed>|null $data  Track data.
	 * @return array{html: string, state: string, track_key: string}
	 */
	private function build_widget_result( string $html, string $state, ?array $data ): array {
		return array(
			'html'      => $html,
			'state'     => $state,
			'track_key' => Renderer::build_track_key( $state, $data ),
		);
	}

	/**
	 * Normalize shortcode attributes.
	 *
	 * @param array<string, string>|string $atts Raw shortcode attributes.
	 * @return array<string, string>
	 */
	private function normalize_atts( $atts ): array {
		return shortcode_atts(
			array(
				'slug'        => '',
				'design'      => '',
				'show_image'  => '',
				'show_artist' => '',
				'class'       => '',
			),
			$atts,
			'syb_nowplaying'
		);
	}

	/**
	 * Resolve a slug to a zone ID.
	 *
	 * @param string $slug Sound zone slug.
	 * @return string|null
	 */
	private function resolve_zone_id( string $slug ): ?string {
		$mappings = get_option( 'soundtrack_mappings', array() );

		if ( ! is_array( $mappings ) || ! isset( $mappings[ $slug ] ) ) {
			return null;
		}

		return (string) $mappings[ $slug ];
	}

	/**
	 * Build display settings with shortcode overrides.
	 *
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return array<string, mixed>
	 */
	private function build_settings( array $atts ): array {
		$settings = Plugin::get_display_settings();

		if ( ! empty( $atts['design'] ) ) {
			$settings['template'] = sanitize_key( $atts['design'] );
		}

		if ( '' !== $atts['show_image'] ) {
			$settings['show_image'] = filter_var( $atts['show_image'], FILTER_VALIDATE_BOOLEAN );
		}

		if ( '' !== $atts['show_artist'] ) {
			$settings['show_artist'] = filter_var( $atts['show_artist'], FILTER_VALIDATE_BOOLEAN );
		}

		if ( ! empty( $atts['class'] ) ) {
			$classes = array_filter( array_map( 'sanitize_html_class', preg_split( '/\s+/', $atts['class'] ) ) );
			if ( ! empty( $classes ) ) {
				$settings['extra_class'] = implode( ' ', $classes );
			}
		}

		return $settings;
	}

	/**
	 * Encode shortcode overrides for live refresh requests.
	 *
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return string
	 */
	private function encode_refresh_atts( array $atts ): string {
		$payload = array();

		foreach ( array( 'design', 'show_image', 'show_artist', 'class' ) as $key ) {
			if ( '' !== ( $atts[ $key ] ?? '' ) ) {
				$payload[ $key ] = $atts[ $key ];
			}
		}

		if ( empty( $payload ) ) {
			return '';
		}

		return (string) wp_json_encode( $payload );
	}

	/**
	 * Decode shortcode overrides from a live refresh request.
	 *
	 * @param string $raw JSON-encoded attributes.
	 * @return array<string, string>
	 */
	private function decode_refresh_atts( string $raw ): array {
		if ( '' === $raw ) {
			return $this->normalize_atts( array() );
		}

		$decoded = json_decode( $raw, true );

		if ( ! is_array( $decoded ) ) {
			return $this->normalize_atts( array() );
		}

		return $this->normalize_atts( $decoded );
	}

	/**
	 * Enqueue frontend assets once per request.
	 */
	private function enqueue_assets(): void {
		if ( self::$assets_enqueued ) {
			return;
		}

		wp_enqueue_style(
			'syb-frontend',
			SYB_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			SYB_VERSION
		);

		wp_enqueue_script(
			'syb-frontend',
			SYB_PLUGIN_URL . 'assets/js/frontend.js',
			array(),
			SYB_VERSION,
			true
		);

		wp_localize_script(
			'syb-frontend',
			'sybFrontend',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'interval' => Plugin::get_update_interval(),
				'action'   => 'syb_refresh_nowplaying',
			)
		);

		self::$assets_enqueued = true;
	}
}