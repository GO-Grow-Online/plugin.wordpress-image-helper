<?php
/**
 * Plugin Name: GO - Image Renderer
 * Description: Display images with render_image(), a powerfull and light function that brings performance and accessibility to your theme. 
 * Version: 1.4.2
 * Author: Grow Online
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load update checker
require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://raw.githubusercontent.com/GO-Grow-Online/plugin.wordpress-image-helper/refs/heads/master/image-helper-version.json',
    __FILE__,
    'wordpress-image-helper'
);  

require_once __DIR__ . '/wp_medias_settings.php';

// Main function to render images
function render_image($args = []) {

    // Classes parameters - Can be overwritten by WebMaster in $args
    // force_portrait   | create a blured bg image
    // display_legend   | allow to show or hide <caption> 
    // seamless         | add a class deleting default styles
    $force_portrait = !empty($img) ? get_field('force_portrait', $img['id']) : false;
    $display_legend = !empty($img) ? get_field('display_legend', $img['id']) : false;
    $seamless = !empty($img) ? get_field('seamless', $img['id']) : false;

    $defaults = [
        'img' => null,
        'image_format' => null,
        'fs' => false,
        'defer' => true,

        'seamless' => $seamless,
        'display_legend' => $display_legend,
        'force_portrait' => $force_portrait,
        
        'figcaption' => false
    ];
    
    // Combine both argument arrays - $args is primary
    $args = wp_parse_args($args, $defaults);
    
    // Validate 'img' argument
    if ($args['img'] && (!is_array($args['img']) || !isset($args['img']['url']))) {
        trigger_error('Invalid image format provided. Expected an array with a "url" key.', E_USER_WARNING);
        $args['img'] = null;
    }

    // If image is empty get placeholder in "general" option page
    $img = $args['img'] ?: get_field('img_placeholder', 'options');

    $loading = $args['defer'] ? "lazy" : "eager";
    $mime_type = $img['mime_type'] ?? '';

    $is_svg = $mime_type == 'image/svg+xml';
       
    ?>

    <div class="imgWrap<?php 
        echo $force_portrait ? ' imgWrap--portrait' : ''; 
        echo $seamless ? ' imgWrap--seamless' : ''; 
        echo $display_legend ? ' imgWrap--displayLegend' : ''; 
        ?>">

        <?php if ($img) : ?>
            <?php 
            // Render blured bg image in "force_portrait" mode
            if ($force_portrait && !$is_svg) { 
                echo '<!--googleoff: index--><img class="imgWrap--portrait-bg" loading="'. esc_attr($loading) .'" type="'. esc_attr($mime_type) .'" src="'. esc_url($img['sizes']['thumbnail']) .'" alt="'. esc_attr($img['alt']) .'"><!--googleon: index-->'; }
            ?>
            
            <?php if ($args['figcaption']) : ?>
                <figure itemscope itemtype="http://schema.org/ImageObject">
            <?php endif; ?>

            <?php 
            // Render picture content if no image format is set, and if file is svg only with tab or mob files assigned
            $no_img_format = !$args['image_format'];

            if ($no_img_format && !$is_svg) {
                $mob = get_field('mob_img', $img['id']);
                $tab = get_field('tab_img', $img['id']);
                $thumbnail = $mob ? $mob['sizes']['thumbnail'] : $img['sizes']['thumbnail'];
                $medium = $tab ? $tab['sizes']['medium'] : $img['sizes']['medium'];

                // Width & Height attr
                $w = $img['width'] ? $img['width'] : 650;
                $h = $img['height'] ? $img['height'] : 650;
                ?>
                <picture>
                    <source media="(max-width: 500px)" type="<?php echo esc_attr($mime_type); ?>" srcset="<?php echo esc_url($thumbnail); ?>">
                    <source media="(max-width: 1023px)" type="<?php echo esc_attr($mime_type); ?>" srcset="<?php echo esc_url($medium); ?>">

                    <?php if ($args['fs']) : ?>
                        <source media="(min-width: 1024px)" type="<?php echo esc_attr($mime_type); ?>" srcset="<?php echo esc_url($img['sizes']['large']); ?>">
                    <?php elseif ($img['sizes']['medium'] !== $medium) : ?>
                        <source media="(min-width: 1024px)" type="<?php echo esc_attr($mime_type); ?>" srcset="<?php echo esc_url($img['sizes']['medium']); ?>">
                    <?php endif; ?>

                    <img width="<?php echo $w; ?>" width="<?php echo $h; ?>" loading="<?php echo esc_attr($loading); ?>" alt="<?php echo esc_attr($img['alt']); ?>" src="<?php echo esc_url($medium); ?>">

                </picture>
                <?php
            } else {
                
                if (!$is_svg) { // Has image format but is not svg
                    printf('<img loading="%s" type="%s" src="%s" alt="%s" width="%d" height="%d">', esc_attr($loading), esc_attr($mime_type), esc_url($img['sizes'][$args['image_format']]), esc_attr($img['alt']), esc_attr($img['width']), esc_attr($img['height']));
                } else { // Is SVG
                    printf('<img loading="%s" type="%s" src="%s" alt="%s" width="%d" height="%d">', esc_attr($loading), esc_attr($mime_type), esc_url($img['url']), esc_attr($img['alt']), esc_attr($img['width']), esc_attr($img['height']));
                }
            }
            ?>

            <?php 
            // Render figcaption 
            if ($args['figcaption']) :
                    if (!empty($img['caption'])) { echo '<figcaption>' . esc_html($img['caption']) . '</figcaption>'; }
                    if (!empty($img['url'])) { echo '<meta itemprop="url" content="' . esc_html($img['url']) . '"/>'; }
                    if (!empty($img['description'])) { echo '<meta itemprop="description" content="' . esc_html($img['description']) . '"/>'; }
                    if (!empty($img['name'])) { echo '<meta itemprop="name" content="' . esc_html($img['name']) . '"/>'; } ?>
                </figure>
            <?php endif; ?>

        <?php else : ?>
            <img width="650" height="650" src="<?php echo esc_url(get_template_directory_uri() . '/assets/static/svg/image_placeholder.svg'); ?>" alt="Logo de <?php bloginfo('name'); ?> - Aucune image trouvée">
        <?php endif; ?>
    </div>

    <?php

    if ( ! class_exists( 'Timber' ) ) {
    }
}