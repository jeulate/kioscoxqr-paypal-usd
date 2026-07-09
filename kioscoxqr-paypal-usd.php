<?php
/**
 * Plugin Name: KioscoxQR PayPal USD Converter
 * Plugin URI: https://github.com/jeulate/kioscoxqr-paypal-usd
 * Description: Convierte automáticamente pagos con PayPal de BOB a USD en WooCommerce
 * Version: 2.0
 * Author: jeulate
 * Text Domain: kioscoxqr-paypal-usd
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar WooCommerce
function kqpu_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><strong>KioscoxQR PayPal USD Converter</strong> requiere <a href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a>.</p>
            </div>
            <?php
        });
        return false;
    }
    return true;
}

if (!kqpu_check_woocommerce()) {
    return;
}

// Constantes
define('KQPU_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KQPU_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('KQPU_VERSION', '2.0');

// Cargar clases
$includes_dir = KQPU_PLUGIN_PATH . 'includes/';

require_once $includes_dir . 'class-settings.php';
require_once $includes_dir . 'class-exchange-rate.php';
require_once $includes_dir . 'class-gateway-visibility.php';
require_once $includes_dir . 'class-checkout-display.php';
require_once $includes_dir . 'class-order-meta.php';

// Cargar integraciones
$integrations_dir = $includes_dir . 'integrations/';
require_once $integrations_dir . 'class-paypal-integration.php'; // ✅ ESTA ES LA QUE USA

// Inicializar clases
new KQPU_Settings();
new KQPU_Gateway_Visibility();
new KQPU_Checkout_Display();
new KQPU_Order_Meta();
new KQPU_PayPal_Integration(); // ✅ Integración con PayPal

// Enlaces rápidos
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'kqpu_add_action_links');
function kqpu_add_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=kqpu-settings') . '">' . __('Configuración', 'kioscoxqr-paypal-usd') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Declarar compatibilidad con HPOS
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
});

// Activación
register_activation_hook(__FILE__, 'kqpu_activate');
function kqpu_activate() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Este plugin requiere WooCommerce.');
    }
    
    if (!get_option('kqpu_enabled')) {
        update_option('kqpu_enabled', 'yes');
    }
    if (!get_option('kqpu_exchange_rate')) {
        update_option('kqpu_exchange_rate', '6.96');
    }
    if (!get_option('kqpu_paypal_gateways')) {
        update_option('kqpu_paypal_gateways', 'ppcp-gateway,paypal');
    }
}

// Desactivación
register_deactivation_hook(__FILE__, 'kqpu_deactivate');
function kqpu_deactivate() {
    // Limpieza opcional
}

?>