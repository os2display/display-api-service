<?php

namespace App\Interactive;

interface InteractiveInterface
{
    public function getConfigOptions(): array;
    public function performAction(): array;
}
