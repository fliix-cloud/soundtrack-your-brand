<?php
/**
 * Build a Markdown translation reference from .po files (for GlotPress entry).
 *
 * Usage:
 *   php bin/export-translation-ref.php path/to/de.po path/to/es.po [output.md]
 */

declare(strict_types=1);

if ( $argc < 3 ) {
	fwrite( STDERR, "Usage: php bin/export-translation-ref.php de.po es.po [output.md]\n" );
	exit( 1 );
}

/**
 * Parse a simple gettext .po file into msgid/msgctxt => msgstr entries.
 *
 * @return array<string, array{id: string, ctxt: ?string, str: string}>
 */
function parse_po( string $path ): array {
	$raw = file_get_contents( $path );
	if ( false === $raw ) {
		throw new RuntimeException( "Cannot read: {$path}" );
	}
	$raw   = str_replace( array( "\r\n", "\r" ), "\n", $raw );
	$lines = explode( "\n", $raw );
	$n     = count( $lines );
	$i     = 0;
	$out   = array();

	while ( $i < $n ) {
		while ( $i < $n && ( '' === trim( $lines[ $i ] ) || str_starts_with( ltrim( $lines[ $i ] ), '#' ) ) ) {
			++$i;
		}
		if ( $i >= $n ) {
			break;
		}

		$ctxt = null;
		if ( preg_match( '/^msgctxt\s+"(.*)"\s*$/', $lines[ $i ], $m ) ) {
			$ctxt = stripcslashes( $m[1] );
			++$i;
		}

		if ( $i >= $n || ! str_starts_with( $lines[ $i ], 'msgid ' ) ) {
			++$i;
			continue;
		}

		$id = '';
		if ( preg_match( '/^msgid\s+"(.*)"\s*$/', $lines[ $i ], $m ) ) {
			$id = $m[1];
			++$i;
		}
		while ( $i < $n && preg_match( '/^"(.*)"\s*$/', $lines[ $i ], $m ) ) {
			$id .= $m[1];
			++$i;
		}
		$id = stripcslashes( $id );

		while ( $i < $n && '' === trim( $lines[ $i ] ) ) {
			++$i;
		}

		// Skip plural forms for this simple exporter.
		if ( $i < $n && str_starts_with( $lines[ $i ], 'msgid_plural' ) ) {
			while ( $i < $n && '' !== trim( $lines[ $i ] ) ) {
				++$i;
			}
			continue;
		}

		if ( $i < $n && preg_match( '/^msgstr(?:\[\d+\])?\s+"(.*)"\s*$/', $lines[ $i ], $m ) ) {
			$str = $m[1];
			++$i;
			while ( $i < $n && preg_match( '/^"(.*)"\s*$/', $lines[ $i ], $m ) ) {
				$str .= $m[1];
				++$i;
			}
			$str = stripcslashes( $str );
			if ( '' !== $id ) {
				$key           = null !== $ctxt ? $ctxt . "\x04" . $id : $id;
				$out[ $key ] = array(
					'id'   => $id,
					'ctxt' => $ctxt,
					'str'  => $str,
				);
			}
		} else {
			++$i;
		}
	}

	return $out;
}

/**
 * Escape a cell for a Markdown table (pipe-safe, single-line).
 */
function md_cell( string $text ): string {
	$text = str_replace( array( "\r\n", "\r", "\n" ), ' ', $text );
	$text = str_replace( '|', '\\|', $text );
	return $text;
}

$de_path = $argv[1];
$es_path = $argv[2];
$out_path = $argv[3] ?? 'docs/translations/TRANSLATIONS.md';

$de = parse_po( $de_path );
$es = parse_po( $es_path );

$keys = array_values( array_unique( array_merge( array_keys( $de ), array_keys( $es ) ) ) );
natcasesort( $keys );
$keys = array_values( $keys );

