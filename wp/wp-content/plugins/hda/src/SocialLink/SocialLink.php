<?php

namespace HDAddons\SocialLink;

use HDAddons\Contracts\SettingsAware;
use HDAddons\Contracts\SettingsHelpers;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class SocialLink implements SettingsAware {

	use SettingsHelpers;

	// ─── Option Keys (single source of truth) ───────────

	public const string OPTION_NAME = 'social_link__options';

	/**
	 * Cached social options for performance.
	 *
	 * @var array|null
	 */
	private static ?array $socialOptions = null;

	/**
	 * Cached social follows links configuration.
	 *
	 * @var array|null
	 */
	private static ?array $socialFollowsLinks = null;

	// ------------------------------------------------------

	public function __construct() {
		add_shortcode( 'social_menu', $this->socialMenu( ... ) );
	}

	// ------------------------------------------------------

	/**
	 * Get cached social options.
	 *
	 * @return array
	 */
	public static function getOptions(): array {
		if ( null === self::$socialOptions ) {
			self::$socialOptions = Helper::getOption( self::OPTION_NAME, [] );
		}

		return self::$socialOptions;
	}

	/**
	 * Get cached social follows links configuration.
	 *
	 * @return array
	 */
	public static function getFollowsLinks(): array {
		if ( null === self::$socialFollowsLinks ) {
			self::$socialFollowsLinks = Helper::filterSettingOptions( 'social_follows_links' );
		}

		return self::$socialFollowsLinks;
	}

	/**
	 * Render social menu shortcode.
	 *
	 * Usage: [social_menu] or [social_menu class="custom-class"]
	 *
	 * @param array|string $atts Shortcode attributes.
	 *
	 * @return string Rendered HTML.
	 */
	private function socialMenu( array|string $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'class' => 'social-menu',
			],
			$atts,
			'social_menu'
		);

		$class          = Helper::escAttr( $atts['class'] );
		$socialOptions  = self::getOptions();
		$socialLinks    = self::getFollowsLinks();
		$items          = [];

		if ( empty( $socialLinks ) ) {
			return '';
		}

		foreach ( $socialLinks as $key => $linkData ) {
			// DB saved URL → theme default URL
			$url = $socialOptions[ $key ]['url'] ?? ( $linkData['url'] ?? '' );

			if ( empty( $url ) ) {
				continue;
			}

			$name  = $linkData['name'] ?? '';
			$icon  = $linkData['icon'] ?? '';
			$thumb = Helper::renderIcon( $icon, $name );

			if ( empty( $thumb ) ) {
				continue;
			}

			$items[] = sprintf(
				'<li><a class="%s" href="%s" title="%s" target="_blank" rel="noopener noreferrer">%s<span class="sr-only">%s</span></a></li>',
				esc_attr( $key ),
				esc_url( $url ),
				esc_attr( $name ),
				$thumb,
				esc_html( $name )
			);
		}

		if ( empty( $items ) ) {
			return '';
		}

		return sprintf(
			'<ul class="menu %s">%s</ul>',
			$class,
			implode( '', $items )
		);
	}

	// ─── SettingsAware ─────────────────────────────────

	/** @inheritDoc */
	public static function getFormKey(): string {
		return 'social-link-hidden';
	}

	/** @inheritDoc */
	public static function sanitizeAndSave( array $data ): void {
		$options = [];

		foreach ( Helper::filterSettingOptions( 'social_follows_links', [] ) as $i => $item ) {
			$url = ! empty( $data[ $i . '-url' ] ) ? sanitize_url( $data[ $i . '-url' ] ) : '';
			if ( $url ) {
				$options[ $i ] = [ 'url' => $url ];
			}
		}

		self::saveOrRemove( self::OPTION_NAME, $options );
	}
}
