<?php

namespace App\Api\Model;

use ApiPlatform\Core\Annotation\ApiProperty;

/**
 * Class Slide.
 */
class Slide extends Shared
{
    /**
     * @ApiProperty(identifier=true)
     */
    public string $id = '';
    private array $template = [];
    private int $duration = 0;
    private array $content = [];
    private int $published = 0;

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getTemplate(): array
    {
        return $this->template;
    }

    /**
     * @return $this
     */
    public function addTemplate(string $id, array $options): self
    {
        $this->template = [
            '@id' => $id,
            'options' => $options,
        ];

        return $this;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    /**
     * @return $this
     */
    public function setDuration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * @return $this
     */
    public function setContent(array $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getPublished(): int
    {
        return $this->published;
    }

    /**
     * @return $this
     */
    public function setPublished(int $published): self
    {
        $this->published = $published;

        return $this;
    }
}
