/**
 * Vitest configuration.
 *
 * wp-scripts 36 runs the consumer's Vitest installation, so the project
 * provides its own config: Jest-style globals (the test files use
 * `describe`/`it`/`expect` without imports) and a DOM for the helpers that
 * parse sprite markup.
 */
import { defineConfig } from 'vitest/config';

export default defineConfig( {
	test: {
		globals: true,
		environment: 'jsdom',
	},
} );
