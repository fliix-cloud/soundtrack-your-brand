<?php
/**
 * Plugin activation handler.
 *
 * @package SoundtrackYourBrand
 */

namespace SoundtrackYourBrand;

defined( 'ABSPATH' ) || exit;

/**
 * Sets default options on plugin activation.
 */
class Activator {

	/**
	 * Run activation routines.
	 */
	public static function activate(): void {
		if ( false === get_option( 'soundtrack_api_base_url' ) ) {
			add_option( 'soundtrack_api_base_url', 'https://api.soundtrackyourbrand.com/v2' );
		}

		if ( false === get_option( 'soundtrack_api_token' ) ) {
			add_option( 'soundtrack_api_token', '' );
		}

		if ( false === get_option( 'soundtrack_update_interval' ) ) {
			add_option( 'soundtrack_update_interval', 30 );
		}

		if ( false === get_option( 'soundtrack_mappings' ) ) {
			add_option( 'soundtrack_mappings', array() );
		}

		if ( false === get_option( 'soundtrack_zones_cache' ) ) {
			add_option( 'soundtrack_zones_cache', array() );
		}

		if ( false === get_option( 'soundtrack_display_settings' ) ) {
			add_option( 'soundtrack_display_settings', self::default_display_settings() );
		}
	}

	/**
	 * Default display settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function default_display_settings(): array {
		return array(
			'template'           => 'classic',
			'show_image'         => true,
			'show_artist'        => true,
			'image_size'         => 'medium',
			'image_size_custom'  => 80,
			'song_color'         => '#111111',
			'artist_color'       => '#666666',
			'song_font_size'     => 18,
			'artist_font_size'   => 14,
			'song_font_weight'   => '600',
			'artist_font_weight' => '400',
			'alignment'          => 'left',
			'fallback_text'      => __( 'Nothing playing right now.', 'soundtrack-your-brand' ),
		);
	}
}