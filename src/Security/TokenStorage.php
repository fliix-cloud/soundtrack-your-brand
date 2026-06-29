<?php
/**
 * Encrypted storage for the Soundtrack API token.
 *
 * @package SoundtrackYourBrand
 */

namespace SoundtrackYourBrand\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Encrypts and retrieves the API token using WordPress salts.
 */
class TokenStorage {

	/**
	 * Option name for the stored token.
	 */
	public const OPTION_NAME = 'soundtrack_api_token';

	/**
	 * Prefix identifying encrypted values.
	 */
	private const ENCRYPTED_PREFIX = 'sybenc:';

	/**
	 * Check whether a token is configured.
	 *
	 * @return bool
	 */
	public static function has_token(): bool {
		$stored = get_option( self::OPTION_NAME, '' );

		return is_string( $stored ) && '' !== $stored;
	}

	/**
	 * Retrieve the decrypted API token.
	 *
	 * @return string
	 */
	public static function get_token(): string {
		$stored = get_option( self::OPTION_NAME, '' );

		if ( ! is_string( $stored ) || '' === $stored ) {
			return '';
		}

		if ( self::is_encrypted( $stored ) ) {
			return self::decrypt( $stored );
		}

		// Migrate legacy plaintext tokens to encrypted storage.
		self::save_token( $stored );

		return $stored;
	}

	/**
	 * Encrypt and persist a new API token.
	 *
	 * @param string $plaintext API token.
	 */
	public static function save_token( string $plaintext ): void {
		$plaintext = trim( $plaintext );

		if ( '' === $plaintext ) {
			return;
		}

		update_option( self::OPTION_NAME, self::encrypt( $plaintext ) );
	}

	/**
	 * Remove the stored API token.
	 */
	public static function clear_token(): void {
		delete_option( self::OPTION_NAME );
	}

	/**
	 * Sanitize token input from the settings form.
	 *
	 * Empty input preserves the existing encrypted value.
	 *
	 * @param mixed $value Submitted field value.
	 * @return string
	 */
	public static function sanitize_setting( $value ): string {
		$existing = get_option( self::OPTION_NAME, '' );
		$existing = is_string( $existing ) ? $existing : '';
		$value    = is_string( $value ) ? trim( sanitize_text_field( $value ) ) : '';

		if ( '' === $value ) {
			return $existing;
		}

		$encrypted = self::encrypt( $value );

		return '' !== $encrypted ? $encrypted : $existing;
	}

	/**
	 * Determine whether a stored value is encrypted.
	 *
	 * @param string $value Stored option value.
	 * @return bool
	 */
	private static function is_encrypted( string $value ): bool {
		return str_starts_with( $value, self::ENCRYPTED_PREFIX );
	}

	/**
	 * Encrypt a plaintext token.
	 *
	 * @param string $plaintext API token.
	 * @return string
	 */
	private static function encrypt( string $plaintext ): string {
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'SYB: OpenSSL is required to encrypt the API token.' );
			return '';
		}

		$iv        = random_bytes( 16 );
		$encrypted = openssl_encrypt( $plaintext, 'AES-256-CBC', self::get_encryption_key(), OPENSSL_RAW_DATA, $iv );

		if ( false === $encrypted ) {
			return '';
		}

		return self::ENCRYPTED_PREFIX . base64_encode( $iv . $encrypted );
	}

	/**
	 * Decrypt a stored token.
	 *
	 * @param string $stored Encrypted option value.
	 * @return string
	 */
	private static function decrypt( string $stored ): string {
		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}

		$payload = base64_decode( substr( $stored, strlen( self::ENCRYPTED_PREFIX ) ), true );

		if ( false === $payload || strlen( $payload ) < 17 ) {
			return '';
		}

		$iv         = substr( $payload, 0, 16 );
		$ciphertext = substr( $payload, 16 );
		$plaintext  = openssl_decrypt( $ciphertext, 'AES-256-CBC', self::get_encryption_key(), OPENSSL_RAW_DATA, $iv );

		return false !== $plaintext ? $plaintext : '';
	}

	/**
	 * Derive an encryption key from WordPress salts.
	 *
	 * @return string
	 */
	private static function get_encryption_key(): string {
		return hash_hmac( 'sha256', 'soundtrack-your-brand-api-token', wp_salt( 'auth' ), true );
	}
}