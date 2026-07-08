<?php

if (!defined('ABSPATH')) exit;

class KQPU_Order_Meta {

    public function __construct() {
        add_action('woocommerce_checkout_create_order', [$this, 'add_order_meta'], 30, 2);
        add_action('woocommerce_admin_order_data_after_order_details', [$this, 'show_admin_order_meta']);
    }

    public function add_order_meta($order, $data) {
        if (!function_exists('WC') || !WC()->session) return;

        $gateway = WC()->session->get('chosen_payment_method');

        if (!in_array($gateway, KQPU_Settings::get_paypal_gateway_ids(), true)) {
            return;
        }

        $rate = KQPU_Settings::get_exchange_rate();
        $total_usd = (float) $order->get_total();
        $total_bob = round($total_usd * $rate, 2);

        $order->set_currency('USD');

        $order->update_meta_data('_kqpu_payment_gateway', $gateway);
        $order->update_meta_data('_kqpu_original_currency', 'BOB');
        $order->update_meta_data('_kqpu_converted_currency', 'USD');
        $order->update_meta_data('_kqpu_exchange_rate', $rate);
        $order->update_meta_data('_kqpu_total_bob_estimated', $total_bob);
        $order->update_meta_data('_kqpu_total_usd_charged', $total_usd);
    }

    public function show_admin_order_meta($order) {
        $rate = $order->get_meta('_kqpu_exchange_rate');

        if (!$rate) return;

        echo '<div class="order_data_column">';
        echo '<h4>Conversión PayPal USD</h4>';
        echo '<p><strong>Gateway:</strong> ' . esc_html($order->get_meta('_kqpu_payment_gateway')) . '</p>';
        echo '<p><strong>Moneda original:</strong> BOB</p>';
        echo '<p><strong>Moneda cobrada:</strong> USD</p>';
        echo '<p><strong>Tipo de cambio:</strong> ' . esc_html($rate) . '</p>';
        echo '<p><strong>Total estimado Bs:</strong> ' . esc_html($order->get_meta('_kqpu_total_bob_estimated')) . '</p>';
        echo '<p><strong>Total cobrado USD:</strong> ' . esc_html($order->get_meta('_kqpu_total_usd_charged')) . '</p>';
        echo '</div>';
    }
}