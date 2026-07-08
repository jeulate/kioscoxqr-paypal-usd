<?php

if (!defined('WP_UNINSTALL_PLUGIN')) exit;

delete_option('kqpu_enabled');
delete_option('kqpu_exchange_rate');
delete_option('kqpu_paypal_gateways');