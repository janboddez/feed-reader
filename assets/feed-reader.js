jQuery( document ).ready( function ( $ ) {
	$( '.feed-reader .entry-content img, .feed-reader .entry-summary img').each( function() {
		if ( this.width > 250 ) {
			// Don't display "wider" images inline.
			$( this ).css( 'display', 'block' );
		// } else if ( this.getBoundingClientRect().width <= 32 ) {
		// 	$( this ).addClass( 'avatar' );
		}
	} );

	$( '.feed-reader .entry-content a:has(img), .feed-reader .entry-summary a:has(img)' ).each( function() {
		var link = $( this );

		if ( link.width() > 250 ) {
			link.addClass( 'image-wide' ); // We use this class to address "image captions" that follow the image (link).

			// "Fix" WordPress's focus outlines.
			link.css( 'display', 'inline-block' );
			link.find( 'img' ).css( 'vertical-align', 'middle' );
		}
	} );

	function mark_read() {
		var button = $( this );
		var entry  = button.closest( '.hentry' );
		var data   = {
			'action': 'feed_reader_entries_mark_read',
			'id': entry.data( 'id' ),
			'_wpnonce': button.data( 'nonce' )
		};

		$.post( ajaxurl, data, function( response ) {
			var feed = entry.closest( '.hfeed' );

			if ( feed.length && ! ( new URLSearchParams( window.location.search ) ).has( 'all' ) ) {
				entry.remove();

				if ( ! feed.find( '.hentry' ).length ) {
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
		var entry  = button.closest( '.hentry' );
		var data   = {
			'action': 'feed_reader_entries_mark_unread',
			'id': entry.data( 'id' ),
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
		var entry  = button.closest( '.hentry' );
		var data   = {
			'action': 'feed_reader_entries_delete',
			'id': entry.data( 'id' ),
			'_wpnonce': button.data( 'nonce' )
		};

		$.post( ajaxurl, data, function( response ) {
			var feed = button.closest( '.hfeed' );

			if ( feed.length ) {
				entry.remove();

				if ( ! feed.find( '.hentry' ).length ) {
					feed.html( feed_reader_obj.all_done );
				}
			} else {
				var url = new URL( window.location.href );
				url.searchParams.delete( 'paged' );
				url.searchParams.set( 'page', 'feed-reader-feeds-view' );
				url.searchParams.set( 'id', entry.data( 'feed-id' ) );
				location.assign( url );
			}
		} );
	} );

	$( '.reader_page_feed-reader-feeds-edit .delete, .reader_page_feed-reader-categories-edit .delete' ).click( function() {
		return confirm( feed_reader_obj.confirm );
	} );

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

	$( '.feed-reader .button-reply' ).click( function() {
		var entry = $( this ).closest( '.hentry' );
		var form  = entry.find( '.reply-form' );

		if ( form.is( ':hidden' ) ) {
			form.show();
			form.find( 'textarea' ).focus();
		} else {
			form.hide();
		}

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

	$( '#site_or_feed_url' ).focus();
	$( '#feed-discover button' ).click( function() {
		var button = $( this );
		var url    = $( '#site_or_feed_url' ).val();
		var data   = {
			'action': 'feed_reader_feeds_discover',
			'url': url,
			'_wpnonce': button.data( 'nonce' )
		};

		$.post( ajaxurl, data, function( response ) {
			var list = $( '#feed-list' );

			list.empty();

			if ( ! response.feeds || ! response.feeds.length ) {
				if ( ! button.hasClass( 'button-primary' ) ) {
					button.addClass( 'button-primary' );
				}

				list.append( '<li>No feeds found.</li>' );
				list.show();
			} else {
				var formats = {
					'atom':      'Atom',
					'json_feed': 'JSON Feed',
					'rss':       'RSS',
					'xml':       'XML',
				};

				button.removeClass( 'button-primary' );

				$.each( response.feeds, function( i, val ) {
					list.append( '<li><div><h3>' + formats[ val.format ] + '</h3>' + val.url + '</div><button type="button" class="button button-primary select-feed" data-url="' + val.url + '">Select</button></li>' );
				} );

				list.show();

				$( '.select-feed' ).click( function() {
					if ( response.title ) {
						$( '#feed-name' ).val( response.title );
					} else {
						$( '#feed-name' ).val( '' );
					}

					$( '#feed-url' ).val( $( this ).data( 'url' ) );

					$( '#site-url' ).val( '' );
					if ( -1 === $.inArray( url, response.feeds.map( el => el.url ) ) ) {
						$( '#site-url' ).val( url );
					} else {
						$( '#site-url' ).val( '' );
					}

					$( '#feed-discover' ).hide();
					list.hide();

					$( '#feed-create' ).show();
					$( '#feed-name' ).focus();
				} );
			}
		} );
	} );

	$( '#site_or_feed_url' ).keyup( function( e ) {
		if ( 'Enter' === e.key ) {
			$( '#feed-discover button' ).click();
		}
	} );
} );
