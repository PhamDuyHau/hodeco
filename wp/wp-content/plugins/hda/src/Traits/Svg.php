<?php
/**
 * SVG utility trait.
 *
 * @author HD
 */

namespace HDAddons\Traits;

\defined( 'ABSPATH' ) || exit;

trait Svg {
	// --------------------------------------------------

	/**
	 * Sanitize SVG content.
	 *
	 * @param string|null $svg
	 *
	 * @return string
	 */
	public static function ksesSvg( ?string $svg ): string {
		if ( ! $svg ) {
			return '';
		}

		return wp_kses( $svg, self::svgAllowedTags() );
	}

	// --------------------------------------------------

	/**
	 * Get allowed SVG tags and attributes.
	 *
	 * @return array
	 */
	public static function svgAllowedTags(): array {
		$commonAttrs = [
			'class'        => true,
			'id'           => true,
			'style'        => true,
			'fill'         => true,
			'stroke'       => true,
			'stroke-width' => true,
			'opacity'      => true,
			'transform'    => true,
		];

		return [
			'svg'      => [
				...$commonAttrs,
				'xmlns'           => true,
				'width'           => true,
				'height'          => true,
				'viewbox'         => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'aria-hidden'     => true,
				'role'            => true,
				'focusable'       => true,
			],
			'g'        => [
				...$commonAttrs,
				'clip-path' => true,
			],
			'path'     => [
				...$commonAttrs,
				'd'               => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'fill-rule'       => true,
				'clip-rule'       => true,
			],
			'circle'   => [
				...$commonAttrs,
				'cx' => true,
				'cy' => true,
				'r'  => true,
			],
			'ellipse'  => [
				...$commonAttrs,
				'cx' => true,
				'cy' => true,
				'rx' => true,
				'ry' => true,
			],
			'rect'     => [
				...$commonAttrs,
				'x'      => true,
				'y'      => true,
				'width'  => true,
				'height' => true,
				'rx'     => true,
				'ry'     => true,
			],
			'line'     => [
				...$commonAttrs,
				'x1' => true,
				'y1' => true,
				'x2' => true,
				'y2' => true,
			],
			'polyline' => [
				...$commonAttrs,
				'points' => true,
			],
			'polygon'  => [
				...$commonAttrs,
				'points' => true,
			],
			'defs'     => [],
			'clipPath' => [ 'id' => true ],
			'use'      => [
				'href'       => true,
				'xlink:href' => true,
				'x'          => true,
				'y'          => true,
				'width'      => true,
				'height'     => true,
				'class'      => true,
				'id'         => true,
				'style'      => true,
			],
			'symbol'   => [
				'id'      => true,
				'viewbox' => true,
				'class'   => true,
			],
			'title'    => [],
			'desc'     => [],
			'i'        => [
				'class' => true,
				'id'    => true,
				'style' => true,
			],
		];
	}
}
