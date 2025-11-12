import { describe, it, expect } from 'vitest';
import { computePercentages } from '../helpers.js';

describe( 'Results logic', () => {
	it( 'computes percentages with rounding', () => {
		const { total, percentages } = computePercentages( {
			0: 2,
			1: 0,
			2: 1,
		} );
		expect( total ).toBe( 3 );
		expect( percentages[ 0 ] ).toBeGreaterThanOrEqual( 66 );
		expect( percentages[ 0 ] ).toBeLessThanOrEqual( 67 );
		expect( percentages[ 2 ] ).toBeGreaterThanOrEqual( 33 );
		expect( percentages[ 2 ] ).toBeLessThanOrEqual( 34 );
	} );
	it( 'handles zero total', () => {
		const { total, percentages } = computePercentages( { 0: 0, 1: 0 } );
		expect( total ).toBe( 0 );
		expect( percentages[ 0 ] ).toBe( 0 );
		expect( percentages[ 1 ] ).toBe( 0 );
	} );
} );
