<?php

// Ajoute un lien vers la page de licence dans les actions du plugin
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=go-image-renderer-license')) . '">' . __('Configuration', 'text-domain') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});

// Ajoute la page de sous-menu pour la configuration de la licence
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

// Affichage de la page de configuration de la licence
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
        <div id="license-validation-message" style="margin-top: 10px;"></div>
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

// Ajout du script AJAX pour la vérification instantanée de la licence
add_action('admin_footer', function () {
    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        let inputField = document.querySelector('input[name="go_image_renderer_license_key"]');
        let messageDiv = document.getElementById('license-validation-message');

        if (inputField) {
            inputField.addEventListener('blur', function() {
                let licenseKey = inputField.value.trim();
                if (licenseKey.length === 0) {
                    messageDiv.innerHTML = "<p style='color: red;'>Veuillez entrer une clé de licence.</p>";
                    return;
                }

                messageDiv.innerHTML = "<p style='color: blue;'>Vérification en cours...</p>";

                fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "action=check_license&license_key=" + encodeURIComponent(licenseKey) + "&nonce=<?php echo wp_create_nonce('check_license_nonce'); ?>"
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageDiv.innerHTML = "<p style='color: green;'>✔ " + data.message + "</p>";
                    } else {
                        messageDiv.innerHTML = "<p style='color: red;'>❌ " + data.message + "</p>";
                    }
                })
                .catch(error => {
                    messageDiv.innerHTML = "<p style='color: red;'>Erreur lors de la vérification.</p>";
                    console.error(error);
                });
            });
        }
    });
    </script>
    <?php
});
