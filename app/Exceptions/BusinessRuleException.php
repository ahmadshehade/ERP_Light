<?php

namespace App\Exceptions;

use RuntimeException;

class BusinessRuleException extends RuntimeException
{
    public function __construct(
        string $message,
        public int $statusCode = 400
    ) {
        parent::__construct($message);
    }
}
