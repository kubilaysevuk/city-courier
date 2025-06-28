<?php
if (!defined('ABSPATH')) exit;

add_action('admin_init', 'citycourier_register_settings');
function citycourier_register_settings() {
    register_setting('citycourier_settings_group', 'citycourier_email');
    register_setting('citycourier_settings_group', 'citycourier_google_api_key');
    register_setting('citycourier_settings_group', 'citycourier_origin_address');
    register_setting('citycourier_settings_group', 'citycourier_km_price');
    register_setting('citycourier_settings_group', 'citycourier_minimum_price');
    register_setting('citycourier_settings_group', 'citycourier_whatsapp_number');
    register_setting('citycourier_settings_group', 'citycourier_default_delivery_slots');
    register_setting('citycourier_settings_group', 'citycourier_notify_admin');
    register_setting('citycourier_settings_group', 'citycourier_notify_whatsapp');
    register_setting('citycourier_settings_group', 'citycourier_package_extra_prices');
}







function citycourier_settings_page_html() {
    $domain = $_SERVER['HTTP_HOST'];
    $license_status = null;
    $license_expiry = null;

    $response = wp_remote_get("https://licenses.gksoft.com.tr/check-license.php?domain=" . urlencode($domain));
    if (!is_wp_error($response)) {
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $license_status = $data['status'] ?? null;
        $license_expiry = $data['expires'] ?? null;

        if (in_array($license_status, ['valid', 'expired', 'not_found'], true)) {
            update_option('citycourier_license_status', $license_status);
        }
    }

    if ($license_status === 'valid') {
        $days_left = '';
        if ($license_expiry) {
            $expire_ts = strtotime($license_expiry);
            $now_ts = current_time('timestamp');
            $diff_days = floor(($expire_ts - $now_ts) / 86400);
            $days_left = " (Kalan: $diff_days g\u00fcn)";
        }
        echo '<div class="notice notice-success is-dismissible"><p><strong>Pro lisans aktif.</strong> Tum ozellikler kullanilabilir.' . esc_html($days_left) . '</p></div>';
    } elseif ($license_status === 'expired') {
        echo '<div class="notice notice-error is-dismissible"><p><strong>\u274c Lisans suresi dolmus.</strong> L\u00fctfen yenileyin.</p></div>';
    } elseif ($license_status === 'not_found') {
        echo '<div class="notice notice-warning is-dismissible"><p><strong>\u26a0\ufe0f Lisans bulunamadi.</strong> Domain kontrol edin.</p></div>';
    } elseif ($license_status) {
        echo '<div class="notice notice-error is-dismissible"><p><strong>\u26a0\ufe0f Lisans dogrulanamadi.</strong></p></div>';
    }

    $is_premium = ($license_status === 'valid');

    echo '<div class="wrap">';
    echo '<h1>CityCourier Ayarlar</h1>';
    if ($license_status) {
        echo '<p><strong>Lisans Durumu:</strong> ' . esc_html(strtoupper($license_status)) . '</p>';
        if ($license_expiry) {
            echo '<p><strong>Bitis Tarihi:</strong> ' . esc_html(date('d M Y', strtotime($license_expiry))) . '</p>';
        }
    }

    ?>
    <div class="wrap">
        <h1>CityCourier Ayarları</h1>
        <form method="post" action="options.php">
            <?php settings_fields('citycourier_settings_group'); ?>
            <?php do_settings_sections('citycourier_settings_group'); ?>

            <table class="form-table">
                <tr>
                    <th>Google Maps API Anahtarı</th>
                    <td><input type="text" name="citycourier_google_api_key" value="<?php echo esc_attr(get_option('citycourier_google_api_key')); ?>" style="width: 400px;" /></td>
                </tr>
                <tr>
                    <th>Başlangıç (Firma) Adresi</th>
                    <td><input type="text" name="citycourier_origin_address" value="<?php echo esc_attr(get_option('citycourier_origin_address')); ?>" style="width: 400px;" /></td>
                </tr>
                <tr>
                    <th>Km Başına Ücret (₺)</th>
                    <td><input type="number" name="citycourier_km_price" value="<?php echo esc_attr(get_option('citycourier_km_price')); ?>" step="0.01" /></td>
                </tr>
                <tr>
                    <th>Minimum Kurye Ücreti (₺)</th>
                    <td><input type="number" name="citycourier_minimum_price" value="<?php echo esc_attr(get_option('citycourier_minimum_price')); ?>" step="0.01" /></td>
                </tr>
                <tr>
                    <th>Varsayılan WhatsApp Numarası</th>
                    <td><input type="text" name="citycourier_whatsapp_number" value="<?php echo esc_attr(get_option('citycourier_whatsapp_number')); ?>" placeholder="90XXXXXXXXXX" /></td>
                </tr>
                
				<tr>
    <th>
        WhatsApp Bildirimi Aktif
        <?php if (!$is_premium): ?>
            <br><small style="color:red;">(Yalnızca Pro sürümde kullanılabilir)</small>
        <?php endif; ?>
    </th>
    <td style="display: flex; align-items: center; gap: 20px;">
        <div style="<?php if (!$is_premium) echo 'filter: blur(1px); opacity: 0.6; pointer-events: none;'; ?>">
            <input type="checkbox" name="citycourier_notify_whatsapp" value="1"
                <?php checked(1, get_option('citycourier_notify_whatsapp'), true); ?>
                <?php if (!$is_premium) echo 'disabled'; ?> />
            WhatsApp'tan müşteri/kurye bilgilendir
        </div>
        <?php if (!$is_premium): ?>
            <a href="<?php echo admin_url('admin.php?page=citycourier-upgrade'); ?>" class="button button-secondary">
                🔒 Yükselt
            </a>
        <?php endif; ?>
    </td>
</tr>
            </table>

            <?php submit_button('Ayarları Kaydet'); ?>
        </form>
    </div>
    <?php
}


