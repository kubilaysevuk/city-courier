<?php
    if (!isset($_GET['order_id'])) return '<p>Sipariş ID bulunamadı.</p>';
    $order_id = absint($_GET['order_id']);
    $order = wc_get_order($order_id);
    if (!$order) return '<p>Sipariş bulunamadı.</p>';

    $from = $order->get_meta('_cc_address_from');
    $to = $order->get_meta('_cc_address_to');
	$pickup_phone       = $order->get_meta('_cc_pickup_phone');
$delivery_phone     = $order->get_meta('_cc_delivery_phone');
$sender_name        = $order->get_meta('_cc_sender_name');
$user_name          = $order->get_meta('_cc_user_name');
$user_email         = $order->get_meta('_cc_user_email');
$pickup_details     = $order->get_meta('_cc_pickup_details');
$delivery_details   = $order->get_meta('_cc_delivery_details');
$weight             = $order->get_meta('_cc_weight');
$package_content    = $order->get_meta('_cc_package_content');
$payment_method     = $order->get_meta('_cc_payment_method');
$duration           = $order->get_meta('_cc_duration');

    $note = $order->get_meta('_cc_user_note');
    $total = $order->get_meta('_cc_total_price');
	
    $courier_id = $order->get_meta('_cc_assigned_courier');
    $courier = $courier_id ? get_userdata($courier_id) : null;
    $lat = $courier ? get_user_meta($courier->ID, '_cc_driver_lat', true) : '';
    $lng = $courier ? get_user_meta($courier->ID, '_cc_driver_lng', true) : '';
    $status = $order->get_status();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['courier_rating'], $_POST['order_id'])) {
    $order_id = absint($_POST['order_id']);
    $rating = intval($_POST['courier_rating']);
    $review = sanitize_textarea_field($_POST['courier_review']);

    update_post_meta($order_id, '_cc_review_rating', $rating);
    update_post_meta($order_id, '_cc_review_text', $review);

    wp_safe_redirect(add_query_arg(['reviewed' => '1', 'order_id' => $order_id], get_permalink()));
    exit;
}



    ob_start();
    echo '<div id="cc-tracker" style="max-width: 700px; margin: 40px auto; font-family: sans-serif;">
    <h2>🚚 Kurye Takip - Sipariş #' . esc_html($order_id) . '</h2>';

    if ($status !== 'completed') {
        echo '<ul>'; 

// Basic info
echo '<li><strong>Gönderim Noktası:</strong> ' . esc_html($from) . '</li>';
echo '<li><strong>Teslimat Noktası:</strong> ' . esc_html($to) . '</li>';

// Contact info
echo '<li><strong>Alıcı Telefonu:</strong> ' . esc_html($delivery_phone) . '</li>';
echo '<li><strong>Gönderici Telefonu:</strong> ' . esc_html($pickup_phone) . '</li>';

// Sender/Recipient
echo '<li><strong>Gönderici Adı:</strong> ' . esc_html($sender_name) . '</li>';
echo '<li><strong>Kullanıcı Adı:</strong> ' . esc_html($user_name) . '</li>';
echo '<li><strong>Kullanıcı Email:</strong> ' . esc_html($user_email) . '</li>';

// Details
echo '<li><strong>Alış Noktası Detayları:</strong> ' . esc_html($pickup_details) . '</li>';
echo '<li><strong>Teslim Noktası Detayları:</strong> ' . esc_html($delivery_details) . '</li>';

echo '<li><strong>Paket Ağırlığı:</strong> ' . esc_html($weight) . '</li>';
echo '<li><strong>Paket İçeriği:</strong> ' . esc_html($package_content) . '</li>';

echo '<li><strong>Ödeme Yöntemi:</strong> ' . esc_html($payment_method) . '</li>';
echo '<li><strong>Süre:</strong> ' . esc_html($duration) . '</li>';
            echo '<li><strong>Toplam Tutar:</strong> ₺' . esc_html($total) . '</li>';
        if ($note) echo '<li><strong>Not:</strong> ' . esc_html($note) . '</li>';
        if ($courier) echo '<li><strong>Atanan Kurye:</strong> ' . esc_html($courier->display_name) . '</li>';
        echo '</ul>';

        $steps = [
            'pending' => 0,
            'processing' => 1,
            'on-hold' => 2,
            'completed' => 3
        ];
        $current_step = $steps[$status] ?? 0;
        $labels = ['Sipariş Alındı', 'Kurye Atandı', 'Yolda', 'Teslim Edildi'];
        echo '<div id="cc-progress" style="display:flex; gap:10px; justify-content:space-between; margin:20px 0;">';
        foreach ($labels as $i => $label) {
            $active = $i <= $current_step ? 'background:#28a745;color:#fff;' : 'background:#ccc;';
            echo '<div style="flex:1; text-align:center;">
                <div style="width:30px; height:30px; margin:auto; border-radius:50%; ' . $active . '"></div>
                <small>' . $label . '</small>
            </div>';
        }
        echo '</div>';

        echo '<div id="eta" style="margin-bottom:10px; font-style:italic; color:#333;"></div>';
        echo '<div id="map" style="width: 100%; height: 400px; margin-top: 30px;"></div>';
        echo '<script>initMap();</script>';
    }

    if ($status === 'completed') {
        if (isset($_GET['reviewed']) && $_GET['reviewed'] === '1') {
    echo '<div style="padding:20px; background:#e0ffe0; border:1px solid #a6d8a8; margin-top:30px;">
        <h3>Teşekkür ederiz 🎉</h3>
        <p>Değerlendirmeniz başarıyla alındı.</p>
    </div>';
}
 else {
            echo '<div id="feedback-form" style="margin-top:30px;">
            <h3>📝 Kurye Değerlendirmesi</h3>
            <form method="post">';
wp_nonce_field('cc_review_submit', 'cc_review_nonce');

    echo '<input type="hidden" name="order_id" value="' . esc_attr($order_id) . '">
    <label>Puanlama (1-5): <input type="number" name="courier_rating" min="1" max="5" required></label><br><br>
    <label>Yorum: <br><textarea name="courier_review" rows="4" style="width:100%;"></textarea></label><br><br>
	<input type="hidden" name="form_type" value="review">
	<input type="hidden" name="cc_review_nonce" value="'. wp_create_nonce("cc_review_submit") .'">

    <button type="submit" class="button button-primary">Gönder</button>
</form>

            </div>';
        }
    }

    echo '</div>';
