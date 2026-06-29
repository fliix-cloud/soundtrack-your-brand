<?php
/**
 * Soundtrack GraphQL API client.
 *
 * @package SoundtrackYourBrand
 */

namespace SoundtrackYourBrand\Api;

defined( 'ABSPATH' ) || exit;

/**
 * Handles GraphQL requests to the Soundtrack API.
 */
class Client {

	/**
	 * GraphQL query to fetch all sound zones.
	 */
	private const QUERY_SOUNDZONES = 'query { me { ... on PublicAPIClient { accounts(first: 100) { edges { node { businessName locations(first: 100) { edges { node { name soundZones(first: 100) { edges { node { id name isPaired } } } } } } } } } } } }';

	/**
	 * GraphQL query to fetch now playing for a zone.
	 */
	private const QUERY_NOW_PLAYING = 'query($zoneId: ID!) { nowPlaying(soundZone: $zoneId) { startedAt track { name artists { name } album { name image { url width height } } } } }';

	/**
	 * Execute a GraphQL request.
	 *
	 * @param string               $query     GraphQL query string.
	 * @param array<string, mixed> $variables Optional query variables.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function request( string $query, array $variables = array() ) {
		$base_url = get_option( 'soundtrack_api_base_url', 'https://api.soundtrackyourbrand.com/v2' );
		$token    = get_option( 'soundtrack_api_token', '' );

		if ( empty( $token ) ) {
			return new \WP_Error(
				'syb_missing_token',
				__( 'API token is not configured.', 'soundtrack-your-brand' )
			);
		}

		$body = array( 'query' => $query );

		if ( ! empty( $variables ) ) {
			$body['variables'] = $variables;
		}

		$response = wp_remote_post(
			esc_url_raw( $base_url ),
			array(
				'timeout' => 15,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Basic ' . $token,
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$cost   = wp_remote_retrieve_header( $response, 'x-ratelimiting-cost' );
			$tokens = wp_remote_retrieve_header( $response, 'x-ratelimiting-tokens-available' );
			if ( $cost || $tokens ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( sprintf( 'SYB API rate limit — cost: %s, tokens available: %s', $cost, $tokens ) );
			}
		}

		if ( $status_code < 200 || $status_code >= 300 ) {
			return new \WP_Error(
				'syb_http_error',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'API request failed with HTTP status %d.', 'soundtrack-your-brand' ),
					$status_code
				)
			);
		}

		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $decoded ) ) {
			return new \WP_Error(
				'syb_invalid_response',
				__( 'Invalid API response.', 'soundtrack-your-brand' )
			);
		}

		if ( ! empty( $decoded['errors'] ) && is_array( $decoded['errors'] ) ) {
			$messages = array_map(
				static function ( $error ) {
					return is_array( $error ) && isset( $error['message'] )
						? (string) $error['message']
						: __( 'Unknown GraphQL error.', 'soundtrack-your-brand' );
				},
				$decoded['errors']
			);

			return new \WP_Error( 'syb_graphql_error', implode( ' ', $messages ) );
		}

		return $decoded;
	}

	/**
	 * Fetch and flatten all sound zones from the API.
	 *
	 * @return array<int, array<string, mixed>>|\WP_Error
	 */
	public function fetch_sound_zones() {
		$response = $this->request( self::QUERY_SOUNDZONES );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$zones = $this->parse_sound_zones( $response );

		if ( empty( $zones ) ) {
			return new \WP_Error(
				'syb_no_zones',
				__( 'No sound zones found for this API token.', 'soundtrack-your-brand' )
			);
		}

		return $zones;
	}

