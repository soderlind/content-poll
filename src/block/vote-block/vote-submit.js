( function () {
	// Version: 1.1 - Fixed percentage display position
	function ready( fn ) {
		if ( document.readyState !== 'loading' ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}
	ready( function () {
		const containers = document.querySelectorAll( '.content-vote' );
		containers.forEach( ( container ) => {
			const blockId = container.getAttribute( 'data-block-id' );
			const nonce = container.getAttribute( 'data-nonce' );
			const isDebug = container.getAttribute( 'data-debug' ) === 'true';
			const messageEl = container.querySelector(
				'.content-vote__message'
			);
			// Read i18n strings from data attributes (CSP-compliant, no inline scripts)
			const i18n = {
				thankYou:
					container.getAttribute( 'data-i18n-thank-you' ) ||
					'Thank you for voting!',
				networkError:
					container.getAttribute( 'data-i18n-network-error' ) ||
					'Network error. Please try again.',
			};

			// Helper function to get option text by index
			function getOptionText( index ) {
				const optionEl = container.querySelector(
					`.content-vote__option[data-index="${ index }"] .content-vote__label`
				);
				return optionEl
					? optionEl.textContent
					: `Option ${ index + 1 }`;
			}

			// Helper function to display results
			function displayResults( res ) {
				if ( isDebug ) {
					// eslint-disable-next-line no-console
					console.log( 'displayResults called with:', res );
				}
				if ( ! res || typeof res.totalVotes === 'undefined' ) {
					if ( isDebug ) {
						// eslint-disable-next-line no-console
						console.log( 'No results or totalVotes undefined' );
					}
					return;
				}
				// In debug mode, always show results even if no votes
				if ( ! isDebug && res.totalVotes === 0 ) {
					if ( isDebug ) {
						// eslint-disable-next-line no-console
						console.log( 'Not debug mode and no votes' );
					}
					return;
				}

				// Get the number of actual options
				const optionElements =
					container.querySelectorAll( '.content-vote__option' );
				const optionCount = optionElements.length;

				// Create results HTML with divs instead of list
				let resultsHTML = '';

				// counts and percentages are objects (associative arrays from PHP)
				const counts = res.counts || {};
				const percentages = res.percentages || {};

				// Iterate only through actual options
				for ( let i = 0; i < optionCount; i++ ) {
					const c = counts[ i ] || 0;
					const pct = percentages[ i ] || 0;
					const optionText = getOptionText( i );
					const label = String.fromCharCode( 65 + i ); // A, B, C, D...

					resultsHTML +=
						'<div class="content-vote__result-item">' +
						'<div class="content-vote__result-percentage">' +
						pct +
						'%</div>' +
						'<div class="content-vote__result-label">' +
						'<span><strong>' +
						label +
						'.</strong> ' +
						optionText +
						'</span>' +
						'<span class="content-vote__result-count">' +
						c +
						' vote' +
						( c !== 1 ? 's' : '' ) +
						'</span>' +
						'</div>' +
						'<div class="content-vote__result-bar">' +
						'<div class="content-vote__result-fill" style="width: ' +
						pct +
						'%"></div>' +
						'</div>' +
						'</div>';
				}

				if ( isDebug ) {
					// eslint-disable-next-line no-console
					console.log( 'resultsHTML:', resultsHTML );
				}
				let resultsContainer = container.querySelector(
					'.content-vote__results'
				);
				if ( ! resultsContainer ) {
					resultsContainer = document.createElement( 'div' );
					resultsContainer.className = 'content-vote__results';
					resultsContainer.setAttribute( 'role', 'status' );
					resultsContainer.setAttribute( 'aria-live', 'polite' );
					container.appendChild( resultsContainer );
				}
				resultsContainer.innerHTML = resultsHTML;
				if ( isDebug ) {
					// eslint-disable-next-line no-console
					console.log( 'Results displayed' );
				}
			}

			// Check for existing votes on page load
			fetch(
				window.location.origin +
					'/wp-json/content-vote/v1/block/' +
					blockId +
					'/results'
			)
				.then( ( r ) => r.json() )
				.then( ( res ) => {
					if ( res && res.totalVotes && res.totalVotes > 0 ) {
						// User has already voted, mark selected option and show results
						messageEl.textContent = i18n.thankYou;
						
						// Mark the user's selected option if available
						if ( typeof res.userVote !== 'undefined' ) {
							const selectedOption = container.querySelector(
								`.content-vote__option[data-index="${ res.userVote }"]`
							);
							if ( selectedOption ) {
								selectedOption.classList.add(
									'content-vote__option--selected'
								);
							}
						}
						
						container
							.querySelectorAll( '.content-vote__option' )
							.forEach( ( opt ) =>
								opt.classList.add(
									'content-vote__option--disabled'
								)
							);
						displayResults( res );
					}
				} )
				.catch( () => {
					/* Silently fail - user hasn't voted yet */
				} );

			container
				.querySelectorAll( '.content-vote__option' )
				.forEach( ( option ) => {
					// Handle click
					option.addEventListener( 'click', () => {
						if (
							option.classList.contains(
								'content-vote__option--disabled'
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
							window.location.origin +
								'/wp-json/content-vote/v1/block/' +
								blockId +
								'/vote',
							{
								method: 'POST',
								headers: {
									'Content-Type': 'application/json',
									'X-WP-Nonce': nonce,
								},
								body: JSON.stringify( {
									optionIndex,
									postId: window.contentVotePostId || 0,
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
									'content-vote__option--selected'
								);
								container
									.querySelectorAll( '.content-vote__option' )
									.forEach( ( opt ) =>
										opt.classList.add(
											'content-vote__option--disabled'
										)
									);
								// Fetch and display results
								fetch(
									window.location.origin +
										'/wp-json/content-vote/v1/block/' +
										blockId +
										'/results'
								)
									.then( ( r ) => r.json() )
									.then( ( res ) => {
										if ( isDebug ) {
											// eslint-disable-next-line no-console
											console.log(
												'Results fetched:',
												res
											);
										}
										displayResults( res );
									} )
									.catch( ( err ) => {
										if ( isDebug ) {
											// eslint-disable-next-line no-console
											console.error(
												'Failed to fetch results:',
												err
											);
										}
									} );
							} )
							.catch( () => {
								messageEl.textContent = i18n.networkError;
							} );
					} );

					// Handle keyboard (Enter/Space)
					option.addEventListener( 'keydown', ( e ) => {
						if ( e.key === 'Enter' || e.key === ' ' ) {
							e.preventDefault();
							option.click();
						}
					} );
				} );

			// Debug reset button handler
			const resetBtn = container.querySelector(
				'.content-vote__reset-btn'
			);
			if ( resetBtn ) {
				resetBtn.addEventListener( 'click', () => {
					fetch(
						window.location.origin +
							'/wp-json/content-vote/v1/block/' +
							blockId +
							'/reset',
						{
							method: 'POST',
							headers: {
								'Content-Type': 'application/json',
							},
						}
					)
						.then( ( r ) => r.json() )
						.then( ( data ) => {
							if ( data.success ) {
								// Re-enable options
								container
									.querySelectorAll( '.content-vote__option' )
									.forEach( ( opt ) => {
										opt.classList.remove(
											'content-vote__option--disabled'
										);
										opt.classList.remove(
											'content-vote__option--selected'
										);
									} );
								// Clear message and results
								messageEl.textContent = '';
								const resultsContainer =
									container.querySelector(
										'.content-vote__results'
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
