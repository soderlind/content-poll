// Pure helper functions (no JSX / WP dependencies) for unit tests.
export const clampOptionCount = ( count ) =>
	Math.min( 6, Math.max( 2, count ) );
export const expandOrTrimOptions = (
	options,
	target,
	defaultLabel = 'Option'
) => {
	const clamped = clampOptionCount( target );
	let next = [ ...options ];
	if ( next.length < clamped ) {
		for ( let i = next.length; i < clamped; i++ ) {
			next.push( defaultLabel );
		}
	} else if ( next.length > clamped ) {
		next = next.slice( 0, clamped );
	}
	return next;
};
export const computePercentages = ( counts ) => {
	const total = Object.values( counts ).reduce( ( a, b ) => a + b, 0 );
	const result = {};
	Object.keys( counts ).forEach( ( k ) => {
		const c = counts[ k ];
		result[ k ] =
			total > 0 ? parseFloat( ( ( c / total ) * 100 ).toFixed( 2 ) ) : 0;
	} );
	return { total, percentages: result };
};
