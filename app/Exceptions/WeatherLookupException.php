<?php

namespace App\Exceptions;

use RuntimeException;

class WeatherLookupException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 502)
    {
        parent::__construct($message);
    }
}
