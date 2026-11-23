( function() {
	'use strict';

	const loader = document.querySelector( '.wholesale-page-loader' );

	if ( ! loader ) {
		return;
	}

	const root = document.documentElement;
	const storageKey = 'wholesale-page-loader-handoff';
	// When the loader markup was parsed (set by the inline script after it).
	const shownAt = Number( window.wholesalePageLoaderShownAt ) || Number( window.wholesalePageLoaderStartedAt ) || Date.now();
	const reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	const isHandoff = root.classList.contains( 'wholesale-page-loader-handoff' );
	// First page of a visit: the head script hid the loader (display:none), so
	// it is only shown again for a link click.
	const isLanding = root.classList.contains( 'wholesale-page-loader-landing' );
	const introDuration = reducedMotion || isHandoff ? 0 : 1700;
	// Hard cap, counted from now: this deferred script runs once the whole HTML
	// has arrived, so a slow connection no longer eats into it. Normally the
	// loader leaves much earlier, as soon as the first screen is fully shown
	// (see firstViewReady), so this only stops a stuck download holding it.
	const maximumAfterReady = 30000;
	let introTimer = 0;
	let maximumTimer = 0;
	let loadTimer = 0;
	let initialExitTimer = 0;
	let navigationTimer = 0;
	let navigating = false;
	let initialHidden = root.classList.contains( 'wholesale-page-loader-failsafe' );
	let loadHandled = false;

	function clearTimer( timer ) {
		if ( timer ) {
			window.clearTimeout( timer );
		}
	}

	function setHidden( hidden ) {
		loader.classList.toggle( 'is-hidden', hidden );
		loader.setAttribute( 'aria-hidden', hidden ? 'true' : 'false' );
	}

	function clearNavigationFlag() {
		try {
			window.sessionStorage.removeItem( storageKey );
		} catch ( error ) {
			console.warn( 'Page loader hand-off storage is unavailable.', error );
		}
	}

	function hideInitialLoader( force ) {
		if ( initialHidden || navigating ) {
			return;
		}

		if ( ! force && ! loader.classList.contains( 'is-exiting' ) ) {
			loader.classList.remove( 'is-intro', 'is-idle' );
			loader.classList.add( 'is-exiting' );
			initialExitTimer = window.setTimeout( function() {
				hideInitialLoader( true );
			}, 380 );
			return;
		}

		initialHidden = true;
		clearTimer( maximumTimer );
		clearTimer( loadTimer );
		clearTimer( initialExitTimer );
		clearTimer( introTimer );
		loader.classList.remove( 'is-intro', 'is-idle' );
		loader.classList.add( 'is-exiting' );
		setHidden( true );
	}

	function showForNavigation() {
		clearTimer( maximumTimer );
		clearTimer( loadTimer );
		clearTimer( introTimer );
		clearTimer( navigationTimer );
		navigating = true;
		window.wholesalePageLoaderNavigating = true;
		root.classList.remove( 'wholesale-page-loader-failsafe', 'wholesale-page-loader-landing' );
		loader.classList.remove( 'is-hidden', 'is-intro', 'is-idle', 'is-exiting' );
		loader.classList.add( 'is-idle' );
		setHidden( false );

		try {
			window.sessionStorage.setItem( storageKey, String( Date.now() ) );
		} catch ( error ) {
			console.warn( 'Page loader hand-off storage is unavailable.', error );
		}

		// Safety net for clicks that never navigate (a download, a 204 reply).
		// Long enough for a slow server to start sending the next page.
		navigationTimer = window.setTimeout( function() {
			navigating = false;
			window.wholesalePageLoaderNavigating = false;
			loader.classList.remove( 'is-idle', 'is-intro' );
			loader.classList.add( 'is-exiting' );
			root.classList.add( 'wholesale-page-loader-failsafe' );
			clearNavigationFlag();
			setHidden( true );
		}, 15000 );
	}

	function onWindowLoad() {
		if ( navigating || loadHandled ) {
			return;
		}
		loadHandled = true;

		const elapsed = Date.now() - shownAt;
		const minimumTimeRemaining = 400 - elapsed;
		const introTimeRemaining = introDuration - elapsed;
		const wait = Math.max( 0, minimumTimeRemaining, introTimeRemaining );
		loadTimer = window.setTimeout( hideInitialLoader, wait );
	}

	/**
	 * Whether an element is (partly) inside the first screen.
	 *
	 * @param {Element} element Element to test.
	 * @return {boolean} True when visible in the viewport.
	 */
	function inFirstView( element ) {
		const rect = element.getBoundingClientRect();
		return rect.width > 0 && rect.height > 0 && rect.bottom > 0 && rect.right > 0 &&
			rect.top < window.innerHeight && rect.left < window.innerWidth;
	}

	/**
	 * Resolve once a stylesheet <link> has loaded (or failed).
	 *
	 * @param {HTMLLinkElement} link Stylesheet link.
	 * @return {Promise} Settles when the sheet is usable.
	 */
	function styleReady( link ) {
		if ( link.sheet || 'print' === link.media ) {
			return Promise.resolve();
		}
		return new Promise( function( resolve ) {
			link.addEventListener( 'load', resolve, { once: true } );
			link.addEventListener( 'error', resolve, { once: true } );
		} );
	}

	/**
	 * Resolve once an <img> has loaded (or failed).
	 *
	 * @param {HTMLImageElement} image Image element.
	 * @return {Promise} Settles when the image is decoded or broken.
	 */
	function imageReady( image ) {
		if ( image.complete ) {
			return Promise.resolve();
		}
		return new Promise( function( resolve ) {
			image.addEventListener( 'load', resolve, { once: true } );
			image.addEventListener( 'error', resolve, { once: true } );
		} );
	}

	/**
	 * Resolve once a CSS background image (such as the hero photo) has
	 * downloaded. The browser picks one file from an image-set(), so wait for
	 * any of the listed URLs to show up in the resource timing list instead of
	 * requesting one ourselves (that could download a second copy).
	 *
	 * @param {string[]} urls Candidate URLs from the computed background.
	 * @return {Promise} Settles when one of them has finished downloading.
	 */
	function backgroundReady( urls ) {
		const downloaded = function() {
			return urls.some( function( url ) {
				return 0 === url.indexOf( 'data:' ) || performance.getEntriesByName( url ).length > 0;
			} );
		};
		if ( downloaded() ) {
			return Promise.resolve();
		}
		return new Promise( function( resolve ) {
			const poll = window.setInterval( function() {
				if ( downloaded() ) {
					window.clearInterval( poll );
					resolve();
				}
			}, 200 );
		} );
	}

	/**
	 * Resolve when the first screen of the page is fully shown: stylesheets,
	 * web fonts, and every photo (img or CSS background) inside the viewport.
	 * Unlike the window load event this does not wait for photos further down
	 * the page or for third-party scripts (chat, analytics).
	 *
	 * @return {Promise} Settles when the visible page is ready.
	 */
	function firstViewReady() {
		const waits = [];

		document.querySelectorAll( 'link[rel="stylesheet"]' ).forEach( function( link ) {
			waits.push( styleReady( link ) );
		} );
		if ( document.fonts && document.fonts.ready ) {
			waits.push( document.fonts.ready.catch( function() {} ) );
		}

		Array.prototype.forEach.call( document.images, function( image ) {
			if ( ! loader.contains( image ) && inFirstView( image ) ) {
				waits.push( imageReady( image ) );
			}
		} );

		document.body.querySelectorAll( 'header, section, main, div, figure, a, span' ).forEach( function( element ) {
			if ( loader.contains( element ) ) {
				return;
			}
			const background = window.getComputedStyle( element ).backgroundImage;
			if ( ! background || -1 === background.indexOf( 'url(' ) || ! inFirstView( element ) ) {
				return;
			}
			const urls = [];
			background.replace( /url\(\s*["']?([^"')]+)["']?\s*\)/g, function( match, url ) {
				urls.push( url );
				return match;
			} );
			if ( urls.length ) {
				waits.push( backgroundReady( urls ) );
			}
		} );

		return Promise.all( waits );
	}

	window.wholesalePageLoaderReady = true;

	// The inline script after the markup already started the intro (or the
	// idle loop on a hand-off); this only covers a missing inline script.
	if ( ! loader.classList.contains( 'is-intro' ) && ! loader.classList.contains( 'is-idle' ) ) {
		loader.classList.add( 'is-idle' );
	}
	root.classList.remove( 'wholesale-page-loader-handoff' );

	if ( isLanding ) {
		initialHidden = true;
		loader.classList.remove( 'is-intro', 'is-idle' );
		setHidden( true );
	} else {
		maximumTimer = window.setTimeout( function() {
			hideInitialLoader();
		}, maximumAfterReady );
		// Leave as soon as the visible page is ready, or at the load event,
		// whichever comes first.
		firstViewReady().then( onWindowLoad, onWindowLoad );
		window.addEventListener( 'load', onWindowLoad, { once: true } );
		if ( 'complete' === document.readyState ) {
			onWindowLoad();
		}
	}

	// Listen on window so handlers on document (such as the header cart, which
	// opens the drawer instead of going to /cart/) run first; a click they
	// handle in place is defaultPrevented and does not show the loader.
	window.addEventListener( 'click', function( event ) {
		if ( event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) {
			return;
		}

		const target = event.target instanceof Element ? event.target : null;
		const link = target ? target.closest( 'a[href]' ) : null;

		if ( ! link || link.hasAttribute( 'download' ) || ( link.target && '_self' !== link.target.toLowerCase() ) ) {
			return;
		}
		if ( link.closest( '.no-page-loader, [data-no-loader]' ) ) {
			return;
		}
		if ( link.matches( '.ajax_add_to_cart, .add_to_cart_button' ) ) {
			return;
		}

		let destination;
		try {
			destination = new URL( link.href, window.location.href );
		} catch ( error ) {
			console.warn( 'Page loader skipped an invalid link URL.', error );
			return;
		}
		if ( ( 'http:' !== destination.protocol && 'https:' !== destination.protocol ) || destination.origin !== window.location.origin ) {
			return;
		}

		const currentPage = window.location.origin + window.location.pathname + window.location.search;
		const destinationPage = destination.origin + destination.pathname + destination.search;
		if ( destinationPage === currentPage ) {
			return;
		}

		const pathname = destination.pathname.replace( /\/+$/, '' );
		if ( /\.(?:pdf|jpe?g|png|gif|bmp|tiff?|webp|avif|heic|heif|svg|ico|zip|rar|7z|gz|bz2|tgz|tar|iso|docx?|xlsx?|pptx?|ppsx?|odt|ods|odp|rtf|csv|xml|txt|md|psd|ai|eps|indd|mp[34]|m4a|mov|m4v|avi|mkv|webm|wav|ogg|woff2?|ttf|otf|eot|css|js|json|wasm)$/i.test( pathname ) ) {
			return;
		}

		showForNavigation();
	}, false );

	window.addEventListener( 'pageshow', function( event ) {
		if ( ! event.persisted ) {
			return;
		}

		clearTimer( navigationTimer );
		clearTimer( maximumTimer );
		clearTimer( loadTimer );
		clearTimer( initialExitTimer );
		clearTimer( introTimer );
		navigating = false;
		window.wholesalePageLoaderNavigating = false;
		initialHidden = true;
		loader.classList.remove( 'is-exiting', 'is-intro', 'is-idle' );
		root.classList.add( 'wholesale-page-loader-failsafe' );
		clearNavigationFlag();
		setHidden( true );
	} );
}() );
