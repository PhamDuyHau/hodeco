<?php
/**
 * Contact Link — floating contact buttons (Hotline, Zalo, Messenger…).
 *
 * Renders a `[contact_link]` shortcode in wp_footer with configurable
 * items (icon, link, target, color). Supports theme defaults via
 * `filterSettingOptions('contact_links')` with admin override.
 *
 * @package HDAddons\ContactLink
 * @author  HD
 */

namespace HDAddons\ContactLink;

use HDAddons\Contracts\SettingsAware;
use HDAddons\Contracts\SettingsHelpers;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class ContactLink implements SettingsAware {

	use SettingsHelpers;

	// ─── Option Keys (single source of truth) ───────────

	public const string OPTION_NAME = 'contact_link__items';

	/** HTML form field name used by the handler */
	public const string KEY_FORM_ITEMS = 'contact_items';

	/**
	 * Cached contact items for performance.
	 *
	 * @var array|null
	 */
	private static ?array $contactItems = null;

	// ------------------------------------------------------

	public function __construct() {
		add_shortcode( 'contact_link', $this->contactLink( ... ) );
		add_action( 'wp_footer', $this->addThisContactLink( ... ), 30 );
		add_filter( 'hd_footer_class_filter', $this->modifyFooterClass( ... ) );

		// Admin: Enqueue media uploader scripts.
		add_action( 'admin_enqueue_scripts', $this->enqueueAdminScripts( ... ) );
	}

	// ------------------------------------------------------

	/**
	 * Enqueue admin scripts for media uploader and localize i18n strings.
	 *
	 * @param string $hook The current admin page.
	 *
	 * @return void
	 */
	private function enqueueAdminScripts( string $hook ): void {
		// Only load on HDA settings page.
		if ( 'toplevel_page_hda-settings' !== $hook ) {
			return;
		}

		wp_enqueue_media();

		// Get the admin script handle from the Asset class.
		$adminHandle = \HDAddons\Asset::handle( 'admin.js' );

		if ( $adminHandle ) {
			wp_localize_script(
				$adminHandle,
				'hdaContactLinkI18n',
				[
					'newContact'       => __( 'New Contact', HDA_TEXTDOMAIN ),
					'remove'           => __( 'Remove', HDA_TEXTDOMAIN ),
					'selectIcon'       => __( 'Select Icon', HDA_TEXTDOMAIN ),
					'useThisIcon'      => __( 'Use this icon', HDA_TEXTDOMAIN ),
					'atLeastOne'       => __( 'You must have at least one contact link.', HDA_TEXTDOMAIN ),
					'icon'             => __( 'Icon', HDA_TEXTDOMAIN ),
					'iconDesc'         => __( 'Select an image or SVG from the media library.', HDA_TEXTDOMAIN ),
					'name'             => __( 'Name', HDA_TEXTDOMAIN ),
					'namePlaceholder'  => __( 'e.g., Hotline, Zalo, Facebook', HDA_TEXTDOMAIN ),
					'linkValue'        => __( 'Link/Value', HDA_TEXTDOMAIN ),
					'valuePlaceholder' => __( 'e.g., tel:+84123456789, https://zalo.me/...', HDA_TEXTDOMAIN ),
					'target'           => __( 'Target', HDA_TEXTDOMAIN ),
					'targetBlank'      => __( 'New Tab (_blank)', HDA_TEXTDOMAIN ),
					'targetSelf'       => __( 'Same Tab (_self)', HDA_TEXTDOMAIN ),
					'cssClass'         => __( 'CSS Class', HDA_TEXTDOMAIN ),
					'classPlaceholder' => __( 'e.g., hotline', HDA_TEXTDOMAIN ),
					'color'            => __( 'Color', HDA_TEXTDOMAIN ),
				]
			);
		}
	}

	// ------------------------------------------------------

	/**
	 * Get cached contact items.
	 *
	 * @return array
	 */
	public static function getItems(): array {
		if ( null === self::$contactItems ) {
			// Use null sentinel to distinguish "never configured" vs "intentionally empty".
			$raw = get_option( self::OPTION_NAME, null );

			if ( null === $raw ) {
				// Option doesn't exist → first time, load theme defaults.
				$items = self::buildItemsFromTheme();
			} else {
				// Option exists (even if empty []) → user has configured.
				$items = is_array( $raw ) ? $raw : [];
			}

			// Sort by order field.
			if ( ! empty( $items ) ) {
				usort( $items, static fn( $a, $b ) => ( $a['order'] ?? 0 ) <=> ( $b['order'] ?? 0 ) );
			}

			self::$contactItems = $items;
		}

		return self::$contactItems;
	}

	/**
	 * Build contact items from theme's contact_links configuration.
	 *
	 * @return array
	 */
	private static function buildItemsFromTheme(): array {
		$themeLinks = Helper::filterSettingOptions( 'contact_links' );

		if ( empty( $themeLinks ) ) {
			return [];
		}

		$items = [];
		$order = 0;

		foreach ( $themeLinks as $key => $link ) {
			$items[] = [
				'id'     => $key,
				'name'   => $link['name'] ?? '',
				'icon'   => $link['icon'] ?? '',
				'value'  => $link['value'] ?? '',
				'target' => $link['target'] ?? '_blank',
				'class'  => $link['class'] ?? '',
				'color'  => '',
				'order'  => $order ++,
			];
		}

		return $items;
	}

	/**
	 * Clear cached items (call after saving).
	 *
	 * @return void
	 */
	public static function clearCache(): void {
		self::$contactItems = null;
	}

	/**
	 * Get active contact items (items with value).
	 *
	 * @return array
	 */
	public static function getActiveItems(): array {
		return array_filter(
			self::getItems(),
			static fn( $item ) => ! empty( $item['value'] )
		);
	}

	/**
	 * Check if any contact link is active.
	 *
	 * @return bool
	 */
	public static function hasActiveLinks(): bool {
		return ! empty( self::getActiveItems() );
	}


	/**
	 * Get default empty item structure.
	 *
	 * @return array
	 */
	public static function getDefaultItem(): array {
		return [
			'id'     => '',
			'name'   => '',
			'icon'   => '',
			'value'  => '',
			'target' => '_blank',
			'class'  => '',
			'color'  => '',
			'order'  => 0,
		];
	}

	// ------------------------------------------------------

	/**
	 * Modify footer class when contact links are present.
	 *
	 * @param mixed $default_class Default footer class.
	 *
	 * @return mixed Modified class string.
	 */
	private function modifyFooterClass( mixed $default_class ): mixed {
		if ( self::hasActiveLinks() ) {
			return $default_class . ' has-contact-link';
		}

		return $default_class;
	}

	// ------------------------------------------------------

	/**
	 * Output contact link shortcode in footer.
	 *
	 * @return void
	 */
	private function addThisContactLink(): void {
		echo Helper::doShortcode( 'contact_link' );
	}

	// ------------------------------------------------------

	/**
	 * Render contact link shortcode.
	 *
	 * Usage: [contact_link] or [contact_link class="custom-class"]
	 *
	 * @param array|string $atts Shortcode attributes.
	 *
	 * @return string Rendered HTML.
	 */
	private function contactLink( array|string $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'class' => 'contact-link',
			],
			$atts,
			'contact_link'
		);

		$class  = Helper::escAttr( $atts['class'] );
		$items  = self::getActiveItems();
		$output = [];

		if ( empty( $items ) ) {
			return '';
		}

		foreach ( $items as $item ) {
			$name   = $item['name'] ?? '';
			$icon   = $item['icon'] ?? '';
			$value  = $item['value'] ?? '';
			$target = $item['target'] ?? '_blank';
			$class_ = $item['class'] ?? '';
			$thumb  = Helper::renderIcon( $icon, $name );

			if ( empty( $value ) || empty( $thumb ) ) {
				continue;
			}

			// Build target attribute.
			$targetAttr = '';
			if ( ! empty( $target ) ) {
				$targetAttr = sprintf( ' target="%s"', esc_attr( $target ) );
				if ( '_blank' === $target ) {
					$targetAttr .= ' rel="noopener noreferrer"';
				}
			}

			$output[] = sprintf(
				'<li><a%s class="%s" href="%s" title="%s">%s<span>%s</span></a></li>',
				$targetAttr,
				esc_attr( $class_ ),
				esc_url( $value ),
				esc_attr( $name ),
				$thumb,
				esc_html( $name )
			);
		}

		if ( empty( $output ) ) {
			return '';
		}

		return sprintf(
			'<ul class="add-this %s">%s</ul>',
			$class,
			implode( '', $output )
		);
	}

	// ─── SettingsAware ─────────────────────────────────

	/** @inheritDoc */
	public static function getFormKey(): string {
		return 'contact-link-hidden';
	}

	/** @inheritDoc */
	public static function sanitizeAndSave( array $data ): void {
		$items          = $data[ self::KEY_FORM_ITEMS ] ?? [];
		$sanitizedItems = [];

		if ( ! is_array( $items ) ) {
			$items = [];
		}

		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$sanitizedItem = [
				'id'     => ! empty( $item['id'] ) ? sanitize_text_field( $item['id'] ) : wp_generate_uuid4(),
				'name'   => ! empty( $item['name'] ) ? sanitize_text_field( $item['name'] ) : '',
				'icon'   => self::sanitizeIconValue( $item['icon'] ?? '' ),
				'value'  => ! empty( $item['value'] ) ? esc_url_raw( $item['value'] ) : '',
				'target' => in_array( $item['target'] ?? '', [ '_blank', '_self' ], true ) ? $item['target'] : '_blank',
				'class'  => ! empty( $item['class'] ) ? sanitize_html_class( $item['class'] ) : '',
				'color'  => ! empty( $item['color'] ) ? sanitize_hex_color( $item['color'] ) : '',
				'order'  => absint( $item['order'] ?? $index ),
			];

			if ( ! empty( $sanitizedItem['name'] ) || ! empty( $sanitizedItem['value'] ) ) {
				$sanitizedItems[] = $sanitizedItem;
			}
		}

		usort( $sanitizedItems, static fn( $a, $b ) => $a['order'] <=> $b['order'] );
		self::clearCache();

		// Save even if empty — distinguishes "never configured" from "intentionally cleared".
		Helper::updateOption( self::OPTION_NAME, $sanitizedItems, 12, false );
	}

	/**
	 * Sanitize icon value (attachment ID, URL, or SVG string).
	 *
	 * @param mixed $value The icon value.
	 *
	 * @return int|string Sanitized value.
	 */
	private static function sanitizeIconValue( mixed $value ): int|string {
		if ( empty( $value ) ) {
			return '';
		}

		// Decode base64-encoded SVG from form submission.
		if ( is_string( $value ) && str_starts_with( $value, 'base64:' ) ) {
			$value = base64_decode( substr( $value, 7 ), true );
			if ( false === $value ) {
				return '';
			}
		}

		// Attachment ID.
		if ( is_numeric( $value ) ) {
			$attachmentId = absint( $value );
			if ( wp_attachment_is_image( $attachmentId ) || 'image/svg+xml' === get_post_mime_type( $attachmentId ) ) {
				return $attachmentId;
			}
			return '';
		}

		// URL.
		if ( Helper::isUrl( $value ) ) {
			return esc_url_raw( $value );
		}

		// SVG string.
		if ( str_starts_with( $value, '<svg' ) ) {
			return Helper::ksesSvg( $value );
		}

		// Icon class.
		return sanitize_text_field( $value );
	}
}
