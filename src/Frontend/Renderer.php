<?php
/**
 * Now playing HTML renderer.
 *
 * @package SoundtrackYourBrand
 */

namespace SoundtrackYourBrand\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Renders now playing widgets with BEM-style markup.
 */
class Renderer {

	/**
	 * Valid template names.
	 *
	 * @var array<int, string>
	 */
	private const TEMPLATES = array( 'classic', 'compact', 'modern', 'minimal' );

	/**
	 * Render a now playing widget.
	 *
	 * @param array<string, mixed>|null $data     Track data or null.
	 * @param array<string, mixed>      $settings Display settings.
	 * @param string                    $state    Render state: track, empty, error.
	 * @return string
	 */
	public function render( ?array $data, array $settings, string $state = 'track' ): string {
		$template = $this->sanitize_template( $settings['template'] ?? 'classic' );
		$classes  = array(
			'syb-nowplaying',
			'syb-nowplaying--' . $template,
			'syb-nowplaying--align-' . $this->sanitize_alignment( $settings['alignment'] ?? 'left' ),
		);

		if ( 'empty' === $state ) {
			$classes[] = 'syb-nowplaying--empty';
		} elseif ( 'error' === $state ) {
			$classes[] = 'syb-nowplaying--error';
		}

		if ( ! empty( $settings['extra_class'] ) ) {
			foreach ( preg_split( '/\s+/', (string) $settings['extra_class'] ) as $extra ) {
				if ( $extra ) {
					$classes[] = sanitize_html_class( $extra );
				}
			}
		}

		$style      = $this->build_inline_style( $settings );
		$settings   = $this->apply_render_metadata( $settings, $state, $data );
		$data_attrs = $this->build_data_attributes( $settings );

		if ( 'track' !== $state || empty( $data ) || empty( $data['has_track'] ) ) {
			return $this->render_fallback( $classes, $style, $settings, $state );
		}

		$show_image  = ! empty( $settings['show_image'] );
		$show_artist = ! empty( $settings['show_artist'] );
		$image_size  = $this->resolve_image_size( $settings );

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" style="<?php echo esc_attr( $style ); ?>"<?php echo $data_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-live="polite">
			<?php
			switch ( $template ) {
				case 'compact':
					$this->render_compact( $data, $show_image, $show_artist, $image_size );
					break;
				case 'modern':
					$this->render_modern( $data, $show_image, $show_artist, $image_size );
					break;
				case 'minimal':
					$this->render_minimal( $data, $show_artist );
					break;
				default:
					$this->render_classic( $data, $show_image, $show_artist, $image_size );
					break;
			}
			?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render fallback/empty/error state.
	 *
	 * @param array<int, string>   $classes  CSS classes.
	 * @param string               $style    Inline style.
	 * @param array<string, mixed> $settings Display settings.
	 * @param string               $state    Render state.
	 * @return string
	 */
	private function render_fallback( array $classes, string $style, array $settings, string $state ): string {
		$text = $settings['fallback_text'] ?? __( 'Nothing playing right now.', 'soundtrack-your-brand' );

		if ( 'error' === $state ) {
			$text = $settings['error_text'] ?? $text;
		}

		$data_attrs = $this->build_data_attributes( $settings );

		return sprintf(
			'<div class="%1$s" style="%2$s"%3$s aria-live="polite"><p class="syb-nowplaying__fallback">%4$s</p></div>',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $style ),
			$data_attrs,
			esc_html( $text )
		);
	}

	/**
	 * Attach render metadata used for live refresh comparisons.
	 *
	 * @param array<string, mixed>      $settings Display settings.
	 * @param string                    $state    Render state.
	 * @param array<string, mixed>|null $data     Track data.
	 * @return array<string, mixed>
	 */
	private function apply_render_metadata( array $settings, string $state, ?array $data ): array {
		$settings['render_state'] = $state;
		$settings['track_key']    = self::build_track_key( $state, $data );

		return $settings;
	}

