<?php

declare(strict_types=1);

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 * @internal
 */
trait EntityTitleDescriptionTrait
{
    #[ORM\Column(type: 'string', length: 255, nullable: false, options: ['default' => ''])]
    private string $title = '';

    #[ORM\Column(type: 'string', length: 255, nullable: false, options: ['default' => ''])]
    private string $description = '';

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
