<?php

namespace App\Interactive;

class QuickBook implements InteractiveInterface
{

    public function getConfigOptions(): array
    {
        return ['haj' => 'dej'];
    }

    public function performAction(): array
    {
        return [];
    }
}
