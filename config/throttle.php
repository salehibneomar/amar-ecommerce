<?php

return [
    'login' => (int) env('THROTTLE_LOGIN_ATTEMPTS', 5),
    'register' => (int) env('THROTTLE_REGISTER_ATTEMPTS', 2),
    'checkout' => (int) env('THROTTLE_CHECKOUT_ATTEMPTS', 5),
    'track_order' => (int) env('THROTTLE_TRACK_ORDER_ATTEMPTS', 10),
    'password_reset' => (int) env('THROTTLE_PASSWORD_RESET_ATTEMPTS', 10),
    'password_reset_link' => (int) env('THROTTLE_PASSWORD_RESET_LINK_ATTEMPTS', 10),
    'assistant' => (int) env('THROTTLE_ASSISTANT_ATTEMPTS', 10),
    'verification_email' => (int) env('THROTTLE_VERIFICATION_EMAIL_ATTEMPTS', 10),
];
