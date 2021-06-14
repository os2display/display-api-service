<?php

namespace App\Api\Model;

use ApiPlatform\Core\Annotation\ApiProperty;

/**
 * Class Slide
 */
class Slide extends Shared {

    /**
     * @ApiProperty(identifier=true)
     */
    public string $id = '';
    private array $template = [];
    private int $duration = 0;
    private array $content = [];
    private int $published = 0;

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
     * @return array
     */
    public function getTemplate(): array
    {
        return $this->template;
    }

    /**
     * @param string $id
     * @param array $options
     *
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

    /**
     * @return int
     */
    public function getDuration(): int
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     *
     * @return $this
     */
    public function setDuration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * @return array
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * @param array $content
     *
     * @return $this
     */
    public function setContent(array $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return int
     */
    public function getPublished(): int
    {
        return $this->published;
    }

    /**
     * @param int $published
     *
     * @return $this
     */
    public function setPublished(int $published): self
    {
        $this->published = $published;

        return $this;
    }

}
