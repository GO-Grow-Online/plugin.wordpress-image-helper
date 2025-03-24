<?php

// Add a link to plugin's "setting" page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=go-image-renderer-license')) . '">' . __('Configuration', 'text-domain') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});

// Add plugin's "setting" page in admin
add_action('admin_menu', function () {
    add_submenu_page(
        'plugins.php',
        'Licence',
        'Licence Plugin',
        'manage_options',
        'go-image-renderer-license',
        'go_image_renderer_license_page'
    );
});

// Create configuration form
function go_image_renderer_license_page() {
    if (isset($_POST['go_image_renderer_license_key'])) {
        $old_value = get_option('go_image_renderer_license_key', '');
        $new_value = sanitize_text_field($_POST['go_image_renderer_license_key']);
        update_option('go_image_renderer_license_key', $new_value);
        do_action('update_option_go_image_renderer_license_key', $old_value, $new_value);
    }

    $license_key = get_option('go_image_renderer_license_key', '');
    ?>
    <div class="wrap">
        <h1>Licence - Image Renderer</h1>
        <form method="post">
            <?php wp_nonce_field('update_license_key', 'license_nonce'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Clé de licence</th>
                    <td><input type="text" name="go_image_renderer_license_key" value="<?php echo esc_attr($license_key); ?>" class="regular-text"></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Ajouter le champ de licence dans l'administration
add_action('admin_init', function () {
    register_setting('go_image_renderer_licence_group', 'go_image_renderer_license_key');

    add_settings_section(
        'go_image_renderer_licence_section',
        'Entrez votre clé de licence',
        null,
        'go-image-renderer-license'
    );

    add_settings_field(
        'go_image_renderer_license_key',
        'Clé de licence',
        function () {
            $value = get_option('go_image_renderer_license_key', '');
            echo '<input type="text" name="go_image_renderer_license_key" value="' . esc_attr($value) . '" class="regular-text">';
        },
        'go-image-renderer-license',
        'go_image_renderer_licence_section'
    );
});

// Update licence status on form submit
add_action('update_option_go_image_renderer_license_key', function ($old_value, $new_value) {
    $license_key = sanitize_text_field($new_value);
    $domain = home_url();
    $endpoint_url = "https://grow-online.be/licences-check/go-image-renderer-licence-check.php";

    $response = wp_remote_post($endpoint_url, [
        'timeout' => 15,
        'body'    => [
            'license_key' => $license_key,
            'domain'      => $domain
        ]
    ]);

    if (is_wp_error($response)) {
        update_option('go_image_renderer_license_status', 'inactive');
        update_option('go_image_renderer_license_message', 'Erreur de communication avec le serveur de licence.');
        return;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (!empty($data['success']) && $data['success'] === true) {
        update_option('go_image_renderer_license_status', 'active');
        update_option('go_image_renderer_license_message', $data['message']);
    } else {
        update_option('go_image_renderer_license_status', 'inactive');
        update_option('go_image_renderer_license_message', $data['message'] ?? 'Licence invalide.');
    }
}, 10, 2);

// Display response after form submission
add_action('admin_notices', function () {
    $screen = get_current_screen();
    if ($screen->id !== 'plugins_page_go-image-renderer-license') return;

    $status  = get_option('go_image_renderer_license_status', 'inactive');
    $message = get_option('go_image_renderer_license_message', '');

    if (!empty($message)) {
        if ($status === 'active') {
            echo '<div class="notice notice-success is-dismissible"><p>✅ Licence activée avec succès. <br/>' . esc_html($message) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Échec de l’activation de la licence. <br/>' . esc_html($message) . '</p></div>';
        }
    }
});
