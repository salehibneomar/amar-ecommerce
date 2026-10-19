<x-ui.error-page
    code="503"
    :title="__('Back soon')"
    :message="__('We are doing some maintenance. Please check back shortly.')"
    :note="$maintenanceEndsAt ? __('Expected back by :time', ['time' => formatDateTime($maintenanceEndsAt)]) : null"
    :actions="false"
/>
