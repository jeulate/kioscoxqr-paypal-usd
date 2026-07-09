<?php

if (!defined('ABSPATH')) exit;

class KQPU_PayPal_Integration {

    public function __construct() {
        add_filter('ppcp_create_order_request_body_data', [$this, 'convert_paypal_payload_to_usd'], 20, 3);
        add_filter('ppcp_patch_order_request_body_data', [$this, 'convert_paypal_patch_to_usd'], 20, 1);
    }

    public function convert_paypal_payload_to_usd($data, $payment_method = null, $request_data = null) {
        if (!KQPU_Settings::is_enabled()) {
            return $data;
        }

        return $this->convert_money_nodes($data);
    }

    public function convert_paypal_patch_to_usd($patches) {
        if (!KQPU_Settings::is_enabled()) {
            return $patches;
        }

        return $this->convert_money_nodes($patches);
    }

    private function convert_money_nodes($value) {
        if (!is_array($value)) {
            return $value;
        }

        if (isset($value['currency_code'], $value['value'])) {
            if ($value['currency_code'] === 'BOB') {
                $value['currency_code'] = 'USD';
                $value['value'] = $this->convert_amount_to_usd($value['value']);
            }

            return $value;
        }

        foreach ($value as $key => $child) {
            $value[$key] = $this->convert_money_nodes($child);
        }

        return $value;
    }

    private function convert_amount_to_usd($amount): string {
        $bob = (float) $amount;
        $usd = KQPU_Exchange_Rate::bob_to_usd_amount($bob);

        return number_format($usd, 2, '.', '');
    }
}