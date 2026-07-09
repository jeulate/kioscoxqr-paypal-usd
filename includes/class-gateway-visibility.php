<?php

if (!defined('ABSPATH')) exit;

class KQPU_Gateway_Visibility {

    public function __construct() {
        add_filter('woocommerce_available_payment_gateways', [$this, 'force_paypal_card_gateway'], 999);
        add_filter('woocommerce_gateway_title', [$this, 'rename_gateways'], 10, 2);
        add_filter('woocommerce_gateway_description', [$this, 'add_gateway_descriptions'], 10, 2);
    }

    public function force_paypal_card_gateway($available_gateways) {
        if (is_admin() && !wp_doing_ajax()) {
            return $available_gateways;
        }

        // Checkout clásico (shortcode) → is_checkout() es true
        // Checkout por bloques (Gutenberg) → la consulta llega vía REST API,
        // por lo que is_checkout() devuelve false. Hay que permitir ambos casos.
        $is_checkout_page = function_exists('is_checkout') && is_checkout();
        $is_rest_request  = defined('REST_REQUEST') && REST_REQUEST;

        if (!$is_checkout_page && !$is_rest_request) {
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
            $gateway          = $all_gateways['ppcp-card-button-gateway'];
            $gateway->enabled = 'yes'; // forzar habilitado aunque is_available() falle
            $available_gateways['ppcp-card-button-gateway'] = $gateway;
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

    public function add_gateway_descriptions($description, $gateway_id) {
        if ($gateway_id === 'ppcp-gateway') {
            return 'Serás redirigido a tu cuenta PayPal para completar el pago. Si tienes la app de PayPal instalada en tu celular, podrás pagar de forma inmediata. El monto se convertirá automáticamente a USD.';
        }

        if ($gateway_id === 'ppcp-card-button-gateway') {
            return 'Ingresa los datos de tu tarjeta de débito o crédito de forma segura. No es necesario tener cuenta PayPal. El monto se convertirá automáticamente a USD.';
        }

        return $description;
    }
}