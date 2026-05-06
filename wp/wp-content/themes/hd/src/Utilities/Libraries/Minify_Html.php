<?php
/**
 * Compress HTML
 *
 * This is a heavy regex-based removal of whitespace, unnecessary comments and
 * tokens. IE conditional comments are preserved. There are also options to have
 * STYLE and SCRIPT blocks compressed by callback functions.
 *
 * A test suite is available.
 *
 * @author Stephen Clay <steve@mrclay.org>
 * Modified by HD for PHP 8.3
 */

namespace HD\Utilities\Libraries;

defined( 'ABSPATH' ) || die;

class Minify_Html {
	protected bool $jsCleanComments = true;
	protected string $html;
	protected ?bool $isXhtml           = null;
	protected ?string $replacementHash = null;
	protected array $placeholders      = [];
	protected mixed $cssMinifier       = null;
	protected mixed $jsMinifier        = null;

	/**
	 * "Minify" an HTML page
	 *
	 * @param string|null $html
	 * @param array $options
	 *
	 * @return string
	 */
	public static function minify( ?string $html, array $options = [] ): string {
		return ( new self( $html, $options ) )->process();
	}

	/**
	 * Create a minifier object
	 *
	 * @param string|null $html
	 * @param array $options
	 */
	public function __construct( ?string $html, array $options = [] ) {
		$this->html = str_replace( "\r\n", "\n", trim( (string) $html ) );

		$this->isXhtml         = $options['xhtml'] ?? null;
		$this->cssMinifier     = $options['cssMinifier'] ?? null;
		$this->jsMinifier      = $options['jsMinifier'] ?? null;
		$this->jsCleanComments = (bool) ( $options['jsCleanComments'] ?? true );
	}

	/**
	 * Minify the markup given in the constructor
	 *
	 * @return string
	 */
	public function process(): string {
		$this->isXhtml ??= str_contains( $this->html, '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML' );

		$this->replacementHash = 'MINIFYHTML' . md5( (string) ( $_SERVER['REQUEST_TIME'] ?? time() ) );
		$this->placeholders    = [];

		// replace SCRIPTs (and minify) with placeholders
		$this->html = preg_replace_callback(
			'/(\s*)<script(\b[^>]*?>)([\s\S]*?)<\/script>(\s*)/iu',
			$this->removeScriptCB( ... ),
			$this->html
		);

		// replace STYLEs (and minify) with placeholders
		$this->html = preg_replace_callback(
			'/\s*<style(\b[^>]*>)([\s\S]*?)<\/style>\s*/iu',
			$this->removeStyleCB( ... ),
			$this->html
		);

		// remove HTML comments (not containing IE conditional comments).
		$this->html = preg_replace_callback(
			'/<!--([\s\S]*?)-->/u',
			$this->commentCB( ... ),
			$this->html
		);

		// replace PREs with placeholders
		$this->html = preg_replace_callback(
			'/\s*<pre(\b[^>]*?>[\s\S]*?<\/pre>)\s*/iu',
			$this->removePreCB( ... ),
			$this->html
		);

		// replace TEXTAREAs with placeholders
		$this->html = preg_replace_callback(
			'/\s*<textarea(\b[^>]*?>[\s\S]*?<\/textarea>)\s*/iu',
			$this->removeTextareaCB( ... ),
			$this->html
		);

		// trim each line.
		$this->html = preg_replace( '/^\s+|\s+$/mu', '', $this->html );

		// remove ws around block/undisplayed elements
		$this->html = preg_replace(
			'/\s+(<\/?(?:area|article|aside|base(?:font)?|blockquote|body'
			. '|canvas|caption|center|col(?:group)?|dd|dir|div|dl|dt|fieldset|figcaption|figure|footer|form'
			. '|frame(?:set)?|h[1-6]|head|header|hgroup|hr|html|legend|li|link|main|map|menu|meta|nav'
			. '|ol|opt(?:group|ion)|output|p|param|section|t(?:able|body|head|d|h|r|foot|itle)'
			. '|ul|video)\b[^>]*>)/iu',
			'$1',
			$this->html
		);

		// remove ws outside of all elements
		$this->html = preg_replace(
			'/>(\s(?:\s*))?([^<]+)(\s(?:\s*))?</u',
			'>$1$2$3<',
			$this->html
		);

		// use newlines before 1st attribute in open tags (to limit line lengths)
		$this->html = preg_replace( '/(<[a-z\-]+)\s+([^>]+>)/iu', "$1\n$2", $this->html );

		// fill placeholders (multi-pass to catch scripts in textarea)
		$this->html = str_replace( array_keys( $this->placeholders ), $this->placeholders, $this->html );
		$this->html = str_replace( array_keys( $this->placeholders ), $this->placeholders, $this->html );

		return $this->html;
	}

	protected function commentCB( array $m ): string {
		return ( str_starts_with( $m[1], '[' ) || str_contains( $m[1], '<![' ) || str_starts_with( $m[1], '#' ) )
			? $m[0]
			: '';
	}

	protected function reservePlace( string $content ): string {
		$placeholder                        = '%' . $this->replacementHash . count( $this->placeholders ) . '%';
		$this->placeholders[ $placeholder ] = $content;

		return $placeholder;
	}

	protected function removePreCB( array $m ): string {
		return $this->reservePlace( "<pre{$m[1]}" );
	}

	protected function removeTextareaCB( array $m ): string {
		return $this->reservePlace( "<textarea{$m[1]}" );
	}

	protected function removeStyleCB( array $m ): string {
		$openStyle = "<style{$m[1]}";
		$css       = $m[2];

		// remove HTML comments
		$css = preg_replace( '/(?:^\s*<!--|-->\s*$)/u', '', $css );

		// remove CDATA section markers
		$css = $this->removeCdata( $css );

		// minify
		$minifier = $this->cssMinifier ?? 'trim';
		$css      = $minifier( $css );

		return $this->reservePlace(
			$this->needsCdata( $css )
				? "{$openStyle}/*<![CDATA[*/{$css}/*]]>*/</style>"
				: "{$openStyle}{$css}</style>"
		);
	}

	protected function removeScriptCB( array $m ): string {
		$openScript = "<script{$m[2]}";
		$js         = $m[3];

		// whitespace surrounding? preserve at least one space
		$ws1 = $m[1] === '' ? '' : ' ';
		$ws2 = $m[4] === '' ? '' : ' ';

		// remove HTML comments (and ending "//" if present)
		if ( $this->jsCleanComments ) {
			$js = preg_replace( '/(?:^\s*<!--\s*|\s*(?:\/\/)?\s*-->\s*$)/u', '', $js );
		}

		// remove CDATA section markers
		$js = $this->removeCdata( $js );

		// minify
		$minifier = $this->jsMinifier ?? 'trim';
		$js       = $minifier( $js );

		return $this->reservePlace(
			$this->needsCdata( $js )
				? "{$ws1}{$openScript}/*<![CDATA[*/{$js}/*]]>*/</script>{$ws2}"
				: "{$ws1}{$openScript}{$js}</script>{$ws2}"
		);
	}

	protected function removeCdata( string $str ): string {
		return str_contains( $str, '<![CDATA[' )
			? str_replace( [ '<![CDATA[', ']]>' ], '', $str )
			: $str;
	}

	protected function needsCdata( string $str ): bool {
		return $this->isXhtml && preg_match( '/(?:[<&]|\-\-|\]\]>)/u', $str );
	}
}
