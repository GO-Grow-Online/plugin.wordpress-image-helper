<?php

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $settings_link = '<a href="admin.php?page=go-image-renderer-license">' . __('Configuration') . '</a>';
    array_unshift($links, $settings_link); // Ajoute le lien en premier
    return $links;
});

// Ajouter la page "Configuration" sous "Réglages"
add_action('admin_menu', function () {
    add_menu_page(
        'Configuration de Image Renderer',
        'GO - Image Renderer',
        'manage_options',
        'go-image-renderer-license',
        'go_image_renderer_license_page',
        'dashicons-admin-generic', // Icône du menu
        80 // Position dans le menu
    );
});

// Affichage de la page "Configuration"
function go_image_renderer_license_page() {
    ?>
    <div class="wrap">
        <h1>Activation du Plugin</h1>
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

// Ajouter les champs d'activation de la licence
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