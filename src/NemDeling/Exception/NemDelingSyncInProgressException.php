<?php

declare(strict_types=1);

namespace App\NemDeling\Exception;

final class NemDelingSyncInProgressException extends \RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
