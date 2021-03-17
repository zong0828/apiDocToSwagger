<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SwaggerService;
use App\Services\Resources\JenkinsService;
use App\Services\Resources\RocketChatService;

/**
 * 轉換團隊 api doc 至 swagger
 */
class SyncApidocToSwaggerTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swagger:update';

    /**
     * The console command description.
     */
    protected $description = '轉換團隊 api 文件至 swagger json 版本';

    private $swaggerService;
    private $jenkinsService;
    private $rocketChat;

    private $taskList = [
        "ubss"    => "/apidoc/ubss",
        "bbs"     => "/apidoc/brand-backstage",
        "invoice" => "/apidoc/invoice",
        "bfs"     => "/apidoc/brand-frontstage",
        "udms"    => "/apidoc/udms",
        "auth"    => "/apidoc/auth"
    ];

    const SWAGGER_PATH = __DIR__ . "/../../../swagger-doc/api/doc";

    /**
     * Create a new command instance.
     *
     * @param SwaggerService    $swaggerService swagger 相關功能
     * @param JenkinsService    $jenkinsService jenkins resources
     * @param RocketChatService $rocketChat     rocketChat resources
     *
     * @return void
     */
    public function __construct(
        SwaggerService $swaggerService,
        JenkinsService $jenkinsService,
        RocketChatService $rocketChat
    ) {
        parent::__construct();

        $this->swaggerService = $swaggerService;
        $this->jenkinsService = $jenkinsService;
        $this->rocketChat = $rocketChat;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        echo "------------- Task Start. " . date('Y-m-d H:i:s') . " ------------- \n";

        foreach ($this->taskList as $service => $docUrl) {
            echo "{$service} updating !\n";
            // Step 1. get apidoc data
            $apiDocInfo = $this->jenkinsService->getApiDoc("{$docUrl}/api_data.json");
            // Step 2. check missing column
            foreach ($apiDocInfo as $apiBlock) {
                $this->assertMissingColumns($apiBlock);
            }
            // Step 3. convert something amazing
            // service prepare
            $this->swaggerService->resetAlertMessage();
            $this->swaggerService->setServiceName($service);
            $this->swaggerService->setExternalDocsUrl($this->jenkinsService->getJenkinsHosts() . $docUrl);
            $swaggerDocInfo = $this->swaggerService->convert($apiDocInfo);
            // Step 4. Generate JSON FILE
            file_put_contents(
                self::SWAGGER_PATH . "/{$service}.json",
                json_encode($swaggerDocInfo, JSON_UNESCAPED_UNICODE)
            );
            // Step 5. send rocket chat alert
            $msg = $this->swaggerService->getAlertMessage();
            !empty($msg) && $this->sendAlert($service, $msg);


            echo "{$service} finishing !\n";
        }//end foreach

        // Step 6. swagger link
        $this->rocketChat->sendMessage("swagger_api_channel", "API 文件更新成功，詳情請見 http://35.221.251.217:8080/job/swagger/Swagger/ ！");

        echo "------------- Task end.   " . date('Y-m-d H:i:s') . " ------------- \n";
    }

    /**
     * 檢查必填欄位
     *
     * @param array $apiAry apiary
     *
     * @return void
     */
    private function assertMissingColumns($apiAry)
    {
        $requireColumn = [
                          'type',
                          'url',
                          'name',
                          'group'
        ];

        foreach ($requireColumn as $column) {
            if (empty($apiAry[$column])) {
                throw new Exception("缺失必要欄位 {$apiAry['url']} {$apiAry['name']}");
            }
        }
    }

    /**
     * send rocketcht alert
     *
     * @param string $service    service
     * @param array  $messages messages
     *
     * @return void
     */
    private function sendAlert(string $service, array $messages) : void
    {
        foreach ($messages as $message) {
            $this->rocketChat->sendMessage("swagger_api_channel", "[{$service}]{$message['msg']}");
        }
    }
}
