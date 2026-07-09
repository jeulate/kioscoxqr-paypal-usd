<?php

if (!defined('ABSPATH')) exit;

class KQPU_Gateway_Visibility {

    public function __construct() {
        add_filter('woocommerce_available_payment_gateways', [$this, 'force_paypal_card_gateway'], 999);
        add_filter('woocommerce_gateway_title', [$this, 'rename_gateways'], 10, 2);
    }

    public function force_paypal_card_gateway($available_gateways) {
        if (is_admin() && !wp_doing_ajax()) {
            return $available_gateways;
        }

        if (!function_exists('is_checkout') || !is_checkout()) {
            return $available_gateways;
        }

        if (!function_exists('WC') || !WC()->payment_gateways()) {
            return $available_gateways;
        }

        $all_gateways = WC()->payment_gateways()->payment_gateways();

        if (
            isset($all_gateways['ppcp-card-button-gateway']) &&
            !isset($available_gateways['ppcp-card-button-gateway'])
        ) {
            $available_gateways['ppcp-card-button-gateway'] = $all_gateways['ppcp-card-button-gateway'];
        }

        return $available_gateways;
    }

    public function rename_gateways($title, $gateway_id) {
        if ($gateway_id === 'ppcp-card-button-gateway') {
            return 'Tarjeta de débito o crédito';
        }

        if ($gateway_id === 'ppcp-gateway') {
            return 'PayPal';
        }

        return $title;
    }
}