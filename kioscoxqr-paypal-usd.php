<?php
/**
 * Plugin Name: KioscoxQR PayPal USD Converter
 * Plugin URI: https://github.com/jeulate/kioscoxqr-paypal-usd
 * Description: Convierte automáticamente pagos con PayPal de BOB a USD en WooCommerce
 * Version: 2.0.1
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
    // Si ya está cargado, no hacer nada
    return;
}
define('KQPU_LOADED', true);

// ============================================
// VERIFICACIÓN MEJORADA DE WOOCOMMERCE
// ============================================
function kqpu_check_woocommerce() {
    // Verificar de múltiples formas
    $woocommerce_active = false;
    
    // Forma 1: Clase principal
    if (class_exists('WooCommerce')) {
        $woocommerce_active = true;
    }
    
    // Forma 2: Constante de versión
    if (defined('WC_VERSION')) {
        $woocommerce_active = true;
    }
    
    // Forma 3: Plugin activo en la lista
    $active_plugins = get_option('active_plugins');
    foreach ($active_plugins as $plugin) {
        if (strpos($plugin, 'woocommerce.php') !== false) {
            $woocommerce_active = true;
            break;
        }
    }
    
    return $woocommerce_active;
}

// ============================================
// INICIALIZACIÓN DIFERIDA
// ============================================
function kqpu_init() {
    // Verificar WooCommerce
    if (!kqpu_check_woocommerce()) {
        add_action('admin_notices', 'kqpu_missing_woocommerce_notice');
        return;
    }
    
    // Inicializar plugin
    kqpu_init_plugin();
}
add_action('plugins_loaded', 'kqpu_init', 10);

// ============================================
// NOTIFICACIÓN DE ERROR
// ============================================
function kqpu_missing_woocommerce_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong>KioscoxQR PayPal USD Converter</strong> 
            requiere <a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>.
        </p>
        <p>
            <a href="<?php echo admin_url('plugins.php'); ?>">Ir a Plugins</a>
        </p>
    </div>
    <?php
    // Desactivar automáticamente
    if (current_user_can('activate_plugins')) {
        deactivate_plugins(plugin_basename(__FILE__));
    }
}

// ============================================
// INICIALIZACIÓN PRINCIPAL DEL PLUGIN
// ============================================
function kqpu_init_plugin() {
    // Prevenir doble inicialización
    if (defined('KQPU_INITIALIZED')) {
        return;
    }
    define('KQPU_INITIALIZED', true);
    
    // Constantes
    define('KQPU_PLUGIN_URL', plugin_dir_url(__FILE__));
    define('KQPU_PLUGIN_PATH', plugin_dir_path(__FILE__));
    define('KQPU_VERSION', '2.0.1');
    
    // Cargar clases
    $includes_dir = KQPU_PLUGIN_PATH . 'includes/';
    
    // Verificar que los archivos existen
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
        } else {
            error_log('[KQPU] Archivo no encontrado: ' . $file);
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
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'kqpu_add_action_links');
    
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
}

// ============================================
// FUNCIONES AUXILIARES
// ============================================
function kqpu_add_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=kqpu-settings') . '">' . __('Configuración', 'kioscoxqr-paypal-usd') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// ============================================
// ACTIVACIÓN Y DESACTIVACIÓN
// ============================================
register_activation_hook(__FILE__, 'kqpu_activate');
function kqpu_activate() {
    // Verificar WooCommerce antes de activar
    if (!kqpu_check_woocommerce()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Este plugin requiere WooCommerce. Por favor, instala y activa WooCommerce primero.');
    }
    
    // Crear opciones por defecto
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
    // Nota: No eliminar opciones para mantener configuración
}

// ============================================
// REINICIALIZAR SI WOOCOMMERCE SE CARGA DESPUÉS
// ============================================
add_action('woocommerce_loaded', function() {
    if (!defined('KQPU_INITIALIZED') && kqpu_check_woocommerce()) {
        kqpu_init_plugin();
    }
}, 5);

// En la función kqpu_init_plugin(), después de cargar las clases:

// Cargar assets para blocks
add_action('wp_enqueue_scripts', function() {
    if (function_exists('is_checkout') && is_checkout()) {
        wp_enqueue_script(
            'kqpu-blocks-integration',
            KQPU_PLUGIN_URL . 'assets/js/blocks-integration.js',
            ['wc-blocks-checkout', 'wp-hooks'],
            KQPU_VERSION,
            true
        );
        
        wp_localize_script('kqpu-blocks-integration', 'kqpuBlocksData', [
            'rate' => KQPU_Exchange_Rate::get_rate(),
            'gateways' => KQPU_Settings::get_paypal_gateway_ids(),
            'currency' => 'USD',
            'symbol' => '$'
        ]);
    }
}, 20);