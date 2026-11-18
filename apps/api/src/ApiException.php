<?php
declare(strict_types=1);

namespace ReFood;

// Error controlado: lleva el código HTTP que verá el front.
final class ApiException extends \RuntimeException
{
    public function __construct(string $message, public readonly int $httpStatus = 400)
    {
        parent::__construct($message, $httpStatus);
    }
}
