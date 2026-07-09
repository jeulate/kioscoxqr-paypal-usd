<?php

if (!defined('ABSPATH')) exit;

class KQPU_Checkout_Display {

    public function __construct() {
        // Script para checkout clásico
        add_action('wp_footer', [$this, 'checkout_switcher_script']);
        
        // Para checkout Blocks (nuevo)
        add_action('woocommerce_blocks_checkout_block_registration', [$this, 'register_blocks_integration']);
        
        // Estilos
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        
        // Filtro para el total en el carrito
        add_filter('woocommerce_cart_total', [$this, 'modify_cart_total_display'], 99);
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
                padding: 8px 12px;
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
            .kqpu-usd-note .exchange-rate {
                font-size: 11px;
                color: #666;
                display: block;
                margin-top: 2px;
            }
            /* Para checkout blocks */
            .wc-block-components-totals-item__price .kqpu-usd-note {
                background: transparent;
                border-left: none;
                padding: 4px 0;
                font-size: 12px;
            }
        </style>
        <?php
    }

    public function checkout_switcher_script() {
        // Solo en checkout clásico
        if (!function_exists('is_checkout') || !is_checkout() || is_order_received_page()) {
            return;
        }

        // Detectar si es checkout Blocks
        if ($this->is_blocks_checkout()) {
            return; // Los blocks usan otro método
        }

        $rate = KQPU_Exchange_Rate::get_rate();
        $gateways = KQPU_Settings::get_paypal_gateway_ids();
        ?>
        <script>
            jQuery(function($) {
                const rate = <?php echo json_encode($rate); ?>;
                const paypalGateways = <?php echo json_encode($gateways); ?>;
                let updateTimeout;

                function updateCurrencyView() {
                    clearTimeout(updateTimeout);
                    updateTimeout = setTimeout(function() {
                        const selected = $('input[name="payment_method"]:checked').val();
                        const isPaypal = paypalGateways.includes(selected);

                        // Remover notas anteriores
                        $('.kqpu-usd-note').remove();

                        if (isPaypal) {
                            // Buscar todos los montos en el checkout
                            $('.order-total .woocommerce-Price-amount, .cart-subtotal .woocommerce-Price-amount, .tax-total .woocommerce-Price-amount').each(function() {
                                const element = $(this);
                                if (element.next('.kqpu-usd-note').length === 0) {
                                    const text = element.text().replace(/[^\d,\.]/g, '').replace(',', '.');
                                    const bob = parseFloat(text);

                                    if (!isNaN(bob) && bob > 0) {
                                        const usd = (bob / rate).toFixed(2);
                                        const note = $('<div class="kqpu-usd-note">' +
                                            '<span class="badge">💳 PayPal</span> ' +
                                            'Se cobrarán <strong>USD ' + usd + '</strong> ' +
                                            '<span class="exchange-rate">Tasa: 1 USD = ' + rate.toFixed(2) + ' Bs</span>' +
                                            '</div>');
                                        
                                        element.after(note);
                                    }
                                }
                            });
                        }

                        // Disparar evento para actualizar checkout
                        $('body').trigger('update_checkout');
                    }, 100);
                }

                // Eventos
                $('form.checkout').on('change', 'input[name="payment_method"]', updateCurrencyView);
                $('body').on('updated_checkout', updateCurrencyView);
                
                // Inicializar
                updateCurrencyView();

                // Re-ejecutar después de cambios
                $(document).ajaxComplete(function(event, xhr, settings) {
                    if (settings.url && settings.url.indexOf('update_order_review') !== -1) {
                        updateCurrencyView();
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Registrar integración con checkout blocks
     */
    public function register_blocks_integration($integration_registry) {
        // Registrar script para blocks
        wp_register_script(
            'kqpu-blocks-integration',
            KQPU_PLUGIN_URL . 'assets/js/blocks-integration.js',
            ['wc-blocks-checkout'],
            KQPU_VERSION,
            true
        );

        // Pasar datos al script
        wp_localize_script('kqpu-blocks-integration', 'kqpuBlocksData', [
            'rate' => KQPU_Exchange_Rate::get_rate(),
            'gateways' => KQPU_Settings::get_paypal_gateway_ids(),
            'currency' => 'USD',
            'symbol' => '$'
        ]);

        // Registrar la integración
        $integration_registry->register(
            'kqpu-paypal-converter',
            [
                'script' => 'kqpu-blocks-integration',
            ]
        );
    }

    /**
     * Modificar el total en el carrito
     */
    public function modify_cart_total_display($total) {
        if (!is_checkout() || !KQPU_Settings::is_enabled()) {
            return $total;
        }

        $payment_method = WC()->session->get('chosen_payment_method');
        if (!in_array($payment_method, KQPU_Settings::get_paypal_gateway_ids(), true)) {
            return $total;
        }

        // Extraer el número del total
        $amount = preg_replace('/[^0-9,.]/', '', $total);
        $amount = (float) str_replace(',', '.', $amount);
        
        if ($amount > 0) {
            $rate = KQPU_Settings::get_exchange_rate();
            $usd = number_format($amount / $rate, 2, '.', '');
            return $total . ' <span style="font-size:0.85em;opacity:0.7;display:block;">≈ USD ' . $usd . '</span>';
        }

        return $total;
    }

    /**
     * Detectar si es checkout blocks
     */
    private function is_blocks_checkout() {
        if (!function_exists('has_block')) {
            return false;
        }
        
        // Verificar si la página contiene el bloque de checkout
        global $post;
        if ($post && has_block('woocommerce/checkout', $post->post_content)) {
            return true;
        }
        
        return false;
    }
}