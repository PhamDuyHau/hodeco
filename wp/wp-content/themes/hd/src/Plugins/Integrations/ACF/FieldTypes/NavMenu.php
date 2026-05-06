<?php
/**
 * ACF Nav Menu Field Type.
 *
 * Provides a custom ACF field for selecting WordPress navigation menus.
 *
 * @author Galaxy Weblinks
 * @link   https://wordpress.org/plugins/acf-nav-menu/
 *
 * Modified by HD
 */

namespace HD\Plugins\Integrations\ACF\FieldTypes;

defined( 'ABSPATH' ) || die;

class NavMenu extends \acf_field {

	/**
	 * Cached navigation menus.
	 *
	 * @var array<int|string, string>|null
	 */
	private ?array $cachedMenus = null;

	// ----------------------------------------------

	/**
	 * Initialize the Nav Menu field type.
	 */
	public function __construct() {
		$this->name     = 'nav_menu';
		$this->label    = esc_html__( 'Nav Menu', TEXT_DOMAIN );
		$this->category = 'choice';
		$this->defaults = [
			'save_format' => 'menu',
			'allow_null'  => 0,
			'container'   => 'div',
		];

		parent::__construct();
	}

	// ----------------------------------------------

	/**
	 * Render field settings in ACF admin.
	 *
	 * @param array $field Field configuration.
	 */
	public function render_field_settings( array $field ): void {
		// Register the Return Value format setting
		acf_render_field_setting(
			$field,
			[
				'label'        => esc_html__( 'Return Value', TEXT_DOMAIN ),
				'instructions' => esc_html__( 'Specify the returned value on front end', TEXT_DOMAIN ),
				'type'         => 'radio',
				'name'         => 'save_format',
				'layout'       => 'horizontal',
				'choices'      => [
					'menu'   => esc_html__( 'Nav Menu HTML', TEXT_DOMAIN ),
					'object' => esc_html__( 'Nav Menu Object', TEXT_DOMAIN ),
					'id'     => esc_html__( 'Nav Menu ID', TEXT_DOMAIN ),
				],
			]
		);

		// Register the Menu Container setting
		acf_render_field_setting(
			$field,
			[
				'label'        => esc_html__( 'Menu Container', TEXT_DOMAIN ),
				'instructions' => esc_html__( "What to wrap the Menu's ul with (when returning HTML only)", TEXT_DOMAIN ),
				'type'         => 'select',
				'name'         => 'container',
				'choices'      => $this->getAllowedContainerTags(),
			]
		);

		// Register the Allow Null setting
		acf_render_field_setting(
			$field,
			[
				'label'   => esc_html__( 'Allow Null?', TEXT_DOMAIN ),
				'type'    => 'radio',
				'name'    => 'allow_null',
				'layout'  => 'horizontal',
				'choices' => [
					1 => esc_html__( 'Yes', TEXT_DOMAIN ),
					0 => esc_html__( 'No', TEXT_DOMAIN ),
				],
			]
		);
	}

	// ----------------------------------------------

	/**
	 * Get allowed HTML container tags for nav menu.
	 *
	 * @return array<string, string> Tag => Label pairs.
	 */
	private function getAllowedContainerTags(): array {
		$tags = apply_filters( 'wp_nav_menu_container_allowedtags', [ 'div', 'nav' ] );

		return [
			'0' => esc_html__( 'None', TEXT_DOMAIN ),
			...array_combine( $tags, array_map( 'ucfirst', $tags ) ),
		];
	}

	// ----------------------------------------------

	/**
	 * Render the field input in admin.
	 *
	 * @param array $field Field configuration.
	 */
	public function render_field( array $field ): void {
		$allowNull = (bool) $field['allow_null'];
		$navMenus  = $this->getNavMenus( $allowNull );

		if ( ! $navMenus ) {
			return;
		}

		?>
		<div class="custom-acf-nav-menu">
			<select title="" id="<?php echo esc_attr( $field['id'] ); ?>" class="<?php echo esc_attr( $field['class'] ); ?>"
					name="<?php echo esc_attr( $field['name'] ); ?>">
				<?php foreach ( $navMenus as $navMenuId => $navMenuName ) : ?>
					<option value="<?php echo esc_attr( $navMenuId ); ?>" <?php selected( $field['value'], $navMenuId ); ?>>
						<?php echo esc_html( $navMenuName ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	// ----------------------------------------------

	/**
	 * Get available navigation menus with caching.
	 *
	 * @param bool $allowNull Whether to include empty option.
	 *
	 * @return array<int|string, string> Menu ID => Menu Name pairs.
	 */
	private function getNavMenus( bool $allowNull = false ): array {
		// Build menus cache if not already cached
		if ( null === $this->cachedMenus ) {
			$this->cachedMenus = [];

			$navs = get_terms(
				[
					'taxonomy'   => 'nav_menu',
					'hide_empty' => false,
				]
			);

			// Check for errors or empty result
			if ( $navs && ! is_wp_error( $navs ) ) {
				foreach ( $navs as $nav ) {
					$this->cachedMenus[ $nav->term_id ] = $nav->name;
				}
			}
		}

		// Prepend empty option if allowed
		if ( $allowNull ) {
			return [ '' => esc_html__( '- Select -', TEXT_DOMAIN ) ] + $this->cachedMenus;
		}

		return $this->cachedMenus;
	}

	// ----------------------------------------------

	/**
	 * Format the field value for frontend output.
	 *
	 * ACF can pass $postId as string for options pages, terms, users, etc.
	 * Examples: "options", "term_5", "user_1", "widget_123"
	 *
	 * @param mixed $value The field value.
	 * @param int|string $postId The post ID or location identifier.
	 * @param array $field The field configuration.
	 *
	 * @return \stdClass|string|int|false Nav Menu Object, HTML, ID, or false if empty.
	 */
	public function format_value( mixed $value, int|string $postId, array $field ): \stdClass|string|int|false {
		// Bail early if no value
		if ( empty( $value ) ) {
			return false;
		}

		return match ( $field['save_format'] ?? 'id' ) {
			'object' => $this->formatAsObject( $value ),
			'menu'   => $this->formatAsHtml( $value, $field ),
			default  => (int) $value, // Return as ID (cast to int for consistency)
		};
	}

	// ----------------------------------------------

	/**
	 * Format value as menu object.
	 *
	 * @param mixed $value Menu ID or slug.
	 *
	 * @return \stdClass|false Menu object or false if not found.
	 */
	private function formatAsObject( mixed $value ): \stdClass|false {
		$wpMenuObject = wp_get_nav_menu_object( $value );
		if ( ! $wpMenuObject ) {
			return false;
		}

		$menuObject        = new \stdClass();
		$menuObject->ID    = $wpMenuObject->term_id;
		$menuObject->name  = $wpMenuObject->name;
		$menuObject->slug  = $wpMenuObject->slug;
		$menuObject->count = $wpMenuObject->count;

		return $menuObject;
	}

	// ----------------------------------------------

	/**
	 * Format value as HTML.
	 *
	 * @param mixed $value Menu ID or slug.
	 * @param array $field Field configuration.
	 *
	 * @return string|false Menu HTML or false if menu not found.
	 */
	private function formatAsHtml( mixed $value, array $field ): string|false {
		return wp_nav_menu(
			[
				'echo'            => false,
				'menu'            => $value,
				'container_class' => 'acf-nav-menu',
				'container'       => $field['container'] ?? 'div',
				'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
				'fallback_cb'     => '__return_false', // Return false instead of default menu fallback
			]
		);
	}
}
