<?php

namespace App\Exceptions;

use Exception;
use Throwable;
use ReflectionClass;
use Symfony\Component\Yaml\Yaml;
use App\Enums\ExceptionTypeEnum;

/**
 * 基礎例外
 *
 * @package    Support
 * @subpackage Exception
 * @filesource
 */
class AppException extends Exception
{
    protected $configPath = ".";
    protected $type = "";
    protected $reason = "";
    protected $message = "";
    protected $code = "";
    private $exceptions = [];

    /**
     * 建構子
     *
     * @param string $configPath 拋出的異常訊息內容
     *
     * @return void
     * @throws Exception 如果找不到異常代碼
     *
     */
    public function __construct()
    {
        // 設定設定檔路徑
        $this->configPath  = realpath(__DIR__ . '/../../config/error/app.yaml');
    }

    /**
     * 異常處理
     *
     * @param mixed     $code       異常代碼
     * @param string    $reason     拋出的異常訊息內容
     * @param Throwable $previous   前一個異常
     * @param integer   $type       錯誤類別
     *
     * @return void 異常資料
     *
     * @throws BaseException
     * @SuppressWarnings("StaticAccess")
     */
    public function error($code, string $reason = "", Throwable $previous = null, int $type = ExceptionTypeEnum::FAILURE)
    {
        empty($this->exceptions) && $this->exceptions = self::mergeConfig();

        if (empty($code) || !isset($this->exceptions[$code])) {
            $this->message = "Exception code: $code is undefined";
            throw $this;
        }

        $this->code   = $code;
        $this->reason = $reason;

        self::setPrevious($previous);
        $data             = $this->exceptions[$code];
        $this->message    = $data["message"];
        $this->type       = ExceptionTypeEnum::getValue(strtoupper($data["type"]));

        throw $this;
    }

    /**
     * 設定ExceptionType
     *
     * @param integer $type ExceptionType
     *
     * @return void
     */
    public function setType(int $type)
    {
        $this->type = $type;
    }

    /**
     * 取得ExceptionType
     *
     * @return string exceptionType
     */
    public function getType()
    {
        if (empty($this->type)) {
            return ExceptionTypeEnum::FAILURE;
        }

        return $this->type;
    }

    /**
     * 設定擴充訊息
     *
     * @param string $reason 擴充訊息
     *
     * @return void
     */
    public function setReason(string $reason)
    {
        $this->reason = $reason;
    }

    /**
     * 取得擴充訊息
     *
     * @return string 擴充訊息
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * 設定前一個Exception
     *
     * @param mixed $previous 異常鏈中的前一個異常
     *
     * @return void
     */
    public function setPrevious($previous)
    {
        if (empty($previous)) {
            return;
        }

        $reflection = new ReflectionClass($this);

        while (!$reflection->hasProperty('previous')) {
            $reflection = $reflection->getParentClass();
        }

        $prop = $reflection->getProperty('previous');
        $prop->setAccessible('true');
        $prop->setValue($this, $previous);
        $prop->setAccessible('false');
    }

    /**
     * 讀取組態
     *
     * @return array $configAry 組態陣列
     * @throws Exception If file not found
     *
     */
    private function mergeConfig()
    {
        $filePath = $this->configPath;

        if (file_exists($filePath)) {
            return Yaml::parse(
                file_get_contents($filePath)
            );
        }

        throw new parent("$filePath not found");
    }
}
