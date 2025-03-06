<?php

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $settings_link = '<a href="admin.php?page=mon-plugin-license">' . __('Configuration') . '</a>';
    array_unshift($links, $settings_link); // Ajoute le lien en premier
    return $links;
});

// Ajouter la page "Configuration" sous "Réglages"
add_action('admin_menu', function () {
    add_menu_page(
        'Configuration du Plugin',
        'Mon Plugin',
        'manage_options',
        'mon-plugin-license',
        'mon_plugin_license_page',
        'dashicons-admin-generic', // Icône du menu
        80 // Position dans le menu
    );
});

// Affichage de la page "Configuration"
function mon_plugin_license_page() {
    ?>
    <div class="wrap">
        <h1>Activation du Plugin</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('mon_plugin_licence_group');
            do_settings_sections('mon-plugin-license');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Ajouter les champs d'activation de la licence
add_action('admin_init', function () {
    register_setting('mon_plugin_licence_group', 'mon_plugin_license_key');

    add_settings_section(
        'mon_plugin_licence_section',
        'Entrez votre clé de licence',
        null,
        'mon-plugin-license'
    );

    add_settings_field(
        'mon_plugin_license_key',
        'Clé de licence',
        function () {
            $value = get_option('mon_plugin_license_key', '');
            echo '<input type="text" name="mon_plugin_license_key" value="' . esc_attr($value) . '" class="regular-text">';
        },
        'mon-plugin-license',
        'mon_plugin_licence_section'
    );
});