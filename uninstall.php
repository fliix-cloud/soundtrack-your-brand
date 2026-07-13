<?php
/**
 * Uninstall routine — removes all plugin data.
 *
 * @package SoundtrackYourBrand
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'fliix_api_base_url' );
delete_option( 'fliix_api_token' );
delete_option( 'fliix_update_interval' );
delete_option( 'fliix_mappings' );
delete_option( 'fliix_zones_cache' );
delete_option( 'fliix_display_settings' );

global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_fliix_nowplaying_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_fliix_nowplaying_' ) . '%'
	)
);