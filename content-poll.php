<?php
/**
 * Plugin Name: ContentPoll AI
 * Description: AI-assisted contextual polls. Ask readers targeted questions about the content they are viewing. Generates smart question + option suggestions (Heuristic, OpenAI, Claude, Gemini, Ollama, Azure OpenAI, Grok). Modern card UI & real-time results.
 * Version: 0.8.0
 * Author: Per Soderlind
 * Author URI: https://soderlind.no
 * Plugin URI: https://github.com/soderlind/content-poll
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.8
 * Requires PHP: 8.3
 * Text Domain: content-poll
 */

/**
 * Bootstrap file for the ContentPoll AI plugin.
 *
 * Responsibilities:
 * - Load Composer autoload (dependencies).
 * - Initialize database via DatabaseManager on plugins_loaded.
 * - Inject CSP nonce into script tags (for strict CSP setups).
 * - Load text domain for translations.
 * - Register REST controllers and block on plugins_loaded.
 *
 * This file intentionally uses core WordPress APIs directly (no function_exists
 * guards) under the assumption it runs inside a fully loaded WP environment.
 *
 * Note: Database initialization moved from activation hook to plugins_loaded
 * to prevent vote data loss during plugin updates. Activation hooks fire on
 * file changes (version bumps), which was causing migration re-triggers.
 */

declare(strict_types=1);
use ContentPoll\Update\GitHubPluginUpdater;
use ContentPoll\Database\DatabaseManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Composer autoload.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

// Inject CSP nonce attribute into enqueued script tags if a server-provided nonce is available.
// This helps satisfy strict Content Security Policy configurations that require a nonce on every script tag.
// You can expose a nonce via:
// 1. Setting $_SERVER['CONTENT_SECURITY_POLICY_NONCE'] in your mu-plugin or server layer.
// 2. Defining a constant CONTENT_POLL_CSP_NONCE.
// 3. Adding a filter: add_filter('content_poll_csp_nonce', fn() => 'your-nonce');
add_filter( 'script_loader_tag', function ( $tag, $handle, $src ) {
	// Skip if nonce already present.
	if ( strpos( $tag, ' nonce=' ) !== false ) {
		return $tag;
	}
	$csp_nonce = '';
	if ( isset( $_SERVER[ 'CONTENT_SECURITY_POLICY_NONCE' ] ) ) {
		$csp_nonce = (string) $_SERVER[ 'CONTENT_SECURITY_POLICY_NONCE' ];
	}
	if ( ! $csp_nonce && defined( 'CONTENT_POLL_CSP_NONCE' ) ) {
		$csp_nonce = (string) CONTENT_POLL_CSP_NONCE;
	}
	// Allow integrators to override / provide nonce value.
	$csp_nonce = apply_filters( 'content_poll_csp_nonce', $csp_nonce, $handle, $src );
	if ( $csp_nonce ) {
		$escaped = esc_attr( $csp_nonce ); // Standard attribute escaping.
		$tag     = preg_replace( '/<script\b/', '<script nonce="' . $escaped . '"', $tag, 1 );
	}
	return $tag;
}, 10, 3 );

add_action( 'plugins_loaded', function () {
	// Load translations. Domain path declared in header.
	load_plugin_textdomain( 'content-poll', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// Initialize database (creates table or runs migrations if needed).
	// This runs early to ensure table exists before any REST/block operations.
	// Uses version-based migration tracking to avoid repeated checks.
	DatabaseManager::instance()->initialize();

	// Update checker via GitHub releases.
	GitHubPluginUpdater::create_with_assets(
		'https://github.com/soderlind/content-poll',
		__FILE__,
		'content-poll',
		'/content-poll\.zip/',
		'main'
	);
	// Register REST endpoints for voting, nonce, results, AI suggestion.
	( new \ContentPoll\REST\VoteController() )->register();
	( new \ContentPoll\REST\NonceController() )->register();
	( new \ContentPoll\REST\ResultsController() )->register();
	( new \ContentPoll\REST\SuggestionController() )->register();

	// Register dynamic block (server-rendered markup) and admin settings.
	( new \ContentPoll\Blocks\VoteBlock() )->register();
	new \ContentPoll\Admin\SettingsPage();

	// Invalidate cached analytics summary when posts are saved (content changes may add/remove poll blocks).
	add_action( 'save_post', function ( $post_id ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( function_exists( 'delete_transient' ) ) {
			delete_transient( 'content_poll_posts_summary' );
		}
	}, 10, 1 );
} );

// One-time migration: backfill missing post_id for early votes stored before post_id capture was added.
// Removed legacy migration logic in favor of runtime fallback counting for post_id = 0 records.
// Removed inline localization to comply with strict CSP (no inline scripts).
// Block editor JS now derives postId via wp.data.select('core/editor').getCurrentPostId()
// and nonce from wpApiSettings.nonce (core REST setup) without plugin-added inline script.

