<?php

namespace App\Entity;

use App\Entity\Interfaces\MultiTenantInterface;
use App\Entity\Tenant\Slide;
use App\Entity\Traits\EntityTitleDescriptionTrait;
use App\Entity\Traits\MultiTenantTrait;
use App\Repository\TemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TemplateRepository::class)
 *
 * @ORM\EntityListeners({"App\EventListener\TemplateDoctrineEventListener"})
 */
class Template extends AbstractBaseEntity implements MultiTenantInterface
{
    use MultiTenantTrait;

    use EntityTitleDescriptionTrait;

    /**
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : ""})
     */
    private string $icon = '';

    /**
     * @ORM\Column(type="array")
     */
    private array $resources = [];

    /**
     * @ORM\OneToMany(targetEntity=Slide::class, mappedBy="template")
     */
    private Collection $slides;

    public function __construct()
    {
        $this->slides = new ArrayCollection();
        $this->tenants = new ArrayCollection();
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getResources(): array
    {
        return $this->resources;
    }

    public function setResources(array $resources): self
    {
        $this->resources = $resources;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getSlides(): Collection
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
                $slide->removeTemplate();
            }
        }

        return $this;
    }

    public function removeAllSlides(): self
    {
        foreach ($this->slides as $slide) {
            // set the owning side to null (unless already changed)
            if ($slide->getTemplate() === $this) {
                $slide->removeTemplate();
            }
        }

        $this->slides->clear();

        return $this;
    }
}
