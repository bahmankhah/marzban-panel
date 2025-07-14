<?php

use App\Adapters\Panel\Contexts\Marzban;

return [
    'panel' => [
        'default' => 'marzban',
        'contexts' => [
            'marzban' => [
                'context' => Marzban::class,
                'baseurl'=> 'https://azad.kavestore.ir:8000'
            ],
        ],
    ],
];