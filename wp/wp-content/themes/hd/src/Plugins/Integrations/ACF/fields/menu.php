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
		$locations = (array) ( $acfMenu['acf_menu_items_locations'] ?? [] );

		$location = array_filter(
			array_map(
				static fn( $menuItems ) => $menuItems
				? [
					[
						'param'    => 'nav_menu_item',
						'operator' => '==',
						'value'    => 'location/' . Helper::toString( $menuItems ),
					],
				]
				: null,
				$locations
			)
		);

		acf_add_local_field_group(
			[
				'key'                   => 'group_64bd0aafbaa3a',
				'title'                 => 'Attributes of Menu Items',
				'fields'                => [
					[
						'key'               => 'field_64bd131c6bca9',
						'label'             => 'Link CSS',
						'name'              => 'menu_link_class',
						'aria-label'        => '',
						'type'              => 'text',
						'instructions'      => '',
						'required'          => 0,
						'conditional_logic' => 0,
						'wrapper'           => [
							'width' => '',
							'class' => '',
							'id'    => '',
						],
						'default_value'     => '',
						'maxlength'         => '',
						'placeholder'       => '',
						'prepend'           => '',
						'append'            => '',
					],
					[
						'key'               => 'field_68cb6d4233853',
						'label'             => 'Span (optional)',
						'name'              => 'menu_span',
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
						'message'           => 'Wrap the title with a `span` tag',
						'default_value'     => 0,
						'ui'                => 0,
						'ui_on_text'        => '',
						'ui_off_text'       => '',
					],
					[
						'key'               => 'field_68cb6d5633854',
						'label'             => 'Span CSS',
						'name'              => 'menu_span_css',
						'aria-label'        => '',
						'type'              => 'text',
						'instructions'      => '',
						'required'          => 0,
						'conditional_logic' => [
							[
								[
									'field'    => 'field_68cb6d4233853',
									'operator' => '==',
									'value'    => '1',
								],
							],
						],
						'wrapper'           => [
							'width' => '',
							'class' => '',
							'id'    => '',
						],
						'default_value'     => '',
						'maxlength'         => '',
						'allow_in_bindings' => 0,
						'placeholder'       => '',
						'prepend'           => '',
						'append'            => '',
					],
					[
						'key'               => 'field_68cb6919920d6',
						'label'             => 'Svg (optional)',
						'name'              => 'menu_svg',
						'aria-label'        => '',
						'type'              => 'textarea',
						'instructions'      => '',
						'required'          => 0,
						'conditional_logic' => 0,
						'wrapper'           => [
							'width' => '',
							'class' => '',
							'id'    => '',
						],
						'default_value'     => '',
						'maxlength'         => '',
						'allow_in_bindings' => 0,
						'rows'              => 6,
						'placeholder'       => '',
						'new_lines'         => '',
					],
					[
						'key'               => 'field_64bd0ab0ea1d7',
						'label'             => 'Thumbnail',
						'name'              => 'menu_image',
						'aria-label'        => '',
						'type'              => 'image',
						'instructions'      => '',
						'required'          => 0,
						'conditional_logic' => 0,
						'wrapper'           => [
							'width' => '',
							'class' => '',
							'id'    => '',
						],
						'return_format'     => 'id',
						'library'           => 'all',
						'min_width'         => '',
						'min_height'        => '',
						'min_size'          => '',
						'max_width'         => '',
						'max_height'        => '',
						'max_size'          => '',
						'mime_types'        => 'png,svg,jpg,jpeg,gif,webp',
						'preview_size'      => 'small-100',
					],
					[
						'key'               => 'field_64bd139df7dfd',
						'label'             => 'Label',
						'name'              => 'menu_label_text',
						'aria-label'        => '',
						'type'              => 'text',
						'instructions'      => '',
						'required'          => 0,
						'conditional_logic' => 0,
						'wrapper'           => [
							'width' => '',
							'class' => '',
							'id'    => '',
						],
						'default_value'     => '',
						'maxlength'         => '',
						'placeholder'       => '"New", "Hot", "Featured" ...',
						'prepend'           => '',
						'append'            => '',
					],
					[
						'key'               => 'field_64bd13ccf7dfe',
						'label'             => 'Label Color',
						'name'              => 'menu_label_color',
						'aria-label'        => '',
						'type'              => 'color_picker',
						'instructions'      => '',
						'required'          => 0,
						'conditional_logic' => [
							[
								[
									'field'    => 'field_64bd139df7dfd',
									'operator' => '!=empty',
								],
							],
						],
						'wrapper'           => [
							'width' => '',
							'class' => '',
							'id'    => '',
						],
						'default_value'     => '',
						'enable_opacity'    => 1,
						'return_format'     => 'string',
					],
					[
						'key'               => 'field_64bd1488092dc',
						'label'             => 'Label Background',
						'name'              => 'menu_label_background',
						'aria-label'        => '',
						'type'              => 'color_picker',
						'instructions'      => '',
						'required'          => 0,
						'conditional_logic' => [
							[
								[
									'field'    => 'field_64bd139df7dfd',
									'operator' => '!=empty',
								],
							],
						],
						'wrapper'           => [
							'width' => '',
							'class' => '',
							'id'    => '',
						],
						'default_value'     => '',
						'enable_opacity'    => 1,
						'return_format'     => 'string',
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
				'show_in_rest'          => 1,
			]
		);
	}
);
