<?php
/**
 * Lazy transient cache with in-request deduplication.
 *
 * @package SoundtrackYourBrand
 */

namespace SoundtrackYourBrand\Cache;

use SoundtrackYourBrand\Api\Client;
use SoundtrackYourBrand\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Manages now-playing data caching.
 */
class NowPlayingCache {

	/**
	 * Transient key prefix.
	 */
	private const TRANSIENT_PREFIX = 'syb_nowplaying_';

	/**
	 * API client instance.
	 *
	 * @var Client
	 */
	private Client $api_client;

	/**
	 * In-request resolved cache keyed by zone ID.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static array $resolved = array();

	/**
	 * In-request pending fetches keyed by zone ID.
	 *
	 * @var array<string, bool>
	 */
	private static array $pending = array();

	/**
	 * Constructor.
	 *
	 * @param Client $api_client API client instance.
	 */
	public function __construct( Client $api_client ) {
		$this->api_client = $api_client;
	}

	/**
	 * Build transient key for a zone ID.
	 *
	 * @param string $zone_id Sound zone ID.
	 * @return string
	 */
	public function get_transient_key( string $zone_id ): string {
		return self::TRANSIENT_PREFIX . md5( $zone_id );
	}

	/**
	 * Get now playing data for a zone (cached or fresh).
	 *
	 * @param string $zone_id Sound zone ID.
	 * @return array{data: array<string, mixed>|null, error: \WP_Error|null}
	 */
	public function get_now_playing( string $zone_id ): array {
		if ( isset( self::$resolved[ $zone_id ] ) ) {
			return array(
				'data'  => self::$resolved[ $zone_id ],
				'error' => null,
			);
		}

		$transient_key = $this->get_transient_key( $zone_id );
		$cached        = get_transient( $transient_key );

		if ( false !== $cached && is_array( $cached ) ) {
			self::$resolved[ $zone_id ] = $cached;

			return array(
				'data'  => $cached,
				'error' => null,
			);
		}

		if ( isset( self::$pending[ $zone_id ] ) ) {
			return array(
				'data'  => null,
				'error' => new \WP_Error(
					'syb_pending',
					__( 'Now playing data is being fetched.', 'soundtrack-your-brand' )
				),
			);
		}

		self::$pending[ $zone_id ] = true;

		$result = $this->api_client->fetch_now_playing( $zone_id );

		unset( self::$pending[ $zone_id ] );

		if ( is_wp_error( $result ) ) {
			return array(
				'data'  => null,
				'error' => $result,
			);
		}

		$ttl = Plugin::get_update_interval();
		set_transient( $transient_key, $result, $ttl );

		self::$resolved[ $zone_id ] = $result;

		return array(
			'data'  => $result,
			'error' => null,
		);
	}

	/**
	 * Flush cached now playing data for a zone.
	 *
	 * @param string $zone_id Sound zone ID.
	 */
	public function flush_zone( string $zone_id ): void {
		delete_transient( $this->get_transient_key( $zone_id ) );
		unset( self::$resolved[ $zone_id ], self::$pending[ $zone_id ] );
	}
}