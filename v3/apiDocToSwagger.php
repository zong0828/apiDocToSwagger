<?php

/**
 * 轉換 api doc -> swagger tags、path、component
 */
class apiDocToSwagger {

    private $undefined_success_example = [
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
    private $team;
    private $docUrl;

    public function __construct($team, $docUrl)
    {
        if (empty($team) || empty($docUrl)) {
            throw new Exception("未帶必要參數");
        }

        $this->team = $team;
        $this->docUrl = $docUrl;
    }

    /**
     *  @param $apiDoc array api doc data
     *
     *  @return $swagger array swagger data
     */
    public function main($apiDoc)
    {
        $swaggerDoc = [
            'tags'   => [],
            'paths' => [],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type'         => 'http',
                        'scheme'       => 'bearer',
                        'bearerFormat' => 'JWT'
                    ]
                ],
                'schemas' => [
                    'undefinition_response' => [
                        'type'        => 'object',
                        'description' => '原始文件缺少範例資料'
                    ]
                ]
            ]
        ];

        foreach ($apiDoc as $apiCollection) {
            $this->assertMissingColumns($apiCollection);
            $swaggerDoc = $this->convertToSwagger($swaggerDoc, $apiCollection);
        }

        return $swaggerDoc;
    }

    /**
     * 把單一的 api 集合轉換成 swagger json
     */
    private function convertToSwagger($swaggerDoc, $api)
    {
        $url = $this->setUrl($api['url']);
        $method = $this->setMethod($api['type']);
        $group = $api['group'];
        $params = isset($api['parameter']['fields']['Parameter']) ? $api['parameter']['fields']['Parameter'] : [];
        $apiDocSuccess = isset($api['success']) ?  $api['success']: [];
        $header = isset($api['header']['fields']['Header']) ? $api['header']['fields']['Header'] : [];
        $apiDocError = isset($api['error']) ?  $api['error']: [];

        $tags = $this->setSwaggerTags($swaggerDoc['tags'], $group);
        $swaggerDoc['tags'] = $tags;
        // 1. set swagger paths
        $swaggerDoc['paths'][$url][$method] = [
            'tags'         => [
                              $group
            ],
            'summary'      => $api['title'],
            'description'  => $api['name'],
            'responses'    => $this->undefined_success_example
        ];

        $parameters = [];
        // 檢查 url 是否有 path parameter
        $pathParams = $this->getPathParams($url);
        if (!empty($pathParams)) {
            // 從 params 取出 path parameters
            $result = $this->setSwaggerPathParams($pathParams, $params);
            $parameters = array_merge($parameters, $result['pathParams']);
            $params = $result['params'];
        }

        if (!empty($header)) {
            $requiredAuth = $this->requireAuth($header);

            // 目前只支援 bearerAuth
            if ($requiredAuth) {
                $swaggerDoc['paths'][$url][$method]['security'][0]['bearerAuth'] = [];
            }

            $parameters = $this->setSwaggerHeaderParams($parameters, $header);
        }

        // 檢查 apidoc 是否有參數
        $swaggerParams = [];
        if (!empty($params)) {
            $swaggerParams = $this->setParams($method, $params);
            if ($method == 'get') {
                $parameters = array_merge($parameters, $swaggerParams);
            } else {
                $swaggerDoc['paths'][$url][$method]['requestBody'] = [
                    'description' => 'request body',
                    'required'    => true,
                    'content'     => [
                        'application/json' => [
                            'schema' => $swaggerParams
                        ]
                    ]
                ];

                if (!empty($api['parameter']['examples'][0]['content'])) {
                    $bodyRequest = json_decode($api['parameter']['examples'][0]['content'], true);
                    if (!empty($bodyRequest)) {
                        $swaggerDoc['paths'][$url][$method]['requestBody']['content']['application/json']['schema']['example'] = $bodyRequest;
                    }
                }
            }
        }

        if (!empty($parameters)) {
            $swaggerDoc['paths'][$url][$method]['parameters'] = $parameters;
        }

        if (!empty($apiDocSuccess)) {
            $schemaName = "{$api['name']}SuccssResponse";
            $swaggerDoc['paths'][$url][$method]['responses']['200'] = [
                'description' => "HTTP/1.1 200 OK",
                'content'     => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => "#/components/schemas/{$schemaName}"
                        ]
                    ]
                ]
            ];

            $swaggerDoc['components']['schemas'][$schemaName] = $this->setResponseData($apiDocSuccess, 'success');
        }

        if (!empty($apiDocError)) {
            $schemaName = "{$api['name']}ErrorResponse";
            $errorCode = ($this->team === 'php') ? '400' : '403';
            $swaggerDoc['paths'][$url][$method]['responses'][$errorCode] = [
                'description' => "Error-Response HTTP/1.1 {$errorCode}",
                'content'     => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => "#/components/schemas/{$schemaName}"
                        ]
                    ]
                ]
            ];

            $swaggerDoc['components']['schemas'][$schemaName] = $this->setResponseData($apiDocError, 'error');
        }

        return $swaggerDoc;
    }

    /**
     * 檢查是否需要 auth，預設 auth 只有 Bearer Authorization
     */
    private function requireAuth($headerAry)
    {
        $requiredAuth = false;

        foreach ($headerAry as $headerSet) {
            if ($headerSet['field'] == 'Authorization') {
                $requiredAuth = true;
                break;
            }
        }

        return $requiredAuth;
    }

    /**
     * 把 apidoc 的 header 放入 swagger parameters
     */
    private function setSwaggerHeaderParams($parameters, $headerAry)
    {
        foreach ($headerAry as $headerSet) {
            if ($headerSet['field'] == 'Authorization') {
                continue;
            }

            $param = [
                'in'   => 'header',
                'name' => $headerSet['field'],
                'schema' => [
                    'type' => 'string'
                ],
                'description' => $headerSet['description'],
            ];

            if (!$headerSet['optional']) {
                $param['required'] = true;
            }
            $parameters[] = $param;
        }

        return $parameters;
    }

    /**
     * 設定 path params ，並且去除 api doc 中的 params
     */
    private function setSwaggerPathParams($pathParams, $params)
    {
        $pathAry = [];
        $paramsAry = $params;

        foreach ($params as $index => $param) {
            if (isset($pathParams[$param['field']])) {
                $pathAry[] = [
                    'in'     => 'path',
                    'name'   => $param['field'],
                    'schema' => [
                        "type" => 'string'
                    ],
                    'required' => true,
                    'description' => $param['description']
                ];

                unset($paramsAry[$index]);
            }
        }

        if (!empty($pathParams)) {
            $pathAry = $this->checkMissingPath($pathParams, $pathAry);
        }

        return [
            'pathParams'  => $pathAry,
            'params'      => $paramsAry
        ];
    }

    /**
     * 根據 api method 判斷 params 的參數是 body 還是 query
     */
    private function setParams($method, $apiDocParams) {

        $params = [];

        foreach ($apiDocParams as $apiDocParam) {
            if ($method === 'get') {
                $param = $this->setQueryParams($apiDocParam);
                $params[]= $param;
                continue;
            } else {
                $params = $this->setBodyParams($apiDocParam, $params);
            }
        }//end foreach

        if ($method !== 'get' && !empty($params)) {
            // var_export($params);exit;
            //TODO array 的改法可能要看一下有沒有問題
            $this->checkArrayThenSetItems($params['properties']);
        }

        return $params;
    }

    /**
     * set body params
     */
    private function setBodyParams($apiDocParam, $result)
    {
        $result['type'] = "object";

        // 用 field 判斷層數
        $columnAry = explode('.', $apiDocParam['field']);
        // TODO have to refactor this function
        $info = $this->convertMutipleAry($columnAry, $apiDocParam, $result);
        $result = array_merge($result, $info);

        return $result;
    }

    /**
     * set query params
     */
    private function setQueryParams($apiDocParam)
    {
        // basic
        $param = [
            'in'     => 'query',
            'name'   => $apiDocParam['field'],
            'schema' => [
                "type" => isset($typeMap[$apiDocParam['type']]) ? $typeMap[$apiDocParam['type']] : 'string'
            ],
            'description' => $apiDocParam['description']
        ];

        if (isset($apiDocParam['allowedValues']) && !empty($apiDocParam['allowedValues'])) {
            foreach ($apiDocParam['allowedValues'] as $allowedValue) {
                $param['description'] .= "\n {$allowedValue}";
                $enum = $this->getEnumValue($allowedValue);
                $param['schema']['enum'][] = $enum;
            }
        }

        if (isset($apiDocParam['optional']) && !$apiDocParam['optional']) {
            $param['required'] = true;
        }

        return $param;
    }

    /**
     * 去除奇怪符號只取資料
     */
    private function getEnumValue($oriEnum)
    {
        $enum = $oriEnum;
        // 去除 :
        $firstExplodeAry = explode(":", $oriEnum);
        $enum = $firstExplodeAry[0];

        if (isset($firstExplodeAry[1]) && !empty($firstExplodeAry[1])) {
            $enum = $firstExplodeAry[1];
        }

        // 去除 "
        $finialExplodeAry = explode("\"", $enum);
        $enum = $finialExplodeAry[0];

        if (isset($finialExplodeAry[1]) && !empty($finialExplodeAry[1])) {
            $enum = $finialExplodeAry[1];
        }

        return $enum;
    }

    /**
     * 回傳屬於 path 的參數名稱
     *
     * @return array
     */
    private function getPathParams($url)
    {
        $pathParams = [];

        $ary = explode("{", $url);

        if (count($ary) == 1) {
            return $pathParams;
        }

        foreach ($ary as $item) {
            $result = explode("}", $item);

            if (count($result) == 1) {
                continue;
            }
            $pathParams[$result[0]] = 'path';
        }

        return $pathParams;
    }

    /**
     * 把 api doc 的 group 放進去 tag內
     */
    private function setSwaggerTags($tags, $apiGroupName) {

        if (empty($tags)) {
            $tags[] = [
                'name'         => $apiGroupName,
                'description'  => $apiGroupName,
                'externalDocs' => [
                    'url'         => $this->docUrl,
                    'description' => "{$this->team} 原始 apiDoc 出處"
                ]
            ];

            return $tags;
        }

        $exist = false;

        foreach ($tags as $tag) {
            if ($tag['name'] === $apiGroupName) {
                $exist = true;
                break;
            }
        }

        if (!$exist) {
            $tags[] = [
                'name'         => $apiGroupName,
                'description'  => $apiGroupName,
                'externalDocs' => [
                    'url'         => $this->docUrl,
                    'description' => "{$this->team} 原始 apiDoc 出處"
                ]
            ];
        }

        return $tags;
    }

    /**
     * 轉換 method 大小寫
     */
    private function setMethod($type) {

        $map = [
            'GET'    => 'get',
            'DELETE' => 'delete',
            'POST'   => 'post',
            'PUT'    => 'put'
        ];

       return isset($map[$type]) ? $map[$type] : $type;
    }

    /**
     * node js 使用 :變數 當作 path 表示
     * 轉換 url :變數 -> {變數}
     */
    private function setUrl($url)
    {
        if ($this->team == 'php') {
            return $url;
        }

        $trimQUrl = explode('?', $url);
        $subUrls = explode('/:', $trimQUrl[0]);
        $result = "/admin{$subUrls[0]}";

        if (count($subUrls) == 1) {
            return $result;
        }

        unset($subUrls[0]);

        foreach ($subUrls as $subUrl) {
            $pathParam = explode('/', $subUrl);
            $result .= "/{" . $pathParam[0] . "}";

            if (count($pathParam) == 1) {
                continue;
            }

            unset($pathParam[0]);

            foreach ($pathParam as $path) {
                $result .= "/{$path}";
            }

        }

        return $result;
    }

    private function checkArrayThenSetItems(&$properties) {
        $items = $properties;

        if (!is_array($items)) {
            return;
        }

        foreach ($items as $key => $item) {
            if (!isset($item['type'])) {
                continue;
            }

            if ($item['type'] == 'string') {
                continue;
            }

            if ($item['type'] == 'array' && isset($properties[$key]['properties'])) {
                $properties[$key]['items'] = [
                    'type'       => 'object',
                    'properties' => $properties[$key]['properties']
                ];
                unset($properties[$key]['properties']);
                $this->checkArrayThenSetItems($properties[$key]['items']['properties']);
            }

            if ($item['type'] == 'object') {
                $this->checkArrayThenSetItems($properties[$key]['properties']);
            }
        }
    }

    /**
     * check existed missing columns
     */
    private function assertMissingColumns($apiAry)
    {
        $requireColumn = [
            'type',
            'url',
            'title',
            'name',
            'group'
        ];

        foreach ($requireColumn as $column) {
            if (empty($apiAry[$column])) {
                throw new Exception("缺失必要欄位 {$url}");
            }
        }
    }

    private function checkMissingPath($pathParams, $params) {
        $paramsHasPath = [];
        foreach($pathParams as $name => $type) {
            foreach ($params as $param) {
                if (isset($param['name']) && !isset($paramsHasPath[$param['name']])) {
                    $paramsHasPath[$param['name']] = 'path';
                }
            }
        }

        $diffResult = array_diff($pathParams, $paramsHasPath);

        if (!empty($diffResult)) {
            foreach ($diffResult as $missingPathName => $type) {
                $pushParam = [
                    'in'     => $type,
                    'name'   => $missingPathName,
                    'schema' => [
                        "type" => 'string'
                    ],
                    'description' => '文件缺少 path 參數！！！！！！！！！！！！！',
                    'required' => true
                ];

                $params[] = $pushParam;
            }
        }

        return $params;
    }

    private function my_merge(&$a,$b){

        foreach($a as $key=>&$val){
            if(is_array($val) && array_key_exists($key, $b) && is_array($b[$key])){
                $this->my_merge($val,$b[$key]);
                $val = $val + $b[$key];
            }else if(is_array($val) || (array_key_exists($key, $b) && is_array($b[$key]))){
                $val = is_array($val)?$val:$b[$key];
            }
        }
        $a = $a + $b;
    }

    private function convertMutipleAry($ori, $info, $result) {
        $num = count($ori);
        $typeMap = [
            'String' => 'string',
            'Object' => 'object',
            'Object[]' => 'array',
            'String[]' => 'array'
        ];

        $property = [
            'type'        => isset($typeMap[$info['type']]) ? $typeMap[$info['type']] : 'string',
            'description' => $info['description']
        ];

        if ($info['type'] == 'String[]') {
            $property['items']['type'] = 'string';
        }

        // notes 如果有 allow values 參數代表該 type 不會是 object/ array
        if (isset($info['allowedValues']) && !empty($info['allowedValues'])) {
            foreach ($info['allowedValues'] as $allowedValue) {
                $property['description'] .= "\n {$allowedValue}";
                $enum = $this->getEnumValue($allowedValue);
                $property['enum'][] = $enum;
            }
        }

        $required = false;
        if (!$info['optional']) {
            $required = true;
        }

        switch ($num) {
            case 1:
                if ($required) {
                    $result['required'][] = $ori[0];
                }
                $result['properties'][$ori[0]] = $property;
                break;
            case 2:
                if ($required) {
                    $result['properties'][$ori[0]]['required'][] = $ori[1];
                }
                $result['properties'][$ori[0]]['properties'][$ori[1]] = $property;
                break;
            case 3:
                if ($required) {
                    $result['properties'][$ori[0]]['properties'][$ori[1]]['required'][] = $ori[2];
                }
                $result['properties'][$ori[0]]['properties'][$ori[1]]['properties'][$ori[2]] = $property;
                break;
            case 4:
                if ($required) {
                    $result['properties'][$ori[0]]['properties'][$ori[1]]['properties'][$ori[2]]['required'][] = $ori[3];
                }
                $result['properties'][$ori[0]]['properties'][$ori[1]]['properties'][$ori[2]]['properties'][$ori[3]] = $property;
                break;
            case 5:
                if ($required) {
                    $result['properties'][$ori[0]]['properties'][$ori[1]]['properties'][$ori[2]]['properties'][$ori[3]]['required'][] = $ori[4];
                }
                $result['properties'][$ori[0]]['properties'][$ori[1]]['properties'][$ori[2]]['properties'][$ori[3]]['properties'][$ori[4]] = $property;
                break;
            case 6:
                if ($required) {
                    $result['properties'][$ori[0]]['properties'][$ori[1]]['properties'][$ori[2]]['properties'][$ori[3]]['properties'][$ori[4]]['required'][] = $ori[5];
                }
                $result['properties'][$ori[0]]['properties'][$ori[1]]['properties'][$ori[2]]['properties'][$ori[3]]['properties'][$ori[4]]['properties'][$ori[5]] = $property;
                break;
            case 7:
                if ($required) {
                    $result['properties'][$ori[0]]['properties'][$ori[1]]['properties'][$ori[2]]['properties'][$ori[3]]['properties'][$ori[4]]['properties'][$ori[5]]['required'][] = $ori[6];
                }
                $result['properties'][$ori[0]]['properties'][$ori[1]]['properties'][$ori[2]]['properties'][$ori[3]]['properties'][$ori[4]]['properties'][$ori[5]]['properties'][$ori[6]] = $property;
                break;
            case 8:
                if ($required) {
                    $result['properties'][$ori[0]]['properties'][$ori[1]]['properties'][$ori[2]]['properties'][$ori[3]]['properties'][$ori[4]]['properties'][$ori[5]]['properties'][$ori[6]]['required'][]= $ori[7];
                }
                $result['properties'][$ori[0]]['properties'][$ori[1]]['properties'][$ori[2]]['properties'][$ori[3]]['properties'][$ori[4]]['properties'][$ori[5]]['properties'][$ori[6]]['properties'][$ori[7]] = $property;
                break;
            default:
                throw new Exception("{$num} 超出土炮的限制了");
        }

        return $result;
    }

    private function setResponseData($apiDocSuccess, $belong) {
        if ($belong == 'success') {
            $fieldText = 'Success 200';
            $explodText = 'HTTP/1.1 200 OK';
        }

        if ($belong == 'error') {
            $fieldText = 'Error 4xx';
            $explodText = ($this->team === 'php') ? 'HTTP/1.1 400 Bad Request' : 'HTTP/1.1 403 Forbidden';
        }

        $response = [
            'type'        => 'object',
            'description' => $fieldText,
        ];
        $typeMap = [
            'String' => 'string',
            'Object' => 'object',
            'Object[]' => 'array'
        ];

        if (isset($apiDocSuccess['fields'][$fieldText])) {
            $result = [];
            $apiDocResponseColumns = $apiDocSuccess['fields'][$fieldText];

            foreach ($apiDocResponseColumns as $columnInfo) {
                // 用 field 判斷層數
                $columnAry = explode('.', $columnInfo['field']);

                // TODO have to refactor this function
                $info = $this->convertMutipleAry($columnAry, $columnInfo, $result);
                $result = array_merge($result, $info);
                // $this->my_merge($result, $info);
            }

            //TODO array 的改法可能要看一下有沒有問題
            $this->checkArrayThenSetItems($result['properties']);

            $response = array_merge($response, $result);
        }

        if (isset($apiDocSuccess['examples'][0]['content'])) {
            $example = explode($explodText, $apiDocSuccess['examples'][0]['content']);
            $exampleAry = json_decode($example[1], true);
            $response['example'] = $exampleAry;
        }

        return $response;
    }
}