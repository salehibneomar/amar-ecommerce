@props(['event' => null])

@if ($event)
    <script>
        window.trackAnalytics?.(@js($event['name']), @js($event['params']));
    </script>
@endif
