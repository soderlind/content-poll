import { describe, it, expect } from 'vitest';
import { clampOptionCount, expandOrTrimOptions } from '../helpers.js';

// Simulate lock effect by refusing changes when locked
const simulateChangeCount = ( options, newCount, locked ) => {
	if ( locked ) {
		return options;
	} // no change
	const num = clampOptionCount( newCount );
	return expandOrTrimOptions( options, num, 'Option' );
};

describe( 'Lock logic simulation', () => {
	it( 'prevents option count changes when locked', () => {
		const initial = [ 'A', 'B', 'C' ];
		const lockedResult = simulateChangeCount( initial, 5, true );
		expect( lockedResult.length ).toBe( 3 );
		const unlockedResult = simulateChangeCount( initial, 5, false );
		expect( unlockedResult.length ).toBe( 5 );
	} );
} );
