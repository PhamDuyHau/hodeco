<?php

use HD\Utilities\Helper;

defined( 'ABSPATH' ) || die;

add_action(
	'acf/include_fields',
	static function (): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		$acfMenu   = Helper::filterSettingOptions( 'acf_menu', [] );
		$locations = (array) ( $acfMenu['acf_mega_menu_locations'] ?? [] );

		$location = array_filter(
			array_map(
				static fn( $menuItem ) => $menuItem
				? [
					[
						'param'    => 'nav_menu_item',
						'operator' => '==',
						'value'    => 'location/' . Helper::toString( $menuItem ),
					],
				]
				: null,
				$locations
			)
		);

		acf_add_local_field_group(
			[
				'key'                   => 'group_64c8be6be97d0',
				'title'                 => 'Attributes of Menu',
				'fields'                => [
					[
						'key'               => 'field_64c8be6c6147a',
						'label'             => 'Mega menu (optional)',
						'name'              => 'menu_mega',
						'aria-label'        => '',
						'type'              => 'true_false',
						'instructions'      => '',
						'required'          => 0,
						'conditional_logic' => 0,
						'wrapper'           => [
							'width' => '',
							'class' => 'checkbox',
							'id'    => '',
						],
						'message'           => 'Mega menu',
						'default_value'     => 0,
						'ui'                => 0,
						'ui_on_text'        => '',
						'ui_off_text'       => '',
					],
				],
				'location'              => $location,
				'menu_order'            => 0,
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen'        => '',
				'active'                => true,
				'description'           => '',
				'show_in_rest'          => 0,
			]
		);
	}
);
