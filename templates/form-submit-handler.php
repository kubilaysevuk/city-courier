<?php
if (!defined('ABSPATH')) exit;


add_action('template_redirect', function () {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    // 1. KURYE DEĞERLENDİRME FORMU
    if (
        isset($_POST['form_type']) &&
        $_POST['form_type'] === 'review' &&
        isset($_POST['courier_rating'], $_POST['order_id']) &&
        wp_verify_nonce($_POST['cc_review_nonce'] ?? '', 'cc_review_submit')
    ) {
        $order_id = absint($_POST['order_id']);
        $rating   = intval($_POST['courier_rating']);
        $review   = sanitize_textarea_field($_POST['courier_review']);

        update_post_meta($order_id, '_cc_review_rating', $rating);
        update_post_meta($order_id, '_cc_review_text',   $review);

        wp_safe_redirect(add_query_arg([
            'reviewed' => '1',
            'order_id' => $order_id
        ], get_permalink()));
        exit;
    }

    // 2. KURYE SİPARİŞ FORMU
    if (
        isset($_POST['form_type']) &&
        $_POST['form_type'] === 'delivery' &&
        !empty($_POST['address_from']) &&
        !empty($_POST['address_to']) &&
        wp_verify_nonce($_POST['cc_nonce'] ?? '', 'citycourier_form_submit')
    ) {
        // Google API kontrol
        $api_key = get_option('citycourier_google_api_key', '');
        if (!$api_key) {
            wp_send_json_error('Google API Key eksik. Lütfen ayarlardan doldurun.');
        }

        // Verileri temizle
        $origin            = sanitize_text_field($_POST['address_from']);
        $destination       = sanitize_text_field($_POST['address_to']);
        $pickup_phone      = sanitize_text_field($_POST['pickup_phone']);
        $delivery_phone    = sanitize_text_field($_POST['delivery_phone']);
        $sender_name       = sanitize_text_field($_POST['sender_name']);
        $user_name         = sanitize_text_field($_POST['user_name']);
        $user_email        = sanitize_email($_POST['user_email']);
        $pickup_details    = sanitize_textarea_field($_POST['pickup_details']);
        $delivery_details  = sanitize_textarea_field($_POST['delivery_details']);
        $weight            = sanitize_text_field($_POST['weight']);
        $package_content   = sanitize_text_field($_POST['package_content']);
        $payment_method    = sanitize_text_field($_POST['payment']);

        // Mesafe hesapla
        require_once CITYCOURIER_PATH . 'includes/class-distance.php';
        $calc = new CityCourier_DistanceCalculator();
        $distance = $calc->calculate_distance($origin, $destination);
        if (is_wp_error($distance)) {
            wp_send_json_error('Google mesafe bilgisi alınamadı: ' . $distance->get_error_message());
        }

        // Ücret hesapla
        $km_price  = (float)get_option('citycourier_km_price', 0);
        $min_price = (float)get_option('citycourier_minimum_price', 0);
        $total_fee = round($min_price + ($distance['distance_km'] * $km_price), 2);

        if (!function_exists('wc_create_order')) {
            wp_send_json_error('WooCommerce yüklü değil.');
        }

        // Sipariş oluştur
        $order = wc_create_order();
        $product_id = (int)get_option('citycourier_product_id', 0);
        $order->add_product(wc_get_product($product_id), 1, [
            'subtotal' => $total_fee,
            'total'    => $total_fee
        ]);

        // Meta veriler
        $meta = [
            '_cc_address_from'     => $origin,
            '_cc_address_to'       => $destination,
            '_cc_pickup_phone'     => $pickup_phone,
            '_cc_delivery_phone'   => $delivery_phone,
            '_cc_sender_name'      => $sender_name,
            '_cc_user_name'        => $user_name,
            '_cc_user_email'       => $user_email,
            '_cc_pickup_details'   => $pickup_details,
            '_cc_delivery_details' => $delivery_details,
            '_cc_weight'           => $weight,
            '_cc_package_content'  => $package_content,
            '_cc_payment_method'   => $payment_method,
            '_cc_total_price'      => $total_fee,
            '_cc_duration'         => sanitize_text_field($distance['duration']),
        ];
        foreach ($meta as $key => $val) {
            $order->update_meta_data($key, $val);
        }

        $order->calculate_totals();
        $order->save();

		$notify = get_option('citycourier_notify_whatsapp');
        $whatsapp_number = preg_replace('/[^0-9]/', '', get_option('citycourier_whatsapp_number'));

        if ($notify && $whatsapp_number) {
            $text = "📦 Yeni Kurye Siparişi!\n\n"
                  . "🚚 Gönderici: $sender_name\n"
                  . "📍 Nereden: $origin\n"
                  . "📍 Nereye: $destination\n"
                  . "📦 İçerik: $package_content\n"
                  . "⚖️ Ağırlık: $weight\n"
                  . "💰 Ücret: ₺$total_fee\n"
                  . "📨 E-posta: $user_email\n"
                  . "⏱️ Süre: " . $distance['duration'] . "\n"
                  . "📝 Ödeme: $payment_method\n\n"
                  . "Sipariş ID: #" . $order->get_id();

            $whatsapp_url = "https://wa.me/$whatsapp_number?text=" . rawurlencode($text);

            // Yönlendirme değil: loglama, aksiyon, webhook, vs.
            // wp_redirect($whatsapp_url); exit; // yönlendirmek istersen

            // Admin için logla veya not bırak
            update_post_meta($order->get_id(), '_cc_whatsapp_link', $whatsapp_url);
        }

        // Başarıyla yanıt dön
        wp_send_json_success([
            'redirect_url' => site_url('/kurye-takip/?order_id=' . $order->get_id())
        ]);
        exit;
    }

});

