<?php

if (!defined('ABSPATH')) exit;

class KQPU_PayPal_Integration {

    public function __construct() {
        // Solo hooks que sabemos que funcionan
        add_filter('ppcp_create_order_request_body_data', [$this, 'convert_paypal_payload_to_usd'], 999, 3);
        add_filter('ppcp_patch_order_request_body_data', [$this, 'convert_paypal_payload_to_usd'], 999, 3);
        add_filter('woocommerce_paypal_payments_sdk_url_params', [$this, 'force_sdk_currency_usd'], 999);
        add_filter('ppcp_sdk_url_params', [$this, 'force_sdk_currency_usd'], 999);
    }

    public function force_sdk_currency_usd($params) {
        if (!is_array($params)) {
            return $params;
        }
        $params['currency'] = 'USD';
        return $params;
    }

    public function convert_paypal_payload_to_usd($data, $payment_method = null, $request_data = null) {
        if (!KQPU_Settings::is_enabled()) {
            return $data;
        }

        if (is_array($data)) {
            return $this->convert_recursive($data);
        }

        return $data;
    }

    private function convert_recursive($value) {
        if (is_object($value)) {
            $value = (array) $value;
        }

        if (!is_array($value)) {
            return $value;
        }

        if (isset($value['currency_code'])) {
            $value['currency_code'] = 'USD';
            if (isset($value['value'])) {
                $value['value'] = $this->convert_amount_to_usd($value['value']);
            }
        }

        foreach ($value as $key => $child) {
            $value[$key] = $this->convert_recursive($child);
        }

        return $value;
    }

    private function convert_amount_to_usd($amount): string {
        $bob = (float) $amount;
        if (!class_exists('KQPU_Exchange_Rate')) {
            return number_format($bob, 2, '.', '');
        }
        $usd = KQPU_Exchange_Rate::bob_to_usd_amount($bob);
        return number_format($usd, 2, '.', '');
    }
}