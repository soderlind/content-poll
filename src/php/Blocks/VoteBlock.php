<?php

declare(strict_types=1);

namespace ContentPoll\Blocks;

use ContentPoll\Security\SecurityHelper;

/**
 * Dynamic vote block registration & rendering.
 *
 * The block is registered on init using the built build/block/vote-block
 * metadata directory which contains block.json + assets. Rendering is server
 * side to inject a nonce and user-facing strings safely.
 */
class VoteBlock {
	/**
	 * Register block type with a render callback.
	 */
	public function register(): void {
		add_action( 'init', function () {
			register_block_type( __DIR__ . '/../../../build/block/vote-block', [
				'render_callback' => [ $this, 'render' ],
			] );
		} );
	}

	/**
	 * Render front-end markup for the block.
	 *
	 * @param array  $attributes Block attributes (question, options, pollId/blockId).
	 * @param string $content    Original block content (unused; dynamic block).
	 * @return string HTML output for the voting interface.
	 */
	public function render( array $attributes, string $content ): string {
		$poll_id      = $attributes[ 'pollId' ] ?? $attributes[ 'blockId' ] ?? uniqid( 'vote_', true );
		$block_id     = $attributes[ 'blockId' ] ?? $poll_id; // legacy attribute support
		$question_raw = $attributes[ 'question' ] ?? 'Your opinion?';
		$question     = esc_html( $question_raw ); // Question text sanitized.
		$options      = $attributes[ 'options' ] ?? [ 'A', 'B', 'C', 'D' ];
		// Capture current post ID for accurate analytics linkage.
		global $post;
		$post_id   = $post ? (int) $post->ID : 0;
		$postIdEsc = esc_attr( $post_id );
		$nonce     = SecurityHelper::create_nonce();
		$opts_html = '';
		foreach ( $options as $i => $label ) {
			$labelEsc   = esc_html( $label ); // Each option label escaped.
			$aria       = 'Vote for option ' . ( $i + 1 );
			$ariaEsc    = esc_attr( $aria ); // Accessibility label escaped.
			$opts_html .= '<li class="content-poll__option" data-index="' . (int) $i . '" role="button" tabindex="0" aria-label="' . $ariaEsc . '"><span class="content-poll__radio"></span><span class="content-poll__label">' . $labelEsc . '</span></li>';
		}
		$pollIdEsc = esc_attr( $poll_id );
		$nonceEsc  = esc_attr( $nonce );

		// Prepare i18n strings for data attributes (CSP-compliant, no inline scripts)
		$thankYou     = __( 'Thank you for voting!', 'content-poll' ); // Translatable user message.
		$networkError = __( 'Network error. Please try again.', 'content-poll' ); // Translatable user message.
		$thankYouEsc  = esc_attr( $thankYou ); // Escaped for data attribute.
		$networkEsc   = esc_attr( $networkError ); // Escaped for data attribute.

		// Add debug reset button if WP_DEBUG is enabled
		$debugButton = '';
		$debugAttr   = '';
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) { // Debug-only reset feature.
			$debugButton = '<button type="button" class="content-poll__reset-btn" style="margin-top: 10px; padding: 5px 10px; font-size: 12px; background: #f0f0f0; border: 1px solid #ccc; cursor: pointer;">Reset Vote (Debug)</button>';
			$debugAttr   = ' data-debug="true"';
		}

		return '<div class="content-poll" data-poll-id="' . $pollIdEsc . '" data-block-id="' . esc_attr( $block_id ) . '" data-post-id="' . $postIdEsc . '" data-nonce="' . $nonceEsc . '" data-i18n-thank-you="' . $thankYouEsc . '" data-i18n-network-error="' . $networkEsc . '"' . $debugAttr . '>' .
			'<p class="content-poll__question">' . $question . '</p>' .
			'<ul class="content-poll__options" role="list">' . $opts_html . '</ul>' .
			'<div class="content-poll__message" aria-live="polite"></div>' .
			$debugButton .
			'</div>';
	}
}
