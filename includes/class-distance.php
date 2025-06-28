<?php
if (!defined('ABSPATH')) exit;

class CityCourier_DistanceCalculator {
    /**
     * @param string $origin      Form’dan gelen adres_from
     * @param string $destination Form’dan gelen address_to
     * @return array|WP_Error     ['distance_km'=>float, 'duration'=>string] veya WP_Error
     */
    public function calculate_distance($origin, $destination) {
        $api_key = get_option('citycourier_google_api_key', '');

        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?units=metric"
             . "&origins="      . urlencode($origin)
             . "&destinations=" . urlencode($destination)
             . "&key="          . $api_key;

        $response = wp_remote_get($url, [ 'timeout' => 15 ]);

if ( is_wp_error($response) ) {
    // Orijinal WP_Error'ı döndür
    return $response;
}

$code = wp_remote_retrieve_response_code($response);
if ( 200 !== (int) $code ) {
    return new WP_Error(
      'api_http_code',
      sprintf('HTTP kodu %d alındı.', $code)
    );
}

$body = json_decode(wp_remote_retrieve_body($response), true);
if ( empty($body['rows'][0]['elements'][0]['distance']) || $body['status'] !== 'OK' ) {
    $err = $body['error_message'] ?? 'Google mesafe bilgisi alınamadı.';
    return new WP_Error('api_error', $err);
}

        $elem = $body['rows'][0]['elements'][0];
        if ($elem['status'] !== 'OK') {
            return new WP_Error('elem_error', $elem['status']);
        }

        return [
            'distance_km' => round($elem['distance']['value'] / 1000, 2),
            'duration'    => $elem['duration']['text'],
        ];
    }
}
