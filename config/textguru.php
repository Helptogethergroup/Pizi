<?php

return [
    'username'    => env('TEXTGURU_USERNAME'),
    'password'    => env('TEXTGURU_PASSWORD'),
    'sender_id'   => env('TEXTGURU_SENDER_ID', 'NRSWAC'),
    'template_id' => env('TEXTGURU_TEMPLATE_ID'),
    'api_url'     => env('TEXTGURU_URL', 'https://www.textguru.in/api/v22.0/'),
];