	/**
	 * Parse Relay-style sound zone response into flat rows.
	 *
	 * @param array<string, mixed> $response API response.
	 * @return array<int, array<string, mixed>>
	 */
	private function parse_sound_zones( array $response ): array {
		$zones    = array();
		$accounts = $response['data']['me']['accounts']['edges'] ?? array();

		if ( ! is_array( $accounts ) ) {
			return $zones;
		}

		foreach ( $accounts as $account_edge ) {
			$account_node   = $account_edge['node'] ?? array();
			$business_name  = $account_node['businessName'] ?? '';
			$location_edges = $account_node['locations']['edges'] ?? array();

			if ( ! is_array( $location_edges ) ) {
				continue;
			}

			foreach ( $location_edges as $location_edge ) {
				$location_node = $location_edge['node'] ?? array();
				$location_name = $location_node['name'] ?? '';
				$zone_edges    = $location_node['soundZones']['edges'] ?? array();

				if ( ! is_array( $zone_edges ) ) {
					continue;
				}

				foreach ( $zone_edges as $zone_edge ) {
					$zone_node = $zone_edge['node'] ?? array();
					$zone_id   = $zone_node['id'] ?? '';
					$zone_name = $zone_node['name'] ?? '';

					if ( empty( $zone_id ) ) {
						continue;
					}

					$zones[] = array(
						'zone_id'       => $zone_id,
						'name'          => $zone_name,
						'is_paired'     => ! empty( $zone_node['isPaired'] ),
						'business_name' => $business_name,
						'location_name' => $location_name,
						'full_label'    => $location_name
							? $location_name . ' – ' . $zone_name
							: $business_name . ' – ' . $zone_name,
					);
				}
			}
		}

		return $zones;
	}

	/**
	 * Build zones cache option from parsed zone rows.
	 *
	 * @param array<int, array<string, mixed>> $zones Parsed zone rows.
	 * @return array<string, array<string, mixed>>
	 */
	public function build_zones_cache( array $zones ): array {
		$cache = array();

		foreach ( $zones as $zone ) {
			$cache[ $zone['zone_id'] ] = array(
				'name'          => $zone['name'],
				'full_label'    => $zone['full_label'],
				'business_name' => $zone['business_name'],
				'location_name' => $zone['location_name'],
				'is_paired'     => $zone['is_paired'],
			);
		}

		return $cache;
	}

	/**
	 * Fetch now playing data for a sound zone.
	 *
	 * @param string $zone_id Sound zone ID.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function fetch_now_playing( string $zone_id ) {
		$response = $this->request(
			self::QUERY_NOW_PLAYING,
			array( 'zoneId' => $zone_id )
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return $this->normalize_now_playing( $response );
	}

	/**
	 * Normalize now playing API response.
	 *
	 * @param array<string, mixed> $response API response.
	 * @return array<string, mixed>
	 */
	private function normalize_now_playing( array $response ): array {
		$now_playing = $response['data']['nowPlaying'] ?? array();
		$track       = $now_playing['track'] ?? null;

		if ( empty( $track ) || ! is_array( $track ) ) {
			return array(
				'started_at'   => $now_playing['startedAt'] ?? '',
				'track_name'   => '',
				'artists'      => '',
				'album_name'   => '',
				'image_url'    => '',
				'image_width'  => 0,
				'image_height' => 0,
				'has_track'    => false,
			);
		}

		$artist_names = array();
		$artists      = $track['artists'] ?? array();

		if ( is_array( $artists ) ) {
			foreach ( $artists as $artist ) {
				if ( ! empty( $artist['name'] ) ) {
					$artist_names[] = $artist['name'];
				}
			}
		}

		$album = $track['album'] ?? array();
		$image = $album['image'] ?? array();

		return array(
			'started_at'   => $now_playing['startedAt'] ?? '',
			'track_name'   => $track['name'] ?? '',
			'artists'      => implode( ', ', $artist_names ),
			'album_name'   => $album['name'] ?? '',
			'image_url'    => $image['url'] ?? '',
			'image_width'  => (int) ( $image['width'] ?? 0 ),
			'image_height' => (int) ( $image['height'] ?? 0 ),
			'has_track'    => true,
		);
	}
}