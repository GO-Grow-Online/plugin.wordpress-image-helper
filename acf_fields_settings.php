<?php

if( function_exists('acf_add_local_field_group') ) {
    acf_add_local_field_group(array(
        'key' => 'group_64ca70a99efd0',
        'title' => '<picture> element',
        'fields' => array(
            array(
                'key' => 'field_64df45b43e4d6',
                'label' => 'Forcer l\'affichage en portrait',
                'name' => 'force_portait',
                'type' => 'true_false',
                'instructions' => '',
                'required' => 0,
                'ui' => 1,
            ),
            array(
                'key' => 'field_66d867834e049',
                'label' => 'Afficher la légende quand c\'est possible',
                'name' => 'display_legend',
                'type' => 'true_false',
                'instructions' => '',
                'required' => 0,
                'default_value' => 1,
                'ui' => 1,
            ),
            array(
                'key' => 'field_64ca70aa0e4f7',
                'label' => 'Image mobile',
                'name' => 'mob_img',
                'type' => 'image',
                'instructions' => "Cette image apparaîtra sur les appareils mobiles.\nUtilisez ce champ à partir du moment où vous ne parvenez pas à avoir une image correctement cadrée sur les appareils mobiles.",
                'return_format' => 'array',
                'preview_size' => 'thumbnail',
                'library' => 'all',
            ),
            array(
                'key' => 'field_64ca74cb0e4f8',
                'label' => 'Image tablette',
                'name' => 'tab_img',
                'type' => 'image',
                'instructions' => "Cette image apparaîtra sur les tablettes.\nUtilisez ce champ à partir du moment où vous ne parvenez pas à avoir une image correctement cadrée sur les tablettes.",
                'return_format' => 'array',
                'preview_size' => 'medium',
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