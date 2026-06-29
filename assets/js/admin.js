( function ( $ ) {
	'use strict';

	var slugPattern = /^[a-z0-9_-]+$/;

	function showNotice( message, type ) {
		var $notice = $( '#syb-mapping-notice' );
		$notice.removeClass( 'syb-notice--success syb-notice--error syb-notice--info' );
		$notice.addClass( 'syb-notice--' + ( type || 'info' ) );
		$notice.text( message );
	}

	function getSlugValues() {
		var slugs = {};
		$( '.syb-slug-input' ).each( function () {
			var $input = $( this );
			var zoneId = $input.data( 'zone-id' );
			var slug = $.trim( $input.val() ).toLowerCase();
			slugs[ zoneId ] = slug;
		} );
		return slugs;
	}

	function validateSlugs() {
		var slugs = getSlugValues();
		var seen = {};
		var valid = true;

		$( '.syb-slug-input' ).each( function () {
			var $input = $( this );
			var $error = $input.siblings( '.syb-slug-error' );
			var zoneId = $input.data( 'zone-id' );
			var slug = slugs[ zoneId ];

			$error.text( '' );
			$input.removeClass( 'syb-slug-input--error' );

			if ( ! slug ) {
				return;
			}

			if ( ! slugPattern.test( slug ) ) {
				$error.text( sybAdmin.i18n.slugInvalid );
				$input.addClass( 'syb-slug-input--error' );
				valid = false;
				return;
			}

			if ( seen[ slug ] ) {
				$error.text( sybAdmin.i18n.slugDuplicate );
				$input.addClass( 'syb-slug-input--error' );
				valid = false;
				return;
			}

			seen[ slug ] = true;
		} );

		return valid;
	}

	function buildZoneRow( zone, slug ) {
		var statusClass = zone.is_paired ? 'syb-status--paired' : 'syb-status--unpaired';
		var statusText = zone.is_paired ? sybAdmin.i18n.paired : sybAdmin.i18n.unpaired;

		return (
			'<tr data-zone-id="' + zone.zone_id + '">' +
				'<td data-label="Account / Location">' +
					'<strong>' + escapeHtml( zone.business_name ) + '</strong><br />' +
					'<span class="syb-location">' + escapeHtml( zone.location_name ) + '</span>' +
				'</td>' +
				'<td data-label="Zone Name">' + escapeHtml( zone.name ) + '</td>' +
				'<td data-label="Zone ID" class="syb-zone-id-cell">' +
					'<code class="syb-zone-id">' + escapeHtml( zone.zone_id ) + '</code> ' +
					'<button type="button" class="button button-small syb-copy-id" data-zone-id="' + escapeHtml( zone.zone_id ) + '">' +
						escapeHtml( sybAdmin.i18n.copy ) +
					'</button>' +
				'</td>' +
				'<td data-label="Status">' +
					'<span class="syb-status ' + statusClass + '">' + statusText + '</span>' +
				'</td>' +
				'<td data-label="Slug">' +
					'<input type="text" class="syb-slug-input regular-text" ' +
						'value="' + escapeHtml( slug || '' ) + '" ' +
						'placeholder="nagold" ' +
						'data-zone-id="' + escapeHtml( zone.zone_id ) + '" />' +
					'<span class="syb-slug-error" role="alert"></span>' +
				'</td>' +
			'</tr>'
		);
	}

	function compareZoneRows( a, b ) {
		var account = ( a.business_name || '' ).localeCompare( b.business_name || '', undefined, { sensitivity: 'base' } );
		if ( account !== 0 ) {
			return account;
		}

		var location = ( a.location_name || '' ).localeCompare( b.location_name || '', undefined, { sensitivity: 'base' } );
		if ( location !== 0 ) {
			return location;
		}

		return ( a.name || '' ).localeCompare( b.name || '', undefined, { sensitivity: 'base' } );
	}

	function escapeHtml( text ) {
		return String( text )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

	function renderZonesTable( zones ) {
		var $tbody = $( '#syb-zones-tbody' );
		var currentSlugs = getSlugValues();
		var html = '';

		if ( ! zones || ! zones.length ) {
			$tbody.html(
				'<tr class="syb-zones-empty"><td colspan="5">' + escapeHtml( sybAdmin.i18n.noZones ) + '</td></tr>'
			);
			return;
		}

		zones.sort( compareZoneRows );

		zones.forEach( function ( zone ) {
			var slug = currentSlugs[ zone.zone_id ] || sybAdmin.zoneIdToSlug[ zone.zone_id ] || '';
			html += buildZoneRow( zone, slug );
		} );

		$tbody.html( html );
	}

	function collectMappings() {
		var mappings = {};
		$( '.syb-slug-input' ).each( function () {
			var $input = $( this );
			var zoneId = $input.data( 'zone-id' );
			var slug = $.trim( $input.val() ).toLowerCase();
			if ( slug ) {
				mappings[ zoneId ] = slug;
			}
		} );
		return mappings;
	}

	function saveMappings() {
		if ( ! validateSlugs() ) {
			showNotice( sybAdmin.i18n.saveError, 'error' );
			return;
		}

		showNotice( sybAdmin.i18n.saving, 'info' );

		$.post( sybAdmin.ajaxUrl, {
			action: 'syb_save_mappings',
			nonce: sybAdmin.nonce,
			mappings: JSON.stringify( collectMappings() ),
		} )
			.done( function ( response ) {
				if ( response.success ) {
					showNotice( response.data.message || sybAdmin.i18n.saveSuccess, 'success' );
					if ( response.data.mappings ) {
						sybAdmin.zoneIdToSlug = {};
						Object.keys( response.data.mappings ).forEach( function ( slug ) {
							sybAdmin.zoneIdToSlug[ response.data.mappings[ slug ] ] = slug;
						} );
					}
				} else {
					var msg = ( response.data && response.data.message ) || sybAdmin.i18n.saveError;
					showNotice( msg, 'error' );
					if ( response.data && response.data.errors ) {
						Object.keys( response.data.errors ).forEach( function ( zoneId ) {
							var $input = $( '.syb-slug-input[data-zone-id="' + zoneId + '"]' );
							$input.addClass( 'syb-slug-input--error' );
							$input.siblings( '.syb-slug-error' ).text( response.data.errors[ zoneId ] );
						} );
					}
				}
			} )
			.fail( function () {
				showNotice( sybAdmin.i18n.saveError, 'error' );
			} );
	}

	function fetchZones() {
		var $btn = $( '#syb-fetch-zones' );
		$btn.prop( 'disabled', true );
		showNotice( sybAdmin.i18n.fetching, 'info' );

		$.post( sybAdmin.ajaxUrl, {
			action: 'syb_fetch_soundzones',
			nonce: sybAdmin.nonce,
		} )
			.done( function ( response ) {
				if ( response.success ) {
					renderZonesTable( response.data.zones );
					showNotice( response.data.message || sybAdmin.i18n.fetchSuccess, 'success' );
				} else {
					showNotice(
						( response.data && response.data.message ) || sybAdmin.i18n.fetchError,
						'error'
					);
				}
			} )
			.fail( function () {
				showNotice( sybAdmin.i18n.fetchError, 'error' );
			} )
			.always( function () {
				$btn.prop( 'disabled', false );
			} );
	}

	function copyToClipboard( text ) {
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			return navigator.clipboard.writeText( text );
		}

		var $temp = $( '<textarea>' ).val( text ).css( { position: 'fixed', left: '-9999px' } );
		$( 'body' ).append( $temp );
		$temp[0].select();
		var ok = document.execCommand( 'copy' );
		$temp.remove();
		return ok ? $.Deferred().resolve().promise() : $.Deferred().reject().promise();
	}

	$( document ).ready( function () {
		$( '.syb-color-picker' ).wpColorPicker();

		$( '#syb-fetch-zones' ).on( 'click', fetchZones );

		$( '#syb-save-mappings-top, #syb-save-mappings-bottom' ).on( 'click', saveMappings );

		$( document ).on( 'input', '.syb-slug-input', function () {
			var $input = $( this );
			$input.val( $.trim( $input.val() ).toLowerCase() );
			validateSlugs();
		} );

		$( document ).on( 'click', '.syb-copy-id', function () {
			var zoneId = $( this ).data( 'zone-id' );
			copyToClipboard( zoneId )
				.then( function () {
					showNotice( sybAdmin.i18n.copied, 'success' );
				} )
				.catch( function () {
					showNotice( sybAdmin.i18n.copyFailed, 'error' );
				} );
		} );
	} );
}( jQuery ) );