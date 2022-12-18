jQuery( document ).ready( function ( $ ) {
	function mark_read() {
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

	function mark_unread() {
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

	$( '.feed-reader .delete, .feed-reader .button-delete' ).click( function() {
		if ( ! confirm( feed_reader_obj.confirm ) ) {
			return;
		}

		var button = $( this );
		var data   = {
			'action': 'feed_reader_entries_delete',
			'id': button.data( 'entry-id' ),
			'_wpnonce': button.data( 'nonce' )
		};

		$.post( ajaxurl, data, function( response ) {
			var feed = button.closest( '.hfeed' );
			button.closest( '.hentry' ).remove();

			if ( ! $( '.hentry' ).length ) {
				feed.html( feed_reader_obj.all_done );
			}
		} );
	} );

	// Category and feed search.
	/** @todo: Rewrite this without the need for JS. */
	$( '#feed-reader-category-search-input' ).keyup( function( e ) {
		if ( 'Enter' === e.key ) {
			$( '#categories-filter #search-submit' ).click();
		}
	} );

	$( '#categories-filter #search-submit' ).click( function() {
		var search = $( '#feed-reader-category-search-input' ).val();

		if ( search ) {
		var url = new URL( window.location.href );
			url.searchParams.delete( 'paged' );
			url.searchParams.set( 's', search );
			location.assign( url );
		}
	} );

	$( '#feed-reader-feed-search-input' ).keyup( function( e ) {
		if ( 'Enter' === e.key ) {
			$( '#feeds-filter #search-submit' ).click();
		}
	} );

	$( '#feeds-filter #search-submit' ).click( function() {
		var search = $( '#feed-reader-feed-search-input' ).val();

		if ( search ) {
			var url = new URL( window.location.href );
			url.searchParams.delete( 'paged' );
			url.searchParams.set( 's', search );
			location.assign( url );
		}
	} );

	// Reacting to entries.
	$( '.feed-reader .button-reply' ).click( function() {
		var entry = $( this ).closest( '.hentry' );
		var form  = entry.find( '.reply-form' );

		if ( form.is( ':hidden' ) ) {
			form.show();
			form.find( 'textarea' ).focus();
		} else {
			form.hide();
		}

		// Hiding the "other" form afterward presents jumps when switching
		// forms.
		entry.find( '.bookmark-form' ).hide();
	} );

	$( '.feed-reader .button-publish-reply' ).click( function() {
		var button = $( this );
		var entry  = button.closest( '.hentry' );
		var form   = entry.find( '.reply-form' );

		if ( '' === form.find( 'textarea' ).val() ) {
			return;
		}

		var data   = {
			'action': 'feed_reader_post',
			'in-reply-to': entry.data( 'url' ),
			'name': form.find( 'input[type="text"]' ).val(),
			'content': form.find( 'textarea' ).val(),
			'_wpnonce': button.closest( '.actions' ).data( 'nonce' )
		};

		$.post( ajaxurl, data, function( response ) {
			form.hide();
			form.find( 'textarea' ).val( '' )
		} );
	} );

	$( '.feed-reader .button-like' ).click( function() {
		var button = $( this );
		var entry  = button.closest( '.hentry' );
		var data   = {
			'action': 'feed_reader_post',
			'like-of': entry.data( 'url' ),
			'_wpnonce': button.closest( '.actions' ).data( 'nonce' )
		};

		$.post( ajaxurl, data, function( response ) {
			entry.find( '.icon-star use' ).attr( 'fill', 'currentColor' );
		} );
	} );

	$( '.feed-reader .button-bookmark' ).click( function() {
		var entry = $( this ).closest( '.hentry' );
		var form  = entry.find( '.bookmark-form' );

		if ( form.is( ':hidden' ) ) {
			form.show();
			form.find( 'textarea' ).focus();
		} else {
			form.hide();
		}

		// Hiding the "other" form afterward presents jumps when switching
		// forms.
		entry.find( '.reply-form' ).hide();
	} );

	$( '.feed-reader .button-publish-bookmark' ).click( function( e ) {
		e.preventDefault();

		var button = $( this );
		var entry  = button.closest( '.hentry' );
		var form   = entry.find( '.bookmark-form' );

		if ( '' === form.find( 'textarea' ).val() ) {
			return;
		}

		var data   = {
			'action': 'feed_reader_post',
			'bookmark-of': entry.data( 'url' ),
			'name': form.find( 'input[type="text"]' ).val(),
			'content': form.find( 'textarea' ).val(),
			'_wpnonce': button.closest( '.actions' ).data( 'nonce' )
		};

		$.post( ajaxurl, data, function( response ) {
			entry.find( '.icon-bookmark use' ).attr( 'fill', 'currentColor' );
			form.hide();
			form.find( 'textarea' ).val( '' );
		} );
	} );
} );
