<?php

if (!defined('ABSPATH')) exit;

class KQPU_Settings {

    const OPTION_ENABLED = 'kqpu_enabled';
    const OPTION_RATE = 'kqpu_exchange_rate';
    const OPTION_PAYPAL_GATEWAYS = 'kqpu_paypal_gateways';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            'PayPal USD Converter',
            'PayPal USD Converter',
            'manage_woocommerce',
            'kqpu-settings',
            [$this, 'render_page']
        );
    }

    public function register_settings() {
        register_setting('kqpu_settings_group', self::OPTION_ENABLED);
        register_setting('kqpu_settings_group', self::OPTION_RATE);
        register_setting('kqpu_settings_group', self::OPTION_PAYPAL_GATEWAYS);
    }

    public function render_page() {
        ?>
        <div class="wrap">
            <h1>KioscoxQR PayPal USD Converter</h1>

            <form method="post" action="options.php">
                <?php settings_fields('kqpu_settings_group'); ?>

                <table class="form-table">
                    <tr>
                        <th>Activar conversión</th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       name="<?php echo esc_attr(self::OPTION_ENABLED); ?>"
                                       value="yes"
                                    <?php checked(get_option(self::OPTION_ENABLED, 'yes'), 'yes'); ?>>
                                Convertir PayPal de BOB a USD
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th>Tipo de cambio</th>
                        <td>
                            <input type="number"
                                   step="0.0001"
                                   min="0.0001"
                                   name="<?php echo esc_attr(self::OPTION_RATE); ?>"
                                   value="<?php echo esc_attr(get_option(self::OPTION_RATE, '6.96')); ?>">
                            <p class="description">Ejemplo: si 1 USD = 6.96 Bs, coloca 6.96.</p>
                        </td>
                    </tr>

                    <tr>
                        <th>IDs de métodos PayPal</th>
                        <td>
                            <input type="text"
                                   style="width: 420px;"
                                   name="<?php echo esc_attr(self::OPTION_PAYPAL_GATEWAYS); ?>"
                                   value="<?php echo esc_attr(get_option(self::OPTION_PAYPAL_GATEWAYS, 'ppcp-gateway,paypal')); ?>">
                            <p class="description">
                                Para WooCommerce PayPal Payments normalmente usa:
                                <code>ppcp-gateway</code>. Separar varios con coma.
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Guardar configuración'); ?>
            </form>
        </div>
        <?php
    }

    public static function is_enabled() {
        return get_option(self::OPTION_ENABLED, 'yes') === 'yes';
    }

    public static function get_exchange_rate() {
        $rate = (float) get_option(self::OPTION_RATE, '6.96');
        return $rate > 0 ? $rate : 6.96;
    }

    public static function get_paypal_gateway_ids() {
        $value = get_option(self::OPTION_PAYPAL_GATEWAYS, 'ppcp-gateway,paypal');
        return array_filter(array_map('trim', explode(',', $value)));
    }
}