<?php

if (!defined('ABSPATH')) exit;

class KQPU_Exchange_Rate {

    public static function bob_to_usd(): float {
        $rate = (float) get_option('kqpu_exchange_rate', '6.96');
        return $rate > 0 ? $rate : 6.96;
    }

    public static function bob_to_usd_amount(float $amount): float {
        return round($amount / self::bob_to_usd(), 2);
    }

    public static function bob_to_ars(): float {
        $rate = (float) get_option('kqpu_exchange_rate_ars', '1');
        return $rate > 0 ? $rate : 1;
    }
}