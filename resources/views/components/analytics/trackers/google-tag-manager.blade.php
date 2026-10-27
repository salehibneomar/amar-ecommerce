@props(['trackingId'])

<script>
    window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
    window.analyticsHandlers.push((name, params) => {
        window.dataLayer.push({ ecommerce: null });
        window.dataLayer.push({ event: name, ecommerce: params });
    });
</script>
<script async src="https://www.googletagmanager.com/gtm.js?id={{ $trackingId }}"></script>
