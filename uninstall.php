<?php
/**
 * Uninstall routine — removes all plugin data.
 *
 * @package SoundtrackYourBrand
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'soundtrack_api_base_url' );
delete_option( 'soundtrack_api_token' );
delete_option( 'soundtrack_update_interval' );
delete_option( 'soundtrack_mappings' );
delete_option( 'soundtrack_zones_cache' );
delete_option( 'soundtrack_display_settings' );

global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_syb_nowplaying_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_syb_nowplaying_' ) . '%'
	)
);