<?php
/**
 * Plugin Name: GO - Image Renderer
 * Description: Display images with render_image(), a powerfull and light function that brings performance and accessibility to your theme. 
 * Version: 1.5.1
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

$license_status = get_option('go_image_renderer_license_status', 'inactive');

// If licence is active, load functions
if ($license_status === 'active') {
    require_once __DIR__ . '/admin/render_functions.php';
} else {
    function render_image( $args = [] ) {
        if ( empty( $args['img'] ) || ! is_array( $args['img'] ) || ! isset( $args['img']['ID'] ) ) {
            if ( is_user_logged_in() ) {
                echo '';
            }
            return;
        }

        $image_id = $args['img']['ID'];
        $image_url = wp_get_attachment_image_url( $image_id, 'full' );
        
        if ( $image_url ) {
            $alt_text = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
            printf( '<div class="img-wrap"><img src="%s" alt="%s" ></div>', esc_url( $image_url ), esc_attr( $alt_text ));
            echo "<span class='admin-msg'>" . __("Image renderer's licence is not active. The performances of your website are impacted.", "Image_renderer") . "</span>";
        } else {
            if ( is_user_logged_in() ) {
                echo '<span class="admin-msg">Image renderer is not active, and the required image was not found.</span>';
            }
        }
    }

    function get_svg($svg) {
        return '<img src="'. $svg['url'] .'"/>'; 
        return '<span class="admin-msg">Image renderer is not active. Icons cannot be rendered.</span>'; 
    }
}


add_action('wp_enqueue_scripts', 'go_image_renderer_styles');

function go_image_renderer_styles() {
    wp_enqueue_style(
        'go-image-renderer-css',
        plugins_url('assets/css/styles.css', __FILE__),
        [],
        '1.5.1'
    );
}