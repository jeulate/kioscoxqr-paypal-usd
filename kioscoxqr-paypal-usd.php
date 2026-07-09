<?php
/**
 * Plugin Name: KioscoxQR PayPal USD Converter
 * Description: Convierte pedidos PayPal de BOB a USD y mantiene QR Bolivia en BOB.
 * Version: 1.0.0
 * Author: KioscoxQR
 */

if (!defined('ABSPATH')) exit;

define('KQPU_PATH', plugin_dir_path(__FILE__));
define('KQPU_URL', plugin_dir_url(__FILE__));

add_action('plugins_loaded', function () {
    if (!class_exists('WooCommerce')) {
        return;
    }

    require_once KQPU_PATH . 'includes/class-settings.php';
    require_once KQPU_PATH . 'includes/class-exchange-rate.php';
    require_once KQPU_PATH . 'includes/integrations/class-paypal-integration.php';
    require_once KQPU_PATH . 'includes/class-checkout-display.php';
    require_once KQPU_PATH . 'includes/class-order-meta.php';

    new KQPU_Settings();
    new KQPU_PayPal_Integration();
    new KQPU_Checkout_Display();
    new KQPU_Order_Meta();
});