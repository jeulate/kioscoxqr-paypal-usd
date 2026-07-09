<?php
/**
 * Plugin Name: KioscoxQR PayPal USD Converter
 * Plugin URI: https://github.com/jeulate/kioscoxqr-paypal-usd
 * Description: Convierte automáticamente pagos con PayPal de BOB a USD en WooCommerce
 * Version: 2.0.2
 * Author: jeulate
 * Text Domain: kioscoxqr-paypal-usd
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================
// PREVENIR CARGA DUPLICADA
// ============================================
if (defined('KQPU_LOADED')) {
    return;
}
define('KQPU_LOADED', true);

// ============================================
// VERIFICACIÓN DE WOOCOMMERCE
// ============================================
function kqpu_check_woocommerce() {
    return class_exists('WooCommerce') || defined('WC_VERSION');
}

// ============================================
// INICIALIZACIÓN PRINCIPAL
// ============================================
function kqpu_init() {
    if (!kqpu_check_woocommerce()) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><strong>KioscoxQR PayPal USD Converter</strong> requiere WooCommerce.</p>
            </div>
            <?php
            deactivate_plugins(plugin_basename(__FILE__));
        });
        return;
    }
    
    kqpu_init_plugin();
}
add_action('plugins_loaded', 'kqpu_init', 20);

// ============================================
// INICIALIZACIÓN DEL PLUGIN
// ============================================
function kqpu_init_plugin() {
    if (defined('KQPU_INITIALIZED')) {
        return;
    }
    define('KQPU_INITIALIZED', true);
    
    // Constantes
    define('KQPU_PLUGIN_URL', plugin_dir_url(__FILE__));
    define('KQPU_PLUGIN_PATH', plugin_dir_path(__FILE__));
    define('KQPU_VERSION', '2.0.2');
    
    // Cargar clases principales
    $includes_dir = KQPU_PLUGIN_PATH . 'includes/';
    
    $files = [
        'class-settings.php',
        'class-exchange-rate.php',
        'class-gateway-visibility.php',
        'class-checkout-display.php',
        'class-order-meta.php',
        'integrations/class-paypal-integration.php'
    ];
    
    foreach ($files as $file) {
        $file_path = $includes_dir . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
    
    // Inicializar clases
    if (class_exists('KQPU_Settings')) {
        new KQPU_Settings();
    }
    if (class_exists('KQPU_Gateway_Visibility')) {
        new KQPU_Gateway_Visibility();
    }
    if (class_exists('KQPU_Checkout_Display')) {
        new KQPU_Checkout_Display();
    }
    if (class_exists('KQPU_Order_Meta')) {
        new KQPU_Order_Meta();
    }
    if (class_exists('KQPU_PayPal_Integration')) {
        new KQPU_PayPal_Integration();
    }
    
    // Enlaces rápidos
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=kqpu-settings') . '">Configuración</a>';
        array_unshift($links, $settings_link);
        return $links;
    });
    
    // Compatibilidad con HPOS
    add_action('before_woocommerce_init', function() {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                __FILE__,
                true
            );
        }
    });
}

// ============================================
// ACTIVACIÓN
// ============================================
register_activation_hook(__FILE__, 'kqpu_activate');
function kqpu_activate() {
    if (!kqpu_check_woocommerce()) {
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

register_deactivation_hook(__FILE__, 'kqpu_deactivate');
function kqpu_deactivate() {
    // Limpieza opcional
}