// Save driver coordinates via AJAX
add_action('wp_ajax_citycourier_update_location', 'citycourier_update_driver_location');
add_action('wp_ajax_nopriv_citycourier_update_location', '__return_false');

function citycourier_update_driver_location() {
    if (!is_user_logged_in() || !current_user_can('read')) {
        wp_send_json_error('Unauthorized');
    }
    $lat = sanitize_text_field($_POST['lat'] ?? '');
    $lng = sanitize_text_field($_POST['lng'] ?? '');
    $user_id = get_current_user_id();

    if ($lat && $lng) {
        update_user_meta($user_id, '_cc_driver_lat', $lat);
        update_user_meta($user_id, '_cc_driver_lng', $lng);
        update_user_meta($user_id, '_cc_driver_updated_at', current_time('mysql'));
        wp_send_json_success('Konum güncellendi');
    } else {
        wp_send_json_error('Eksik veri');
    }
}

// Cron job to notify admin if driver inactive for 5+ min
add_action('citycourier_check_driver_inactive', function () {
    $couriers = get_users(['role' => 'driver']);
    $now = current_time('timestamp');
    foreach ($couriers as $courier) {
        $last = strtotime(get_user_meta($courier->ID, '_cc_driver_updated_at', true));
        if (!$last || ($now - $last) > 300) {
            wp_mail(get_option('admin_email'), 'Kurye Takip Uyarısı', $courier->display_name . ' son 5 dakikadır konum güncellemedi.');
        }
    }
});
if (!wp_next_scheduled('citycourier_check_driver_inactive')) {
    wp_schedule_event(time(), 'minute', 'citycourier_check_driver_inactive');
}

// Admin panelde uyarı ve zaman hesaplama
function citycourier_time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    if ($diff < 60) return $diff . ' sn önce';
    elseif ($diff < 3600) return floor($diff / 60) . ' dk önce';
    elseif ($diff < 86400) return floor($diff / 3600) . ' saat önce';
    else return date('d M Y H:i', $timestamp);
}

add_action('admin_notices', function () {
    if (!function_exists('citycourier_is_premium_user') || !citycourier_is_premium_user()) return;

    $screen = get_current_screen();
    if ($screen && $screen->id === 'toplevel_page_citycourier-settings') {
        $couriers = get_users(['role' => 'driver']);
        $now = current_time('timestamp');
        foreach ($couriers as $courier) {
            $last = strtotime(get_user_meta($courier->ID, '_cc_driver_updated_at', true));
            if (!$last || ($now - $last) > 300) {
                echo '<div class="notice notice-warning is-dismissible"><p><strong>Uyarı:</strong> ' . esc_html($courier->display_name) . ' son 5 dakikadır konum göndermiyor.</p></div>';
            }
        }
    }
});












