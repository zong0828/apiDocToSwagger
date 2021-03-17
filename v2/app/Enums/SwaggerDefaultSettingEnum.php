<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * SwaggerDefaultSettingEnum class
 */
final class SwaggerDefaultSettingEnum extends Enum
{
    const BASE_FORMAT = [
        'openapi'    => '3.0.1',
        'info'       => [
            'title'       => 'UBSS API DOCUMENT',
            'version'     => '1.0.0',
            'description' => "結合 php team , node.js team api 文件，如果有文件上的疑慮，可以點上方原始文件連結確認，是否是轉譯的時候發生錯誤，還是原始文件無定義清楚。"
        ],
        'tags'       => [],
        'paths'      => [],
        'components' => [
            'securitySchemes' => [
                'bearerAuth' => [
                    'type'         => 'http',
                    'scheme'       => 'bearer',
                    'bearerFormat' => 'JWT'
                ]
            ],
            'schemas'         => [
                'undefinition_response' => [
                    'type'        => 'object',
                    'description' => '原始文件缺少範例資料'
                ]
            ]
        ]
    ];

    const SUCCESS_EXAMPLE = [
        '200' => [
            'description' => 'undefined 200',
            'content'     => [
                'application/json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/undefinition_response'
                    ]
                ]
            ]
        ]
    ];
}
