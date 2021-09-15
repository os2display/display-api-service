<?php

namespace App\Entity;

use App\Repository\TemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=TemplateRepository::class)
 */
class Template
{
    use EntityIdTrait;
    use EntityTitleDescriptionTrait;
    use EntityModificationTrait;
    use TimestampableEntity;

    // @TODO: Add resources:
    // "component": "http://example.com",
    //    "admin": "string",
    //    "schema": "string"
    //    "assets": [
    //      {
    //        "type": "string",
    //        "url": "http://example.com"
    //      }
    //    ],
    //    "options": {},
    //    "content": {},

    /**
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : ""})
     */
    private string $icon = '';

    /**
     * @ORM\OneToMany(targetEntity=Slide::class, mappedBy="template")
     */
    private ArrayCollection $slides;

    public function __construct()
    {
        $this->slides = new ArrayCollection();
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return ArrayCollection|Slide[]
     */
    public function getSlides(): ArrayCollection
    {
        return $this->slides;
    }

    public function addSlide(Slide $slide): self
    {
        if (!$this->slides->contains($slide)) {
            $this->slides->add($slide);
            $slide->setTemplate($this);
        }

        return $this;
    }

    public function removeSlide(Slide $slide): self
    {
        if ($this->slides->removeElement($slide)) {
            // set the owning side to null (unless already changed)
            if ($slide->getTemplate() === $this) {
                $slide->setTemplate(null);
            }
        }

        return $this;
    }
}
