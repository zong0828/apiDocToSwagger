<?php

return [
    'exception' => [
        'Illuminate\Auth\AuthenticationException' => [
            'type'         => 'UNAUTHORIZED',
            'errorCode'    =>'00003',
            'errorMessage' => 'AccessToken 驗證失敗'
        ],
        'Illuminate\Validation\ValidationException' => [
            'type'         => 'ARGUMENT_ERROR',
            'errorCode'    => '00002',
            'errorMessage' => '參數錯誤'
        ],
        'Symfony\Component\HttpKernel\Exception\NotFoundHttpException' => [
            'type'         => 'NOT_FOUND',
            'errorCode'    => '00001',
            'errorMessage' => '查無此API'
        ],
    ]
];
