( function () {
	'use strict';
	var cfg = window.MitosChecklist || {};
	var S = cfg.strings || {};

	function el( tag, cls, text ) {
		var n = document.createElement( tag );
		if ( cls ) { n.className = cls; }
		if ( text != null ) { n.textContent = text; }
		return n;
	}
	function fmt( str ) {
		var a = arguments;
		var i = 1;
		return str.replace( /%(\d\$)?[ds]/g, function () { return a[ i++ ]; } );
	}
	function link( url, text ) {
		var a = el( 'a', null, text || url );
		a.href = url;
		a.target = '_blank';
		a.rel = 'noopener';
		return a;
	}
	function get( path ) {
		return fetch( cfg.rest + path ).then( function ( r ) {
			if ( ! r.ok ) { throw new Error( r.status ); }
			return r.json();
		} );
	}
	function date( ts ) {
		return new Date( ts * 1000 ).toLocaleDateString( cfg.dateFmt || 'en-GB', { year: 'numeric', month: 'long', day: 'numeric' } );
	}
	function load( id ) {
		try { return JSON.parse( localStorage.getItem( 'mitoschk:' + id ) ) || {}; } catch ( e ) { return {}; }
	}
	function save( id, state ) {
		try { localStorage.setItem( 'mitoschk:' + id, JSON.stringify( state ) ); } catch ( e ) {}
	}

	function footer( root ) {
		var f = el( 'p', 'mitoschk-foot' );
		f.appendChild( document.createTextNode( S.source + ' · ' ) );
		f.appendChild( link( 'https://creativecommons.org/licenses/by-sa/4.0/', S.licence ) );
		root.appendChild( f );
		root.appendChild( el( 'p', 'mitoschk-foot', S.disclaimer ) );
	}

	function section( root, title, items, render ) {
		if ( ! items || ! items.length ) { return; }
		var sec = el( 'section', 'mitoschk-sec' );
		sec.appendChild( el( 'h3', null, title ) );
		var ul = el( 'ul' );
		items.forEach( function ( it, i ) { ul.appendChild( render( it, i ) ); } );
		sec.appendChild( ul );
		root.appendChild( sec );
	}

	function detail( root, p, single ) {
		root.textContent = '';
		var state = load( p.id );
		state.done = state.done || {};

		if ( ! single ) {
			var back = el( 'button', 'mitoschk-back', S.back );
			back.type = 'button';
			back.onclick = function () { history.pushState( null, '', location.pathname + location.search ); list( root ); };
			root.appendChild( back );
		}
		root.appendChild( el( 'h2', null, p.title ) );
		if ( p.owner ) { root.appendChild( el( 'p', 'mitoschk-owner', p.owner ) ); }
		if ( p.description ) { root.appendChild( el( 'p', null, p.description ) ); }

		var trust = el( 'p', 'mitoschk-trust' );
		if ( p.reviewed ) { trust.appendChild( el( 'span', 'mitoschk-badge', fmt( S.reviewed, date( p.reviewed ) ) ) ); }
		trust.appendChild( el( 'span', null, fmt( S.refreshed, date( p.synced ) ) ) );
		root.appendChild( trust );

		var bar = el( 'div', 'mitoschk-progress' );
		var msg = el( 'p', 'mitoschk-msg' );
		var rem = el( 'ul', 'mitoschk-rem' );
		var track = el( 'div', 'mitoschk-track' );
		var fill = el( 'div', 'mitoschk-fill' );
		track.appendChild( fill );
		bar.appendChild( msg ); bar.appendChild( track ); bar.appendChild( rem );
		root.appendChild( bar );

		function refresh() {
			var docs = p.documents || [];
			if ( ! docs.length ) { msg.textContent = S.noDocs; track.hidden = true; return; }
			var n = docs.filter( function ( d ) { return state.done[ 'd' + d.key ]; } ).length;
			msg.textContent = n === docs.length ? S.allDone : fmt( S.progress, n, docs.length );
			fill.style.width = Math.round( n / docs.length * 100 ) + '%';
			rem.textContent = '';
			if ( n < docs.length ) {
				rem.appendChild( el( 'li', 'mitoschk-remhead', S.remaining ) );
				docs.forEach( function ( d ) {
					if ( ! state.done[ 'd' + d.key ] ) { var t = d.text ? ( d.text.length > 90 ? d.text.slice( 0, 90 ) + '…' : d.text ) : '';
						rem.appendChild( el( 'li', null, [ d.title, t ].filter( Boolean ).join( ': ' ) ) ); }
				} );
			}
		}

		function check( prefix, it, i ) {
			var li = el( 'li', 'mitoschk-item' );
			var key = prefix + ( it.key || i );
			var lab = el( 'label' );
			var cb = el( 'input' );
			cb.type = 'checkbox';
			cb.checked = !! state.done[ key ];
			cb.onchange = function () {
				if ( cb.checked ) { state.done[ key ] = 1; } else { delete state.done[ key ]; }
				save( p.id, state );
				refresh();
			};
			lab.appendChild( cb );
			lab.appendChild( el( 'strong', null, ' ' + ( it.title || it.type || '' ) ) );
			if ( it.alt ) { lab.appendChild( el( 'em', null, ' (' + S.alternative + ')' ) ); }
			li.appendChild( lab );
			if ( it.text ) { li.appendChild( el( 'p', null, it.text ) ); }
			if ( it.how ) { li.appendChild( el( 'p', 'mitoschk-small', it.how ) ); }
			if ( it.note ) { li.appendChild( el( 'p', 'mitoschk-small', it.note ) ); }
			if ( it.url ) { li.appendChild( link( it.url ) ); }
			return li;
		}

		section( root, S.conditions, p.conditions, function ( c, i ) { return check( 'c', c, i ); } );
		section( root, S.documents, p.documents, function ( d, i ) { d.key = d.key || i + 1; return check( 'd', d, i ); } );
		section( root, S.fees, p.fees, function ( f ) {
			var li = el( 'li', 'mitoschk-item' );
			li.appendChild( el( 'strong', null, f.type ) );
			if ( f.min || f.max ) { li.appendChild( el( 'span', null, ' ' + [ f.min, f.max ].filter( Boolean ).join( '–' ) + ' €' ) ); }
			if ( f.text ) { li.appendChild( el( 'p', null, f.text ) ); }
			return li;
		} );
		section( root, S.steps, p.steps, function ( s, i ) {
			var li = el( 'li', 'mitoschk-item' );
			li.appendChild( el( 'strong', null, ( i + 1 ) + '. ' + s.title ) );
			if ( s.time ) { li.appendChild( el( 'span', 'mitoschk-small', ' (' + s.time + ')' ) ); }
			if ( s.text ) { li.appendChild( el( 'p', null, s.text ) ); }
			if ( s.url ) { li.appendChild( link( s.url ) ); }
			return li;
		} );

		var go = el( 'div', 'mitoschk-go' );
		( p.apply || [] ).forEach( function ( a ) {
			var l = link( a.url, S.apply + ': ' + a.title );
			l.className = 'mitoschk-btn';
			go.appendChild( l );
		} );
		root.appendChild( go );

		section( root, S.links, p.links.concat( [ { title: S.source, url: p.source_url } ] ), function ( l ) {
			var li = el( 'li' );
			li.appendChild( link( l.url, l.title ) );
			return li;
		} );

		var notes = el( 'section', 'mitoschk-sec mitoschk-notes' );
		notes.appendChild( el( 'h3', null, S.notes ) );
		var ta = el( 'textarea' );
		ta.rows = 4;
		ta.value = state.notes || '';
		ta.oninput = function () { state.notes = ta.value; save( p.id, state ); };
		notes.appendChild( ta );
		root.appendChild( notes );

		var pr = el( 'button', 'mitoschk-print', S.print );
		pr.type = 'button';
		pr.onclick = function () { window.print(); };
		root.appendChild( pr );

		footer( root );
		refresh();
	}

	function open( root, id, single ) {
		root.textContent = S.loading;
		get( 'procedures/' + id ).then( function ( p ) { detail( root, p, single ); } )
			.catch( function () { root.textContent = S.error; } );
	}

	function list( root ) {
		root.textContent = '';
		var q = el( 'input', 'mitoschk-search' );
		q.type = 'search';
		q.placeholder = S.search;
		q.setAttribute( 'aria-label', S.search );
		var ul = el( 'ul', 'mitoschk-list' );
		root.appendChild( q ); root.appendChild( ul );
		get( 'procedures' ).then( function ( items ) {
			function draw() {
				var t = q.value.trim().toLowerCase();
				ul.textContent = '';
				var shown = items.filter( function ( it ) {
					return ! t || ( it.title + ' ' + it.description + ' ' + it.owner ).toLowerCase().indexOf( t ) > -1;
				} );
				if ( ! shown.length ) { ul.appendChild( el( 'li', null, S.none ) ); }
				shown.forEach( function ( it ) {
					var li = el( 'li' );
					var b = el( 'button', 'mitoschk-row' );
					b.type = 'button';
					b.appendChild( el( 'strong', null, it.title ) );
					if ( it.owner ) { b.appendChild( el( 'span', 'mitoschk-small', it.owner ) ); }
					b.onclick = function () { history.pushState( null, '', '#mitos-' + it.id ); open( root, it.id, false ); };
					li.appendChild( b );
					ul.appendChild( li );
				} );
			}
			q.oninput = draw;
			draw();
			footer( root );
		} ).catch( function () { root.textContent = S.error; } );
	}

	function start( root ) {
		var fixed = root.getAttribute( 'data-id' );
		if ( fixed ) { open( root, fixed, true ); return; }
		var m = /^#mitos-(\d+)$/.exec( location.hash );
		if ( m ) { open( root, m[ 1 ], false ); } else { list( root ); }
		window.addEventListener( 'popstate', function () {
			var h = /^#mitos-(\d+)$/.exec( location.hash );
			if ( h ) { open( root, h[ 1 ], false ); } else { list( root ); }
		} );
	}

	document.querySelectorAll( '.mitoschk' ).forEach( start );
}() );
