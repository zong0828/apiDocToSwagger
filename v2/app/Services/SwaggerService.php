<?php
namespace App\Services;

use App\Enums\SwaggerDefaultSettingEnum;

/**
 * SwaggerService
 * 處理 apidoc json to swagger json part
 *
 * @author zong <zong.xie.udn@gmail.com>
 */
class SwaggerService
{
    private $alertMessage = [];
    private $externalDocsUrl = '';
    private $serviceName = '';
    // log var
    private $apiTitle;
    private $apiName;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        // TODO
    }

    /**
     * setExternalDocsUrl
     *
     * @param string $url apidoc url
     *
     * @return void
     */
    public function setExternalDocsUrl(string $url)
    {
        $this->externalDocsUrl = $url;
    }

    /**
     * setServiceName
     *
     * @param string $service 服務名稱
     *
     * @return void
     */
    public function setServiceName(string $service)
    {
        $this->serviceName = $service;
    }

    /**
     * convert apidoc format data to swagger format
     *
     * @param array $apiDocInfo apidoc data
     *
     * @return array
     */
    public function convert(array $apiDocInfo) : array
    {
        $swaggerDocInfo = SwaggerDefaultSettingEnum::BASE_FORMAT;
        $swaggerDocInfo['servers'] = [
            0 => [
                'url'         => "http://api-alpha-{$this->serviceName}.udnshopping.com",
                'description' => 'the alpha server api'
            ],
            1 => [
                'url'         => "http://api-beta-{$this->serviceName}.udnshopping.com",
                'description' => 'the beta server api'
            ],
            2 => [
                'url'         => "http://api-gamma-{$this->serviceName}.udnshopping.com",
                'description' => 'the gamma server api'
            ]
        ];

        $apidocName = strtoupper($this->serviceName);
        $swaggerDocInfo['info']['title'] = "{$apidocName} API DOCUMENT";

        // apidoc 有多個 api block
        foreach ($apiDocInfo as $apiBlock) {
            $swaggerDocInfo = $this->prepareSwagger($swaggerDocInfo, $apiBlock);
        }

        return $swaggerDocInfo;
    }

    /**
     * 取得 alert log
     *
     * @return array
     */
    public function getAlertMessage()
    {
        return $this->alertMessage;
    }

    /**
     * reset alert message log
     *
     * @return void
     */
    public function resetAlertMessage()
    {
        $this->alertMessage = [];
    }

    /**
     * 準備 swagger 資料
     *
     * @param array $swaggerDocInfo swagger data
     * @param array $apiBlock       apiBlock
     *
     * @return array
     */
    private function prepareSwagger(array $swaggerDocInfo,array $apiBlock) : array
    {
        // set log val
        $this->apiTitle = $apiBlock['title'] ?? '';
        $this->apiName = $apiBlock['name'];

        // set var
        $url = $apiBlock['url'];
        $method = strtolower($apiBlock['type']);
        $group = $apiBlock['group'];
        $params = isset($apiBlock['parameter']['fields']['Parameter']) ? $apiBlock['parameter']['fields']['Parameter'] : [];
        $apiDocSuccess = isset($apiBlock['success']) ? $apiBlock['success'] : [];
        $header = isset($apiBlock['header']['fields']['Header']) ? $apiBlock['header']['fields']['Header'] : [];
        $apiDocError = isset($apiBlock['error']) ? $apiBlock['error'] : [];
        $swaggerDocInfo['tags'] = $this->setSwaggerTags($swaggerDocInfo['tags'], $group);

        // set paths
        $swaggerDocInfo['paths'][$url][$method] = [
            'tags'        => [ $group ],
            'summary'     => $apiBlock['title'],
            'description' => $apiBlock['name'],
            'responses'   => SwaggerDefaultSettingEnum::SUCCESS_EXAMPLE
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
            // 目前只支援 bearerAuth
            if ($this->requireAuth($header)) {
                $swaggerDocInfo['paths'][$url][$method]['security'][0]['bearerAuth'] = [];
            }

            $parameters = $this->setSwaggerHeaderParams($parameters, $header);
        }

        // 檢查 apidoc 是否有 body params or query params
        $swaggerParams = [];
        if (!empty($params)) {
            $swaggerParams = $this->setParams($method, $params);
            if ($method == 'get') {
                $parameters = array_merge($parameters, $swaggerParams);
            } else {
                $swaggerDocInfo['paths'][$url][$method]['requestBody'] = [
                    'description' => 'request body',
                    'required'    => true,
                    'content'     => [
                        'application/json' => [
                            'schema' => $swaggerParams
                        ]
                    ]
                ];

                if (!empty($apiBlock['parameter']['examples'][0]['content'])) {
                    $bodyRequest = json_decode($apiBlock['parameter']['examples'][0]['content'], true);
                    if (!empty($bodyRequest)) {
                        $swaggerDocInfo['paths'][$url][$method]['requestBody']['content']['application/json']['schema']['example'] = $bodyRequest;
                    }
                }
            }
        }//end if

        if (!empty($parameters)) {
            $swaggerDocInfo['paths'][$url][$method]['parameters'] = $parameters;
        }

        if (!empty($apiDocSuccess)) {
            $schemaName = "{$apiBlock['name']}SuccssResponse";
            $swaggerDocInfo['paths'][$url][$method]['responses']['200'] = [
                'description' => "HTTP/1.1 200 OK",
                'content'     => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => "#/components/schemas/{$schemaName}"
                        ]
                    ]
                ]
            ];

            $swaggerDocInfo['components']['schemas'][$schemaName] = $this->setResponseData($apiDocSuccess, 'success');
        } else {
            // 新增 Alert message
            $this->alertMessage[] = [
                'msg' => "[MissSuccessExample] {$this->apiTitle} - {$this->apiName}"
            ];
        }

        if (!empty($apiDocError)) {
            $schemaName = "{$apiBlock['name']}ErrorResponse";
            $swaggerDocInfo['paths'][$url][$method]['responses']['400'] = [
                'description' => "Error-Response HTTP/1.1 400",
                'content'     => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => "#/components/schemas/{$schemaName}"
                        ]
                    ]
                ]
            ];

            $swaggerDocInfo['components']['schemas'][$schemaName] = $this->setResponseData($apiDocError, 'error');
        }

        return $swaggerDocInfo;
    }

    /**
     * 檢查是否需要 BearerAuth
     *
     * @param array $headerAry header
     *
     * @return boolean
     */
    private function requireAuth(array $headerAry) : bool
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
     * setSwaggerHeaderParams
     *
     * @param array $parameters parameters
     * @param array $headerAry  headerAry
     *
     * @return array
     */
    private function setSwaggerHeaderParams(array $parameters, array $headerAry) : array
    {
        foreach ($headerAry as $headerSet) {
            if ($headerSet['field'] == 'Authorization') {
                continue;
            }

            $param = [
                'in'          => 'header',
                'name'        => $headerSet['field'],
                'schema'      => [ 'type' => 'string' ],
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
     * setSwaggerPathParams
     *
     * @param array $pathParams pathParams
     * @param array $params     params 集合
     *
     * @return array ['pathParams' => [], 'params' => []]
     */
    private function setSwaggerPathParams(array $pathParams, array $params) : array
    {
        $pathAry = [];
        $paramsAry = $params;

        foreach ($params as $index => $param) {
            if (isset($pathParams[$param['field']])) {
                $pathAry[] = [
                    'in'          => 'path',
                    'name'        => $param['field'],
                    'schema'      => [ "type" => 'string' ],
                    'required'    => true,
                    'description' => $param['description']
                ];

                unset($paramsAry[$index]);
            }
        }

        if (!empty($pathParams)) {
            $pathAry = $this->checkMissingPath($pathParams, $pathAry);
        }

        return [
            'pathParams' => $pathAry,
            'params'     => $paramsAry
        ];
    }

    /**
     * 取得 path 的參數名稱
     *
     * @param string $url api url
     *
     * @return array
     */
    private function getPathParams(string $url)
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
     * 去除奇怪符號只取資料
     *
     * @param string $oriEnum oriEnum
     *
     * @return string
     */
    private function getEnumValue(string $oriEnum)
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
     * 塞 swagger tag
     *
     * @param array  $tags         swagger tag
     * @param string $apiGroupName apidoc group name
     *
     * @return array
     */
    private function setSwaggerTags(array $tags, string $apiGroupName) : array
    {

        if (empty($tags)) {
            $tags[] = [
                'name'         => $apiGroupName,
                'description'  => $apiGroupName,
                'externalDocs' => [
                    'url'         => $this->externalDocsUrl,
                    'description' => "原始 apiDoc 出處"
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
                    'url'         => $this->externalDocsUrl,
                    'description' => "原始 apiDoc 出處"
                ]
            ];
        }

        return $tags;
    }

    /**
     * setParams
     *
     * @param string $method       http method
     * @param array  $apiDocParams apiDocParams
     *
     * @return array
     */
    private function setParams(string $method, array $apiDocParams) : array
    {
        $params = [];

        foreach ($apiDocParams as $apiDocParam) {
            if ($method === 'get') {
                $param = $this->setQueryParams($apiDocParam);
                $params[] = $param;
                continue;
            } else {
                $params = $this->setBodyParams($apiDocParam, $params);
            }
        }//end foreach

        if ($method !== 'get' && !empty($params)) {
            // TODO array 的改法可能要看一下有沒有問題
            $this->checkArrayThenSetItems($params['properties']);
        }

        return $params;
    }

    /**
     * set query params
     *
     * @param array $apiDocParam apiDocParam
     *
     * @return array
     */
    private function setQueryParams(array $apiDocParam) : array
    {
        // basic
        $param = [
            'in'          => 'query',
            'name'        => $apiDocParam['field'],
            'schema'      => [
                "type" => 'string'
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
     * setBodyParams
     *
     * @param array $apiDocParam apiDocParam
     * @param array $result      result
     *
     * @return array
     */
    private function setBodyParams(array $apiDocParam, array $result) : array
    {
        $result['type'] = "object";

        // 用 field 判斷層數
        $columnAry = explode('.', $apiDocParam['field']);
        // TODO have to refactor this function
        $info = $this->convertMutipleAry($columnAry, $apiDocParam, $result);

        return array_merge($result, $info);
    }

    /**
     * 轉換 URL 格式
     * node js 使用 :變數 當作 path 表示
     * 轉換 url :變數 -> {變數}
     *
     * @param string $url apidoc url
     *
     * @return string
     */
    private function setNodeJSUrl(string $url) : string
    {
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

    /**
     * setResponseData
     *
     * @param array  $apiDocSuccess apiDocSuccess
     * @param string $belong        belong
     *
     * @return array
     */
    private function setResponseData(array $apiDocSuccess, string $belong) : array
    {
        if ($belong == 'success') {
            $fieldText = 'Success 200';
            $explodText = 'HTTP/1.1 200 OK';
        } else {
            $fieldText = 'Error 4xx';
            $explodText = 'HTTP/1.1 400 Bad Request';
        }

        $response = [
            'type'        => 'object',
            'description' => $fieldText,
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
            if (isset($example[1])) {
                $exampleAry = json_decode($example[1], true);
                $response['example'] = $exampleAry;
            }
        }

        return $response;
    }

    private function checkMissingPath(array $pathParams, array $params)
    {
        $paramsHasPath = [];
        foreach ($pathParams as $name => $type) {
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
                    'in'          => $type,
                    'name'        => $missingPathName,
                    'schema'      => [
                        "type" => 'string'
                    ],
                    'description' => '文件缺少 path 參數！！！！！！！！！！！！！',
                    'required'    => true
                ];

                $params[] = $pushParam;
            }

            $this->alertMessage[] = [
                'msg' => "[MissPathParams] {$this->apiTitle} - {$this->apiName}"
            ];
        }//end if

        return $params;
    }

    private function checkArrayThenSetItems(&$properties)
    {
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
        }//end foreach
    }

    private function convertMutipleAry($ori, $info, $result) {
        $num = count($ori);
        $typeMap = [
            'String' => 'string',
            'Object' => 'object',
            'Object[]' => 'array',
            'String[]' => 'array',
            'Boolean'  => 'boolean'
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
            case 9:
                if ($required) {
                    $result['properties'][$ori[0]]['properties'][$ori[1]]['properties'][$ori[2]]['properties'][$ori[3]]['properties'][$ori[4]]['properties'][$ori[5]]['properties'][$ori[6]]['required'][$ori[7]]['required'][]= $ori[8];
                }
                $result['properties'][$ori[0]]['properties'][$ori[1]]['properties'][$ori[2]]['properties'][$ori[3]]['properties'][$ori[4]]['properties'][$ori[5]]['properties'][$ori[6]]['properties'][$ori[7]]['properties'][$ori[8]] = $property;
                break;
            case 10:
                if ($required) {
                    $result['properties'][$ori[0]]['properties'][$ori[1]]['properties'][$ori[2]]['properties'][$ori[3]]['properties'][$ori[4]]['properties'][$ori[5]]['properties'][$ori[6]]['required'][$ori[7]]['required'][$ori[8]]['required'][]= $ori[9];
                }
                $result['properties'][$ori[0]]['properties'][$ori[1]]['properties'][$ori[2]]['properties'][$ori[3]]['properties'][$ori[4]]['properties'][$ori[5]]['properties'][$ori[6]]['properties'][$ori[7]]['properties'][$ori[8]]['properties'][$ori[9]] = $property;
                break;

            default:
                throw new Exception("{$this->log} 超出硬幹的限制了");
        }

        return $result;
    }
}
