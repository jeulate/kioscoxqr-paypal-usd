<?php

if (!defined('ABSPATH')) exit;

class KQPU_Currency_Converter {

    private bool $processing = false;

    public function __construct() {
        add_action('woocommerce_checkout_update_order_review', [$this, 'store_selected_payment_method'], 10, 1);
        add_filter('woocommerce_currency', [$this, 'force_usd_for_paypal'], 999);
        add_action('woocommerce_before_calculate_totals', [$this, 'convert_cart_prices_for_paypal'], 999);
        add_filter('woocommerce_get_price_html', [$this, 'show_dual_price_message'], 20, 2);
    }

    public function store_selected_payment_method($posted_data) {
        if (!function_exists('WC') || !WC()->session) return;

        parse_str($posted_data, $data);

        if (!empty($data['payment_method'])) {
            WC()->session->set(
                'chosen_payment_method',
                sanitize_text_field($data['payment_method'])
            );
        }
    }

    private function selected_gateway(): string {
        if (!function_exists('WC') || !WC()->session) return '';

        if (!empty($_POST['payment_method'])) {
            return sanitize_text_field(wp_unslash($_POST['payment_method']));
        }

        return (string) WC()->session->get('chosen_payment_method');
    }

    private function is_paypal_selected(): bool {
        if (!KQPU_Settings::is_enabled()) return false;

        return in_array(
            $this->selected_gateway(),
            KQPU_Settings::get_paypal_gateway_ids(),
            true
        );
    }

    public function force_usd_for_paypal($currency) {
        if (is_admin() && !wp_doing_ajax()) return $currency;

        return $this->is_paypal_selected() ? 'USD' : $currency;
    }

    public function convert_cart_prices_for_paypal($cart) {
        if (is_admin() && !wp_doing_ajax()) return;
        if (!$cart || $cart->is_empty()) return;
        if (!$this->is_paypal_selected()) return;
        if ($this->processing) return;

        $this->processing = true;

        $rate = KQPU_Settings::get_exchange_rate();

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (empty($cart_item['data'])) continue;

            $product = $cart_item['data'];

            if (!isset($cart_item['kqpu_original_price_bob'])) {
                $cart->cart_contents[$cart_item_key]['kqpu_original_price_bob'] = (float) $product->get_price();
            }

            $original_bob = (float) $cart->cart_contents[$cart_item_key]['kqpu_original_price_bob'];
            $usd_price = round($original_bob / $rate, 2);

            $product->set_price($usd_price);
        }

        $this->processing = false;
    }

    public function show_dual_price_message($price_html, $product) {
        if (is_admin()) return $price_html;

        $rate = KQPU_Settings::get_exchange_rate();
        $price_bob = (float) $product->get_price();

        if ($price_bob <= 0) return $price_html;

        $price_usd = round($price_bob / $rate, 2);

        return $price_html . '<br><small style="opacity:.75;">Pago PayPal aprox.: USD ' . esc_html(number_format($price_usd, 2)) . '</small>';
    }
}