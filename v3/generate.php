<?php
include('config.php');
include('apiDocToSwagger.php');
include('helper.php');

// 取得 php team backend api doc
$team = 'php';
$apiDoc = Helper::getApiDoc($env[$team]['doc_data']['backend_master']);
$phpBackendSwaggerDoc = (new apiDocToSwagger($team, $env[$team]['doc']['backend_master']))->main($apiDoc);

// 取得 node js team backend api doc
$team = 'node.js';
$apiDoc = Helper::getApiDoc($env[$team]['doc_data']['backend']);
$nodeJsBackendSwaggerDoc = (new apiDocToSwagger($team, $env[$team]['doc']['backend']))->main($apiDoc);

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
$nodeJsFrontendSwaggerDoc = (new apiDocToSwagger($team, $env[$team]['doc']['frontend']))->main($apiDoc);

$frontendSwaggerDoc = array_merge($basicFormat, $nodeJsFrontendSwaggerDoc);
Helper::generate_json_file($frontendSwaggerDoc, 'frontend_api');

// generater php backend brand and dos api doc
$team = 'php';
$apiDoc = Helper::getApiDoc($env[$team]['doc_data']['backend_brand']);
$phpBackendSwaggerDoc = (new apiDocToSwagger($team, $env[$team]['doc']['backend_brand']))->main($apiDoc);

$brandSwaggerDoc = array_merge($basicFormat, $phpBackendSwaggerDoc);
Helper::generate_json_file($brandSwaggerDoc, 'backend_brand_api');

$apiDoc = Helper::getApiDoc($env[$team]['doc_data']['backend_dos']);
$phpBackendSwaggerDoc = (new apiDocToSwagger($team, $env[$team]['doc']['backend_dos']))->main($apiDoc);

$brandSwaggerDoc = array_merge($basicFormat, $phpBackendSwaggerDoc);
Helper::generate_json_file($brandSwaggerDoc, 'backend_dos_api');