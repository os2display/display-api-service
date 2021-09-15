<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait EntityModificationTrait
{
    /**
     * @ORM\Column(type="string", nullable=false, options={"default":""})
     */
    private string $modifiedBy = '';

    /**
     * @ORM\Column(type="string", nullable=false, options={"default":""})
     */
    private string $createdBy = '';

    public function getModifiedBy(): string
    {
        return $this->modifiedBy;
    }

    public function setModifiedBy(string $modifiedBy): self
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    public function setCreatedBy(string $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}
