<?php

namespace App\Api\Model;

use ApiPlatform\Core\Annotation\ApiProperty;

/**
 * Class Template
 */
class Template extends Shared
{
    /**
     * @ApiProperty(identifier=true)
     */
    public string $id = '';
    private string $icon;
    private array $resources = [];

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
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     *
     * @return $this
     */
    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return array
     */
    public function getResources(): array
    {
        return $this->resources;
    }

    /**
     * @param string $component
     * @param array $asset
     * @param array $options
     * @param array $content
     *
     * @return $this
     */
    public function addResource(string $component, array $asset, array $options, array $content): self
    {
        $this->resources = [
            'component' => $component,
            'asset' => $asset,
            'options' => $options,
            'content' => $content,
        ];

        return $this;
    }


}
