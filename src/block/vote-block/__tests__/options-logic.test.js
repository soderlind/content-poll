import { describe, it, expect } from 'vitest';
import { clampOptionCount, expandOrTrimOptions } from '../helpers.js';

describe( 'Option logic helpers', () => {
	it( 'clamps option count within 2..6', () => {
		expect( clampOptionCount( 1 ) ).toBe( 2 );
		expect( clampOptionCount( 2 ) ).toBe( 2 );
		expect( clampOptionCount( 5 ) ).toBe( 5 );
		expect( clampOptionCount( 10 ) ).toBe( 6 );
	} );

	it( 'expands options when target larger', () => {
		const start = [ 'A', 'B' ];
		const result = expandOrTrimOptions( start, 4, 'Option' );
		expect( result.length ).toBe( 4 );
		expect( result.slice( 0, 2 ) ).toEqual( [ 'A', 'B' ] );
	} );

	it( 'trims options when target smaller', () => {
		const start = [ 'A', 'B', 'C', 'D', 'E' ];
		const result = expandOrTrimOptions( start, 3, 'Option' );
		expect( result ).toEqual( [ 'A', 'B', 'C' ] );
	} );

	it( 'does not exceed 6 options even if target >6', () => {
		const start = [ 'A', 'B' ];
		const result = expandOrTrimOptions( start, 12, 'Option' );
		expect( result.length ).toBe( 6 );
	} );

	it( 'fills with default label when expanding', () => {
		const start = [ 'A' ];
		const result = expandOrTrimOptions( start, 2, 'Default' );
		expect( result ).toEqual( [ 'A', 'Default' ] );
	} );
} );
