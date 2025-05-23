/* global _ */

/**
 * Internal dependencies.
 */
import { initChosen } from 'utils/chosen.js';

jQuery( document ).ready( function( $ ) {

	// Globally apply to elements on the page.
	$( '.edd-select-chosen' ).each( function() {
		initChosen( $( this ) );
	} );

	$( '.edd-select-chosen .chosen-search input' ).each( function() {
		// Bail if placeholder already set
		if ( $( this ).attr( 'placeholder' ) ) {
			return;
		}

		const selectElem = $( this ).parent().parent().parent().prev( 'select.edd-select-chosen' ),
			placeholder = selectElem.data( 'search-placeholder' );

		if ( placeholder ) {
			$( this ).attr( 'placeholder', placeholder );
		}
	} );

	// Add placeholders for Chosen input fields
	$( '.chosen-choices' ).on( 'click', function() {
		let placeholder = $( this ).parent().prev().data( 'search-placeholder' );
		if ( typeof placeholder === 'undefined' ) {
			placeholder = edd_vars.type_to_search;
		}
		$( this ).children( 'li' ).children( 'input' ).attr( 'placeholder', placeholder );
	} );

	// This fixes the Chosen box being 0px wide when the thickbox is opened
	$( '#post' ).on( 'click', '.edd-thickbox', function() {
		$( '.edd-select-chosen', '#choose-download' ).css( 'width', '100%' );
	} );

	// Variables for setting up the typing timer
	// Time in ms, Slow - 521ms, Moderate - 342ms, Fast - 300ms
	let userInteractionInterval = 521,
		typingTimerElements = '.edd-select-chosen .chosen-search input, .edd-select-chosen .search-field input';

	// Replace options with search results
	$( document.body ).on( 'keyup', typingTimerElements, _.debounce( function( e ) {
		let	element = $( this ),
			val = element.val(),
			container = element.closest( '.edd-select-chosen' ),

			select = container.prev(),
			select_type = select.data( 'search-type' ),
			no_bundles = container.hasClass( 'no-bundles' ),
			variations = container.hasClass( 'variations' ),
			variations_only = container.hasClass( 'variations-only' ),
			current_id = container.hasClass( 'exclude-current' ) ? edd_vars.post_id : 0,
			lastKey = e.which,
			search_type = 'edd_download_search',
			exclusions = select.data( 'excluded-products' );

		// String replace the chosen container IDs
		container.attr( 'id' ).replace( '_chosen', '' );

		// Detect if we have a defined search type, otherwise default to downloads
		if ( typeof select_type !== 'undefined' ) {
			// Don't trigger AJAX if this select has all options loaded
			if ( 'no_ajax' === select_type ) {
				return;
			}

			search_type = 'edd_' + select_type + '_search';
		} else {
			return;
		}

		// Don't fire if short or is a modifier key (shift, ctrl, apple command key, or arrow keys)
		if (
			( val.length <= 3 && 'edd_download_search' === search_type ) ||
			[ 16, 13, 91, 17, 37, 38, 39, 40 ].includes( lastKey ) ||
			e.ctrlKey ||
			e.metaKey
		) {
			container.children( '.spinner' ).remove();
			return;
		}

		// Maybe append a spinner
		if ( ! container.children( '.spinner' ).length ) {
			container.append( '<span class="spinner is-active"></span>' );
		}

		$.ajax( {
			type: 'GET',
			dataType: 'json',
			url: ajaxurl,
			data: {
				s: val,
				action: search_type,
				no_bundles: no_bundles,
				variations: variations,
				variations_only: variations_only,
				current_id: current_id,
				exclusions: exclusions,
			},

			beforeSend: function() {
				select.closest( 'ul.chosen-results' ).empty();
			},

			success: function( data ) {
				// Remove all options but those that are selected
				$( 'option:not(:selected)', select ).remove();

				// Add any option that doesn't already exist
				$.each( data, function( key, item ) {
					if ( ! $( 'option[value="' + item.id + '"]', select ).length ) {
						select.append( '<option value="' + item.id + '">' + item.name + '</option>' );
					}
				} );

				// Get the text immediately before triggering an update.
				// Any sooner will cause the text to jump around.
				const val = element.val();

				// Update the options
				select.trigger( 'chosen:updated' );

				element.val( val );
			},
		} ).fail( function( response ) {
			console.log( response );
		} ).done( function( response ) {
			container.children( '.spinner' ).remove();
		} );
	}, userInteractionInterval ) );
} );