	/**
	 * Build a stable key representing the currently displayed track.
	 *
	 * @param string                    $state Render state.
	 * @param array<string, mixed>|null $data  Track data.
	 * @return string
	 */
	public static function build_track_key( string $state, ?array $data ): string {
		if ( 'track' !== $state || empty( $data ) || empty( $data['has_track'] ) ) {
			return $state;
		}

		return md5(
			( $data['started_at'] ?? '' ) . '|' .
			( $data['track_name'] ?? '' ) . '|' .
			( $data['artists'] ?? '' )
		);
	}

	/**
	 * Build data attributes for live frontend refresh.
	 *
	 * @param array<string, mixed> $settings Display settings.
	 * @return string
	 */
	private function build_data_attributes( array $settings ): string {
		if ( empty( $settings['live_refresh'] ) || empty( $settings['slug'] ) ) {
			return '';
		}

		$attributes = array(
			'data-syb-live'       => 'true',
			'data-syb-slug'       => (string) $settings['slug'],
			'data-syb-state'      => (string) ( $settings['render_state'] ?? 'track' ),
			'data-syb-track-key'  => (string) ( $settings['track_key'] ?? '' ),
		);

		if ( ! empty( $settings['refresh_atts'] ) ) {
			$attributes['data-syb-atts'] = (string) $settings['refresh_atts'];
		}

		$parts = array();

		foreach ( $attributes as $name => $value ) {
			$parts[] = sprintf( ' %s="%s"', $name, esc_attr( $value ) );
		}

		return implode( '', $parts );
	}

