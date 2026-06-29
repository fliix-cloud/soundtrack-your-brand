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
	}

	/**
	 * Render the shortcode output.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ): string {
		$atts = shortcode_atts(
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

		$this->enqueue_assets();

		$settings = $this->build_settings( $atts );
		$slug     = sanitize_title( $atts['slug'] );

		if ( empty( $slug ) ) {
			$settings['error_text'] = __( 'No slug specified.', 'soundtrack-your-brand' );
			return $this->renderer->render( null, $settings, 'error' );
		}

		$zone_id = $this->resolve_zone_id( $slug );

		if ( null === $zone_id ) {
			$settings['error_text'] = sprintf(
				/* translators: %s: slug name */
				__( 'Unknown slug: %s', 'soundtrack-your-brand' ),
				$slug
			);
			return $this->renderer->render( null, $settings, 'error' );
		}

		$result = $this->cache->get_now_playing( $zone_id );

		if ( null !== $result['error'] ) {
			$settings['error_text'] = $result['error']->get_error_message();
			return $this->renderer->render( null, $settings, 'error' );
		}

		$data = $result['data'];

		if ( empty( $data ) || empty( $data['has_track'] ) ) {
			return $this->renderer->render( $data, $settings, 'empty' );
		}

		return $this->renderer->render( $data, $settings, 'track' );
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
	 * Enqueue frontend styles once per request.
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

		self::$assets_enqueued = true;
	}
}