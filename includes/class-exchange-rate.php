<?php

if (!defined('ABSPATH')) exit;

class KQPU_Exchange_Rate {

    public static function get_rate(): float {
        $rate = (float) get_option('kqpu_exchange_rate', '6.96');
        return $rate > 0 ? $rate : 6.96;
    }

    public static function bob_to_usd_amount(float $amount): float {
        $rate = self::get_rate();
        if ($rate <= 0) {
            return 0;
        }
        return round($amount / $rate, 2);
    }

    public static function usd_to_bob_amount(float $amount): float {
        $rate = self::get_rate();
        if ($rate <= 0) {
            return 0;
        }
        return round($amount * $rate, 2);
    }
}