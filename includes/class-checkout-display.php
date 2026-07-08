<?php

if (!defined('ABSPATH')) exit;

class KQPU_Checkout_Display {

    public function __construct() {
        add_action('wp_footer', [$this, 'checkout_switcher_script']);
    }

    public function checkout_switcher_script() {
        if (!function_exists('is_checkout') || !is_checkout() || is_order_received_page()) {
            return;
        }

        $rate = KQPU_Exchange_Rate::bob_to_usd();
        ?>
        <script>
            jQuery(function($) {
                const rate = <?php echo json_encode($rate); ?>;

                function updateCurrencyView() {
                    const selected = $('input[name="payment_method"]:checked').val();
                    const isPaypal = selected === 'ppcp-gateway' || selected === 'ppcp-card-button-gateway';

                    $('.kqpu-usd-note').remove();

                    if (isPaypal) {
                        $('.order-total .woocommerce-Price-amount').each(function() {
                            const text = $(this).text().replace(/[^\d,\.]/g, '').replace(',', '.');
                            const bob = parseFloat(text);

                            if (!isNaN(bob)) {
                                const usd = (bob / rate).toFixed(2);
                                $(this).after('<div class="kqpu-usd-note" style="font-size:13px;opacity:.75;">PayPal cobrará aprox. USD ' + usd + '</div>');
                            }
                        });
                    }

                    $('body').trigger('update_checkout');
                }

                $('form.checkout').on('change', 'input[name="payment_method"]', updateCurrencyView);
                updateCurrencyView();
            });
        </script>
        <?php
    }
}