function citycourier_orders_page_html() {
    // Handle form submission before any output to avoid headers already sent
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['assign_courier_order_id']) ) {
        // Manual nonce check to prevent WP die on failure
        $nonce = isset($_POST['assign_courier_nonce']) ? $_POST['assign_courier_nonce'] : '';
        if ( ! wp_verify_nonce($nonce, 'assign_courier_action') ) {
            wp_safe_redirect(admin_url('admin.php?page=citycourier-orders&error=nonce'));
            exit;
        }

        $order_id   = absint($_POST['assign_courier_order_id']);
        $courier_id = absint($_POST['courier_id']);
        $order      = wc_get_order($order_id);
        $courier    = get_userdata($courier_id);

        if ( $order && $courier && ! is_wp_error($courier) ) {
            $order->update_meta_data('_cc_assigned_courier', $courier_id);
            $order->set_status('processing');
            $order->save();

            wp_safe_redirect(admin_url('admin.php?page=citycourier-orders&assigned=1'));
            exit;
        }

        // Fallback error
        wp_safe_redirect(admin_url('admin.php?page=citycourier-orders&error=assign'));
        exit;
    }

    // Begin output
    echo '<div class="wrap"><h1>Kurye Siparişleri</h1>';

    // Show notices after redirect
    if ( isset($_GET['assigned']) ) {
        echo '<div class="updated notice"><p>Kurye başarıyla atandı.</p></div>';
    } elseif ( isset($_GET['error']) ) {
        $msg = $_GET['error'] === 'nonce'
             ? 'Güvenlik hatası. Lütfen sayfayı yenileyip tekrar deneyin.'
             : 'Kurye atama hatası. Lütfen tekrar deneyin.';
        echo '<div class="error notice"><p>' . esc_html($msg) . '</p></div>';
    }

    // Fetch orders and couriers
    $orders   = wc_get_orders([
        'limit'    => 50,
        'orderby'  => 'date',
        'order'    => 'DESC',
        'meta_key' => '_cc_address_from',
    ]);
    $couriers = get_users(['role' => 'driver']);

    if ( empty($orders) ) {
        echo '<p>Henüz sipariş yok.</p></div>';
        return;
    }

    echo '<table class="widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th><th>Adres (Gönderici→Alıcı)</th>
                <th>İçerik</th><th>Ağırlık</th><th>Tutar</th>
                <th>Kurye</th><th>Durum</th><th>İşlem</th>
            </tr>
        </thead>
        <tbody>';

    foreach ( $orders as $order ) {
        $id            = $order->get_id();
        $from          = esc_html($order->get_meta('_cc_address_from'));
        $to            = esc_html($order->get_meta('_cc_address_to'));
        $package       = esc_html($order->get_meta('_cc_package_content'));
        $weight        = esc_html($order->get_meta('_cc_weight'));
        $total         = esc_html($order->get_meta('_cc_total_price'));
        $assigned_id   = $order->get_meta('_cc_assigned_courier');
        $assigned_user = $assigned_id ? get_userdata($assigned_id) : false;
        $courier_name  = ( $assigned_user && ! is_wp_error($assigned_user) )
                         ? esc_html($assigned_user->display_name)
                         : '<em>Atanmadı</em>';
        $status        = esc_html(wc_get_order_status_name($order->get_status()));

        echo '<tr>' .
             '<td><a href="' . admin_url("post.php?post=$id&action=edit") . '">#' . $id . '</a></td>' .
             "<td>$from → $to</td>" .
             "<td>$package</td><td>$weight</td><td>₺$total</td>" .
             "<td>$courier_name</td><td>$status</td>" .
             '<td>'; 

        // Form and nonce
        echo '<form method="post" style="display:flex; gap:5px;">';
        echo '<input type="hidden" name="assign_courier_nonce" value="' . wp_create_nonce('assign_courier_action') . '" />';
        echo '<input type="hidden" name="assign_courier_order_id" value="' . esc_attr($id) . '" />' .
             '<select name="courier_id">';

        foreach ( $couriers as $courier ) {
            $selected = ( $assigned_id == $courier->ID ) ? 'selected' : '';
            echo '<option value="' . esc_attr($courier->ID) . '" ' . $selected . '>' . esc_html($courier->display_name) . '</option>';
        }

        echo '</select>' .
             '<button type="submit" class="button button-primary">Kurye Ata</button>' .
             '</form>';

        echo '</td></tr>';
    }

    echo '</tbody></table></div>';
}




