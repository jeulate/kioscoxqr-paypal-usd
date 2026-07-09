// assets/js/blocks-integration.js
(function() {
    'use strict';

    const { registerCheckoutFilters } = wc.blocksCheckout;
    const { withFilter } = wc.blocksCheckout;

    // Obtener datos del plugin
    const { rate, gateways, currency, symbol } = kqpuBlocksData || { rate: 6.96, gateways: ['ppcp-gateway'], currency: 'USD' };

    /**
     * Modificar el total en el checkout blocks
     */
    const modifyTotal = (value, extensions, args) => {
        const paymentMethod = args?.paymentMethod || '';
        const isPaypal = gateways.includes(paymentMethod);

        if (!isPaypal) {
            return value;
        }

        // Si el valor es numérico, convertirlo
        if (typeof value === 'number') {
            const usd = (value / rate).toFixed(2);
            return parseFloat(usd);
        }

        // Si es string, mostrar con nota
        if (typeof value === 'string' && value.includes('BOB')) {
            const amount = parseFloat(value.replace(/[^0-9.]/g, ''));
            if (!isNaN(amount) && amount > 0) {
                const usd = (amount / rate).toFixed(2);
                return `${symbol}${usd} USD`;
            }
        }

        return value;
    };

    /**
     * Agregar nota de conversión en el checkout
     */
    const addConversionNote = (value, extensions, args) => {
        const paymentMethod = args?.paymentMethod || '';
        const isPaypal = gateways.includes(paymentMethod);

        if (!isPaypal) {
            return value;
        }

        // Buscar el total en el contexto
        const total = args?.cart?.total?.value || 0;
        if (total > 0) {
            const usd = (total / rate).toFixed(2);
            return (
                <div>
                    {value}
                    <div className="kqpu-usd-note" style={{ fontSize: '12px', opacity: 0.7, marginTop: '4px' }}>
                        💳 Se cobrarán <strong>USD {usd}</strong> (tasa 1 USD = {rate.toFixed(2)} Bs)
                    </div>
                </div>
            );
        }

        return value;
    };

    // Registrar filtros para checkout blocks
    registerCheckoutFilters('kqpu-paypal-converter', {
        total: modifyTotal,
        subtotal: modifyTotal,
        shippingTotal: modifyTotal,
        taxTotal: modifyTotal,
    });

    console.log('KQPU PayPal Converter - Blocks Integration loaded');
})();