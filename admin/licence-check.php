<?php
// Vérification de la licence via AJAX
add_action('wp_ajax_check_license', function() {
    check_ajax_referer('check_license_nonce', 'nonce');

    $license_key = sanitize_text_field($_POST['license_key']);
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
        wp_send_json([
            'success' => false,
            'message' => "Erreur de communication avec le serveur de licence."
        ]);
    } else {
        $data = json_decode(wp_remote_retrieve_body($response), true);
        wp_send_json($data);
    }
});
