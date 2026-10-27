@props(['trackingId'])

<script>
    window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', @js($trackingId));
    window.analyticsHandlers.push((name, params) => window.gtag('event', name, { ...params, send_to: @js($trackingId) }));
</script>
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $trackingId }}"></script>
