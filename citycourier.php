<?php
/*
Plugin Name: CityCourier – Kurye Siparişi ve Takip Sistemi
Description: Tek sayfa (tabs) form mantığıyla çalışan kurye hizmeti formu. JS ile adım geçişi yapar.
Version: 1.1
Author: GKSofttt
*/

if (!defined('ABSPATH')) exit;

// Sabitler
define('CITYCOURIER_PATH', plugin_dir_path(__FILE__));
define('CITYCOURIER_URL', plugin_dir_url(__FILE__));

// === Ortak Gereksinimler ===
require_once CITYCOURIER_PATH . 'admin/settings-page.php';
require_once CITYCOURIER_PATH . 'includes/class-distance.php';
require_once CITYCOURIER_PATH . 'templates/form-submit-handler.php';

// === Oturum Başlat ===
add_action('init', function () {
    if (!session_id()) session_start();
});




add_action('admin_menu', function () {
    add_menu_page(
        'CityCourier Ayarları',
        'CityCourier',
        'manage_options',
        'citycourier-settings',
        'citycourier_settings_page_html',
        'dashicons-location',
        56
    );

    add_submenu_page('citycourier-settings', 'Kurye Siparişleri', 'Siparişler', 'manage_options', 'citycourier-orders', 'citycourier_orders_page_html');
    add_submenu_page('citycourier-settings', 'Kurye Listesi', 'Kuryeler', 'manage_options', 'citycourier-couriers', 'citycourier_couriers_page_html');
    add_submenu_page('citycourier-settings', 'Güzergahlar', 'Routes', 'manage_options', 'citycourier-routes', 'citycourier_routes_page_html');
    add_submenu_page('citycourier-settings', 'Raporlar', 'Reports', 'manage_options', 'citycourier-reports', 'citycourier_reports_page_html');
	add_submenu_page('citycourier-settings', 'Upgrade', 'Upgrade', 'manage_options', 'citycourier-upgrade', 'citycourier_upgrade_page_html');
    add_submenu_page('citycourier-settings', 'İletişim', 'Contact', 'manage_options', 'citycourier-contact', 'citycourier_contact_page_html');
});


add_action('admin_notices', function () {
    $current = $_GET['page'] ?? '';
    if (strpos($current, 'citycourier') === false) return;

    $menu = [
        'citycourier-settings'  => ['Ayarlar', '⚙️'],
        'citycourier-orders'    => ['Siparişler', '📦'],
        'citycourier-couriers'  => ['Kuryeler', '🧍‍♂️'],
        'citycourier-routes'    => ['Güzergahlar', '🗺️'],
        'citycourier-reports'   => ['Raporlar', '📊'],
        'citycourier-upgrade'   => ['<span class="pro-badge">PRO</span>', '🚀'],
    ];
    ?>
    <div class="cc-header-bar">
        <!-- Sol Logo + Başlık -->
        <div class="cc-header-brand">
            <img src="https://test.gksoft.com.tr/wp-content/uploads/2025/06/cty.png" alt="City Courier">
            <div>
                <strong>City Courier WP Eklentisi</strong><br>
                <span class="version">Versiyon: 1.8.4</span>
            </div>
        </div>

        <!-- Menü -->
        <nav class="cc-header-nav">
            <?php foreach ($menu as $slug => [$label, $icon]): ?>
                <a href="<?= admin_url('admin.php?page=' . $slug); ?>"
                   class="<?= $current === $slug ? 'active' : '' ?>">
                    <?= $icon ?> <?= $label ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Sağ: Dropdown Menü -->
        <div class="cc-header-actions">
            <div class="cc-dropdown">
                <button class="cc-dropdown-toggle">⋯</button>
                <div class="cc-dropdown-menu">
                    <a href="https://gksoft.com.tr/destek" target="_blank">❓ Destek</a>
                    <a href="mailto:support@gksoft.com.tr?subject=CityCourier Geri Bildirimi">💬 Geri Bildirim</a>
                </div>
            </div>
        </div>
    </div>
    <?php
});