	/**
	 * Render classic template.
	 *
	 * @param array<string, mixed> $data        Track data.
	 * @param bool                 $show_image  Whether to show album image.
	 * @param bool                 $show_artist Whether to show artist.
	 * @param int                  $image_size  Image size in px.
	 */
	private function render_classic( array $data, bool $show_image, bool $show_artist, int $image_size ): void {
		?>
		<div class="syb-nowplaying__inner">
			<?php if ( $show_image && ! empty( $data['image_url'] ) ) : ?>
				<div class="syb-nowplaying__image">
					<img src="<?php echo esc_url( $data['image_url'] ); ?>"
						alt="<?php echo esc_attr( sprintf( __( 'Album art for %s', 'soundtrack-your-brand' ), $data['album_name'] ) ); ?>"
						width="<?php echo esc_attr( (string) $image_size ); ?>"
						height="<?php echo esc_attr( (string) $image_size ); ?>"
						loading="lazy" />
				</div>
			<?php endif; ?>
			<div class="syb-nowplaying__meta">
				<p class="syb-nowplaying__song"><?php echo esc_html( $data['track_name'] ); ?></p>
				<?php if ( $show_artist && ! empty( $data['artists'] ) ) : ?>
					<p class="syb-nowplaying__artist"><?php echo esc_html( $data['artists'] ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render compact template.
	 *
	 * @param array<string, mixed> $data        Track data.
	 * @param bool                 $show_image  Whether to show album image.
	 * @param bool                 $show_artist Whether to show artist.
	 * @param int                  $image_size  Image size in px.
	 */
	private function render_compact( array $data, bool $show_image, bool $show_artist, int $image_size ): void {
		?>
		<div class="syb-nowplaying__inner">
			<?php if ( $show_image && ! empty( $data['image_url'] ) ) : ?>
				<img class="syb-nowplaying__image syb-nowplaying__image--inline"
					src="<?php echo esc_url( $data['image_url'] ); ?>"
					alt="<?php echo esc_attr( sprintf( __( 'Album art for %s', 'soundtrack-your-brand' ), $data['album_name'] ) ); ?>"
					width="<?php echo esc_attr( (string) $image_size ); ?>"
					height="<?php echo esc_attr( (string) $image_size ); ?>"
					loading="lazy" />
			<?php endif; ?>
			<span class="syb-nowplaying__song"><?php echo esc_html( $data['track_name'] ); ?></span>
			<?php if ( $show_artist && ! empty( $data['artists'] ) ) : ?>
				<span class="syb-nowplaying__separator">—</span>
				<span class="syb-nowplaying__artist"><?php echo esc_html( $data['artists'] ); ?></span>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render modern card template.
	 *
	 * @param array<string, mixed> $data        Track data.
	 * @param bool                 $show_image  Whether to show album image.
	 * @param bool                 $show_artist Whether to show artist.
	 * @param int                  $image_size  Image size in px.
	 */
	private function render_modern( array $data, bool $show_image, bool $show_artist, int $image_size ): void {
		?>
		<div class="syb-nowplaying__card">
			<?php if ( $show_image && ! empty( $data['image_url'] ) ) : ?>
				<div class="syb-nowplaying__image syb-nowplaying__image--centered">
					<img src="<?php echo esc_url( $data['image_url'] ); ?>"
						alt="<?php echo esc_attr( sprintf( __( 'Album art for %s', 'soundtrack-your-brand' ), $data['album_name'] ) ); ?>"
						width="<?php echo esc_attr( (string) $image_size ); ?>"
						height="<?php echo esc_attr( (string) $image_size ); ?>"
						loading="lazy" />
				</div>
			<?php endif; ?>
			<p class="syb-nowplaying__song"><?php echo esc_html( $data['track_name'] ); ?></p>
			<?php if ( $show_artist && ! empty( $data['artists'] ) ) : ?>
				<p class="syb-nowplaying__artist"><?php echo esc_html( $data['artists'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render minimal template.
	 *
	 * @param array<string, mixed> $data        Track data.
	 * @param bool                 $show_artist Whether to show artist.
	 */
	private function render_minimal( array $data, bool $show_artist ): void {
		?>
		<p class="syb-nowplaying__minimal">
			<?php if ( $show_artist && ! empty( $data['artists'] ) ) : ?>
				<span class="syb-nowplaying__artist"><?php echo esc_html( $data['artists'] ); ?></span>
				<span class="syb-nowplaying__separator"> – </span>
			<?php endif; ?>
			<span class="syb-nowplaying__song"><?php echo esc_html( $data['track_name'] ); ?></span>
		</p>
		<?php
	}

	/**
	 * Build inline CSS custom properties.
	 *
	 * @param array<string, mixed> $settings Display settings.
	 * @return string
	 */
	private function build_inline_style( array $settings ): string {
		$image_size = $this->resolve_image_size( $settings );

		$vars = array(
			'--syb-song-color'       => $settings['song_color'] ?? '#111111',
			'--syb-artist-color'     => $settings['artist_color'] ?? '#666666',
			'--syb-song-font-size'   => ( $settings['song_font_size'] ?? 18 ) . 'px',
			'--syb-artist-font-size' => ( $settings['artist_font_size'] ?? 14 ) . 'px',
			'--syb-song-font-weight' => $settings['song_font_weight'] ?? '600',
			'--syb-artist-font-weight' => $settings['artist_font_weight'] ?? '400',
			'--syb-image-size'       => $image_size . 'px',
		);

		$parts = array();
		foreach ( $vars as $key => $value ) {
			$parts[] = $key . ':' . $value;
		}

		return implode( ';', $parts );
	}

	/**
	 * Resolve image size in pixels.
	 *
	 * @param array<string, mixed> $settings Display settings.
	 * @return int
	 */
	private function resolve_image_size( array $settings ): int {
		$size = $settings['image_size'] ?? 'medium';

		return match ( $size ) {
			'small'  => 48,
			'large'  => 120,
			'custom' => max( 16, min( 400, (int) ( $settings['image_size_custom'] ?? 80 ) ) ),
			default  => 80,
		};
	}

	/**
	 * Sanitize template name.
	 *
	 * @param string $template Template name.
	 * @return string
	 */
	private function sanitize_template( string $template ): string {
		return in_array( $template, self::TEMPLATES, true ) ? $template : 'classic';
	}

	/**
	 * Sanitize alignment value.
	 *
	 * @param string $alignment Alignment value.
	 * @return string
	 */
	private function sanitize_alignment( string $alignment ): string {
		return in_array( $alignment, array( 'left', 'center', 'right' ), true ) ? $alignment : 'left';
	}
}