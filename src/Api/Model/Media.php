<?php

namespace App\Api\Model;

use ApiPlatform\Core\Annotation\ApiProperty;

/**
 * Class Media
 */
class Media extends Shared
{
    /**
     * @ApiProperty(identifier=true)
     */
    public string $id = '';
    private array $assets = [];

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Add asset to the object.
     *
     * @param $type
     *   The asset type eg. image/png.
     * @param $uri
     *   The URI location of the asset.
     *
     * @return $this
     */
    public function addAsset($type, $uri): self
    {
        $this->assets[] = [
            'type' => $type,
            'uri' => $uri,
        ];

        return $this;
    }

    /**
     * Get all assets.
     *
     * @return array
     *   Array keyed by 'type' and 'uri'.
     */
    public function getAssets(): array
    {
        return $this->assets;
    }
}