add_action('admin_enqueue_scripts', function () {
    wp_add_inline_style('wp-admin', '
/* Genel */
.cc-header-bar {
    background: #1e293b;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    border-radius: 8px;
    margin: 20px 0;
    gap: 24px;
    color: #fff;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

/* Sol */
.cc-header-brand {
    display: flex;
    align-items: center;
    gap: 12px;
}
.cc-header-brand img {
    width: 38px;
    height: 38px;
}
.cc-header-brand .version {
    font-size: 12px;
    color: #cbd5e1;
}

/* Orta Menü */
.cc-header-nav {
    display: flex;
    gap: 28px;
    flex: 1;
}
.cc-header-nav a {
    color: #cbd5e1;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 8px;
    border-radius: 6px;
    transition: 0.15s;
}
.cc-header-nav a:hover,
.cc-header-nav a.active {
    background: #334155;
    color: #fff;
}
.cc-header-nav .pro-badge {
    background: #3b82f6;
    color: white;
    font-size: 10px;
    font-weight: bold;
    border-radius: 6px;
    padding: 2px 6px;
    margin-left: 4px;
}

/* Dropdown */
.cc-header-actions {
    position: relative;
}
.cc-dropdown {
    position: relative;
}
.cc-dropdown-toggle {
    background: none;
    border: none;
    color: #fff;
    font-size: 20px;
    cursor: pointer;
    padding: 6px 10px;
    border-radius: 6px;
}
.cc-dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 36px;
    background: #334155;
    border-radius: 6px;
    padding: 6px 0;
    z-index: 9999;
    min-width: 160px;
}
.cc-dropdown-menu a {
    display: block;
    padding: 8px 14px;
    text-decoration: none;
    color: #fff;
    font-size: 13px;
}
.cc-dropdown-menu a:hover {
    background: #475569;
}
.cc-dropdown:hover .cc-dropdown-menu {
    display: block;
}

    ');
});





// === Kısa Kod: [citycourier_form] ===
add_shortcode('citycourier_form', function () {
    ob_start();
    require CITYCOURIER_PATH . 'templates/form-wrapper.php';
    return ob_get_clean();
});

// === Sipariş Takip Sayfası ===
add_action('template_redirect', function () {
    if (is_page() && isset($_GET['order_id']) && strpos($_SERVER['REQUEST_URI'], 'kurye-takip') !== false) {
        include CITYCOURIER_PATH . 'tracking/kurye-takip.php';
        exit;
    }
});

// === Stil & Script ===
add_action('wp_enqueue_scripts', function () {
    $api_key = get_option('citycourier_google_api_key');
    if ($api_key) {
        wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($api_key) . '&libraries=places', [], null, true);
    }
    wp_enqueue_script('citycourier-js', CITYCOURIER_URL . 'assets/js/citycourier.js', ['google-maps'], '1.0', true);
    wp_enqueue_style('citycourier-style', CITYCOURIER_URL . 'assets/css/style.css', [], '1.0');
});


// === Lisans Kontrol Fonksiyonu ===
function citycourier_is_premium_user() {
    $domain = $_SERVER['HTTP_HOST'];
    $token = get_option('citycourier_license_token');

    $cached = get_transient('cc_license_valid');
    if ($cached !== false) return $cached;

    $res = wp_remote_get("https://licenses.gksoft.com.tr/check-license.php?domain=$domain&token=$token");
    if (is_wp_error($res)) return false;

    $data = json_decode(wp_remote_retrieve_body($res), true);
    $valid = isset($data['status']) && $data['status'] === 'valid';

    set_transient('cc_license_valid', $valid, 12 * HOUR_IN_SECONDS);
    return $valid;
}

// === Pro Modül Yükleyici ===
if (citycourier_is_premium_user()) {
    $pro_dir = CITYCOURIER_PATH . 'pro/';
    if (is_dir($pro_dir)) {
        foreach (glob($pro_dir . '*.php') as $file) {
            include_once $file;
        }
    }
}