@if ($analyticsTrackers)
    <script>
        window.dataLayer = window.dataLayer || [];
        window.analyticsHandlers = [];
        window.trackAnalytics = (name, params) => window.analyticsHandlers.forEach((handler) => handler(name, params));
        window.addEventListener('analytics-event', (event) => window.trackAnalytics(event.detail.name, event.detail.params));
    </script>

    @foreach ($analyticsTrackers as $tracker)
        <x-dynamic-component :component="$tracker['component']" :tracking-id="$tracker['tracking_id']" />
    @endforeach
@endif
