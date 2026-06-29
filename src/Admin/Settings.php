<?php
/**
 * Admin settings page and Settings API registration.
 *
 * @package SoundtrackYourBrand
 */

namespace SoundtrackYourBrand\Admin;

use SoundtrackYourBrand\Activator;
use SoundtrackYourBrand\Plugin;
use SoundtrackYourBrand\Security\TokenStorage;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin settings registration and rendering.
 */
class Settings {

	/**
	 * Option group name.
	 */
	private const OPTION_GROUP = 'soundtrack_your_brand_settings';

	/**
	 * Constructor — register settings.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register all settings sections and fields.
	 */
	public function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			'soundtrack_api_base_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_base_url' ),
				'default'           => 'https://api.soundtrackyourbrand.com/v2',
			)
		);

		register_setting(
			self::OPTION_GROUP,
			'soundtrack_api_token',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( TokenStorage::class, 'sanitize_setting' ),
				'default'           => '',
			)
		);

		register_setting(
			self::OPTION_GROUP,
			'soundtrack_update_interval',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_interval' ),
				'default'           => 30,
			)
		);

		register_setting(
			self::OPTION_GROUP,
			'soundtrack_display_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_display_settings' ),
				'default'           => Activator::default_display_settings(),
			)
		);

		add_settings_section(
			'syb_api_section',
			__( 'API Configuration', 'soundtrack-your-brand' ),
			array( $this, 'render_api_section' ),
			'soundtrack-your-brand'
		);

		add_settings_field(
			'soundtrack_api_base_url',
			__( 'API Base URL', 'soundtrack-your-brand' ),
			array( $this, 'render_base_url_field' ),
			'soundtrack-your-brand',
			'syb_api_section'
		);

		add_settings_field(
			'soundtrack_api_token',
			__( 'API Token', 'soundtrack-your-brand' ),
			array( $this, 'render_token_field' ),
			'soundtrack-your-brand',
			'syb_api_section'
		);

		add_settings_section(
			'syb_cache_section',
			__( 'Update Interval & Caching', 'soundtrack-your-brand' ),
			array( $this, 'render_cache_section' ),
			'soundtrack-your-brand'
		);

		add_settings_field(
			'soundtrack_update_interval',
			__( 'Update Interval (seconds)', 'soundtrack-your-brand' ),
			array( $this, 'render_interval_field' ),
			'soundtrack-your-brand',
			'syb_cache_section'
		);

		add_settings_section(
			'syb_mapping_section',
			__( 'SoundZone Mapping', 'soundtrack-your-brand' ),
			array( $this, 'render_mapping_section' ),
			'soundtrack-your-brand'
		);

		add_settings_section(
			'syb_display_section',
			__( 'Widget Appearance', 'soundtrack-your-brand' ),
			array( $this, 'render_display_section' ),
			'soundtrack-your-brand'
		);
	}

	/**
	 * Render the settings page.
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap syb-settings-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form action="options.php" method="post">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( 'soundtrack-your-brand' );
				submit_button( __( 'Save Settings', 'soundtrack-your-brand' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Convert zones cache to table rows.
	 *
	 * @param array<string, array<string, mixed>> $zones_cache Zones cache option.
	 * @return array<int, array<string, mixed>>
	 */
	private static function zones_for_table( array $zones_cache ): array {
		$rows = array();

		foreach ( $zones_cache as $zone_id => $meta ) {
			$rows[] = array(
				'zone_id'       => $zone_id,
				'name'          => $meta['name'] ?? '',
				'is_paired'     => ! empty( $meta['is_paired'] ),
				'business_name' => $meta['business_name'] ?? '',
				'location_name' => $meta['location_name'] ?? '',
				'full_label'    => $meta['full_label'] ?? '',
			);
		}

		usort( $rows, array( self::class, 'compare_zone_rows' ) );

		return $rows;
	}

	/**
	 * Sort zone rows by account, location, then zone name.
	 *
	 * @param array<string, mixed> $a First row.
	 * @param array<string, mixed> $b Second row.
	 * @return int
	 */
	public static function compare_zone_rows( array $a, array $b ): int {
		$compare = strcasecmp( (string) ( $a['business_name'] ?? '' ), (string) ( $b['business_name'] ?? '' ) );

		if ( 0 !== $compare ) {
			return $compare;
		}

		$compare = strcasecmp( (string) ( $a['location_name'] ?? '' ), (string) ( $b['location_name'] ?? '' ) );

		if ( 0 !== $compare ) {
			return $compare;
		}

		return strcasecmp( (string) ( $a['name'] ?? '' ), (string) ( $b['name'] ?? '' ) );
	}

	/**
	 * Render zone table rows from cache.
	 *
	 * @param array<string, array<string, mixed>> $zones_cache   Zones cache.
	 * @param array<string, string>             $zone_id_to_slug Zone ID to slug map.
	 */
	private static function render_zone_rows( array $zones_cache, array $zone_id_to_slug ): void {
		$rows = self::zones_for_table( $zones_cache );

		if ( empty( $rows ) ) {
			echo '<tr class="syb-zones-empty"><td colspan="5">' . esc_html__( 'No sound zones loaded. Click "Fetch / Refresh SoundZones from API" to get started.', 'soundtrack-your-brand' ) . '</td></tr>';
			return;
		}

		foreach ( $rows as $zone ) {
			$zone_id = $zone['zone_id'];
			$slug    = $zone_id_to_slug[ $zone_id ] ?? '';
			$status  = ! empty( $zone['is_paired'] )
				? __( 'Paired', 'soundtrack-your-brand' )
				: __( 'Unpaired', 'soundtrack-your-brand' );
			$status_class = ! empty( $zone['is_paired'] ) ? 'syb-status--paired' : 'syb-status--unpaired';
			?>
			<tr data-zone-id="<?php echo esc_attr( $zone_id ); ?>">
				<td data-label="<?php esc_attr_e( 'Account / Location', 'soundtrack-your-brand' ); ?>">
					<strong><?php echo esc_html( $zone['business_name'] ); ?></strong><br />
					<span class="syb-location"><?php echo esc_html( $zone['location_name'] ); ?></span>
				</td>
				<td data-label="<?php esc_attr_e( 'Zone Name', 'soundtrack-your-brand' ); ?>"><?php echo esc_html( $zone['name'] ); ?></td>
				<td data-label="<?php esc_attr_e( 'Zone ID', 'soundtrack-your-brand' ); ?>" class="syb-zone-id-cell">
					<code class="syb-zone-id"><?php echo esc_html( $zone_id ); ?></code>
					<button type="button" class="button button-small syb-copy-id" data-zone-id="<?php echo esc_attr( $zone_id ); ?>">
						<?php esc_html_e( 'Copy', 'soundtrack-your-brand' ); ?>
					</button>
				</td>
				<td data-label="<?php esc_attr_e( 'Status', 'soundtrack-your-brand' ); ?>">
					<span class="syb-status <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status ); ?></span>
				</td>
				<td data-label="<?php esc_attr_e( 'Slug', 'soundtrack-your-brand' ); ?>">
					<input type="text"
						class="syb-slug-input regular-text"
						name="syb_slug_<?php echo esc_attr( $zone_id ); ?>"
						value="<?php echo esc_attr( $slug ); ?>"
						placeholder="<?php esc_attr_e( 'nagold', 'soundtrack-your-brand' ); ?>"
						data-zone-id="<?php echo esc_attr( $zone_id ); ?>" />
					<span class="syb-slug-error" role="alert"></span>
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * API section description.
	 */
	public function render_api_section(): void {
		printf(
			'<p>%s <a href="%s" target="_blank" rel="noopener noreferrer">%s</a></p>',
			esc_html__( 'Configure your Soundtrack API credentials. The token is sent as an Authorization: Basic header with each request.', 'soundtrack-your-brand' ),
			esc_url( 'https://api.soundtrackyourbrand.com/v2/docs' ),
			esc_html__( 'Soundtrack API Documentation', 'soundtrack-your-brand' )
		);
	}

	/**
	 * Render API base URL field.
	 */
	public function render_base_url_field(): void {
		$value = get_option( 'soundtrack_api_base_url', 'https://api.soundtrackyourbrand.com/v2' );
		?>
		<input type="url" name="soundtrack_api_base_url" id="soundtrack_api_base_url"
			value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<?php
	}

	/**
	 * Render API token field.
	 */
	public function render_token_field(): void {
		$has_token = TokenStorage::has_token();
		?>
		<input type="password" name="soundtrack_api_token" id="soundtrack_api_token"
			value="" class="large-text" autocomplete="new-password"
			placeholder="<?php echo esc_attr( $has_token ? __( 'Token configured — enter a new token to replace', 'soundtrack-your-brand' ) : __( 'Enter your API token', 'soundtrack-your-brand' ) ); ?>" />
		<p class="description">
			<?php
			if ( $has_token ) {
				esc_html_e( 'A token is saved and encrypted. It cannot be viewed — enter a new value only to replace it. Leave blank to keep the current token.', 'soundtrack-your-brand' );
			} else {
				esc_html_e( 'Your API token is encrypted before storage and sent as: Authorization: Basic <token>', 'soundtrack-your-brand' );
			}
			?>
		</p>
		<?php
	}

	/**
	 * Cache section description.
	 */
	public function render_cache_section(): void {
		echo '<p>' . esc_html__( 'The plugin uses lazy, on-demand caching. Now playing data is fetched from the API when a visitor loads a page with the shortcode (or when the cache expires during live refresh). The frontend polls for updates at this interval so idle visitors see new tracks without reloading. No WP-Cron is used.', 'soundtrack-your-brand' ) . '</p>';
	}

	/**
	 * Render update interval field.
	 */
	public function render_interval_field(): void {
		$value = (int) get_option( 'soundtrack_update_interval', 30 );
		?>
		<input type="number" name="soundtrack_update_interval" id="soundtrack_update_interval"
			value="<?php echo esc_attr( (string) $value ); ?>" min="10" max="120" step="1" class="small-text" />
		<p class="description">
			<?php esc_html_e( 'How long (in seconds) to cache now playing data. Minimum 10, maximum 120.', 'soundtrack-your-brand' ); ?>
		</p>
		<?php
	}

	/**
	 * Mapping section with zone table.
	 */
	public function render_mapping_section(): void {
		echo '<p>' . esc_html__( 'Map each SoundZone to a unique slug for use in the shortcode. Fetch zones from the API, assign slugs, then click Save All Mappings.', 'soundtrack-your-brand' ) . '</p>';
		echo '<p><code>[syb_nowplaying slug="your-slug-here"]</code></p>';

		$zones_cache = get_option( 'soundtrack_zones_cache', array() );
		if ( ! is_array( $zones_cache ) ) {
			$zones_cache = array();
		}

		$mappings = get_option( 'soundtrack_mappings', array() );
		if ( ! is_array( $mappings ) ) {
			$mappings = array();
		}

		$zone_id_to_slug = array_flip( $mappings );
		?>
		<div id="syb-mapping-app" class="syb-mapping-app">
			<div class="syb-mapping-toolbar">
				<button type="button" id="syb-fetch-zones" class="button button-primary button-hero">
					<?php esc_html_e( 'Fetch / Refresh SoundZones from API', 'soundtrack-your-brand' ); ?>
				</button>
				<button type="button" id="syb-save-mappings-top" class="button button-secondary">
					<?php esc_html_e( 'Save All Mappings', 'soundtrack-your-brand' ); ?>
				</button>
			</div>

			<div id="syb-mapping-notice" class="syb-mapping-notice" role="status" aria-live="polite"></div>

			<div class="syb-table-wrap">
				<table class="widefat striped syb-zones-table" id="syb-zones-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Account / Location', 'soundtrack-your-brand' ); ?></th>
							<th><?php esc_html_e( 'Zone Name', 'soundtrack-your-brand' ); ?></th>
							<th><?php esc_html_e( 'Zone ID', 'soundtrack-your-brand' ); ?></th>
							<th><?php esc_html_e( 'Status', 'soundtrack-your-brand' ); ?></th>
							<th><?php esc_html_e( 'Slug', 'soundtrack-your-brand' ); ?></th>
						</tr>
					</thead>
					<tbody id="syb-zones-tbody">
						<?php self::render_zone_rows( $zones_cache, $zone_id_to_slug ); ?>
					</tbody>
				</table>
			</div>

			<div class="syb-mapping-toolbar syb-mapping-toolbar--bottom">
				<button type="button" id="syb-save-mappings-bottom" class="button button-secondary">
					<?php esc_html_e( 'Save All Mappings', 'soundtrack-your-brand' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Display section — description and appearance controls (no form-table wrapper).
	 */
	public function render_display_section(): void {
		echo '<p class="syb-section-lead">' . esc_html__( 'Configure the default appearance of the now playing widget. Individual shortcodes can override some settings via attributes.', 'soundtrack-your-brand' ) . '</p>';
		$this->render_display_fields();
	}

	/**
	 * Render display settings fields.
	 */
	public function render_display_fields(): void {
		$settings = Plugin::get_display_settings();
		?>
		<div class="syb-display-panel">
			<div class="syb-display-grid">
				<section class="syb-display-card syb-display-card--span-2">
					<header class="syb-display-card__header">
						<h3 class="syb-display-card__title"><?php esc_html_e( 'Layout', 'soundtrack-your-brand' ); ?></h3>
						<p class="syb-display-card__desc"><?php esc_html_e( 'Template, alignment, and visibility options.', 'soundtrack-your-brand' ); ?></p>
					</header>
					<div class="syb-display-card__body">
						<div class="syb-field syb-field--template">
							<span class="syb-field__label"><?php esc_html_e( 'Template', 'soundtrack-your-brand' ); ?></span>
							<div class="syb-template-picker" role="radiogroup" aria-label="<?php esc_attr_e( 'Template', 'soundtrack-your-brand' ); ?>">
								<?php
								$templates = array(
									'classic' => array(
										'label' => __( 'Classic', 'soundtrack-your-brand' ),
										'desc'  => __( 'Bordered frame with icon and text', 'soundtrack-your-brand' ),
									),
									'compact' => array(
										'label' => __( 'Compact', 'soundtrack-your-brand' ),
										'desc'  => __( 'Single-line, space-efficient', 'soundtrack-your-brand' ),
									),
									'modern'  => array(
										'label' => __( 'Modern Card', 'soundtrack-your-brand' ),
										'desc'  => __( 'Rounded card with soft shadow', 'soundtrack-your-brand' ),
									),
									'minimal' => array(
										'label' => __( 'Minimal', 'soundtrack-your-brand' ),
										'desc'  => __( 'Text only, no decoration', 'soundtrack-your-brand' ),
									),
								);
								foreach ( $templates as $value => $template ) :
									?>
									<label class="syb-template-option">
										<input type="radio" class="syb-template-option__input" name="soundtrack_display_settings[template]"
											value="<?php echo esc_attr( $value ); ?>" <?php checked( $settings['template'], $value ); ?> />
										<span class="syb-template-option__card">
											<span class="syb-template-option__name"><?php echo esc_html( $template['label'] ); ?></span>
											<span class="syb-template-option__desc"><?php echo esc_html( $template['desc'] ); ?></span>
										</span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
						<div class="syb-layout-row">
							<div class="syb-field">
								<span class="syb-field__label"><?php esc_html_e( 'Alignment', 'soundtrack-your-brand' ); ?></span>
								<div class="syb-segmented" role="radiogroup" aria-label="<?php esc_attr_e( 'Alignment', 'soundtrack-your-brand' ); ?>">
									<?php
									$alignments = array(
										'left'   => __( 'Left', 'soundtrack-your-brand' ),
										'center' => __( 'Center', 'soundtrack-your-brand' ),
										'right'  => __( 'Right', 'soundtrack-your-brand' ),
									);
									foreach ( $alignments as $value => $label ) :
										?>
										<label class="syb-segmented__item">
											<input type="radio" class="syb-segmented__input" name="soundtrack_display_settings[alignment]"
												value="<?php echo esc_attr( $value ); ?>" <?php checked( $settings['alignment'], $value ); ?> />
											<span class="syb-segmented__label"><?php echo esc_html( $label ); ?></span>
										</label>
									<?php endforeach; ?>
								</div>
							</div>
							<div class="syb-field syb-field--toggles">
							<span class="syb-field__label"><?php esc_html_e( 'Visibility', 'soundtrack-your-brand' ); ?></span>
							<div class="syb-toggle-group">
								<?php $this->render_display_toggle( 'show_image', __( 'Icon / Artwork', 'soundtrack-your-brand' ), ! empty( $settings['show_image'] ) ); ?>
								<?php $this->render_display_toggle( 'show_prefix', __( 'Currently playing label', 'soundtrack-your-brand' ), ! empty( $settings['show_prefix'] ) ); ?>
								<?php $this->render_display_toggle( 'show_artist', __( 'Artist name', 'soundtrack-your-brand' ), ! empty( $settings['show_artist'] ) ); ?>
							</div>
							</div>
						</div>
					</div>
				</section>

				<section class="syb-display-card">
					<header class="syb-display-card__header">
						<h3 class="syb-display-card__title"><?php esc_html_e( 'Artwork', 'soundtrack-your-brand' ); ?></h3>
						<p class="syb-display-card__desc"><?php esc_html_e( 'How the visual indicator appears beside the track.', 'soundtrack-your-brand' ); ?></p>
					</header>
					<div class="syb-display-card__body">
						<div class="syb-field">
							<label class="syb-field__label" for="syb_image_display"><?php esc_html_e( 'Display mode', 'soundtrack-your-brand' ); ?></label>
							<select name="soundtrack_display_settings[image_display]" id="syb_image_display" class="syb-field__control">
								<option value="waves" <?php selected( $settings['image_display'], 'waves' ); ?>><?php esc_html_e( 'Animated waves', 'soundtrack-your-brand' ); ?></option>
								<option value="icon" <?php selected( $settings['image_display'], 'icon' ); ?>><?php esc_html_e( 'Static icon', 'soundtrack-your-brand' ); ?></option>
								<option value="album" <?php selected( $settings['image_display'], 'album' ); ?>><?php esc_html_e( 'Album art (square only)', 'soundtrack-your-brand' ); ?></option>
							</select>
						</div>
						<div class="syb-field syb-field--inline-controls">
							<label class="syb-field__label" for="syb_image_size"><?php esc_html_e( 'Size', 'soundtrack-your-brand' ); ?></label>
							<div class="syb-field__row">
								<select name="soundtrack_display_settings[image_size]" id="syb_image_size" class="syb-field__control">
									<option value="small" <?php selected( $settings['image_size'], 'small' ); ?>><?php esc_html_e( 'Small (48px)', 'soundtrack-your-brand' ); ?></option>
									<option value="medium" <?php selected( $settings['image_size'], 'medium' ); ?>><?php esc_html_e( 'Medium (80px)', 'soundtrack-your-brand' ); ?></option>
									<option value="large" <?php selected( $settings['image_size'], 'large' ); ?>><?php esc_html_e( 'Large (120px)', 'soundtrack-your-brand' ); ?></option>
									<option value="custom" <?php selected( $settings['image_size'], 'custom' ); ?>><?php esc_html_e( 'Custom', 'soundtrack-your-brand' ); ?></option>
								</select>
								<input type="number" name="soundtrack_display_settings[image_size_custom]" id="syb_image_size_custom"
									value="<?php echo esc_attr( (string) $settings['image_size_custom'] ); ?>" min="16" max="400"
									class="syb-field__control syb-field__control--narrow" placeholder="px" />
							</div>
						</div>
					</div>
				</section>

				<section class="syb-display-card">
					<header class="syb-display-card__header">
						<h3 class="syb-display-card__title"><?php esc_html_e( 'Text', 'soundtrack-your-brand' ); ?></h3>
						<p class="syb-display-card__desc"><?php esc_html_e( 'Labels shown when playing or idle.', 'soundtrack-your-brand' ); ?></p>
					</header>
					<div class="syb-display-card__body">
						<div class="syb-field">
							<label class="syb-field__label" for="syb_prefix_text"><?php esc_html_e( 'Playing label', 'soundtrack-your-brand' ); ?></label>
							<input type="text" name="soundtrack_display_settings[prefix_text]" id="syb_prefix_text"
								value="<?php echo esc_attr( $settings['prefix_text'] ); ?>" class="syb-field__control" />
						</div>
						<div class="syb-field">
							<label class="syb-field__label" for="syb_fallback_text"><?php esc_html_e( 'Fallback text', 'soundtrack-your-brand' ); ?></label>
							<input type="text" name="soundtrack_display_settings[fallback_text]" id="syb_fallback_text"
								value="<?php echo esc_attr( $settings['fallback_text'] ); ?>" class="syb-field__control" />
						</div>
					</div>
				</section>

				<section class="syb-display-card syb-display-card--span-2">
					<header class="syb-display-card__header">
						<h3 class="syb-display-card__title"><?php esc_html_e( 'Typography & Colors', 'soundtrack-your-brand' ); ?></h3>
						<p class="syb-display-card__desc"><?php esc_html_e( 'Fine-tune fonts and colors for each text element.', 'soundtrack-your-brand' ); ?></p>
					</header>
					<div class="syb-display-card__body">
						<div class="syb-type-grid">
							<div class="syb-type-column">
								<h4 class="syb-type-column__title"><?php esc_html_e( 'Song', 'soundtrack-your-brand' ); ?></h4>
								<div class="syb-field">
									<label class="syb-field__label" for="syb_song_color"><?php esc_html_e( 'Color', 'soundtrack-your-brand' ); ?></label>
									<input type="text" name="soundtrack_display_settings[song_color]" id="syb_song_color"
										value="<?php echo esc_attr( $settings['song_color'] ); ?>" class="syb-color-picker syb-field__control" data-default-color="#111111" />
								</div>
								<div class="syb-field syb-field--inline-controls">
									<label class="syb-field__label" for="syb_song_font_size"><?php esc_html_e( 'Size (px)', 'soundtrack-your-brand' ); ?></label>
									<input type="number" name="soundtrack_display_settings[song_font_size]" id="syb_song_font_size"
										value="<?php echo esc_attr( (string) $settings['song_font_size'] ); ?>" min="8" max="72" class="syb-field__control syb-field__control--narrow" />
								</div>
								<div class="syb-field">
									<label class="syb-field__label" for="syb_song_font_weight"><?php esc_html_e( 'Weight', 'soundtrack-your-brand' ); ?></label>
									<?php $this->render_font_weight_select( 'song_font_weight', $settings['song_font_weight'] ); ?>
								</div>
							</div>
							<div class="syb-type-column">
								<h4 class="syb-type-column__title"><?php esc_html_e( 'Artist', 'soundtrack-your-brand' ); ?></h4>
								<div class="syb-field">
									<label class="syb-field__label" for="syb_artist_color"><?php esc_html_e( 'Color', 'soundtrack-your-brand' ); ?></label>
									<input type="text" name="soundtrack_display_settings[artist_color]" id="syb_artist_color"
										value="<?php echo esc_attr( $settings['artist_color'] ); ?>" class="syb-color-picker syb-field__control" data-default-color="#666666" />
								</div>
								<div class="syb-field syb-field--inline-controls">
									<label class="syb-field__label" for="syb_artist_font_size"><?php esc_html_e( 'Size (px)', 'soundtrack-your-brand' ); ?></label>
									<input type="number" name="soundtrack_display_settings[artist_font_size]" id="syb_artist_font_size"
										value="<?php echo esc_attr( (string) $settings['artist_font_size'] ); ?>" min="8" max="72" class="syb-field__control syb-field__control--narrow" />
								</div>
								<div class="syb-field">
									<label class="syb-field__label" for="syb_artist_font_weight"><?php esc_html_e( 'Weight', 'soundtrack-your-brand' ); ?></label>
									<?php $this->render_font_weight_select( 'artist_font_weight', $settings['artist_font_weight'] ); ?>
								</div>
							</div>
							<div class="syb-type-column">
								<h4 class="syb-type-column__title"><?php esc_html_e( 'Label', 'soundtrack-your-brand' ); ?></h4>
								<div class="syb-field">
									<label class="syb-field__label" for="syb_prefix_color"><?php esc_html_e( 'Color', 'soundtrack-your-brand' ); ?></label>
									<input type="text" name="soundtrack_display_settings[prefix_color]" id="syb_prefix_color"
										value="<?php echo esc_attr( $settings['prefix_color'] ); ?>" class="syb-color-picker syb-field__control" data-default-color="#444444" />
								</div>
								<div class="syb-field syb-field--inline-controls">
									<label class="syb-field__label" for="syb_prefix_font_size"><?php esc_html_e( 'Size (px)', 'soundtrack-your-brand' ); ?></label>
									<input type="number" name="soundtrack_display_settings[prefix_font_size]" id="syb_prefix_font_size"
										value="<?php echo esc_attr( (string) $settings['prefix_font_size'] ); ?>" min="8" max="72" class="syb-field__control syb-field__control--narrow" />
								</div>
							</div>
						</div>
					</div>
				</section>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a modern toggle control for display settings.
	 *
	 * @param string $name    Setting field name.
	 * @param string $label   Visible label.
	 * @param bool   $checked Whether the toggle is on.
	 */
	private function render_display_toggle( string $name, string $label, bool $checked ): void {
		?>
		<div class="syb-toggle">
			<input type="hidden" name="soundtrack_display_settings[<?php echo esc_attr( $name ); ?>]" value="0" />
			<label class="syb-toggle__label-wrap">
				<input type="checkbox" class="syb-toggle__input" name="soundtrack_display_settings[<?php echo esc_attr( $name ); ?>]"
					value="1" <?php checked( $checked ); ?> />
				<span class="syb-toggle__track" aria-hidden="true"></span>
				<span class="syb-toggle__text"><?php echo esc_html( $label ); ?></span>
			</label>
		</div>
		<?php
	}

	/**
	 * Render a font weight select field.
	 *
	 * @param string $name  Field name suffix.
	 * @param string $value Current value.
	 */
	private function render_font_weight_select( string $name, string $value ): void {
		$weights = array( '300', '400', '500', '600', '700', '800' );
		?>
		<select name="soundtrack_display_settings[<?php echo esc_attr( $name ); ?>]" id="syb_<?php echo esc_attr( $name ); ?>" class="syb-field__control">
			<?php foreach ( $weights as $weight ) : ?>
				<option value="<?php echo esc_attr( $weight ); ?>" <?php selected( $value, $weight ); ?>><?php echo esc_html( $weight ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Sanitize API base URL.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public function sanitize_base_url( string $value ): string {
		$url = esc_url_raw( trim( $value ) );
		return $url ?: 'https://api.soundtrackyourbrand.com/v2';
	}

	/**
	 * Sanitize update interval.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public function sanitize_interval( $value ): int {
		return max( 10, min( 120, absint( $value ) ) );
	}

	/**
	 * Sanitize display settings array.
	 *
	 * @param mixed $value Raw value.
	 * @return array<string, mixed>
	 */
	public function sanitize_display_settings( $value ): array {
		$defaults = Activator::default_display_settings();

		if ( ! is_array( $value ) ) {
			return $defaults;
		}

		$templates      = array( 'classic', 'compact', 'modern', 'minimal' );
		$alignments     = array( 'left', 'center', 'right' );
		$sizes          = array( 'small', 'medium', 'large', 'custom' );
		$weights        = array( '300', '400', '500', '600', '700', '800' );
		$image_displays = array( 'waves', 'icon', 'album' );

		$clean = array(
			'template'           => in_array( $value['template'] ?? '', $templates, true ) ? $value['template'] : $defaults['template'],
			'show_image'         => ! empty( $value['show_image'] ),
			'image_display'      => in_array( $value['image_display'] ?? '', $image_displays, true ) ? $value['image_display'] : $defaults['image_display'],
			'show_prefix'        => ! empty( $value['show_prefix'] ),
			'prefix_text'        => sanitize_text_field( $value['prefix_text'] ?? $defaults['prefix_text'] ),
			'show_artist'        => ! empty( $value['show_artist'] ),
			'image_size'         => in_array( $value['image_size'] ?? '', $sizes, true ) ? $value['image_size'] : $defaults['image_size'],
			'image_size_custom'  => max( 16, min( 400, absint( $value['image_size_custom'] ?? $defaults['image_size_custom'] ) ) ),
			'song_color'         => sanitize_hex_color( $value['song_color'] ?? '' ) ?: $defaults['song_color'],
			'artist_color'       => sanitize_hex_color( $value['artist_color'] ?? '' ) ?: $defaults['artist_color'],
			'prefix_color'       => sanitize_hex_color( $value['prefix_color'] ?? '' ) ?: $defaults['prefix_color'],
			'song_font_size'     => max( 8, min( 72, absint( $value['song_font_size'] ?? $defaults['song_font_size'] ) ) ),
			'artist_font_size'   => max( 8, min( 72, absint( $value['artist_font_size'] ?? $defaults['artist_font_size'] ) ) ),
			'prefix_font_size'   => max( 8, min( 72, absint( $value['prefix_font_size'] ?? $defaults['prefix_font_size'] ) ) ),
			'song_font_weight'   => in_array( $value['song_font_weight'] ?? '', $weights, true ) ? $value['song_font_weight'] : $defaults['song_font_weight'],
			'artist_font_weight' => in_array( $value['artist_font_weight'] ?? '', $weights, true ) ? $value['artist_font_weight'] : $defaults['artist_font_weight'],
			'alignment'          => in_array( $value['alignment'] ?? '', $alignments, true ) ? $value['alignment'] : $defaults['alignment'],
			'fallback_text'      => sanitize_text_field( $value['fallback_text'] ?? $defaults['fallback_text'] ),
		);

		return $clean;
	}
}