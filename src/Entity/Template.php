<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\MultiTenantInterface;
use App\Entity\Interfaces\RelationsChecksumInterface;
use App\Entity\Tenant\Slide;
use App\Entity\Traits\MultiTenantTrait;
use App\Entity\Traits\RelationsChecksumTrait;
use App\EventListener\TemplateDoctrineEventListener;
use App\Repository\TemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TemplateRepository::class)]
#[ORM\EntityListeners([TemplateDoctrineEventListener::class])]
#[ORM\Index(fields: ['changed'], name: 'changed_idx')]
class Template extends AbstractBaseEntity implements MultiTenantInterface, RelationsChecksumInterface
{
    use MultiTenantTrait;
    use RelationsChecksumTrait;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false, options: ['default' => ''])]
    private string $title = '';

    /**
     * @var Collection<int, Slide>
     */
    #[ORM\OneToMany(targetEntity: Slide::class, mappedBy: 'template')]
    private Collection $slides;

    public function __construct()
    {
        $this->slides = new ArrayCollection();
        $this->tenants = new ArrayCollection();

        parent::__construct();
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
