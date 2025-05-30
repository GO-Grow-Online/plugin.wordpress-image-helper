<?php
/**
 * Plugin Name: GO - Image Renderer
 * Description: Display images with render_image(), a powerfull and light function that brings performance and accessibility to your theme. 
 * Version: 1.4.9
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

// Automatic shutdown
register_shutdown_function( function() {
    $err = error_get_last();
    // Error types
    $fatals = [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ];
    if ( $err && in_array( $err['type'], $fatals, true ) ) {
        if ( is_admin() && current_user_can( 'activate_plugins' ) ) {
            // Disactivate
            deactivate_plugins( plugin_basename( __FILE__ ) );
            // Store a message in admin for next load
            update_option( 'mon_plugin_crit_error', $err );
        }
    }
} );

// Display a notification for automatic shutdown
add_action( 'admin_notices', function() {
    if ( $err = get_option( 'mon_plugin_crit_error' ) ) {
        ?>
        <div class="notice notice-error">
            <p><strong><?php echo __('Image renderer has been disactivated due to a error.', 'Non-editable strings'); ?></strong></p>
            <pre style="white-space: pre-wrap;"><?php echo esc_html( $err['message'] ); ?></pre>
        </div>
        <?php
        delete_option( 'mon_plugin_crit_error' );
    }
} );


// Check if ACF in with us, otherwise plugin wont work
if (!function_exists('acf_add_local_field_group')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>Plugins manquants</strong> : Rendez-vous dans "thème", "Install Plguins". Installez et/ou activez ensuite les plugins affichés.</p></div>';
    });
    return;
}


// Plugin disactivation handle - mandatory to have it in main file with the use of __FILE__
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('go_image_renderer_auto_license_check');

    // Update WP db
    update_option('go_image_renderer_license_status', 'inactive');
    update_option('go_image_renderer_license_message', 'Le plugin a été désactivé. La licence est mise en pause.');

    // Update licence database
    $license_key = get_option('go_image_renderer_license_key', '');
    $domain = home_url();

    if (!empty($license_key)) {
        wp_remote_post('https://grow-online.be/licences/go-image-renderer-licence-deactivate.php', [
            'timeout' => 15,
            'body'    => [
                'license_key' => $license_key,
                'domain'      => $domain,
            ]
        ]);
    }
});


// Required
require_once __DIR__ . '/admin/wp_medias_settings.php';
require_once __DIR__ . '/admin/acf_fields_settings.php';
require_once __DIR__ . '/admin/licence.php';


// Main function to render images
function render_image($args = []) {

    // If image is empty get placeholder in "general" option page
    $img = $args['img'] ?: get_field('img_placeholder', 'options');

    // Classes parameters - Can be overwritten by WebMaster in $args
    // force_portrait   | create a blured bg image
    // display_legend   | allow to show or hide <caption> 
    // seamless         | add a class deleting default styles
    $force_portrait = !empty($img) ? get_field('force_portrait', $img['id']) : false;
    $display_legend = !empty($img) ? get_field('display_legend', $img['id']) : false;
    $seamless = !empty($img) ? get_field('seamless', $img['id']) : false;

    $defaults = [
        'img' => null,
        'format' => null,
        'fs' => false,
        'defer' => true,

        'seamless' => $seamless,
        'force_portrait' => $force_portrait,
        'display_legend' => $display_legend,
        
        // If display legend is true, we need <figcaption> anyway
        'figcaption' => $display_legend,
    ];
    
    // Combine both argument arrays - $args is primary
    $args = wp_parse_args($args, $defaults);

    // Validate 'img' argument
    if ($args['img'] && (!is_array($args['img']) || !isset($args['img']['url']))) {
        trigger_error('Invalid image format provided. Expected an array with a "url" key.', E_USER_WARNING);
        $args['img'] = null;
    }

    $loading = $args['defer'] ? "lazy" : "eager";
    $mime_type = $img['mime_type'] ?? '';
    $is_svg = $mime_type == 'image/svg+xml';

    
    $license_status = get_option('go_image_renderer_license_status', 'inactive');

    if ($license_status !== 'active' && is_user_logged_in()) {
        echo '<p class="admin-msg">Image Renderer :</strong> Votre licence n’est pas activée. Veuillez la saisir dans les paramètres du plugin.</p>';
    }

    ?>

    <div class="imgWrap<?php 
        echo $args['force_portrait'] ? ' imgWrap--portrait' : ''; 
        echo $args['seamless'] ? ' imgWrap--seamless' : ''; 
        echo $args['display_legend'] ? ' imgWrap--displayLegend' : ''; 
        ?>">

        <?php if ($img) : ?>
            <?php 
            // Render blured bg image in "force_portrait" mode
            if ($args['force_portrait'] && !$is_svg) { 
                echo '<!--googleoff: index--><img class="imgWrap--portrait-bg" loading="'. esc_attr($loading) .'" type="'. esc_attr($mime_type) .'" src="'. esc_url($img['sizes']['thumbnail']) .'" alt="'. esc_attr($img['alt']) .'"><!--googleon: index-->'; }
            ?>
            
            <?php if ($args['figcaption']) : ?>
                <figure itemscope itemtype="http://schema.org/ImageObject">
            <?php endif; ?>

            <?php 
            // Render picture content if no image format is set, and if file is svg only with tab or mob files assigned
            $no_img_format = !$args['format'];

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

                    
                    // Fallback if the image format is not generated on the website
                    $format = isset($img['sizes'][$args['format']]) ? $img['sizes'][$args['format']] : $img['sizes']['thumbnail'];
                    if (!isset($img['sizes'][$args['format']]) && is_user_logged_in()) {
                        echo "<span class='admin-msg'>Format not found. Thumbnail loaded.</span>";
                    }

                    printf('<img loading="%s" type="%s" src="%s" alt="%s" width="%d" height="%d">', esc_attr($loading), esc_attr($mime_type), esc_url($format), esc_attr($img['alt']), esc_attr($img['width']), esc_attr($img['height']));

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
            <img width="650" height="650" loading="<?php echo esc_attr($loading) ?>" src="<?php echo esc_url(get_template_directory_uri() . '/assets/static/svg/image_placeholder.svg'); ?>" alt="Logo de <?php bloginfo('name'); ?> - Aucune image trouvée">
        <?php endif; ?>
    </div>

    <?php
}