function citycourier_couriers_page_html() {
	echo '<pre>Lisans durumu: ' . esc_html(get_option('citycourier_license_status')) . '</pre>';

 $is_premium = citycourier_is_premium_user();
    echo '<div class="wrap"><h1>Kurye Listesi</h1>';

    $couriers = get_users(['role' => 'driver']);

    if ($is_premium) {
    echo '<div id="map" style="width: 100%; height: 400px; margin-bottom: 20px;"></div>';
} else {
    echo '<div style="position: relative; width: 100%; height: 400px; margin-bottom: 20px;">
    <img src="https://test.gksoft.com.tr/wp-content/uploads/2025/06/kurye.png" alt="premium-harita" style="width:100%; height:100%; object-fit: cover; filter: blur(2px); border: 1px solid #ccc;" />
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(255,255,255,0.85); padding: 20px 30px; border-radius: 12px; font-size: 18px; font-weight: bold; text-align: center;">
        🔒 Harita sadece <strong>Premium</strong> kullanıcılar için aktif<br><br>
        <a href="' . admin_url('admin.php?page=citycourier-upgrade') . '" class="button button-primary">Premium Sürümü Yükselt</a>
    </div>
</div>';

}

    echo '<table class="widefat fixed striped">
    <thead><tr>
        <th>ID</th>
        <th>İsim</th>
        <th>Email</th>
        <th>Durum</th>
        <th>Konum</th>
        <th>Güncelleme</th>
    </tr></thead><tbody>';

foreach ($couriers as $courier) {
    $available = get_user_meta($courier->ID, '_cc_driver_available', true);
    $status = $available === '1' ? '✅ Uygun' : '❌ Meşgul';
    $lat = get_user_meta($courier->ID, '_cc_driver_lat', true);
    $lng = get_user_meta($courier->ID, '_cc_driver_lng', true);
    $updated = get_user_meta($courier->ID, '_cc_driver_updated_at', true);

    $loc_html = ($lat && $lng) ? '<a href="https://maps.google.com/?q=' . esc_attr($lat) . ',' . esc_attr($lng) . '" target="_blank">📍 Göster</a>' : '-';
    $updated_html = esc_html($updated ?: '-');

    echo '<tr>
        <td>' . $courier->ID . '</td>
        <td>' . esc_html($courier->display_name) . '</td>
        <td>' . esc_html($courier->user_email) . '</td>
        <td>' . $status . '</td>';

    if ($is_premium) {
        echo '<td>' . $loc_html . '</td>';
        echo '<td>' . $updated_html . '</td>';
    } else {
		echo '<td>
    <div class="blurred-cell">
        📍 Konum<br>
        </div><a href="' . admin_url('admin.php?page=citycourier-upgrade') . '">Yükselt 🔓</a>
    
</td>';

echo '<td>
    <div class="blurred-cell">
        Zaman<br>
        </div><a href="' . admin_url('admin.php?page=citycourier-upgrade') . '">Yükselt 🔓</a>
    
</td>';

    }

    echo '</tr>';
}
echo '</tbody></table>';


    // HERKES İÇİN AÇIK: Kurye ekleme formu
    echo '<h2>Yeni Kurye Ekle</h2>
    <form method="post">
        <table class="form-table">
            <tr><th>İsim</th><td><input type="text" name="new_courier_name" required /></td></tr>
            <tr><th>Email</th><td><input type="email" name="new_courier_email" required /></td></tr>
            <tr><th>Şifre</th><td><input type="text" name="new_courier_password" required /></td></tr>
        </table>
        <button class="button button-primary">Kaydet</button>
    </form>';

    // HARİTA JS (yalnızca premium)
    if ($is_premium) {
        $map_data = array_map(function ($c) {
            $updated_at = get_user_meta($c->ID, '_cc_driver_updated_at', true);
            $diff = time() - strtotime($updated_at);
            $ago = $diff < 60 ? $diff . 'sn' : ($diff < 3600 ? floor($diff / 60) . 'dk' : floor($diff / 3600) . 'saat');
            return [
                'id' => $c->ID,
                'name' => $c->display_name,
                'lat' => get_user_meta($c->ID, '_cc_driver_lat', true),
                'lng' => get_user_meta($c->ID, '_cc_driver_lng', true),
                'ago' => $ago
            ];
        }, $couriers);

        $google_api_key = esc_js(get_option('citycourier_google_api_key'));

        echo '<script>
        let map;
        function initMap() {
            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 6,
                center: { lat: 39.9208, lng: 32.8541 },
                gestureHandling: "greedy"
            });
            renderMarkers();
            setInterval(renderMarkers, 30000);
        }

        function renderMarkers() {
            const drivers = ' . json_encode($map_data) . ';
            const bounds = new google.maps.LatLngBounds();
            drivers.forEach(driver => {
                if (driver.lat && driver.lng) {
                    const position = { lat: parseFloat(driver.lat), lng: parseFloat(driver.lng) };
                    const marker = new google.maps.Marker({
                        position: position,
                        map,
                        icon: {
                            url: "https://test.gksoft.com.tr/wp-content/uploads/2025/05/9889310a4faf07b4986dec649ce6bbfd.jpg",
                            scaledSize: new google.maps.Size(32, 32),
                            anchor: new google.maps.Point(16, 16)
                        },
                        title: `${driver.name} (güncel: ${driver.ago} önce)`,
                        label: {
                            text: driver.name,
                            fontSize: "12px",
                            fontWeight: "bold",
                            color: "#000",
                            className: "courier-label"
                        }
                    });

                    const infoWindow = new google.maps.InfoWindow({
                        content: `<strong>${driver.name}</strong><br>Son konum: ${driver.ago} önce`
                    });
                    marker.addListener("click", () => infoWindow.open(map, marker));
                    bounds.extend(position);
                }
            });
            map.fitBounds(bounds);
            google.maps.event.addListenerOnce(map, "bounds_changed", function () {
                if (map.getZoom() > 15) map.setZoom(15);
            });
        }
        </script>';

        echo '<style>.gm-style .courier-label { transform: translateY(-35px); }</style>';
        echo '<script async defer src="https://maps.googleapis.com/maps/api/js?key=' . $google_api_key . '&callback=initMap"></script>';
    }

    echo '</div>';
	
	echo '<style>
.blurred-cell {
    filter: blur(1px);
    pointer-events: none;
    color: #888;
}
.blurred-cell a {
    filter: none !important;
    pointer-events: auto !important;
    display: inline-block;
    margin-top: 5px;
    font-size: 13px;
}
</style>';
	

}