$md   = array();
$md[] = '# Translation reference (DE / ES)';
$md[] = '';
$md[] = 'This file is a **maintainer cheat sheet** for entering strings on [translate.wordpress.org](https://translate.wordpress.org/).';
$md[] = 'It is **not** loaded by WordPress and must **not** be included as a locale catalog in the plugin package.';
$md[] = '';
$md[] = '**Text domain:** `fliix-now-playing-for-soundtrack-your-brand`';
$md[] = '';
$md[] = '## How WordPress.org translations work';
$md[] = '';
$md[] = '1. Publish (or update) the plugin on WordPress.org.';
$md[] = '2. Within roughly a few hours, GlotPress imports the English source strings from the plugin code.';
$md[] = '3. Open the project on translate.wordpress.org (search for the plugin slug).';
$md[] = '4. Choose a locale (e.g. **Deutsch** → `de_DE`, **Español** → `es_ES`).';
$md[] = '5. Enter or paste translations string by string (or import a `.po` if you are a project/locale contributor with import rights).';
$md[] = '6. Waiting / fuzzy strings need **approval** by a locale Translation Editor (PTE/GTE) before language packs ship.';
$md[] = '7. Users then get translations automatically via **Dashboard → Updates → Translations** (language packs).';
$md[] = '';
$md[] = '### Practical tips';
$md[] = '';
$md[] = '- You usually **cannot** bulk-upload a full `.po` as a regular plugin author unless you are a **Project Translation Editor (PTE)** for this plugin + locale. Request PTE status on the [Polyglots blog](https://make.wordpress.org/polyglots/) with a short post, or translate interactively in GlotPress.';
$md[] = '- Keep placeholders identical (`%s`, `%1$s`, `%d`, etc.).';
$md[] = '- Prefer natural wording over word-for-word calques.';
$md[] = '- After plugin string changes, GlotPress re-imports new/changed `msgid`s; old translations may become fuzzy.';
$md[] = '- Source of these rows: previous bundled `de_DE` / `es_ES` catalogs (git history).';
$md[] = '';
$md[] = '### Direct links (after the plugin is live)';
$md[] = '';
$md[] = 'Replace only if the slug differs:';
$md[] = '';
$md[] = '- Project: `https://translate.wordpress.org/projects/wp-plugins/fliix-now-playing-for-soundtrack-your-brand/`';
$md[] = '- German (Development): `https://translate.wordpress.org/projects/wp-plugins/fliix-now-playing-for-soundtrack-your-brand/dev/de/default/`';
$md[] = '- Spanish (Development): `https://translate.wordpress.org/projects/wp-plugins/fliix-now-playing-for-soundtrack-your-brand/dev/es/default/`';
$md[] = '';
$md[] = 'Stable releases use the `stable` subproject once a stable tag is set; during review you often translate **Development**.';
$md[] = '';
$md[] = '## String table';
$md[] = '';
$md[] = '| # | Context | English (msgid) | German (de_DE) | Spanish (es_ES) |';
$md[] = '|---|---------|-----------------|----------------|-----------------|';

$n = 0;
foreach ( $keys as $key ) {
	++$n;
	$id   = $de[ $key ]['id'] ?? $es[ $key ]['id'] ?? $key;
	$ctxt = $de[ $key ]['ctxt'] ?? $es[ $key ]['ctxt'] ?? '';
	$de_s = $de[ $key ]['str'] ?? '';
	$es_s = $es[ $key ]['str'] ?? '';
	$md[] = '| ' . $n . ' | ' . md_cell( (string) $ctxt ) . ' | ' . md_cell( $id ) . ' | ' . md_cell( $de_s ) . ' | ' . md_cell( $es_s ) . ' |';
}

$md[] = '';
$md[] = '## Counts';
$md[] = '';
$md[] = '- German entries: **' . count( $de ) . '**';
$md[] = '- Spanish entries: **' . count( $es ) . '**';
$md[] = '- Unique source strings in this table: **' . count( $keys ) . '**';
$md[] = '';
$md[] = '---';
$md[] = '';
$md[] = '*Generated for maintainer use. Safe to keep in the GitHub repo; exclude from WordPress.org ZIP if you prefer a lean package (optional).*';
$md[] = '';

$dir = dirname( $out_path );
if ( ! is_dir( $dir ) ) {
	mkdir( $dir, 0755, true );
}

file_put_contents( $out_path, implode( "\n", $md ) );
echo "Wrote {$out_path} (" . count( $keys ) . " strings)\n";
