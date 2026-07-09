<?php

if (!defined('ABSPATH')) exit;

class KQPU_PayPal_Integration {

    public function __construct() {
        add_action('woocommerce_checkout_create_order', [$this, 'convert_order_to_usd_for_paypal'], 20, 2);
    }

    private function is_paypal_checkout(): bool {
        $method = '';

        if (!empty($_POST['payment_method'])) {
            $method = sanitize_text_field(wp_unslash($_POST['payment_method']));
        }

        return in_array($method, KQPU_Settings::get_paypal_gateway_ids(), true);
    }

    public function convert_order_to_usd_for_paypal($order, $data) {
        if (!$this->is_paypal_checkout()) {
            return;
        }

        $rate = KQPU_Exchange_Rate::bob_to_usd();

        $original_total_bob = (float) $order->get_total();

        $order->set_currency('USD');

        foreach ($order->get_items('line_item') as $item) {
            $subtotal_bob = (float) $item->get_subtotal();
            $total_bob    = (float) $item->get_total();

            $item->update_meta_data('_kqpu_original_subtotal_bob', $subtotal_bob);
            $item->update_meta_data('_kqpu_original_total_bob', $total_bob);

            $item->set_subtotal(round($subtotal_bob / $rate, 2));
            $item->set_total(round($total_bob / $rate, 2));
            $item->set_subtotal_tax(0);
            $item->set_total_tax(0);
        }

        foreach ($order->get_items('fee') as $item) {
            $total_bob = (float) $item->get_total();
            $item->update_meta_data('_kqpu_original_fee_bob', $total_bob);
            $item->set_total(round($total_bob / $rate, 2));
            $item->set_total_tax(0);
        }

        foreach ($order->get_items('shipping') as $item) {
            $total_bob = (float) $item->get_total();
            $item->update_meta_data('_kqpu_original_shipping_bob', $total_bob);
            $item->set_total(round($total_bob / $rate, 2));
            $item->set_total_tax(0);
        }

        $order->set_cart_tax(0);
        $order->set_shipping_tax(0);
        $order->set_total(round($original_total_bob / $rate, 2));

        $order->update_meta_data('_kqpu_original_currency', 'BOB');
        $order->update_meta_data('_kqpu_converted_currency', 'USD');
        $order->update_meta_data('_kqpu_exchange_rate', $rate);
        $order->update_meta_data('_kqpu_total_bob_original', $original_total_bob);
        $order->update_meta_data('_kqpu_total_usd_charged', round($original_total_bob / $rate, 2));
    }
}