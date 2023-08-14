<?php

namespace MediaWiki\Extension\WikibaseInWikitext;

use Parser;
use PPFrame;
use SpecialPage;

class Hooks {

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
	 * @param \Parser $parser
	 */
	public static function onParserFirstCallInit( \Parser $parser ) {
		$parser->setHook( 'sparql', [ self::class, 'renderTagSparql' ] );
	}

	/**
	 * @param mixed $input
	 * @param array $args
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @return string
	 */
	public static function renderTagSparql( $input, array $args, Parser $parser, PPFrame $frame ) {
		global $wgWikibaseInWikitextSparqlDefaultUi;

		if ( array_key_exists( 'ui', $args ) ) {
			$sparqlUi = $args['ui'];
		} else {
			// TODO get from Wikibase config if exists?
			$sparqlUi = $wgWikibaseInWikitextSparqlDefaultUi;
		}
		$shouldList = array_key_exists( 'list', $args );

		$output = '';

		if ( $shouldList ) {
			$referencesEntities = [];
			foreach ( explode( PHP_EOL, $input ) as $line ) {
				if ( strlen( $line ) === 0 || $line[0] === '#' ) {
					continue;
				}
				preg_match_all( '/([QP]\d+)/i', $line, $matches );
				$referencesEntities = array_merge( $referencesEntities, $matches[1] );
			}
			$referencesEntities = array_unique( $referencesEntities );
			sort( $referencesEntities );

			if ( $referencesEntities ) {
				$output .= sprintf(
					'<p>%s</p>',
					wfMessage('query_uses')->parse()
				);
				$output .= '<ul>';
				foreach ( $referencesEntities as $id ) {
					// TODO what if the entity is not on this local wiki?

					$output .= sprintf(
						'<li>
							<a href="%s">%s</a>
						</li>',
						SpecialPage::getTitleFor( 'EntityPage', $id )->getLinkURL(),
						$id
					);
				}
				$output .= '</ul>';
				$output .= PHP_EOL;
			}
		}

		if ( \ExtensionRegistry::getInstance()->isLoaded( 'SyntaxHighlight' ) ) {
			$output .= $parser->recursiveTagParse(
				sprintf(
					'<syntaxhighlight lang="sparql" >%s</syntaxhighlight>',
					$input
				)
			);
		} else {
			$output .= sprintf( '<pre>%s</pre>', $input );
		}
		$output .= PHP_EOL;

		if ( array_key_exists( 'tryit', $args ) ) {
			$output .= sprintf(
				'<a href="%s#%s" target="_blank">%s</a>',
				$sparqlUi,
				htmlentities( rawurlencode( trim( $input ) ) ),
				wfMessage('try_it')->parse()
			);
			$output .= PHP_EOL;
		}

		return $output;
	}

}
