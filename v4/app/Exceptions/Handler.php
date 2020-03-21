<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use App\Enums\AppResponseStatusEnum;
use App\Enums\ExceptionTypeEnum;

/**
 * ExceptionHandler
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param \Throwable $exception excpetion
     *
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        if (app()->bound('sentry') && $this->shouldReport($exception)){
            app('sentry')->captureException($exception);
        }

        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        // return parent::render($request, $exception);
        // 未知的錯誤
        $errorMessage = "未知的錯誤";
        $errorCode    = "10001";
        $httpCode     = ExceptionTypeEnum::FAILURE;
        $reason = '';

        // Exception mapping
        $exceptionName = get_class($exception);
        $exceptionAry  = config('exception-handler.exception');
        if (isset($exceptionAry[$exceptionName])) {
            extract($exceptionAry[$exceptionName]);
            $httpCode = ExceptionTypeEnum::getValue(strtoupper($type));
            $reason = '';
            if ($exception instanceof ValidationException) {
                $reason = '';
                foreach ($exception->validator->getMessageBag()->getMessages() as $key => $value) {
                    foreach ($value as $message) {
                        $reason .= !empty($reason) ? "; " : "";
                        $reason .= "'$key', $message";
                    }
                }
            }
        }

        if (!isset($request->requestId)) {
            $request->requestId = '';
        }

        return $this->exceptionResponse($request->requestId, $httpCode, $errorCode, $errorMessage, $reason);
    }

    /**
     * Exception Response
     *
     * @param string  $requestId RequestId
     * @param integer $httpCode  Http Code
     * @param string  $code      錯誤代碼
     * @param string  $message   錯誤訊息
     * @param string  $reason    錯誤細部原因
     *
     * @return json
     */
    private function exceptionResponse(string $requestId, int $httpCode, string $code, string $message, string $reason = '')
    {
        // 例外的傳出格式
        return response()->json(
            [
                "id"     => $requestId,
                "status" => AppResponseStatusEnum::FAILURE,
                "error"  => [
                    "code"        => $code,
                    "message"     => $message,
                    "reason"      => $reason
                ],
            ],
            $httpCode
        );
    }
}
