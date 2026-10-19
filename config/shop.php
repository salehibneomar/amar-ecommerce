<?php

return [
    'order_prefix' => env('SHOP_ORDER_PREFIX', 'AE'),
    'max_extra_discount' => (int) env('SHOP_MAX_EXTRA_DISCOUNT', 50),
    'offer_global_only' => (bool) env('OFFER_GLOBAL_ONLY', false),
    'owner_email' => env('SHOP_OWNER_EMAIL'),
    'support_email' => env('SUPPORT_EMAIL'),
    'messenger_url' => env('FACEBOOK_MESSENGER_URL'),
    'facebook_page_url' => env('FACEBOOK_PAGE_URL'),
    'facebook_group_url' => env('FACEBOOK_GROUP_URL'),
    'whatsapp_number' => env('WHATSAPP_NUMBER'),
    'whatsapp_country_code' => env('WHATSAPP_COUNTRY_CODE', '880'),
];
