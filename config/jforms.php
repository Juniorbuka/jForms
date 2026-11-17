<?php

return [
    /*
     * Define every form server-side. Keys in `rules` are the only fields
     * accepted from the browser; validation rules are never supplied by it.
     */
    'forms' => [
        'contact' => [
            'name' => 'Зворотний дзвінок',
            'success_message' => 'Дякуємо! Ми скоро з вами зв’яжемося.',
            'rules' => [
                'phone' => ['required', 'string', 'max:40'],
            ],
            'messages' => [],
            'honeypot' => 'website',
            'rate_limit' => ['attempts' => 10, 'decay_seconds' => 60],
            'max_payload_bytes' => 16384,
        ],
    ],
];
