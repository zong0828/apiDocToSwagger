<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * 錯誤類別列舉。
 */
final class ExceptionTypeEnum extends Enum
{
    const SYSTEM_ERROR   = 500;
    const FAILURE        = 400;
    const ARGUMENT_ERROR = 400;
    const NOT_FOUND      = 404;
    const FORBIDDEN      = 403;
    const UNAUTHORIZED   = 401;
}
