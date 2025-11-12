<?php

declare(strict_types=1);

namespace ContentVote\Blocks;

use ContentVote\Security\SecurityHelper;

class VoteBlock {
	public function register(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		add_action( 'init', function () {
			register_block_type( __DIR__ . '/../../../build/block/vote-block', [
				'render_callback' => [ $this, 'render' ],
			] );
		} );
	}

	public function render( array $attributes, string $content ): string {
		$block_id     = $attributes[ 'blockId' ] ?? uniqid( 'vote_', true );
		$question_raw = $attributes[ 'question' ] ?? 'Your opinion?';
		$question     = function_exists( 'esc_html' ) ? esc_html( $question_raw ) : htmlspecialchars( $question_raw, ENT_QUOTES, 'UTF-8' );
		$options      = $attributes[ 'options' ] ?? [ 'A', 'B', 'C', 'D' ];
		$nonce        = SecurityHelper::create_nonce();
		$opts_html    = '';
		foreach ( $options as $i => $label ) {
			$labelEsc   = function_exists( 'esc_html' ) ? esc_html( $label ) : htmlspecialchars( $label, ENT_QUOTES, 'UTF-8' );
			$aria       = 'Vote for option ' . ( $i + 1 );
			$ariaEsc    = function_exists( 'esc_attr' ) ? esc_attr( $aria ) : htmlspecialchars( $aria, ENT_QUOTES, 'UTF-8' );
			$opts_html .= '<li class="content-vote__option" data-index="' . (int) $i . '" role="button" tabindex="0" aria-label="' . $ariaEsc . '"><span class="content-vote__radio"></span><span class="content-vote__label">' . $labelEsc . '</span></li>';
		}
		$blockIdEsc = function_exists( 'esc_attr' ) ? esc_attr( $block_id ) : htmlspecialchars( $block_id, ENT_QUOTES, 'UTF-8' );
		$nonceEsc   = function_exists( 'esc_attr' ) ? esc_attr( $nonce ) : htmlspecialchars( $nonce, ENT_QUOTES, 'UTF-8' );

		// Prepare i18n strings for data attributes (CSP-compliant, no inline scripts)
		$thankYou     = function_exists( '__' ) ? __( 'Thank you for voting!', 'content-vote' ) : 'Thank you for voting!';
		$networkError = function_exists( '__' ) ? __( 'Network error. Please try again.', 'content-vote' ) : 'Network error. Please try again.';
		$thankYouEsc  = function_exists( 'esc_attr' ) ? esc_attr( $thankYou ) : htmlspecialchars( $thankYou, ENT_QUOTES, 'UTF-8' );
		$networkEsc   = function_exists( 'esc_attr' ) ? esc_attr( $networkError ) : htmlspecialchars( $networkError, ENT_QUOTES, 'UTF-8' );

		// Add debug reset button if WP_DEBUG is enabled
		$debugButton = '';
		$debugAttr   = '';
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$debugButton = '<button type="button" class="content-vote__reset-btn" style="margin-top: 10px; padding: 5px 10px; font-size: 12px; background: #f0f0f0; border: 1px solid #ccc; cursor: pointer;">Reset Vote (Debug)</button>';
			$debugAttr   = ' data-debug="true"';
		}

		return '<div class="content-vote" data-block-id="' . $blockIdEsc . '" data-nonce="' . $nonceEsc . '" data-i18n-thank-you="' . $thankYouEsc . '" data-i18n-network-error="' . $networkEsc . '"' . $debugAttr . '>' .
			'<p class="content-vote__question">' . $question . '</p>' .
			'<ul class="content-vote__options" role="list">' . $opts_html . '</ul>' .
			'<div class="content-vote__message" aria-live="polite"></div>' .
			$debugButton .
			'</div>';
	}
}
