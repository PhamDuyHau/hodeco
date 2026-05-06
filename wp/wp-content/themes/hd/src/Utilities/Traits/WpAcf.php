<?php
/**
 * ACF-related helper methods for links and fields.
 *
 * @author HD
 */

namespace HD\Utilities\Traits;

defined( 'ABSPATH' ) || exit;

trait WpAcf {
	// -------------------------------------------------------------

	/**
	 * @param mixed $link
	 * @param string|null $cssClass
	 * @param string|array|null $attrs String for title (backward compatible) or array of attributes
	 *                                  Example array: ['title' => '...', 'data-fx-scroll' => true, 'data-fx-offset' => '32']
	 * @param string|null $extraTitle Extra content to append after the link text
	 *
	 * @return string
	 */
	public static function acfLink( mixed $link, ?string $cssClass = '', string|array|null $attrs = '', ?string $extraTitle = '' ): string {
		// Build extra attributes string and get title for link text
		$attrData = self::buildLinkAttrsWithTitle( $attrs, $link );

		// string
		if ( $link && is_string( $link ) ) {
			return sprintf(
				'<a class="%2$s" href="%1$s"%3$s>%4$s</a>',
				esc_url( trim( $link ) ),
				self::escAttr( $cssClass ),
				$attrData['attrsStr'],
				$attrData['title'] . $extraTitle
			);
		}

		// array
		if ( $link && is_array( $link ) ) {
			$linkUrl    = $link['url'] ?? '';
			$linkTarget = $link['target'] ?? '';

			if ( $linkUrl ) {
				$targetAttr = $linkTarget ? ' target="_blank" rel="noopener noreferrer nofollow"' : '';

				return sprintf(
					'<a class="%2$s" href="%1$s"%3$s%4$s>%5$s</a>',
					esc_url( $linkUrl ),
					self::escAttr( $cssClass ),
					$attrData['attrsStr'],
					$targetAttr,
					$attrData['title'] . $extraTitle
				);
			}
		}

		return '';
	}

	// -------------------------------------------------------------

	/**
	 * @param mixed $link
	 * @param string|false $emptyLinkDefaultTag
	 *
	 * @return string
	 */
	public static function acfLinkClose( mixed $link, string|false $emptyLinkDefaultTag = 'span' ): string {
		if ( $link ) {
			return '</a>';
		}

		return $emptyLinkDefaultTag ? '</' . $emptyLinkDefaultTag . '>' : '';
	}

	// -------------------------------------------------------------

	/**
	 * @param mixed $link
	 * @param string|null $cssClass
	 * @param string|array|null $attrs String for title (backward compatible) or array of attributes
	 *                                  Example array: ['title' => '...', 'data-fx-scroll' => true, 'data-fx-offset' => '32']
	 * @param string|false $emptyLinkDefaultTag
	 *
	 * @return string
	 */
	public static function acfLinkOpen( mixed $link, ?string $cssClass = '', string|array|null $attrs = '', string|false $emptyLinkDefaultTag = 'span' ): string {
		// Build extra attributes string
		$extraAttrsStr = self::buildLinkAttrs( $attrs, $link );

		// string
		if ( $link && is_string( $link ) ) {
			return sprintf(
				'<a class="%2$s" href="%1$s"%3$s>',
				esc_url( trim( $link ) ),
				self::escAttr( $cssClass ),
				$extraAttrsStr
			);
		}

		// array
		if ( $link && is_array( $link ) ) {
			$linkUrl    = $link['url'] ?? '';
			$linkTarget = $link['target'] ?? '';

			if ( $linkUrl ) {
				$targetAttr = $linkTarget ? ' target="_blank" rel="noopener noreferrer nofollow"' : '';

				return sprintf(
					'<a class="%2$s" href="%1$s"%3$s%4$s>',
					esc_url( $linkUrl ),
					self::escAttr( $cssClass ),
					$extraAttrsStr,
					$targetAttr
				);
			}
		}

		return $emptyLinkDefaultTag
			? '<' . $emptyLinkDefaultTag . ' class="' . self::escAttr( $cssClass ) . '">'
			: '';
	}

	// -------------------------------------------------------------

	/**
	 * Build attributes string for link element.
	 *
	 * @param string|array|null $attrs
	 * @param mixed $link
	 *
	 * @return string
	 */
	private static function buildLinkAttrs( string|array|null $attrs, mixed $link ): string {
		return self::buildLinkAttrsWithTitle( $attrs, $link )['attrsStr'];
	}

	// -------------------------------------------------------------

