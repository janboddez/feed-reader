jQuery( document ).ready( function ( $ ) {
	function mark_read( e ) {
		e.preventDefault();

		var button = $( this );
		var data   = {
			'action': 'feed_reader_entries_mark_read',
			'id': button.data( 'entry-id' ),
			'_wpnonce': button.data( 'nonce' )
		};

		$.post( ajaxurl, data, function( response ) {
			var feed = button.closest( '.hfeed' );
			if ( feed && ! ( new URLSearchParams( window.location.search ) ).has( 'all' ) ) {
				button.closest( '.hentry' ).remove();

				if ( ! $( '.hentry' ).length ) {
					feed.html( feed_reader_obj.all_done );
				}

				return;
			}

			button.unbind( 'click', mark_read );
			button.bind( 'click', mark_unread );

			button.toggleClass( 'mark-read mark-unread' );
			button.text( feed_reader_obj.mark_unread );
		} );
	}

	function mark_unread( e ) {
		e.preventDefault();

		var button = $( this );
		var data   = {
			'action': 'feed_reader_entries_mark_unread',
			'id': button.data( 'entry-id' ),
			'_wpnonce': button.data( 'nonce' )
		};

		$.post( ajaxurl, data, function( response ) {
			button.unbind( 'click', mark_unread );
			button.bind( 'click', mark_read );

			button.toggleClass( 'mark-read mark-unread' );
			button.text( feed_reader_obj.mark_read );
		} );
	}

	$( '.feed-reader .mark-read' ).click( mark_read );
	$( '.feed-reader .mark-unread' ).click( mark_unread );

	$( '#feed-reader-category-search-input' ).keyup( function( e ) {
		if ( 'Enter' === e.key ) {
			$( '#categories-filter #search-submit' ).click();
		}
	} );

	$( '#categories-filter #search-submit' ).click( function( e ) {
		var url = new URL( window.location.href );
		url.searchParams.delete( 'paged' );
		url.searchParams.set( 's', $( '#feed-reader-category-search-input' ).val() );
		location.assign( url );
	} );

	$( '#feed-reader-feed-search-input' ).keyup( function( e ) {
		if ( 'Enter' === e.key ) {
			$( '#feeds-filter #search-submit' ).click();
		}
	} );

	$( '#feeds-filter #search-submit' ).click( function( e ) {
		var url = new URL( window.location.href );
		url.searchParams.delete( 'paged' );
		url.searchParams.set( 's', $( '#feed-reader-feed-search-input' ).val() );
		location.assign( url );
	} );
} );
