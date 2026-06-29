( function () {
	'use strict';

	if ( typeof sybFrontend === 'undefined' ) {
		return;
	}

	var intervalMs = Math.max( 10, sybFrontend.interval || 30 ) * 1000;
	var pollTimer = null;

	function getWidgets() {
		return Array.prototype.slice.call( document.querySelectorAll( '[data-syb-live="true"]' ) );
	}

	function groupWidgets( widgets ) {
		var groups = new Map();

		widgets.forEach( function ( widget ) {
			var slug = widget.getAttribute( 'data-syb-slug' ) || '';
			var atts = widget.getAttribute( 'data-syb-atts' ) || '';
			var key = slug + '|' + atts;

			if ( ! groups.has( key ) ) {
				groups.set( key, {
					slug: slug,
					atts: atts,
					widgets: [],
				} );
			}

			groups.get( key ).widgets.push( widget );
		} );

		return groups;
	}

	function requestRefresh( slug, atts ) {
		var body = new window.FormData();
		body.append( 'action', sybFrontend.action );
		body.append( 'slug', slug );

		if ( atts ) {
			body.append( 'atts', atts );
		}

		return window.fetch( sybFrontend.ajaxUrl, {
			method: 'POST',
			body: body,
			credentials: 'same-origin',
		} ).then( function ( response ) {
			return response.json();
		} );
	}

	function replaceWidget( widget, html ) {
		var wrapper = document.createElement( 'div' );
		wrapper.innerHTML = html.trim();

		var nextWidget = wrapper.firstElementChild;

		if ( ! nextWidget ) {
			return;
		}

		widget.replaceWith( nextWidget );
	}

	function updateGroup( group ) {
		return requestRefresh( group.slug, group.atts ).then( function ( response ) {
			if ( ! response || ! response.success || ! response.data ) {
				return;
			}

			var trackKey = response.data.track_key || '';
			var html = response.data.html || '';

			group.widgets.forEach( function ( widget ) {
				if ( ! document.contains( widget ) ) {
					return;
				}

				var currentKey = widget.getAttribute( 'data-syb-track-key' ) || '';

				if ( currentKey !== trackKey ) {
					replaceWidget( widget, html );
				}
			} );
		} ).catch( function () {
			// Silently ignore transient network errors; next poll will retry.
		} );
	}

	function pollWidgets() {
		var widgets = getWidgets();

		if ( ! widgets.length ) {
			return;
		}

		var groups = groupWidgets( widgets );
		var requests = [];

		groups.forEach( function ( group ) {
			requests.push( updateGroup( group ) );
		} );

		return Promise.all( requests );
	}

	function startPolling() {
		if ( pollTimer ) {
			return;
		}

		pollTimer = window.setInterval( pollWidgets, intervalMs );
	}

	function init() {
		if ( ! getWidgets().length ) {
			return;
		}

		startPolling();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );