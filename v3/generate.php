<?php
include('config.php');
include('apiDocToSwagger.php');
include('helper.php');

// 取得 php team backend api doc
$team = 'php';
$apiDoc = Helper::getApiDoc($env[$team]['doc_data']['backend_master']);
$config = [
    'team'    => $team,
    'docUrl'  => $env[$team]['doc']['backend_master'],
    'docName' => 'backend_master'
];

$phpBackendSwaggerDoc = (new apiDocToSwagger($config))->main($apiDoc);

// 取得 node js team backend api doc
$team = 'node.js';
$apiDoc = Helper::getApiDoc($env[$team]['doc_data']['backend']);
$config = [
    'team'    => $team,
    'docUrl'  => $env[$team]['doc']['backend'],
    'docName' => 'backend'
];

$nodeJsBackendSwaggerDoc = (new apiDocToSwagger($config))->main($apiDoc);

// generate backend api
$backendDoc = $basicFormat;
$backendDoc['tags'] = array_merge($phpBackendSwaggerDoc['tags'], $nodeJsBackendSwaggerDoc['tags']);
$backendDoc['paths'] = $phpBackendSwaggerDoc['paths'] + $nodeJsBackendSwaggerDoc['paths'];
$backendDoc['components']['schemas'] = $phpBackendSwaggerDoc['components']['schemas'] + $nodeJsBackendSwaggerDoc['components']['schemas'];
$backendDoc['components']['securitySchemes'] = $phpBackendSwaggerDoc['components']['securitySchemes'] + $nodeJsBackendSwaggerDoc['components']['securitySchemes'];

Helper::generate_json_file($backendDoc, 'backend_api');

// genereate frontend api
$team = 'node.js';
$apiDoc = Helper::getApiDoc($env[$team]['doc_data']['frontend']);
$config = [
    'team'    => $team,
    'docUrl'  => $env[$team]['doc']['frontend'],
    'docName' => 'frontend'
];

$nodeJsFrontendSwaggerDoc = (new apiDocToSwagger($config))->main($apiDoc);

$frontendSwaggerDoc = array_merge($basicFormat, $nodeJsFrontendSwaggerDoc);
Helper::generate_json_file($frontendSwaggerDoc, 'frontend_api');

// generater php backend brand and dos api doc
$team = 'php';
$apiDoc = Helper::getApiDoc($env[$team]['doc_data']['backend_brand']);
$config = [
    'team'    => $team,
    'docUrl'  => $env[$team]['doc']['backend_brand'],
    'docName' => 'backend_brand'
];

$phpBackendSwaggerDoc = (new apiDocToSwagger($config))->main($apiDoc);

$brandSwaggerDoc = array_merge($basicFormat, $phpBackendSwaggerDoc);
Helper::generate_json_file($brandSwaggerDoc, 'backend_brand_api');

$apiDoc = Helper::getApiDoc($env[$team]['doc_data']['backend_dos']);
$config = [
    'team'    => $team,
    'docUrl'  => $env[$team]['doc']['backend_dos'],
    'docName' => 'backend_dos'
];

$phpBackendSwaggerDoc = (new apiDocToSwagger($config))->main($apiDoc);

$brandSwaggerDoc = array_merge($basicFormat, $phpBackendSwaggerDoc);
Helper::generate_json_file($brandSwaggerDoc, 'backend_dos_api');


Helper::sendMessage([
    'account' => $account,
    'message' => $message
]);
