<?php
namespace App\Services\Resources\Traits;

use Illuminate\Support\Facades\Http;
use Exception;

/**
 * 統一 Http request 處理
 */
trait HttpTrait
{
    /**
     * http request get
     *
     * @param string $url    http/https url
     * @param array  $header header
     * @param array  $params params
     *
     * @return array
     */
    public function get(string $url, array $header = [], array $params = []) : array
    {
        $response = Http::withHeaders($header)->get($url, $params);

        if ($response->failed()) {
            // TODO Exception Handler
            throw new Exception("[GET][{$response->status()}]" . $response->body());
        }

        return $response->json();
    }

    /**
     * http request get
     *
     * @param string $url    http/https url
     * @param array  $header header
     * @param array  $params params
     *
     * @return array
     */
    public function post(string $url, array $header = [], array $params = []) : array
    {
        $response = Http::withHeaders($header)->post($url, $params);

        if ($response->failed()) {
            // TODO Exception Handler
            throw new Exception("[POST][{$response->status()}]" . $response->body());
        }

        return $response->json();
    }
}
