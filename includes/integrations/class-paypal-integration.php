<?php

if (!defined('ABSPATH')) exit;

class KQPU_PayPal_Integration {

    public function __construct() {
        // Hooks principales para conversión
        add_filter('ppcp_create_order_request_body_data', [$this, 'convert_paypal_payload_to_usd'], 999, 3);
        add_filter('ppcp_patch_order_request_body_data', [$this, 'convert_paypal_payload_to_usd'], 999, 3);
        
        // Forzar moneda USD en SDK
        add_filter('woocommerce_paypal_payments_sdk_url_params', [$this, 'force_sdk_currency_usd'], 999);
        add_filter('ppcp_sdk_url_params', [$this, 'force_sdk_currency_usd'], 999);
        add_filter('woocommerce_paypal_payments_sdk_script_url', [$this, 'force_sdk_url_currency_usd'], 999);
        
        // Log de depuración (opcional)
        add_action('ppcp_before_order_request', [$this, 'log_conversion_debug'], 10, 2);
    }

    /**
     * Forzar USD en la URL del SDK
     */
    public function force_sdk_url_currency_usd($url) {
        if (!is_string($url)) {
            return $url;
        }

        $url = remove_query_arg('currency', $url);
        $url = add_query_arg('currency', 'USD', $url);

        return $url;
    }

    /**
     * Forzar USD en parámetros del SDK
     */
    public function force_sdk_currency_usd($params) {
        if (!is_array($params)) {
            return $params;
        }

        $params['currency'] = 'USD';

        return $params;
    }

    /**
     * Convertir todo el payload de PayPal a USD
     */
    public function convert_paypal_payload_to_usd($data, $payment_method = null, $request_data = null) {
        // Verificar si el plugin está activo
        if (!KQPU_Settings::is_enabled()) {
            return $data;
        }

        // Si es un array, convertir recursivamente
        if (is_array($data)) {
            return $this->convert_recursive($data);
        }

        return $data;
    }

    /**
     * Conversión recursiva de todos los montos en el payload
     */
    private function convert_recursive($value) {
        // Convertir objeto a array si es necesario
        if (is_object($value)) {
            $value = json_decode(json_encode($value), true);
        }

        if (!is_array($value)) {
            return $value;
        }

        // Si tiene currency_code, convertirlo a USD
        if (isset($value['currency_code'])) {
            $value['currency_code'] = 'USD';

            if (isset($value['value'])) {
                $value['value'] = $this->convert_amount_to_usd($value['value']);
            }
        }

        // Si tiene items, convertir cada item
        if (isset($value['items']) && is_array($value['items'])) {
            foreach ($value['items'] as &$item) {
                if (isset($item['unit_amount']['value'])) {
                    $item['unit_amount']['value'] = $this->convert_amount_to_usd($item['unit_amount']['value']);
                    $item['unit_amount']['currency_code'] = 'USD';
                }
                if (isset($item['tax']['value'])) {
                    $item['tax']['value'] = $this->convert_amount_to_usd($item['tax']['value']);
                    $item['tax']['currency_code'] = 'USD';
                }
            }
        }

        // Procesar recursivamente los hijos
        foreach ($value as $key => $child) {
            $value[$key] = $this->convert_recursive($child);
        }

        return $value;
    }

    /**
     * Convierte un monto de BOB a USD
     */
    private function convert_amount_to_usd($amount): string {
        // Si es string, limpiar
        if (is_string($amount)) {
            $amount = (float) str_replace(['$', ',', ' '], '', $amount);
        }
        
        $bob = (float) $amount;
        $usd = KQPU_Exchange_Rate::bob_to_usd_amount($bob);

        return number_format($usd, 2, '.', '');
    }

    /**
     * Log de depuración para verificar conversión
     */
    public function log_conversion_debug($request, $order) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        if (isset($request['purchase_units'][0]['amount'])) {
            $amount = $request['purchase_units'][0]['amount'];
            error_log('[KQPU] PayPal Conversion Debug:');
            error_log('[KQPU] Currency: ' . ($amount['currency_code'] ?? 'NO SET'));
            error_log('[KQPU] Amount: ' . ($amount['value'] ?? 'NO SET'));
            error_log('[KQPU] Rate used: ' . KQPU_Exchange_Rate::get_rate());
        }
    }
}