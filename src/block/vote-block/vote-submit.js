( function () {
	function ready( fn ) {
		if ( document.readyState !== 'loading' ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}
	ready( function () {
		const containers = document.querySelectorAll( '.content-poll' );
		containers.forEach( ( container ) => {
			const blockId = container.getAttribute( 'data-block-id' );
			const postId = parseInt( container.getAttribute( 'data-post-id' ), 10 ) || 0;
			const nonce = container.getAttribute( 'data-nonce' );
			const isDebug = container.getAttribute( 'data-debug' ) === 'true';
			const messageEl = container.querySelector(
				'.content-poll__message'
			);
			const i18n = {
				thankYou:
					container.getAttribute( 'data-i18n-thank-you' ) ||
					'Thank you for voting!',
				networkError:
					container.getAttribute( 'data-i18n-network-error' ) ||
					'Network error. Please try again.',
			};

			function getOptionText( index ) {
				const optionEl = container.querySelector(
					`.content-poll__option[data-index="${ index }"] .content-poll__label`
				);
				return optionEl
					? optionEl.textContent
					: `Option ${ index + 1 }`;
			}

			function displayResults( res ) {
				// Removed debug console output.
				if ( ! res || typeof res.totalVotes === 'undefined' ) {
					return;
				}
				if ( ! isDebug && res.totalVotes === 0 ) {
					return;
				}
				const optionElements = container.querySelectorAll(
					'.content-poll__option'
				);
				const optionCount = optionElements.length;
				let resultsHTML = '';
				const counts = res.counts || {};
				const percentages = res.percentages || {};
				for ( let i = 0; i < optionCount; i++ ) {
					const c = counts[ i ] || 0;
					const pct = percentages[ i ] || 0;
					const optionText = getOptionText( i );
					const label = String.fromCharCode( 65 + i );
					resultsHTML +=
						'<div class="content-poll__result-item">' +
						'<div class="content-poll__result-percentage">' +
						pct +
						'%</div>' +
						'<div class="content-poll__result-label">' +
						'<span><strong>' +
						label +
						'.</strong> ' +
						optionText +
						'</span>' +
						'<span class="content-poll__result-count">' +
						c +
						' vote' +
						( c !== 1 ? 's' : '' ) +
						'</span>' +
						'</div>' +
						'<div class="content-poll__result-bar">' +
						'<div class="content-poll__result-fill" style="width: ' +
						pct +
						'%"></div>' +
						'</div>' +
						'</div>';
				}
				let resultsContainer = container.querySelector(
					'.content-poll__results'
				);
				if ( ! resultsContainer ) {
					resultsContainer = document.createElement( 'div' );
					resultsContainer.className = 'content-poll__results';
					resultsContainer.setAttribute( 'role', 'status' );
					resultsContainer.setAttribute( 'aria-live', 'polite' );
					container.appendChild( resultsContainer );
				}
				resultsContainer.innerHTML = resultsHTML;
				// Removed debug console output.
			}

			fetch(
				`${ window.location.origin }/wp-json/content-poll/v1/block/${ blockId }/results`
			)
				.then( ( r ) => r.json() )
				.then( ( res ) => {
					if ( res && res.totalVotes && res.totalVotes > 0 ) {
						messageEl.textContent = i18n.thankYou;
						if ( typeof res.userVote !== 'undefined' ) {
							const selectedOption = container.querySelector(
								`.content-poll__option[data-index="${ res.userVote }"]`
							);
							if ( selectedOption ) {
								selectedOption.classList.add(
									'content-poll__option--selected'
								);
							}
						}
						container
							.querySelectorAll( '.content-poll__option' )
							.forEach( ( opt ) =>
								opt.classList.add(
									'content-poll__option--disabled'
								)
							);
						displayResults( res );
					}
				} )
				.catch( () => {} );

			container
				.querySelectorAll( '.content-poll__option' )
				.forEach( ( option ) => {
					option.addEventListener( 'click', () => {
						if (
							option.classList.contains(
								'content-poll__option--disabled'
							)
						) {
							return;
						}
						const optionIndex = parseInt(
							option.getAttribute( 'data-index' ),
							10
						);
						messageEl.textContent = '';
						fetch(
							`${ window.location.origin }/wp-json/content-poll/v1/block/${ blockId }/vote`,
							{
								method: 'POST',
								headers: {
									'Content-Type': 'application/json',
									'X-WP-Nonce': nonce,
								},
								body: JSON.stringify( {
									optionIndex,
									postId,
								} ),
							}
						)
							.then( ( r ) => r.json() )
							.then( ( data ) => {
								if ( data.error ) {
									messageEl.textContent =
										data.message || i18n.networkError;
									return;
								}
								messageEl.textContent = i18n.thankYou;
								option.classList.add(
									'content-poll__option--selected'
								);
								container
									.querySelectorAll( '.content-poll__option' )
									.forEach( ( opt ) =>
										opt.classList.add(
											'content-poll__option--disabled'
										)
									);
								fetch(
									`${ window.location.origin }/wp-json/content-poll/v1/block/${ blockId }/results`
								)
									.then( ( r ) => r.json() )
									.then( ( res ) => {
										// Removed debug console output.
										displayResults( res );
									} )
									.catch( () => {
										/* swallow fetch error silently */
									} );
							} )
							.catch( () => {
								messageEl.textContent = i18n.networkError;
							} );
					} );
					option.addEventListener( 'keydown', ( e ) => {
						if ( e.key === 'Enter' || e.key === ' ' ) {
							e.preventDefault();
							option.click();
						}
					} );
				} );

			const resetBtn = container.querySelector(
				'.content-poll__reset-btn'
			);
			if ( resetBtn ) {
				resetBtn.addEventListener( 'click', () => {
					fetch(
						`${ window.location.origin }/wp-json/content-poll/v1/block/${ blockId }/reset`,
						{
							method: 'POST',
							headers: { 'Content-Type': 'application/json' },
						}
					)
						.then( ( r ) => r.json() )
						.then( ( data ) => {
							if ( data.success ) {
								container
									.querySelectorAll( '.content-poll__option' )
									.forEach( ( opt ) => {
										opt.classList.remove(
											'content-poll__option--disabled'
										);
										opt.classList.remove(
											'content-poll__option--selected'
										);
									} );
								messageEl.textContent = '';
								const resultsContainer =
									container.querySelector(
										'.content-poll__results'
									);
								if ( resultsContainer ) {
									resultsContainer.remove();
								}
							}
						} )
						.catch( () => {
							messageEl.textContent =
								'Failed to reset. Please refresh the page.';
						} );
				} );
			}
		} );
	} );
} )();
