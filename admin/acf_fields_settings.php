<?php

if( function_exists('acf_add_local_field_group') ) {

    acf_add_local_field_group(array(
        'key' => 'go-image-renderer-picture-fields',
        'title' => '<picture> element',
        'fields' => array(
            array(
                'key' => 'image_renderer_force_portrait_field',
                'label' => 'Force to display full image',
                'name' => 'force_portait',
                'type' => 'true_false',
                'ui' => 1,
            ),
            array(
                'key' => 'image_renderer_display_legend_field',
                'label' => 'Display legend if possible',
                'name' => 'display_legend',
                'type' => 'true_false',
                'default_value' => 1,
                'ui' => 1,
            ),
            array(
                'key' => 'image_renderer_mob_img',
                'label' => 'Mobile image',
                'name' => 'mob_img',
                'type' => 'image',
                'instructions' => "This image will appear on mobile. Use this field whenever you can’t get an image properly framed on mobile.",
                'return_format' => 'array',
                'preview_size' => 'thumbnail',
                'library' => 'all',
            ),
            array(
                'key' => 'image_renderer_tab_img',
                'label' => 'Tablet image',
                'name' => 'tab_img',
                'type' => 'image',
                'instructions' => "This image will appear on tablets. Use this field whenever you can’t get an image properly framed on tablets.",
                'return_format' => 'array',
                'preview_size' => 'thumbnail',
                'library' => 'all',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'attachment',
                    'operator' => '==',
                    'value' => 'image',
                ),
                array(
                    'param' => 'attachment',
                    'operator' => '!=',
                    'value' => 'image/svg+xml',
                ),
            ),
        ),

        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'left',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
    ));
}