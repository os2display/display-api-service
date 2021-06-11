<?php

namespace App\Api\Model;

class Media extends Shared {
    public string $id = '';
    private array $assets = [];

    public function addAsset($type, $url): void
    {
        $this->assets[] = [
            'type' => $type,
            'url' => $url,
        ];
    }

    public function getAssets(): array
    {
        return $this->assets;
    }
}
