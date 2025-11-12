// Use vitest/config ESM entry to avoid CommonJS require transformation issues under Node 18.
// eslint-disable-next-line import/no-unresolved
import { defineConfig } from 'vitest/config';

export default defineConfig( {
	// Treat block source files (which contain JSX) as JSX even though they use .js extension.
	esbuild: {
		loader: 'jsx',
		include: /src\/block\/vote-block\/.*\.js$/,
	},
	test: {
		environment: 'jsdom',
		include: [ 'src/block/vote-block/__tests__/**/*.test.{js,jsx,ts,tsx}' ],
		coverage: {
			reporter: [ 'text', 'json', 'html' ],
		},
	},
} );
