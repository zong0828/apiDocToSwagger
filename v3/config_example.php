<?php

$rocketDomain = '';

$message = "";

$account = [
    'user'     => '',
    'password' => ''
];

// ini master setting
$env = [
    'php' => [
        "doc" => [
            "backend_master" => "",
            "backend_brand"  => "",
            "backend_dos"  => ""
        ],
        "doc_data" => [
            "backend_master" => "",
            "backend_brand"  => "",
            "backend_dos"  => "",
        ]
    ],
    'node.js' => [
        "doc" => [
            "backend"  => "",
            "frontend" => ""
        ],
        "doc_data" => [
            "backend"  => "",
            "frontend" => ""
        ]
    ]
];

// swagger basic setting
$basicFormat = [
    'openapi' => '3.0.1',
    'info' => [
        'title'       => 'API DOCUMENT',
        'version'     => '1.0.0',
        'description' => "結合 php team , node.js team api 文件，如果有文件上的疑慮，可以點上方原始文件連結確認，是否是轉譯的時候發生錯誤，還是原始文件無定義清楚。"
    ],
    'servers' => [
        0 => [
            'url'         => '',
            'description' => 'the alpha server api'
        ],
        1 => [
            'url'         => '',
            'description' => 'the beta server api'
        ]
    ]
];