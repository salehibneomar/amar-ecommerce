@props(['trackingId'])

<script>
    (function (window, document) {
        if (window.fbq) return;
        const fbq = window.fbq = function () { fbq.callMethod ? fbq.callMethod.apply(fbq, arguments) : fbq.queue.push(arguments); };
        window._fbq = fbq;
        fbq.push = fbq;
        fbq.loaded = true;
        fbq.version = '2.0';
        fbq.queue = [];
        const script = document.createElement('script');
        script.async = true;
        script.src = 'https://connect.facebook.net/en_US/fbevents.js';
        document.head.appendChild(script);
    })(window, document);

    window.fbq('init', @js($trackingId));
    window.fbq('track', 'PageView');

    window.analyticsHandlers.push((name, params) => {
        const event = { view_item: 'ViewContent', add_to_cart: 'AddToCart', begin_checkout: 'InitiateCheckout', purchase: 'Purchase' }[name];

        if (! event) return;

        const items = params.items || [];

        window.fbq('track', event, {
            content_type: 'product',
            content_ids: items.map((item) => item.item_id),
            contents: items.map((item) => ({ id: item.item_id, quantity: item.quantity, item_price: item.price })),
            num_items: items.reduce((total, item) => total + item.quantity, 0),
            value: params.value,
            currency: params.currency,
        }, params.transaction_id ? { eventID: params.transaction_id } : {});
    });
</script>
