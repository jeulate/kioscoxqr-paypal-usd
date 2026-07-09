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
            }
            .kqpu-usd-note .badge {
                background: #0070ba;
                color: white;
                padding: 2px 8px;
                border-radius: 12px;
                font-size: 11px;
                margin-right: 4px;
            }
            .payment-method-option-paypal .kqpu-usd-note {
                background: #f0f8ff;
                padding: 8px 12px;
                border-radius: 4px;
                border-left: 3px solid #0070ba;
            }
        </style>
        <?php
    }

    public function checkout_switcher_script() {
        if (!function_exists('is_checkout') || !is_checkout() || is_order_received_page()) {
            return;
        }

        $rate = KQPU_Exchange_Rate::get_rate();
        ?>
        <script>
            jQuery(function($) {
                const rate = <?php echo json_encode($rate); ?>;
                let updateTimeout;

                function updateCurrencyView() {
                    clearTimeout(updateTimeout);
                    updateTimeout = setTimeout(function() {
                        const selected = $('input[name="payment_method"]:checked').val();
                        const paypalGateways = <?php echo json_encode(KQPU_Settings::get_paypal_gateway_ids()); ?>;
                        const isPaypal = paypalGateways.includes(selected);

                        // Remover notas anteriores
                        $('.kqpu-usd-note').remove();

                        if (isPaypal) {
                            // Buscar todos los montos en el checkout
                            $('.order-total .woocommerce-Price-amount, .cart-subtotal .woocommerce-Price-amount, .tax-total .woocommerce-Price-amount').each(function() {
                                const element = $(this);
                                // Verificar si ya tiene nota para evitar duplicados
                                if (element.next('.kqpu-usd-note').length === 0) {
                                    const text = element.text().replace(/[^\d,\.]/g, '').replace(',', '.');
                                    const bob = parseFloat(text);

                                    if (!isNaN(bob) && bob > 0) {
                                        const usd = (bob / rate).toFixed(2);
                                        const note = $('<div class="kqpu-usd-note"><span class="badge">USD</span> ≈ ' + usd + ' USD</div>');
                                        
                                        // Si es el total, agregar un mensaje más descriptivo
                                        if (element.closest('.order-total').length) {
                                            note.html('<span class="badge">💳 PayPal</span> Se cobrarán aproximadamente <strong>USD ' + usd + '</strong> (tasa 1 USD = ' + rate + ' Bs)');
                                        }
                                        
                                        element.after(note);
                                    }
                                }
                            });
                        }

                        // Disparar evento para que WooCommerce actualice
                        $('body').trigger('update_checkout');
                    }, 100);
                }

                // Observar cambios en métodos de pago
                $('form.checkout').on('change', 'input[name="payment_method"]', updateCurrencyView);
                
                // Observar cambios en el total (por cupones, etc)
                $('body').on('updated_checkout', updateCurrencyView);
                
                // Ejecutar inicialmente
                updateCurrencyView();

                // Re-ejecutar después de cambios en el carrito
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