	/**
	 * Build attributes string for link element and return title for link text.
	 * Used for acfLink which needs both attributes and text content.
	 *
	 * @param string|array|null $attrs
	 * @param mixed $link
	 *
	 * @return array{attrsStr: string, title: string}
	 */
	private static function buildLinkAttrsWithTitle( string|array|null $attrs, mixed $link ): array {
		$title    = '';
		$attrsStr = '';

		// String - backward compatible (used as title)
		if ( is_string( $attrs ) && $attrs !== '' ) {
			$title    = $attrs;
			$attrsStr = ' title="' . self::escAttr( $attrs ) . '"';

			return compact( 'attrsStr', 'title' );
		}

		// Array of attributes
		if ( is_array( $attrs ) && ! empty( $attrs ) ) {
			$attrParts = [];

			// Get title for link text
			$title = $attrs['title'] ?? ( is_array( $link ) ? ( $link['title'] ?? '' ) : '' );

			// Default title from link if not specified in attrs
			if ( ! isset( $attrs['title'] ) && is_array( $link ) && ! empty( $link['title'] ) ) {
				$attrs['title'] = $link['title'];
			}

			foreach ( $attrs as $key => $value ) {
				$key = self::escAttr( $key );

				// Boolean true -> attribute without value (data-fx-scroll)
				if ( $value === true ) {
					$attrParts[] = $key;
				} elseif ( $value !== false && $value !== null && $value !== '' ) {
					// Normal attribute with value
					$attrParts[] = $key . '="' . self::escAttr( (string) $value ) . '"';
				}
				// false, null, empty string -> skip attribute
			}

			$attrsStr = $attrParts ? ' ' . implode( ' ', $attrParts ) : '';

			return compact( 'attrsStr', 'title' );
		}

		// If no attrs, try to get default title from link array
		if ( is_array( $link ) && ! empty( $link['title'] ) ) {
			$title    = $link['title'];
			$attrsStr = ' title="' . self::escAttr( $link['title'] ) . '"';
		}

		return compact( 'attrsStr', 'title' );
	}

	// -------------------------------------------------------------

	/**
	 * @param string|null $content
	 * @param mixed $link
	 * @param string|null $cssClass
	 * @param string|null $label
	 * @param string|false $emptyLinkDefaultTag
	 *
	 * @return string
	 */
	public static function acfLinkWrap( ?string $content, mixed $link, ?string $cssClass = '', ?string $label = '', string|false $emptyLinkDefaultTag = 'span' ): string {
		// string
		if ( is_string( $link ) && $link ) {
			return sprintf(
				'<a class="%3$s" href="%1$s" title="%2$s">%4$s</a>',
				esc_url( trim( $link ) ),
				self::escAttr( $label ),
				self::escAttr( $cssClass ),
				$content
			);
		}

		// array (ACF link field returns associative array with 'url', 'title', 'target' keys)
		if ( is_array( $link ) && ! empty( $link['url'] ) ) {
			$linkUrl    = $link['url'];
			$linkTitle  = $label ?: ( $link['title'] ?? '' );
			$linkTarget = $link['target'] ?? '';
			$targetAttr = $linkTarget ? ' target="_blank" rel="noopener noreferrer nofollow"' : '';

			return sprintf(
				'<a class="%3$s" href="%1$s" title="%2$s"%4$s>%5$s</a>',
				esc_url( $linkUrl ),
				self::escAttr( $linkTitle ),
				self::escAttr( $cssClass ),
				$targetAttr,
				$content
			);
		}

		// empty link
		return $emptyLinkDefaultTag
			? '<' . $emptyLinkDefaultTag . ' class="' . self::escAttr( $cssClass ) . '">' . $content . '</' . $emptyLinkDefaultTag . '>'
			: $content;
	}

	// -------------------------------------------------------------

	/**
	 * @param mixed $postId
	 * @param bool $forceObject
	 * @param bool $formatValue
	 * @param bool $escapeHtml
	 *
	 * @return object|array
	 */
	public static function getFields( mixed $postId = false, bool $forceObject = false, bool $formatValue = true, bool $escapeHtml = false ): object|array {
		if ( ! function_exists( 'get_fields' ) || ! self::isAcfActive() ) {
			return [];
		}

		$fields = get_fields( $postId, $formatValue, $escapeHtml );

		// get_fields() returns false when no fields exist
		if ( ! $fields ) {
			return $forceObject ? (object) [] : [];
		}

		return $forceObject ? self::toObject( $fields ) : $fields;
	}

	// -------------------------------------------------------------

	/**
	 * @param string|null $selector
	 * @param mixed $postId
	 * @param bool $formatValue
	 * @param bool $escapeHtml
	 *
	 * @return mixed
	 */
	public static function getField( ?string $selector, mixed $postId = false, bool $formatValue = true, bool $escapeHtml = false ): mixed {
		if ( ! $selector || ! function_exists( 'get_field' ) || ! self::isAcfActive() ) {
			return false;
		}

		return get_field( $selector, $postId, $formatValue, $escapeHtml );
	}

	// -------------------------------------------------------------

	/**
	 * @param mixed $term
	 * @param string|null $acfFieldName
	 * @param string $size
	 * @param bool $imgWrap
	 * @param string|array $attr
	 *
	 * @return string|null
	 */
	public static function acfTermThumb( mixed $term, ?string $acfFieldName = null, string $size = 'thumbnail', bool $imgWrap = false, string|array $attr = '' ): ?string {
		if ( ! $term ) {
			return null;
		}

		if ( is_numeric( $term ) ) {
			$term = self::getTerm( $term );
		}

		$attachId = self::getField( $acfFieldName, $term );
		if ( ! $attachId ) {
			return null;
		}

		return $imgWrap
			? self::attachmentImageHTML( $attachId, $size, $attr )
			: self::attachmentImageSrc( $attachId, $size );
	}
}