function citycourier_routes_page_html() {
    if (!current_user_can('manage_options')) return;

    echo '<div class="wrap"><h1>Aktif Güzergahlar</h1>';

    if (!citycourier_is_premium_user()) {
    echo '<div style="max-width: 800px; margin: 40px auto; position: relative; filter: blur(2px); pointer-events: none;">
        <div style="max-width: 700px; margin: 40px auto; font-family: sans-serif;">
            <h2><img draggable="false" role="img" class="emoji" alt="🚚" src="https://s.w.org/images/core/emoji/15.1.0/svg/1f69a.svg"> Kurye: deneme-kurye</h2>
            <ul>
                <li><strong>Gönderim:</strong> İncesu Yalı, Atatürk Bl. No:138, 55270 Atakum/Samsun, Türkiye 
                <strong>→</strong> <strong>Teslimat:</strong> Körfez, Hürriyet Blv. No:7, 55270 Atakum/Samsun, Türkiye 
                <span> <em>(1,8 km, 4 dakika)</em></span></li>
                <li><strong>Gönderim:</strong> Samsun, Türkiye 
                <strong>→</strong> <strong>Teslimat:</strong> Atakum/Samsun, Türkiye 
                <span> <em>(24,4 km, 34 dakika)</em></span></li>
            </ul>
            <div style="width: 100%; height: 400px; margin-top: 30px; background: url(\'https://test.gksoft.com.tr/wp-content/uploads/2025/06/map2.png\') center center / cover no-repeat; border-radius: 12px;"></div>
        </div>
        
        <div style="max-width: 700px; margin: 40px auto; font-family: sans-serif;">
            <h2><img draggable="false" role="img" class="emoji" alt="🚚" src="https://s.w.org/images/core/emoji/15.1.0/svg/1f69a.svg"> Kurye: testkurye1</h2>
            <ul>
                <li><strong>Gönderim:</strong> Çarşamba, Samsun, Türkiye 
                <strong>→</strong> <strong>Teslimat:</strong> Terme, Samsun, Türkiye 
                <span> <em>(31,8 km, 37 dakika)</em></span></li>
            </ul>
            <div style="width: 100%; height: 400px; margin-top: 30px; background: url(\'https://test.gksoft.com.tr/wp-content/uploads/2025/06/map2.png\') center center / cover no-repeat; border-radius: 12px;"></div>
        </div>
    </div>';

    echo '<div style="position: absolute; top: 80px; left: 50%; transform: translateX(-50%);
        background: rgba(255,255,255,0.95); padding: 30px 50px; text-align: center;
        border-radius: 12px; font-size: 17px; font-weight: 500; box-shadow: 0 0 12px rgba(0,0,0,0.2); z-index: 999;">
        Bu güzergah görüntüleme özelliği <strong>Premium</strong> kullanıcılar içindir. <br><br>
        <a href="' . admin_url('admin.php?page=citycourier-upgrade') . '" class="button button-primary">Premium Sürüm için Yükselt</a>
    </div>';
    
    return;
}


    $couriers = get_users(['role' => 'driver']);
    $api_key = esc_js(get_option('citycourier_google_api_key'));
    $map_scripts = "";
    $map_index = 0;

    foreach ($couriers as $courier) {
        $orders = wc_get_orders([
            'limit' => -1,
            'meta_key' => '_cc_assigned_courier',
            'meta_value' => $courier->ID,
            'status' => ['processing', 'on-hold']
        ]);

        if ($orders) {
            echo '<div style="max-width: 700px; margin: 40px auto; font-family: sans-serif;">';
            echo '<h2>🚚 Kurye: ' . esc_html($courier->display_name) . '</h2>';
            echo '<ul>';
            $addresses = [];
            foreach ($orders as $order) {
                $from = $order->get_meta('_cc_address_from');
                $to = $order->get_meta('_cc_address_to');
                echo '<li><strong>Gönderim:</strong> ' . esc_html($from) . ' <strong>→</strong> <strong>Teslimat:</strong> ' . esc_html($to) . ' <span id="duration-' . $map_index . '-' . count($addresses) . '"></span></li>';
                if ($from && $to) {
                    $addresses[] = ["from" => $from, "to" => $to];
                }
            }
            echo '</ul>';
            echo '<div id="map-' . $map_index . '" style="width: 100%; height: 400px; margin-top: 30px;"></div>';
            echo '</div>';

            $encoded_routes = json_encode($addresses);
            $colors = json_encode(['#007bff', '#28a745', '#ffc107', '#dc3545', '#6610f2']);

            $map_scripts .= "<script>
            function initCourierMap_{$map_index}() {
                const map = new google.maps.Map(document.getElementById('map-{$map_index}'), {
                    zoom: 7,
                    center: { lat: 39.9208, lng: 32.8541 },
                    gestureHandling: 'greedy'
                });
                const bounds = new google.maps.LatLngBounds();
                const geocoder = new google.maps.Geocoder();
                const directionsService = new google.maps.DirectionsService();
                const routes = {$encoded_routes};
                const colors = {$colors};

                if (routes.length === 0) return;
                let completed = 0;
                routes.forEach((pair, idx) => {
                    geocoder.geocode({ address: pair.from }, function(resultsFrom, statusFrom) {
                        if (statusFrom === 'OK') {
                            const fromLoc = resultsFrom[0].geometry.location;
                            geocoder.geocode({ address: pair.to }, function(resultsTo, statusTo) {
                                if (statusTo === 'OK') {
                                    const toLoc = resultsTo[0].geometry.location;
                                    new google.maps.Marker({ map, position: fromLoc, label: 'A' });
                                    new google.maps.Marker({ map, position: toLoc, label: 'B' });

                                    directionsService.route({
                                        origin: fromLoc,
                                        destination: toLoc,
                                        travelMode: 'DRIVING'
                                    }, (result, status) => {
                                        if (status === 'OK' && result.routes[0].overview_path.length > 0) {
                                            const routePath = new google.maps.Polyline({
                                                path: result.routes[0].overview_path,
                                                geodesic: true,
                                                strokeColor: colors[idx % colors.length],
                                                strokeOpacity: 0.8,
                                                strokeWeight: 4
                                            });
                                            routePath.setMap(map);

                                            const leg = result.routes[0].legs[0];
                                            const durationText = leg.distance.text + ', ' + leg.duration.text;
                                            const span = document.getElementById('duration-{$map_index}-' + idx);
                                            if (span) span.innerHTML = ' <em>(' + durationText + ')</em>';

                                            bounds.extend(fromLoc);
                                            bounds.extend(toLoc);
                                        }
                                        completed++;
                                        if (completed === routes.length && !bounds.isEmpty()) {
                                            setTimeout(() => map.fitBounds(bounds), 500);
                                        }
                                    });
                                } else {
                                    completed++;
                                    if (completed === routes.length && !bounds.isEmpty()) {
                                        setTimeout(() => map.fitBounds(bounds), 500);
                                    }
                                }
                            });
                        } else {
                            completed++;
                            if (completed === routes.length && !bounds.isEmpty()) {
                                setTimeout(() => map.fitBounds(bounds), 500);
                            }
                        }
                    });
                });
            }
            window.addEventListener('load', initCourierMap_{$map_index});
            </script>";

            $map_index++;
        }
    }

    echo $map_scripts;
    echo '<script async defer src="https://maps.googleapis.com/maps/api/js?key=' . $api_key . '&libraries=places"></script>';
    echo '</div>';
}





