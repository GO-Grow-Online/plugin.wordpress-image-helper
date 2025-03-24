<?php

// Add a link to plugin's "setting" page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=go-image-renderer-license')) . '">' . __('Configuration', 'text-domain') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});


add_filter('cron_schedules', function ($schedules) {
    $schedules['every_minute'] = [
        'interval' => 60, // 1 min.
        'display'  => __('Toutes les minutes')
    ];
    return $schedules;
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
    ?>
    <div class="wrap">
        <h1>Licence - Image Renderer</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('go_image_renderer_licence_group');
            do_settings_sections('go-image-renderer-license');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register required field for licence activation
add_action('admin_init', function () {
    register_setting('go_image_renderer_licence_group', 'go_image_renderer_license_key', [
        'sanitize_callback' => 'go_image_renderer_validate_license_key'
    ]);

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

// Check licence when saving
function go_image_renderer_validate_license_key($license_key) {
    $license_key = sanitize_text_field($license_key);
    $domain = home_url();
    $endpoint_url = "https://grow-online.be/licences-check/go-image-renderer-licence-check.php";

    // Delete previous values to avoid cache issues
    delete_option('go_image_renderer_license_status');
    delete_option('go_image_renderer_license_message');

    $response = wp_remote_post($endpoint_url, [
        'timeout' => 15,
        'body' => [
            'license_key' => $license_key,
            'domain'      => $domain
        ]
    ]);

    if (is_wp_error($response)) {
        update_option('go_image_renderer_license_status', 'inactive');
        update_option('go_image_renderer_license_message', 'Erreur de communication avec le serveur de licence.');
        return $license_key;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($data['success']) && $data['success'] === true) {
        update_option('go_image_renderer_license_status', 'active');
        update_option('go_image_renderer_license_message', $data['message']);
    } else {
        update_option('go_image_renderer_license_status', 'inactive');
        update_option('go_image_renderer_license_message', $data['message'] ?? 'Licence invalide.');
    }

    wp_cache_flush(); // Force update

    return $license_key;
}

// Display notification
add_action('admin_notices', function () {
    if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
        wp_cache_flush();

        $status = get_option('go_image_renderer_license_status', 'inactive');
        $message = get_option('go_image_renderer_license_message', '');

        if ($status === 'active') {
            echo '<div class="notice notice-success is-dismissible"><p>✅ Licence activée avec succès. ' . esc_html($message) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Échec de l’activation de la licence. ' . esc_html($message) . '</p></div>';
        }
    }
});

// Monthly verification
add_action('wp', function () {
    if (!wp_next_scheduled('go_image_renderer_monthly_license_check')) {
        wp_schedule_event(time(), 'every_minute', 'go_image_renderer_monthly_license_check');
    }
});

add_action('go_image_renderer_monthly_license_check', 'go_image_renderer_check_license_status');

function go_image_renderer_check_license_status() {
    $license_key = get_option('go_image_renderer_license_key', '');
    $domain = home_url();
    $endpoint_url = "https://grow-online.be/licences-check/go-image-renderer-licence-check.php";

    if (empty($license_key)) {
        return;
    }

    $response = wp_remote_post($endpoint_url, [
        'timeout' => 15,
        'body'    => [
            'license_key' => $license_key,
            'domain'      => $domain
        ]
    ]);

    if (is_wp_error($response)) {
        update_option('go_image_renderer_license_status', 'inactive');
        return;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($data['success']) && $data['success'] === true) {
        update_option('go_image_renderer_license_status', 'active');
    } else {
        update_option('go_image_renderer_license_status', 'inactive');
    }
}

// Handling the sheduled verification with plugin activation/disactivation
register_activation_hook(__FILE__, function () {
    go_image_renderer_schedule_license_check();
});

register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('go_image_renderer_monthly_license_check');
});