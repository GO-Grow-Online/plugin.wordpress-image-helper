<?php
// Par exemple, lors de la sauvegarde des réglages du plugin où l'utilisateur fournit la clé :
$license_key = sanitize_text_field( get_option('mon_plugin_license_key') );  // ou valeur fournie par l'utilisateur
$domain      = $_SERVER['SERVER_NAME'];  // ou home_url() pour obtenir le domaine du site

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
        update_option('mon_plugin_license_status', 'active');
        echo "Licence activée avec succès sur ce domaine.";
    } else {
        // Échec : la licence est invalide ou déjà utilisée
        $message = !empty($data['message']) ? $data['message'] : "Validation de licence échouée.";
        update_option('mon_plugin_license_status', 'inactive');
        echo "Échec de l'activation de la licence : " . esc_html($message);
    }
}

function mon_plugin_schedule_license_check() {
    if (!wp_next_scheduled('mon_plugin_check_license')) {
        wp_schedule_event(time(), 'weekly', 'mon_plugin_check_license');
    }
}
add_action('wp', 'mon_plugin_schedule_license_check');

add_action('mon_plugin_check_license', function () {
    $license_key = get_option('mon_plugin_license_key');
    if (!$license_key) return;

    $is_valid = mon_plugin_validate_license($license_key);
    update_option('mon_plugin_license_status', $is_valid ? 'active' : 'inactive');
});

