<?php
namespace App\Services\Resources;

use App\Services\Resources\Traits\HttpTrait;
use Exception;

/**
 * RocketChat api Service
 *
 * @author zong <zong.xie.udn@gmail.com>
 */
class RocketChatService
{
    use HttpTrait;

    private $host;
    private $token;
    private $userId;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->host = env('ROCKET_CHAT_URL', 'https://rocket.chat');
        $this->token = env('ROCKET_TOKEN', '');
        $this->userId = env('ROCKET_ID', '');
    }

    /**
     * 發送 string message
     *
     * @param string $channel 頻道
     * @param string $message 訊息
     *
     * @return void
     */
    public function sendMessage(string $channel, string $message) : void
    {
        $response = $this->post(
            $this->host . "/api/v1/chat.postMessage",
            [
                'X-Auth-Token' => $this->token,
                'X-User-Id'    => $this->userId
            ],
            [
                'channel' => $channel,
                'text'    => $message
            ]
        );

        if (!isset($response['success']) || !$response['success']) {
            // TODO Error Handler
            throw new Exception('[RocketChat] send message fail');
        }
    }
}
