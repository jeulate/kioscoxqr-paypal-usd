<?php

if (!defined('ABSPATH')) exit;

class KQPU_Checkout_Display {

    public function __construct() {
        add_action('wp_footer', [$this, 'checkout_switcher_script']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    public function enqueue_styles() {
        if (!function_exists('is_checkout') || !is_checkout()) {
            return;
        }
        ?>
        <style>
            .kqpu-usd-note {
                font-size: 13px;
                opacity: 0.8;
                color: #4a4a4a;
                margin-top: 4px;
                padding: 6px 10px;
                background: #f8f9fa;
                border-radius: 4px;
                border-left: 3px solid #0070ba;
            }
            .kqpu-usd-note .badge {
                background: #0070ba;
                color: white;
                padding: 2px 8px;
                border-radius: 12px;
                font-size: 11px;
                margin-right: 4px;
            }
        </style>
        <?php
    }

    public function checkout_switcher_script() {
        if (!function_exists('is_checkout') || !is_checkout() || is_order_received_page()) {
            return;
        }

        // Obtener datos
        if (!class_exists('KQPU_Exchange_Rate') || !class_exists('KQPU_Settings')) {
            return;
        }

        $rate     = KQPU_Exchange_Rate::get_rate();
        $gateways = KQPU_Settings::get_paypal_gateway_ids();
        $paypal_account_gateways = ['ppcp-gateway', 'paypal'];
        $paypal_card_gateways    = ['ppcp-card-button-gateway'];
        ?>
        <script>
            jQuery(function($) {
                const rate                 = <?php echo json_encode($rate); ?>;
                const paypalGateways        = <?php echo json_encode($gateways); ?>;
                const paypalAccountGateways = <?php echo json_encode($paypal_account_gateways); ?>;
                const paypalCardGateways    = <?php echo json_encode($paypal_card_gateways); ?>;
                let updateTimeout;

                function updateCurrencyView() {
                    clearTimeout(updateTimeout);
                    updateTimeout = setTimeout(function() {
                        const selected  = $('input[name="payment_method"]:checked').val();
                        const isPaypal  = paypalGateways.includes(selected);
                        const isAccount = paypalAccountGateways.includes(selected);
                        const isCard    = paypalCardGateways.includes(selected);

                        $('.kqpu-usd-note').remove();

                        if (isPaypal) {
                            $('.order-total .woocommerce-Price-amount, .cart-subtotal .woocommerce-Price-amount').each(function() {
                                const element = $(this);
                                if (element.next('.kqpu-usd-note').length === 0) {
                                    const text = element.text().replace(/[^\d,\.]/g, '').replace(',', '.');
                                    const bob  = parseFloat(text);

                                    if (!isNaN(bob) && bob > 0) {
                                        const usd   = (bob / rate).toFixed(2);
                                        let label   = 'PayPal';
                                        let message = 'Se cobrarán <strong>USD ' + usd + '</strong> (tasa: 1 USD = ' + rate.toFixed(2) + ' Bs)';

                                        if (isAccount) {
                                            label   = 'PayPal';
                                            message = 'Serás redirigido a PayPal o se abrirá la app. Se cobrarán <strong>USD ' + usd + '</strong>';
                                        } else if (isCard) {
                                            label   = 'Tarjeta';
                                            message = 'Pago seguro con tarjeta vía PayPal. Se cobrarán <strong>USD ' + usd + '</strong>';
                                        }

                                        const note = $('<div class="kqpu-usd-note">' +
                                            '<span class="badge">' + label + '</span> ' +
                                            message +
                                            ' <small>(tasa: 1 USD = ' + rate.toFixed(2) + ' Bs)</small>' +
                                            '</div>');
                                        element.after(note);
                                    }
                                }
                            });
                        }

                        $('body').trigger('update_checkout');
                    }, 200);
                }

                $('form.checkout').on('change', 'input[name="payment_method"]', updateCurrencyView);
                $('body').on('updated_checkout', updateCurrencyView);
                updateCurrencyView();

                $(document).ajaxComplete(function(event, xhr, settings) {
                    if (settings.url && settings.url.indexOf('update_order_review') !== -1) {
                        updateCurrencyView();
                    }
                });
            });
        </script>
        <?php
    }
}