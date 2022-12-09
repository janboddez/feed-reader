jQuery( document ).ready( function ( $ ) {
	function mark_read( e ) {
		e.preventDefault();

		var button = $( this );
		var data   = {
			'action': 'feed_reader_entries_mark_read',
			'id': button.data( 'entry-id' ), // Current post ID.
			'_wpnonce': button.data( 'nonce' ) // Nonce.
		};

		$.post( ajaxurl, data, function( response ) {
			button.unbind( 'click', mark_read );
			button.bind( 'click', mark_unread );

			button.toggleClass( 'mark-read mark-unread' );
			button.text( 'Mark as unread' );
		} );
	}

	function mark_unread( e ) {
		e.preventDefault();

		var button = $( this );
		var data   = {
			'action': 'feed_reader_entries_mark_unread',
			'id': button.data( 'entry-id' ), // Current post ID.
			'_wpnonce': button.data( 'nonce' ) // Nonce.
		};

		$.post( ajaxurl, data, function( response ) {
			button.unbind( 'click', mark_unread );
			button.bind( 'click', mark_read );

			button.toggleClass( 'mark-read mark-unread' );
			button.text( 'Mark as read' );
		} );
	}

	$( '.feed-reader .mark-read' ).click( mark_read );
	$( '.feed-reader .mark-unread' ).click( mark_unread );
} );
