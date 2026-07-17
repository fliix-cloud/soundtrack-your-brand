<?php
/**
 * Compile gettext .po files into binary .mo files (no gettext tools required).
 *
 * Usage:
 *   php bin/compile-mo.php
 *   php bin/compile-mo.php docs/translations/fliix-now-playing-for-soundtrack-your-brand-de_DE.po
 *
 * Local testing only — do not ship .mo files in the plugin package.
 *
 * @package SoundtrackYourBrand
 */

declare(strict_types=1);

namespace Fliix\SoundtrackYourBrand\Tools;

if ( PHP_SAPI !== 'cli' ) {
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	exit;
}

if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
	fwrite( STDERR, "PHP 8.0+ required.\n" );
	exit( 1 );
}

$root = dirname( __DIR__ );
$args = array_slice( $argv, 1 );

$files = [] === $args
	? ( glob( $root . '/docs/translations/*.po' ) ?: [] )
	: array_map(
		static function ( string $arg ) use ( $root ): string {
			if ( is_file( $arg ) ) {
				return $arg;
			}
			$path = $root . '/' . ltrim( str_replace( '\\', '/', $arg ), '/' );
			if ( ! is_file( $path ) ) {
				fwrite( STDERR, "File not found: {$arg}\n" );
				exit( 1 );
			}
			return $path;
		},
		$args
	);

if ( [] === $files ) {
	fwrite( STDERR, "No .po files found.\n" );
	exit( 1 );
}

foreach ( $files as $po_file ) {
	$entries = parse_po( $po_file );
	$mo_file = preg_replace( '/\.po$/i', '.mo', $po_file ) ?? ( $po_file . '.mo' );
	write_mo( $entries, $mo_file );
	echo 'Compiled ' . basename( $po_file ) . ' → ' . basename( $mo_file ) . ' (' . count( $entries ) . " strings)\n";
}

/**
 * Parse a simple .po file into msgid => msgstr map (no plurals).
 *
 * @return array<string, string>
 */
function parse_po( string $file ): array {
	$lines = file( $file, FILE_IGNORE_NEW_LINES );
	if ( false === $lines ) {
		return [];
	}

	$entries = [];
	$msgid   = null;
	$msgstr  = null;
	$mode    = null;

	$flush = static function () use ( &$entries, &$msgid, &$msgstr, &$mode ): void {
		if ( null !== $msgid && null !== $msgstr ) {
			$entries[ $msgid ] = $msgstr;
		}
		$msgid  = null;
		$msgstr = null;
		$mode   = null;
	};

	foreach ( $lines as $line ) {
		$line = rtrim( $line, "\r" );

		if ( '' === $line || 0 === strncmp( $line, '#', 1 ) ) {
			if ( '' === $line ) {
				$flush();
			}
			continue;
		}

		if ( preg_match( '/^msgid\s+"(.*)"\s*$/', $line, $m ) ) {
			$flush();
			$msgid = po_unescape( $m[1] );
			$mode  = 'msgid';
			continue;
		}

		if ( preg_match( '/^msgstr\s+"(.*)"\s*$/', $line, $m ) ) {
			$msgstr = po_unescape( $m[1] );
			$mode   = 'msgstr';
			continue;
		}

		if ( preg_match( '/^"(.*)"\s*$/', $line, $m ) ) {
			$chunk = po_unescape( $m[1] );
			if ( 'msgid' === $mode && null !== $msgid ) {
				$msgid .= $chunk;
			} elseif ( 'msgstr' === $mode && null !== $msgstr ) {
				$msgstr .= $chunk;
			}
		}
	}

	$flush();

	return $entries;
}

/**
 * Unescape a gettext string fragment.
 */
function po_unescape( string $s ): string {
	return stripcslashes( $s );
}

/**
 * Write a little-endian GNU .mo file.
 *
 * @param array<string, string> $entries msgid => msgstr.
 */
function write_mo( array $entries, string $path ): void {
	$ids = array_keys( $entries );
	sort( $ids, SORT_STRING );

	$id_blobs  = [];
	$str_blobs = [];

	foreach ( $ids as $id ) {
		$id_blobs[]  = $id . "\0";
		$str_blobs[] = $entries[ $id ] . "\0";
	}

	$n              = count( $ids );
	$ids_blob       = implode( '', $id_blobs );
	$strs_blob      = implode( '', $str_blobs );
	$key_table_size = $n * 8;
	$header_size    = 28;
	$o_offset       = $header_size;
	$t_offset       = $header_size + $key_table_size;
	$data_offset    = $header_size + ( 2 * $key_table_size );

	$id_table  = '';
	$str_table = '';
	$ido       = $data_offset;
	$stro      = $data_offset + strlen( $ids_blob );

	foreach ( $ids as $i => $id ) {
		$ilen       = strlen( $id_blobs[ $i ] ) - 1;
		$slen       = strlen( $str_blobs[ $i ] ) - 1;
		$id_table  .= pack( 'VV', $ilen, $ido );
		$str_table .= pack( 'VV', $slen, $stro );
		$ido       += strlen( $id_blobs[ $i ] );
		$stro      += strlen( $str_blobs[ $i ] );
	}

	$binary  = pack( 'V', 0x950412de );
	$binary .= pack( 'V', 0 );
	$binary .= pack( 'V', $n );
	$binary .= pack( 'V', $o_offset );
	$binary .= pack( 'V', $t_offset );
	$binary .= pack( 'V', 0 );
	$binary .= pack( 'V', 0 );
	$binary .= $id_table . $str_table . $ids_blob . $strs_blob;

	if ( false === file_put_contents( $path, $binary ) ) {
		fwrite( STDERR, "Failed to write {$path}\n" );
		exit( 1 );
	}
}
