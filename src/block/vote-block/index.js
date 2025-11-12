import { registerBlockType } from '@wordpress/blocks';
import { useState, useEffect } from '@wordpress/element';
import { Button, PanelBody, RangeControl, Notice } from '@wordpress/components';
import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import './style.css';
// Re-export pure helpers from separate module so tests can import without pulling JSX.
import {
	clampOptionCount,
	expandOrTrimOptions,
	computePercentages,
} from './helpers.js';
export { clampOptionCount, expandOrTrimOptions, computePercentages };

registerBlockType( 'content-vote/vote-block', {
	edit: Edit,
	save: () => null,
} );

function Edit( props ) {
	const { attributes, setAttributes } = props;
	const { question, options, blockId } = attributes;
	const [ count, setCount ] = useState( options.length );
	const [ locked, setLocked ] = useState( false );
	const [ loadingSuggest, setLoadingSuggest ] = useState( false );
	const [ suggestError, setSuggestError ] = useState( '' );
	const blockProps = useBlockProps( {
		className: 'content-vote-editor',
	} );

	if ( ! blockId ) {
		const generateId = () => {
			if (
				window?.crypto &&
				typeof window.crypto.randomUUID === 'function'
			) {
				return window.crypto.randomUUID();
			}
			return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(
				/[xy]/g,
				( c ) => {
					// eslint-disable-next-line no-bitwise
					const r = ( Math.random() * 16 ) | 0;
					// eslint-disable-next-line no-bitwise
					const v = c === 'x' ? r : ( r & 0x3 ) | 0x8;
					return v.toString( 16 );
				}
			);
		};
		setAttributes( { blockId: generateId() } );
	}

	useEffect( () => {
		if ( ! blockId ) {
			return;
		}
		fetch(
			window.location.origin +
				'/wp-json/content-vote/v1/block/' +
				blockId +
				'/results'
		)
			.then( ( r ) => r.json() )
			.then( ( data ) => {
				if ( data && data.totalVotes && data.totalVotes > 0 ) {
					setLocked( true );
				}
			} )
			.catch( () => {} );
	}, [ blockId ] );

	const updateOption = ( value, index ) => {
		if ( locked ) {
			return;
		}
		const next = [ ...options ];
		next[ index ] = value;
		setAttributes( { options: next } );
	};

	const changeCount = ( newCount ) => {
		if ( locked ) {
			return;
		}
		const num = clampOptionCount( newCount );
		setCount( num );
		const next = expandOrTrimOptions(
			options,
			num,
			__( 'Option', 'content-vote' )
		);
		setAttributes( { options: next } );
	};

	const runSuggest = () => {
		if ( locked ) {
			return;
		}
		setLoadingSuggest( true );
		setSuggestError( '' );
		// Derive postId from editor data store (avoids inline localized script for CSP compliance)
		const postId =
			window?.wp?.data?.select( 'core/editor' )?.getCurrentPostId?.() ||
			window?.contentVotePostId ||
			0;
		// Nonce sourced from core REST API settings (wpApiSettings)
		const nonce = window?.wpApiSettings?.nonce || '';
		fetch(
			window.location.origin +
				'/wp-json/content-vote/v1/suggest?postId=' +
				postId,
			{
				headers: nonce ? { 'X-WP-Nonce': nonce } : {},
			}
		)
			.then( ( r ) => r.json() )
			.then( ( data ) => {
				if ( data.error ) {
					setSuggestError( data.message || 'Error' );
					return;
				}
				if ( data.question ) {
					setAttributes( { question: data.question } );
				}
				if ( Array.isArray( data.options ) ) {
					setAttributes( {
						options: expandOrTrimOptions(
							data.options,
							data.options.length,
							__( 'Option', 'content-vote' )
						),
					} );
					setCount( clampOptionCount( data.options.length ) );
				}
			} )
			.catch( () => {
				setSuggestError( 'Network error' );
			} )
			.finally( () => setLoadingSuggest( false ) );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Settings', 'content-vote' ) }
					initialOpen={ true }
				>
					<RangeControl
						label={ __( 'Number of options', 'content-vote' ) }
						value={ count }
						min={ 2 }
						max={ 6 }
						onChange={ ( v ) => changeCount( v ) }
						disabled={ locked }
					/>
					<Button
						variant="primary"
						onClick={ runSuggest }
						disabled={ locked || loadingSuggest }
						style={ { marginTop: '0.5rem' } }
					>
						{ loadingSuggest
							? __( 'Suggesting…', 'content-vote' )
							: __( 'Generate Suggestions', 'content-vote' ) }
					</Button>
					{ locked && (
						<Notice status="info" isDismissible={ false }>
							{ __(
								'Options locked after votes',
								'content-vote'
							) }
						</Notice>
					) }
					{ suggestError && (
						<Notice status="error" isDismissible={ false }>
							{ suggestError }
						</Notice>
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<RichText
					tagName="h4"
					value={ question }
					placeholder={ __( 'Enter question…', 'content-vote' ) }
					onChange={ ( v ) =>
						! locked && setAttributes( { question: v } )
					}
				/>
				<ul className="content-vote-options">
					{ options.slice( 0, count ).map( ( opt, i ) => (
						<li key={ i } className="content-vote-option-item">
							<RichText
								tagName="span"
								value={ opt }
								placeholder={
									__( 'Option', 'content-vote' ) +
									' ' +
									( i + 1 )
								}
								onChange={ ( v ) => updateOption( v, i ) }
								allowedFormats={ [] }
								className="content-vote-option"
							/>
						</li>
					) ) }
				</ul>
			</div>
		</>
	);
}
