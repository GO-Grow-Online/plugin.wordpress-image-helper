<?php

if( function_exists('acf_add_local_field_group') ) {

    acf_add_local_field_group(array(
        'key' => 'go-image-renderer-picture-fields',
        'title' => '<picture> element',
        'fields' => array(
            array(
                'key' => 'image_renderer_force_portrait_field',
                'label' => 'Forcer l\'affichage en portrait',
                'name' => 'force_portait',
                'type' => 'true_false',
                'ui' => 1,
            ),
            array(
                'key' => 'image_renderer_display_legend_field',
                'label' => 'Afficher la légende quand c\'est possible',
                'name' => 'display_legend',
                'type' => 'true_false',
                'default_value' => 1,
                'ui' => 1,
            ),
            array(
                'key' => 'image_renderer_mob_img',
                'label' => 'Image mobile',
                'name' => 'mob_img',
                'type' => 'image',
                'instructions' => "Cette image apparaîtra sur les appareils mobiles.\nUtilisez ce champ à partir du moment où vous ne parvenez pas à avoir une image correctement cadrée sur les appareils mobiles.",
                'return_format' => 'array',
                'preview_size' => 'thumbnail',
                'library' => 'all',
            ),
            array(
                'key' => 'image_renderer_tab_img',
                'label' => 'Image tablette',
                'name' => 'tab_img',
                'type' => 'image',
                'instructions' => "Cette image apparaîtra sur les tablettes.\nUtilisez ce champ à partir du moment où vous ne parvenez pas à avoir une image correctement cadrée sur les tablettes.",
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