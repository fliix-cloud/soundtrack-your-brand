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
	 * Minimum height/width ratio for usable square album art.
	 */
	private const MIN_ALBUM_ASPECT_RATIO = 0.75;

	/**
	 * Maximum height/width ratio for usable square album art.
	 */
	private const MAX_ALBUM_ASPECT_RATIO = 1.25;

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

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" style="<?php echo esc_attr( $style ); ?>"<?php echo $data_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-live="polite">
			<?php
			switch ( $template ) {
				case 'compact':
					$this->render_compact( $data, $settings );
					break;
				case 'modern':
					$this->render_modern( $data, $settings );
					break;
				case 'minimal':
					$this->render_minimal( $data, $settings );
					break;
				default:
					$this->render_classic( $data, $settings );
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
	 * Render classic template.
	 *
	 * @param array<string, mixed> $data     Track data.
	 * @param array<string, mixed> $settings Display settings.
	 */
	private function render_classic( array $data, array $settings ): void {
		$image_size = $this->resolve_image_size( $settings );
		?>
		<div class="syb-nowplaying__inner">
			<?php $this->render_media( $data, $settings, $image_size ); ?>
			<div class="syb-nowplaying__meta">
				<?php $this->render_prefix( $settings, 'p' ); ?>
				<p class="syb-nowplaying__song"><?php echo esc_html( $data['track_name'] ); ?></p>
				<?php if ( ! empty( $settings['show_artist'] ) && ! empty( $data['artists'] ) ) : ?>
					<p class="syb-nowplaying__artist"><?php echo esc_html( $data['artists'] ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render compact template.
	 *
	 * @param array<string, mixed> $data     Track data.
	 * @param array<string, mixed> $settings Display settings.
	 */
	private function render_compact( array $data, array $settings ): void {
		$image_size = $this->resolve_image_size( $settings );
		?>
		<div class="syb-nowplaying__inner">
			<?php $this->render_media( $data, $settings, $image_size, true ); ?>
			<div class="syb-nowplaying__text">
				<?php $this->render_prefix( $settings, 'span', true ); ?>
				<span class="syb-nowplaying__song"><?php echo esc_html( $data['track_name'] ); ?></span>
				<?php if ( ! empty( $settings['show_artist'] ) && ! empty( $data['artists'] ) ) : ?>
					<span class="syb-nowplaying__separator">—</span>
					<span class="syb-nowplaying__artist"><?php echo esc_html( $data['artists'] ); ?></span>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render modern card template.
	 *
	 * @param array<string, mixed> $data     Track data.
	 * @param array<string, mixed> $settings Display settings.
	 */
	private function render_modern( array $data, array $settings ): void {
		$image_size = $this->resolve_image_size( $settings );
		?>
		<div class="syb-nowplaying__card">
			<?php $this->render_media( $data, $settings, $image_size, false, true ); ?>
			<?php $this->render_prefix( $settings, 'p' ); ?>
			<p class="syb-nowplaying__song"><?php echo esc_html( $data['track_name'] ); ?></p>
			<?php if ( ! empty( $settings['show_artist'] ) && ! empty( $data['artists'] ) ) : ?>
				<p class="syb-nowplaying__artist"><?php echo esc_html( $data['artists'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render minimal template.
	 *
	 * @param array<string, mixed> $data     Track data.
	 * @param array<string, mixed> $settings Display settings.
	 */
	private function render_minimal( array $data, array $settings ): void {
		?>
		<p class="syb-nowplaying__minimal">
			<?php $this->render_prefix( $settings, 'span', true ); ?>
			<?php if ( ! empty( $settings['show_artist'] ) && ! empty( $data['artists'] ) ) : ?>
				<span class="syb-nowplaying__artist"><?php echo esc_html( $data['artists'] ); ?></span>
				<span class="syb-nowplaying__separator"> – </span>
			<?php endif; ?>
			<span class="syb-nowplaying__song"><?php echo esc_html( $data['track_name'] ); ?></span>
		</p>
		<?php
	}

	/**
	 * Render the prefix label when enabled.
	 *
	 * @param array<string, mixed> $settings  Display settings.
	 * @param string               $tag       HTML tag name.
	 * @param bool                 $inline    Whether the label is inline with track text.
	 */
	private function render_prefix( array $settings, string $tag = 'p', bool $inline = false ): void {
		if ( empty( $settings['show_prefix'] ) ) {
			return;
		}

		$text = trim( (string) ( $settings['prefix_text'] ?? '' ) );

		if ( '' === $text ) {
			return;
		}

		$class = 'syb-nowplaying__prefix';

		if ( $inline ) {
			$class .= ' syb-nowplaying__prefix--inline';
		}

		printf(
			'<%1$s class="%2$s">%3$s</%1$s>',
			tag_escape( $tag ),
			esc_attr( $class ),
			esc_html( $text )
		);

		if ( $inline ) {
			echo ' ';
		}
	}

	/**
	 * Render icon or album artwork.
	 *
	 * @param array<string, mixed> $data      Track data.
	 * @param array<string, mixed> $settings  Display settings.
	 * @param int                  $size      Media size in px.
	 * @param bool                 $inline    Whether image is inline (compact template).
	 * @param bool                 $centered  Whether image is centered (modern template).
	 */
	private function render_media( array $data, array $settings, int $size, bool $inline = false, bool $centered = false ): void {
		if ( empty( $settings['show_image'] ) ) {
			return;
		}

		if ( $this->should_use_album_art( $data, $settings ) ) {
			$this->render_album_image( $data, $size, $inline, $centered );
			return;
		}

		$this->render_music_icon( $size, $inline, $centered );
	}

	/**
	 * Render the default music icon.
	 *
	 * @param int  $size     Icon size in px.
	 * @param bool $inline   Whether icon is inline.
	 * @param bool $centered Whether icon is centered.
	 */
	private function render_music_icon( int $size, bool $inline = false, bool $centered = false ): void {
		$classes = array( 'syb-nowplaying__icon' );

		if ( $inline ) {
			$classes[] = 'syb-nowplaying__icon--inline';
		}

		if ( $centered ) {
			$classes[] = 'syb-nowplaying__icon--centered';
		}

		$icon_size = (int) round( $size * 0.55 );
		?>
		<span class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" aria-hidden="true">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="<?php echo esc_attr( (string) $icon_size ); ?>" height="<?php echo esc_attr( (string) $icon_size ); ?>" focusable="false">
				<path fill="currentColor" d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
			</svg>
		</span>
		<?php
	}

	/**
	 * Render album artwork when it is usable.
	 *
	 * @param array<string, mixed> $data     Track data.
	 * @param int                  $size     Image size in px.
	 * @param bool                 $inline   Whether image is inline.
	 * @param bool                 $centered Whether image is centered.
	 */
	private function render_album_image( array $data, int $size, bool $inline = false, bool $centered = false ): void {
		if ( empty( $data['image_url'] ) ) {
			$this->render_music_icon( $size, $inline, $centered );
			return;
		}

		$classes = array( 'syb-nowplaying__image' );

		if ( $inline ) {
			$classes[] = 'syb-nowplaying__image--inline';
		}

		if ( $centered ) {
			$classes[] = 'syb-nowplaying__image--centered';
		}

		if ( $this->is_wide_artwork( $data ) ) {
			$classes[] = 'syb-nowplaying__image--wide';
		}

		$tag   = $inline ? 'img' : 'div';
		$class = esc_attr( implode( ' ', $classes ) );

		if ( $inline ) {
			printf(
				'<img class="%1$s" src="%2$s" alt="%3$s" width="%4$d" height="%4$d" loading="lazy" />',
				$class,
				esc_url( $data['image_url'] ),
				esc_attr( sprintf( __( 'Album art for %s', 'soundtrack-your-brand' ), $data['album_name'] ) ),
				$size
			);
			return;
		}

		printf( '<div class="%s">', $class );
		printf(
			'<img src="%s" alt="%s" width="%d" height="%d" loading="lazy" />',
			esc_url( $data['image_url'] ),
			esc_attr( sprintf( __( 'Album art for %s', 'soundtrack-your-brand' ), $data['album_name'] ) ),
			$size,
			$size
		);
		echo '</div>';
	}

	/**
	 * Determine whether album art should be used instead of the icon.
	 *
	 * @param array<string, mixed> $data     Track data.
	 * @param array<string, mixed> $settings Display settings.
	 * @return bool
	 */
	private function should_use_album_art( array $data, array $settings ): bool {
		if ( 'album' !== ( $settings['image_display'] ?? 'icon' ) ) {
			return false;
		}

		if ( empty( $data['image_url'] ) ) {
			return false;
		}

		return $this->is_squareish_artwork( $data );
	}

	/**
	 * Check if artwork has a usable square aspect ratio.
	 *
	 * @param array<string, mixed> $data Track data.
	 * @return bool
	 */
	private function is_squareish_artwork( array $data ): bool {
		$width  = (int) ( $data['image_width'] ?? 0 );
		$height = (int) ( $data['image_height'] ?? 0 );

		if ( $width < 1 || $height < 1 ) {
			return false;
		}

		$ratio = $height / $width;

		return $ratio >= self::MIN_ALBUM_ASPECT_RATIO && $ratio <= self::MAX_ALBUM_ASPECT_RATIO;
	}

	/**
	 * Check if artwork is a wide banner.
	 *
	 * @param array<string, mixed> $data Track data.
	 * @return bool
	 */
	private function is_wide_artwork( array $data ): bool {
		$width  = (int) ( $data['image_width'] ?? 0 );
		$height = (int) ( $data['image_height'] ?? 0 );

		if ( $width < 1 || $height < 1 ) {
			return false;
		}

		return ( $height / $width ) < self::MIN_ALBUM_ASPECT_RATIO;
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
	 * Build inline CSS custom properties.
	 *
	 * @param array<string, mixed> $settings Display settings.
	 * @return string
	 */
	private function build_inline_style( array $settings ): string {
		$image_size = $this->resolve_image_size( $settings );

		$vars = array(
			'--syb-song-color'         => $settings['song_color'] ?? '#111111',
			'--syb-artist-color'       => $settings['artist_color'] ?? '#666666',
			'--syb-prefix-color'       => $settings['prefix_color'] ?? '#444444',
			'--syb-song-font-size'     => ( $settings['song_font_size'] ?? 18 ) . 'px',
			'--syb-artist-font-size'   => ( $settings['artist_font_size'] ?? 14 ) . 'px',
			'--syb-prefix-font-size'   => ( $settings['prefix_font_size'] ?? 13 ) . 'px',
			'--syb-song-font-weight'   => $settings['song_font_weight'] ?? '600',
			'--syb-artist-font-weight' => $settings['artist_font_weight'] ?? '400',
			'--syb-image-size'         => $image_size . 'px',
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