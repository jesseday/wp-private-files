<?php

if( function_exists('acf_add_local_field_group') ):

	acf_add_local_field_group(array (
		'key' => 'group_5966939f12770',
		'title' => 'Private Media',
		'fields' => array (
			array (
				'key' => 'field_5966952be34dc',
				'label' => 'Is Private',
				'name' => 'is_private',
				'type' => 'true_false',
				'instructions' => 'Should this image be viewable by the public or by logged in customers only.',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array (
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'message' => '',
				'default_value' => 0,
			),
		),
		'location' => array (
			array (
				array (
					'param' => 'attachment',
					'operator' => '==',
					'value' => 'all',
				),
			),
		),
		'menu_order' => -100,
		'position' => 'acf_after_title',
		'style' => 'seamless',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
		'active' => 1,
		'description' => '',
	));

endif;