function citycourier_reports_page_html() {
    echo '<div class="wrap"><h1>CityCourier Raporlar</h1>';

    $orders = wc_get_orders([
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_key' => '_cc_address_from'
    ]);

    if (empty($orders)) {
        echo '<p>Henüz sipariş bulunamadı.</p>';
        return;
    }

    $is_premium = citycourier_is_premium_user();

    if (!$is_premium) {
        echo '<div style="position: relative; filter: blur(2px); pointer-events: none;">';
    }

    // === Siparişler Tablosu ===
    echo '<h2>Tüm Siparişler</h2>';
    echo '<table class="widefat fixed striped">
        <thead><tr><th>ID</th><th>Tarih</th><th>Kurye</th><th>Adres</th><th>Durum</th><th>Desi</th><th>Tutar</th><th>Puan</th><th>Yorum</th></tr></thead><tbody>';

    foreach ($orders as $order) {
        $id = $order->get_id();
        $courier_id = $order->get_meta('_cc_assigned_courier');
        $courier_name = $courier_id ? get_userdata($courier_id)->display_name : '-';
        $from = $order->get_meta('_cc_address_from');
        $to = $order->get_meta('_cc_address_to');
        $status = wc_get_order_status_name($order->get_status());
        $package       = esc_html($order->get_meta('_cc_package_content'));
        $total = $order->get_meta('_cc_total_price');
        $rating = get_post_meta($order->get_id(), '_cc_review_rating', true);
        $review = get_post_meta($order->get_id(), '_cc_review_text', true);
		
        echo '<tr>
            <td><a href="' . admin_url("post.php?post=$id&action=edit") . '">#' . $id . '</a></td>
            <td>' . $order->get_date_created()->date('d.m.Y H:i') . '</td>
            <td>' . esc_html($courier_name) . '</td>
            <td>' . esc_html($from) . ' → ' . esc_html($to) . '</td>
            <td>' . esc_html($status) . '</td>
            <td>' . esc_html($package) . '</td>
            <td>₺' . esc_html($total) . '</td>
            <td>' . ($rating ? '⭐' . $rating : '-') . '</td>
            <td>' . ($review ?: '-') . '</td>
        </tr>';
    }
    echo '</tbody></table>';

    // === Kurye Puan Ortalamaları ===
    echo '<h2 style="margin-top:40px;">Kurye Puan Ortalamaları</h2>';
    $couriers = get_users(['role' => 'driver']);
    echo '<table class="widefat fixed striped">
        <thead><tr><th>Kurye</th><th>Ortalama Puan</th><th>Sipariş Sayısı</th></tr></thead><tbody>';

    global $wpdb;

    foreach ($couriers as $courier) {
        $courier_id = $courier->ID;

        $order_ids = $wpdb->get_col($wpdb->prepare("
            SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_cc_assigned_courier'
            AND meta_value = %d
        ", $courier_id));

        if (empty($order_ids)) {
            echo '<tr><td>' . esc_html($courier->display_name) . '</td><td>-</td><td>0</td></tr>';
            continue;
        }

        $ratings = [];
        foreach ($order_ids as $order_id) {
            $rating = get_post_meta($order_id, '_cc_review_rating', true);
            if ($rating) {
                $ratings[] = (int)$rating;
            }
        }

        $avg = $ratings ? round(array_sum($ratings) / count($ratings), 2) : '-';
        $count = count($ratings);

        echo '<tr>
            <td>' . esc_html($courier->display_name) . '</td>
            <td>' . ($avg !== '-' ? '⭐ ' . $avg : '-') . '</td>
            <td>' . $count . '</td>
        </tr>';
    }
    echo '</tbody></table>';

    if (!$is_premium) {
        echo '</div>'; // blur kapsayıcıyı kapat

        echo '<div style="position: absolute; top: 80px; left: 50%; transform: translateX(-50%);
            background: rgba(255,255,255,0.95); padding: 30px 50px; text-align: center;
            border-radius: 12px; font-size: 17px; font-weight: 500; box-shadow: 0 0 12px rgba(0,0,0,0.2); z-index: 999;">
            Bu rapor sayfası sadece <strong>Premium kullanıcılar</strong> içindir. <br><br>
            <a href="' . admin_url('admin.php?page=citycourier-upgrade') . '" class="button button-primary">Yükselt</a>
        </div>';
    }

    echo '</div>';
}






function citycourier_upgrade_page_html() {
    ?>
    <div class="wrap" style="font-family: Arial, sans-serif;">
        <h1>🚀 CityCourier Premium</h1>
        <p>Mevcut sürüm: <strong><?php echo esc_html($status); ?></strong></p>


        <div style="display: flex; gap: 40px; margin-top: 30px;">
            <!-- Free -->
            <div style="flex: 1; border: 1px solid #ccc; padding: 20px; border-radius: 8px; background: #f9f9f9;">
                <h2>🥈 Free Sürüm</h2>
                <ul style="line-height: 1.8;">
                    <li>✔ Kurye atama</li>
                    <li>✔ A-B adres arası fiyat hesaplama</li>
                    <li>✔ Desi hesaplama</li>
                    <li>✔ WooCommerce entegrasyonu</li>
                </ul>
            </div>

            <!-- Pro -->
            <div style="flex: 1; border: 2px solid #0073aa; padding: 20px; border-radius: 8px; background: #e8f4ff;">
                <h2>🏆 Pro Sürüm <span style="font-size: 14px;">(Yıllık $50)</span></h2>
                <ul style="line-height: 1.8;">
                    <li>✅ Tüm Free özellikleri</li>
                    <li>✅ Kurye canlı konum takibi (WebSocket)</li>
                    <li>✅ Teslimat zaman dilimi seçimi</li>
                    <li>✅ Kullanıcı puan & yorum sistemi</li>
                    <li>✅ WhatsApp ile hızlı iletişim</li>
                    <li>✅ Admin panelde gelişmiş raporlar</li>
                    <li>✅ PDF / Excel sipariş dışa aktarımı</li>
                    <li>✅ Öncelikli teknik destek</li>
                </ul>

                <form method="post" action="https://gksoft.com.tr/satin-al">
                    <input type="hidden" name="product" value="citycourier-pro">
                    <button type="submit" class="button button-primary button-hero" style="margin-top: 20px; font-size: 16px;">💳 Şimdi Satın Al</button>
                </form>
            </div>
        </div>
    </div>
    <?php
}



function citycourier_contact_page_html() {
    ?>
    <div class="wrap" style="max-width:700px;">
        <h1>📨 CityCourier İletişim</h1>
        <p>Bir sorunla mı karşılaştınız veya öneriniz mi var? Aşağıdaki formu doldurarak bizimle iletişime geçebilirsiniz.</p>

        <form method="post" action="" style="margin-top:20px;">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="cc_contact_name">Adınız</label></th>
                    <td><input name="cc_contact_name" type="text" id="cc_contact_name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="cc_contact_email">E-posta</label></th>
                    <td><input name="cc_contact_email" type="email" id="cc_contact_email" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="cc_contact_message">Mesajınız</label></th>
                    <td><textarea name="cc_contact_message" id="cc_contact_message" rows="5" class="large-text" required></textarea></td>
                </tr>
            </table>
            <?php submit_button('Gönder'); ?>
        </form>

    <?php
    // Form gönderildi mi kontrolü
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cc_contact_message'])) {
        $name = sanitize_text_field($_POST['cc_contact_name']);
        $email = sanitize_email($_POST['cc_contact_email']);
        $message = sanitize_textarea_field($_POST['cc_contact_message']);

        $body = "İsim: $name\nE-posta: $email\n\nMesaj:\n$message";
        wp_mail('destek@gksoft.com.tr', 'CityCourier İletişim Formu', $body);

        echo '<div class="notice notice-success is-dismissible"><p>Mesajınız gönderildi. En kısa sürede size dönüş yapacağız.</p></div>';
    }

    echo '</div>';
}











// Driver page shortcode
add_shortcode('citycourier_driver_dashboard', 'citycourier_driver_page');

function citycourier_driver_page() {
    ob_start();

    if (!is_user_logged_in()) {
        echo '<div style="max-width: 400px; margin: 50px auto; text-align:center;">
            <img src="https://cdn-icons-png.flaticon.com/512/3144/3144456.png" alt="Kurye" style="max-width: 100px; margin-bottom: 20px;">
            <h2>WELCOME</h2>
            <p>To delivery drivers manager</p>
            <p><a href="' . wp_login_url(get_permalink()) . '" class="button button-primary" style="margin-top:10px;">Get started</a></p>
        </div>';
        return ob_get_clean();
    }

    $user = wp_get_current_user();
    if (!in_array('driver', $user->roles)) {
        echo '<p>Bu alan yalnızca kuryeler içindir.</p>';
        return ob_get_clean();
    }

    // Available status update
    if (isset($_POST['driver_available_toggle'])) {
        update_user_meta($user->ID, '_cc_driver_available', sanitize_text_field($_POST['driver_available_toggle']));
    }
    $is_available = get_user_meta($user->ID, '_cc_driver_available', true);

    $orders = wc_get_orders([
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_key' => '_cc_assigned_courier',
        'meta_value' => $user->ID
    ]);

    $counts = [
        'processing' => 0,
        'on-hold' => 0,
        'cancelled' => 0,
        'completed' => 0
    ];
    $total_earnings = 0;
    $route_addresses = [];

    foreach ($orders as $order) {
        $status = $order->get_status();
        if (isset($counts[$status])) $counts[$status]++;
        $total_earnings += floatval($order->get_meta('_cc_total_price'));
        if (in_array($status, ['on-hold', 'processing'])) {
            $route_addresses[] = $order->get_meta('_cc_address_to');
        }
    }

    echo '<div style="max-width:800px; margin:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2>Dashboard</h2>
            <form method="post">
                <label style="margin-right:10px;">I am Available</label>
                <input type="hidden" name="driver_available_toggle" value="0">
                <input type="checkbox" name="driver_available_toggle" value="1" onchange="this.form.submit();" ' . checked($is_available, '1', false) . '>
            </form>
        </div>

        <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:15px; margin: 20px 0;">
            <div style="background:#f9f9f9; padding:20px; text-align:center;"><strong>' . $counts['processing'] . '</strong><br>Assign to driver</div>
            <div style="background:#f9f9f9; padding:20px; text-align:center;"><strong>' . $counts['on-hold'] . '</strong><br>Out for delivery</div>
            <div style="background:#f9f9f9; padding:20px; text-align:center;"><strong>' . $counts['cancelled'] . '</strong><br>Failed delivery</div>
            <div style="background:#f9f9f9; padding:20px; text-align:center;"><strong>' . $counts['completed'] . '</strong><br>Delivered</div>
            <div style="background:#e8f5e9; padding:20px; text-align:center;"><strong>₺' . number_format($total_earnings, 2) . '</strong><br>Total earnings</div>
        </div>';

    if (!empty($route_addresses)) {
        $encoded = implode('/', array_map('urlencode', $route_addresses));
        $first = urlencode($route_addresses[0]);
        $last = urlencode(end($route_addresses));
        $waypoints = array_slice($route_addresses, 1, -1);
        $waypoint_str = implode('|', array_map('urlencode', $waypoints));
        $maps_url = "https://www.google.com/maps/dir/?api=1&origin=$first&destination=$last&travelmode=driving" . ($waypoint_str ? "&waypoints=$waypoint_str" : '');

        echo '<div style="margin-bottom:20px;"><a href="' . esc_url($maps_url) . '" target="_blank" class="button button-success">📍 View Route on Map</a></div>';
    }

    if (empty($orders)) {
        echo '<p>Şu anda size atanan sipariş bulunmamaktadır.</p>';
        return ob_get_clean();
    }

    echo '<table class="widefat fixed striped">
        <thead><tr><th>ID</th><th>Gönderim</th><th>Teslimat</th><th>Teslimat Saati</th><th>Desi</th><th>Tutar</th><th>Durum</th><th>Not</th><th>Güncelle</th></tr></thead><tbody>';

    foreach ($orders as $order) {
        $id = $order->get_id();
        $from = esc_html($order->get_meta('_cc_address_from'));
        $to = esc_html($order->get_meta('_cc_address_to'));
        $slot = esc_html($order->get_meta('_cc_delivery_slot'));
        $desi = esc_html($order->get_meta('_cc_desi'));
        $total = esc_html($order->get_meta('_cc_total_price'));
        $status = $order->get_status();
        $note = esc_html($order->get_meta('_cc_delivery_note'));

        echo '<tr>
            <td>#' . $id . '</td>
            <td>' . $from . '</td>
            <td>' . $to . '</td>
            <td>' . $slot . '</td>
            <td>' . $desi . '</td>
            <td>₺' . $total . '</td>
            <td>' . ucfirst($status) . '</td>
            <td>' . $note . '</td>
            <td>
                <form method="post">
                    <input type="hidden" name="update_order_id" value="' . $id . '">
                    <select name="new_status">
                        <option value="processing" ' . selected($status, 'processing', false) . '>Atandı</option>
                        <option value="on-hold" ' . selected($status, 'on-hold', false) . '>Yolda</option>
                        <option value="completed" ' . selected($status, 'completed', false) . '>Teslim Edildi</option>
                        <option value="cancelled" ' . selected($status, 'cancelled', false) . '>İptal</option>
                    </select>
                    <br><input type="text" name="delivery_note" placeholder="Teslimat notu" style="width:100%; margin:5px 0;">
                    <button class="button">Kaydet</button>
                </form>
            </td>
        </tr>';
    }

    echo '</tbody></table></div>';


// Driver tracking script in footer
add_action('wp_footer', function () {
    if (!is_user_logged_in()) return;
    $user = wp_get_current_user();
    if (!in_array('driver', $user->roles)) return;

    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        if ('geolocation' in navigator) {
            navigator.geolocation.watchPosition(function (position) {
                const data = new FormData();
                data.append('action', 'citycourier_update_location');
                data.append('lat', position.coords.latitude);
                data.append('lng', position.coords.longitude);
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: data
                });
            }, function (err) {
                console.warn('Konum alınamadı:', err.message);
            }, {
                enableHighAccuracy: true,
                maximumAge: 0,
                timeout: 10000
            });
        }
    });
    </script>
    <?php
});

    return ob_get_clean();

}

add_action('init', function () {
    if (!is_user_logged_in()) return;

    // Update order status + note
    if (isset($_POST['update_order_id'], $_POST['new_status'])) {
        $order_id = absint($_POST['update_order_id']);
        $new_status = sanitize_text_field($_POST['new_status']);
        $order = wc_get_order($order_id);

        if ($order && current_user_can('read')) {
            $assigned_courier = $order->get_meta('_cc_assigned_courier');
            if (intval($assigned_courier) === get_current_user_id()) {
                $order->update_status($new_status);
                if (!empty($_POST['delivery_note'])) {
                    $order->update_meta_data('_cc_delivery_note', sanitize_text_field($_POST['delivery_note']));
                }
                $order->save();
            }
        }
    }
});
