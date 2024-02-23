<?php

namespace App\Interactive;

readonly class InteractionRequest
{
    public string $implementationClass;
    public string $action;
    public array $data;

    public function __construct(string $implementationClass, string $action, array $data)
    {
        $this->implementationClass = $implementationClass;
        $this->action = $action;
        $this->data = $data;
    }
}