?>
<script>
let map, courierMarker, lastPosition = null;
const courierLat = <?php echo json_encode($lat); ?>;
const courierLng = <?php echo json_encode($lng); ?>;
const fromAddress = <?php echo json_encode($from); ?>;
const toAddress = <?php echo json_encode($to); ?>;

function getETA(from, to) {
  const service = new google.maps.DistanceMatrixService();
  service.getDistanceMatrix({
    origins: [from],
    destinations: [to],
    travelMode: 'DRIVING'
  }, function (response, status) {
    if (status === 'OK') {
      const result = response.rows[0].elements[0];
      if (result.status === 'OK') {
        document.getElementById('eta').innerText = 'Tahmini teslim süresi: ' + result.duration.text + ' (' + result.distance.text + ')';
      }
    }
  });
}

function getRotation(start, end) {
  const dx = end.lng - start.lng;
  const dy = end.lat - start.lat;
  return Math.atan2(dy, dx) * (180 / Math.PI);
}

function animateMarker(marker, newPosition, duration = 1000) {
  const oldPos = marker.getPosition();
  const newLat = newPosition.lat;
  const newLng = newPosition.lng;
  const deltaLat = (newLat - oldPos.lat()) / (duration / 10);
  const deltaLng = (newLng - oldPos.lng()) / (duration / 10);
  let i = 0;
  const move = () => {
    i++;
    const lat = oldPos.lat() + deltaLat * i;
    const lng = oldPos.lng() + deltaLng * i;
    marker.setPosition(new google.maps.LatLng(lat, lng));
    if (i < duration / 10) requestAnimationFrame(move);
  };
  move();
}

function moveMarker(marker, newPos) {
  const current = marker.getPosition();
  const heading = getRotation({ lat: current.lat(), lng: current.lng() }, newPos);
  marker.setIcon({
    url: 'https://test.gksoft.com.tr/wp-content/uploads/2025/05/9889310a4faf07b4986dec649ce6bbfd.jpg',
    scaledSize: new google.maps.Size(32, 32),
    anchor: new google.maps.Point(16, 16),
    rotation: heading
  });
  animateMarker(marker, newPos);
}

function fetchCourierLocation() {
  fetch(window.location.href).then(r => r.text()).then(html => {
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');
    const lat = parseFloat(doc.querySelector('[data-driver-lat]').dataset.driverLat);
    const lng = parseFloat(doc.querySelector('[data-driver-lng]').dataset.driverLng);
    if (!isNaN(lat) && !isNaN(lng)) {
      const newPos = { lat, lng };
      if (courierMarker) {
        moveMarker(courierMarker, newPos);
        getETA(newPos, toAddress);
      }
    }
  });
}

function initMap() {
  const geocoder = new google.maps.Geocoder();
  map = new google.maps.Map(document.getElementById("map"), {
    zoom: 7,
    center: { lat: 39.9208, lng: 32.8541 }
  });
  const bounds = new google.maps.LatLngBounds();
  [fromAddress, toAddress].forEach((address, i) => {
    geocoder.geocode({ address }, function (results, status) {
      if (status === "OK") {
        const marker = new google.maps.Marker({
          map,
          position: results[0].geometry.location,
          label: i === 0 ? "A" : "B"
        });
        bounds.extend(results[0].geometry.location);
        map.fitBounds(bounds);
      }
    });
  });
  if (courierLat && courierLng) {
    const pos = { lat: parseFloat(courierLat), lng: parseFloat(courierLng) };
    courierMarker = new google.maps.Marker({
      map,
      position: pos,
      icon: {
        url: 'https://test.gksoft.com.tr/wp-content/uploads/2025/05/9889310a4faf07b4986dec649ce6bbfd.jpg',
        scaledSize: new google.maps.Size(32, 32),
        anchor: new google.maps.Point(16, 16)
      },
      title: 'Kurye Konumu'
    });
    bounds.extend(pos);
    map.fitBounds(bounds);
    getETA(pos, toAddress);
  }
  setInterval(fetchCourierLocation, 10000);
}
	
	function refreshStatus() {
    fetch(window.location.href).then(res => res.text()).then(html => {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const newContent = doc.querySelector('#cc-tracker').innerHTML;
        document.querySelector('#cc-tracker').innerHTML = newContent;
        if (typeof initMap === 'function') initMap();
    });
}

setInterval(refreshStatus, 15000);
</script>
<div style="display:none" data-driver-lat="<?php echo esc_attr($lat); ?>" data-driver-lng="<?php echo esc_attr($lng); ?>"></div>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo get_option('citycourier_google_api_key'); ?>&callback=initMap"></script>
