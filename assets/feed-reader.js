jQuery( document ).ready( function ( $ ) {
	if ( 'undefined' !== typeof hljs ) {
		document.querySelectorAll( '.feed-reader article pre' ).forEach( function ( el ) {
			hljs.highlightElement( el );
		} );
	}

	$( '.feed-reader .entry-content img, .feed-reader .entry-summary img').each( function() {
		if ( this.width > 50 ) {
			// Don't display "wider" images inline.
			$( this ).css( 'display', 'block' );
		}
	} );

	$( '.feed-reader .entry-content a:has(img), .feed-reader .entry-summary a:has(img)' ).each( function() {
		var link = $( this );

		// Note that this does not always work, as the script may run before
		// images are downloaded and then the styles may not be applied.
		if ( link.width() > 250 ) {
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

				updateReadCount();

				return;
			}

			button.unbind( 'click', mark_read );
			button.bind( 'click', mark_unread );

			button.toggleClass( 'mark-read mark-unread' );
			button.text( feed_reader_obj.mark_unread );

			updateReadCount();
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

			updateReadCount();
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

				updateReadCount();
			} else {
				var url = new URL( window.location.href );
				url.searchParams.delete( 'paged' );
				url.searchParams.set( 'page', 'feed-reader/feeds/view' );
				url.searchParams.set( 'id', entry.data( 'feed-id' ) );
				location.assign( url );
			}
		} );
	} );

	$( '.reader_page_feed-reader-feeds .delete, .reader_page_feed-reader-categories .delete, .reader_page_feed-reader-feeds-edit .delete, .reader_page_feed-reader-categories-edit .delete' ).click( function() {
		return confirm( feed_reader_obj.confirm );
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
			'status': form.find( 'select' ).val(),
			'mp-syndicate-to': form.find( 'input:checked' ).map( function() { return $( this ).val(); } ).get(),
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
			entry.find( '.icon-heart use' ).attr( 'fill', 'currentColor' );
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
			'status': form.find( 'select' ).val(),
			'mp-syndicate-to': form.find( 'input:checked' ).map( function() { return $( this ).val(); } ).get(),
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
					'mf2':       'Microformats',
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

	$( '#feed-reader-generate-secret' ).click( function() {
		var chars = '0123456789abcdefghijklmnopqrstuvwxyz!@#$%^&*()ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		var pass  = '';
		var rand  = 0;

		for ( var i = 0; i <= 32; i++ ) {
			rand  = Math.floor( Math.random() * chars.length );
			pass += chars.substring( rand, rand + 1 );
		}

		$( '#feed-reader-image-proxy-secret' ).val( pass );
	} );

	var menuLabel = $( '#wp-admin-bar-feed-reader .ab-label');

	// Fetch unread post count, and all categories and feeds and their unread post count.
	// Something something update `#wp-admin-bar-feed-reader .ab-label`.
	// And then each `.feed-reader-sidebar details summary a` and `.feed-reader-sidebar details li a`.
	function updateReadCount() {
		// Like a time-out.
		const controller = new AbortController();
		const timeoutId  = setTimeout( () => {
			controller.abort();
		}, 6000 );

		window.wp.apiFetch( {
			path: '/feed-reader/v1/unread-count',
			signal: controller.signal, // That time-out thingy.
		} ).then( function( response ) {
			clearTimeout( timeoutId );

			if ( response.hasOwnProperty( 'unread' ) ) {
				/** @todo: Get rid of hardcoded label. */
				menuLabel.text( 'Reader (' + response.unread + ')' );
			}

			// Next, loop over categories and feeds, and update sidebar unread
			// counts.
			if ( response.hasOwnProperty( 'categories' ) ) {
				Object.entries( response.categories ).forEach( function ( entry ) {
					const [ key, value ] = entry;

					const link = document.querySelector( '[data-category-id="' + key + '"] > summary a' );
					if ( link ) {
						const url = new URL( link.href )

						if ( 0 === value ) {
							url.searchParams.set( 'all', '1' );
						} else {
							url.searchParams.delete( 'all');
						}
						link.href = url.toString();

						const counter = link.querySelector( '[data-category-id="' + key + '"] > summary .unread-count' );
						if ( counter ) {
							if ( 0 === value ) {
								counter.textContent = '';
							} else {
								counter.textContent = '(' + value + ')';
							}
						}
					}
				} );

			}

			if ( response.hasOwnProperty( 'feeds' ) ) {
				Object.entries( response.feeds ).forEach( function ( entry ) {
					const [ key, value ] = entry;
					const link = document.querySelector( '[data-feed-id="' + key + '"] a' );
					if ( link ) {
						const url = new URL( link.href )

						if ( 0 === value ) {
							url.searchParams.set( 'all', '1' );
						} else {
							url.searchParams.delete( 'all');
						}
						link.href = url.toString();

						const counter = document.querySelector( '[data-feed-id="' + key + '"] .unread-count' );
						if ( counter ) {
							if ( 0 === value ) {
								counter.textContent = '';
							} else {
								counter.textContent = '(' + value + ')';
							}
						}
					}
				} );
			}
		} ).catch( function( error ) {
			// The request timed out or otherwise failed. Leave as is.
		} );
	}

	var lastReload = parseInt( Date.now() / 1000 );

	function setTimer() {
		return setInterval( () => {
			if ( parseInt( Date.now() / 1000 ) - lastReload > 30 ) {
				// The data was last refreshed over 30 seconds ago.
				lastReload = parseInt( Date.now() / 1000 );
				updateReadCount();
			}
		}, 5000 ); // Run every 5 seconds.
	};

	// Immediately update read counts to cover single entries, too.
	updateReadCount();

	var intervalId = setTimer();

	window.addEventListener( 'blur', () => {
		clearInterval( intervalId );
	} );

	window.addEventListener( 'focus', () => {
		intervalId = setTimer();
	} );
} );
