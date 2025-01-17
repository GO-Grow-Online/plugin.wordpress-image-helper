<?php
/**
 * Plugin Name: Wordpress Image Renderer
 * Description: A plugin to render images based on a Twig-like template logic.
 * Version: 1.0.10
 * Author: Grow Online
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Check if required plugins are activated 
if ( ! class_exists( 'Timber' ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="error"><p>Le plugin Timber est requis pour que ce plugin fonctionne correctement. Veuillez l’installer et l’activer.</p></div>';
    });
    return;
}

if ( ! class_exists( 'ACF' ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="error"><p>Le plugin Advanced Custom Fields (ACF) est requis pour que ce plugin fonctionne correctement. Veuillez l’installer et l’activer.</p></div>';
    });
    return;
}

// Check if class allready exists
if ( ! class_exists( 'PucFactory' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php';
}

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;


// Init plugin update checker
$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/GO-Grow-Online/plugin.wordpress-image-helper/blob/8f33809516502b7fcfb4ed96474a68d4158aed82/plugin-version.json',
    __FILE__,
    'wordpress-image-renderer'
);


// Main function to render images
function render_custom_image($args = []) {
    $defaults = [
        'img' => null,
        'eager_loading' => null,
        'image_format' => null,
        'figcaption' => '',
        'is_seamless' => null,
        'is_fs' => false
    ];

    $args = wp_parse_args($args, $defaults);

    // Validate 'img' argument
    if ($args['img'] && (!is_array($args['img']) || !isset($args['img']['src']))) {
        trigger_error('Invalid image format provided. Expected an array with a "src" key.', E_USER_WARNING);
        $args['img'] = null;
    }

    $img = $args['img'] ?: get_image_placeholder();
    $loading_type = $args['eager_loading'] ? 'eager' : 'lazy';
    $mime_type = $img['post_mime_type'] ?? '';

    $figcaption = !empty($args['figcaption']) ? $args['figcaption'] : false;
    $force_portrait = get_field('force_portrait', $img['id']) ?: false;
    $display_legend = get_field('display_legend', $img['id']) ?: false;
    $is_seamless = $args['is_seamless'] ?: get_field('seamless', $img['id']);

    return render_image_wrap($img, $loading_type, $mime_type, $figcaption, $force_portrait, $display_legend, $is_seamless, $args);
}

// Function to render the image wrapper
function render_image_wrap($img, $loading_type, $mime_type, $figcaption, $force_portrait, $display_legend, $is_seamless, $args) {
    ob_start();
    ?>
    <div class="imgWrap<?php 
        echo $force_portrait ? ' imgWrap--portrait' : ''; 
        echo $is_seamless ? ' imgWrap--seamless' : ''; 
        echo $display_legend ? ' imgWrap--displayLegend' : ''; 
    ?>">
        <?php if ($img) : ?>
            <?php echo render_portrait_background($img, $force_portrait, $mime_type, $loading_type); ?>
            <?php if ($figcaption) : ?>
                <figure itemscope itemtype="http://schema.org/ImageObject">
            <?php endif; ?>

            <?php echo render_image_content($img, $args, $mime_type, $loading_type); ?>

            <?php if ($figcaption) : ?>
                <?php echo render_figcaption($img); ?>
                </figure>
            <?php endif; ?>

        <?php else : ?>
            <img width="500" height="500" src="<?php echo esc_url(get_template_directory_uri() . '/assets/static/svg/image_placeholder.svg'); ?>" alt="Logo de <?php bloginfo('name'); ?> - Aucune image trouvée">
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// Function to render the portrait background
function render_portrait_background($img, $force_portrait, $mime_type, $loading_type) {
    if ($force_portrait && $mime_type !== 'image/svg+xml') {
        return sprintf(
            '<!--googleoff: index--><img class="imgWrap--portrait-bg" loading="%s" type="image/webp" src="%s" alt="%s"><!--googleon: index-->',
            esc_attr($loading_type),
            esc_url($img['src']['thumbnail']),
            esc_attr($img['alt'])
        );
    }
    return '';
}

// Function to render the image content
function render_image_content($img, $args, $mime_type, $loading_type) {
    ob_start();
    if (!$args['image_format'] && $mime_type !== 'image/svg+xml') {
        $mob = get_field('mob_img', $img['id']);
        $tab = get_field('tab_img', $img['id']);
        $thumbnail = $mob ? get_image($mob)['src']['thumbnail'] : $img['src']['thumbnail'];
        $medium = $tab ? get_image($tab)['src']['medium'] : $img['src']['medium'];
        ?>
        <picture>
            <source media="(max-width: 500px)" type="image/webp" srcset="<?php echo esc_url($thumbnail); ?>">
            <source media="(max-width: 1023px)" type="image/webp" srcset="<?php echo esc_url($medium); ?>">
            <?php if ($args['is_fs']) : ?>
                <source media="(min-width: 1024px)" type="image/webp" srcset="<?php echo esc_url($img['src']['large']); ?>">
            <?php elseif ($img['src']['medium'] !== $medium) : ?>
                <source media="(min-width: 1024px)" type="image/webp" srcset="<?php echo esc_url($img['src']['medium']); ?>">
            <?php endif; ?>
            <img loading="<?php echo esc_attr($loading_type); ?>" alt="<?php echo esc_attr($img['alt']); ?>" src="<?php echo esc_url($medium); ?>">
        </picture>
        <?php
    } else {
        if ($mime_type !== 'image/svg+xml') {
            printf('<img loading="%s" type="image/webp" src="%s" alt="%s">', esc_attr($loading_type), esc_url($img['src'][$args['image_format']]), esc_attr($img['alt']));
        } else {
            printf('<img loading="%s" type="image/svg+xml" src="%s" alt="%s">', esc_attr($loading_type), esc_url($img['src']), esc_attr($img['alt']));
        }
    }
    return ob_get_clean();
}

// Function to render the figcaption
function render_figcaption($img) {
    ob_start();
    if (!empty($img['caption'])) {
        echo '<figcaption>' . esc_html($img['caption']) . '</figcaption>';
    }
    ?>
    <meta itemprop="url" content="<?php echo esc_url($img['url']); ?>">
    <meta itemprop="description" content="<?php echo esc_attr($img['description']); ?>">
    <meta itemprop="name" content="<?php echo esc_attr($img['title']); ?>">
    <?php
    return ob_get_clean();
}

// Placeholder function for fallback image
function get_image_placeholder() {
    return [
        'src' => get_template_directory_uri() . '/assets/static/svg/image_placeholder.svg',
        'alt' => __('Image non disponible', 'text-domain'),
        'id' => null,
        'caption' => '',
        'post_mime_type' => 'image/svg+xml'
    ];
}

// Example usage: echo render_custom_image(['img' => $image_array]);
