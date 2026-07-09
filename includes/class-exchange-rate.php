<?php

if (!defined('ABSPATH')) exit;

class KQPU_Exchange_Rate {

    /**
     * Obtiene la tasa de cambio desde settings
     */
    public static function get_rate(): float {
        $rate = (float) get_option('kqpu_exchange_rate', '6.96');
        return $rate > 0 ? $rate : 6.96;
    }

    /**
     * Convierte BOB a USD (método principal)
     */
    public static function bob_to_usd_amount(float $amount): float {
        $rate = self::get_rate();
        return $rate > 0 ? round($amount / $rate, 2) : 0;
    }

    /**
     * Convierte USD a BOB (para mostrar en admin)
     */
    public static function usd_to_bob_amount(float $amount): float {
        $rate = self::get_rate();
        return $rate > 0 ? round($amount * $rate, 2) : 0;
    }

    /**
     * Obtiene tasa desde API externa (opcional)
     */
    public static function fetch_live_rate(): float {
        $api_url = 'https://api.exchangerate-api.com/v4/latest/USD';
        $response = wp_remote_get($api_url, ['timeout' => 3]);
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($data['rates']['BOB'])) {
                return (float) $data['rates']['BOB'];
            }
        }
        
        return 0;
    }

    /**
     * Actualiza la tasa automáticamente (para usar en cron)
     */
    public static function update_rate_automatically() {
        $live_rate = self::fetch_live_rate();
        if ($live_rate > 0) {
            update_option('kqpu_exchange_rate', $live_rate);
            return true;
        }
        return false;
    }
}