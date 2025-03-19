<?php
// Par exemple, lors de la sauvegarde des réglages du plugin où l'utilisateur fournit la clé :
$license_key = sanitize_text_field( get_option('go_image_renderer_license_key') );  // ou valeur fournie par l'utilisateur
$domain      = home_url();  // obtenir le domaine du site

// Préparation de la requête vers le serveur de licence
$endpoint_url = "https://grow-online.be/licences-check/go-image-renderer-licence-check.php";  // URL de l'endpoint PHP sur le serveur Infomaniak

$response = wp_remote_post($endpoint_url, [
    'timeout' => 15,
    'body'    => [
        'license_key' => $license_key,
        'domain'      => $domain
    ]
]);

if (is_wp_error($response)) {
    // Erreur de communication avec le serveur
    $error_message = $response->get_error_message();
    echo "Erreur de vérification de la licence : $error_message";
} else {
    $data = json_decode( wp_remote_retrieve_body($response), true );
    if (!empty($data['success']) && $data['success'] === true) {
        // Succès : la licence est valide et activée
        // Par exemple, on peut mettre à jour une option pour mémoriser que la licence est activée
        update_option('go_image_renderer_license_status', 'active');
        echo "Licence activée avec succès sur ce domaine.";
    } else {
        // Échec : la licence est invalide ou déjà utilisée
        $message = !empty($data['message']) ? $data['message'] : "Validation de licence échouée.";
        update_option('go_image_renderer_license_status', 'inactive');
        echo "Échec de l'activation de la licence : " . esc_html($message);
    }
}

function go_image_renderer_schedule_license_check() {
    if (!wp_next_scheduled('go_image_renderer_check_license')) {
        wp_schedule_event(time(), 'weekly', 'go_image_renderer_check_license');
    }
}
add_action('wp', 'go_image_renderer_schedule_license_check');

add_action('go_image_renderer_check_license', function () {
    $license_key = get_option('go_image_renderer_license_key');
    if (!$license_key) return;

    $is_valid = go_image_renderer_validate_license($license_key);
    update_option('go_image_renderer_license_status', $is_valid ? 'active' : 'inactive');
});



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


add_action('wp_ajax_check_license', function() {
    check_ajax_referer('check_license_nonce', 'nonce');

    $license_key = sanitize_text_field($_POST['license_key']);
    $domain = home_url(); // Prends l'URL du site
    $endpoint_url = "https://grow-online.be/licences-check/go-image-renderer-licence-check.php";

    $response = wp_remote_post($endpoint_url, [
        'timeout' => 15,
        'body'    => [
            'license_key' => $license_key,
            'domain'      => $domain
        ]
    ]);

    if (is_wp_error($response)) {
        wp_send_json([
            'success' => false,
            'message' => "Erreur de communication avec le serveur de licence."
        ]);
    } else {
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!empty($data['success']) && $data['success'] === true) {
            wp_send_json([
                'success' => true,
                'message' => $data['message']
            ]);
        } else {
            wp_send_json([
                'success' => false,
                'message' => !empty($data['message']) ? $data['message'] : "Validation de licence échouée."
            ]);
        }
    }
});
