<?php

declare(strict_types=1);

namespace App\Entity\Tenant;

use App\Entity\Interfaces\RelationsChecksumInterface;
use App\Entity\Traits\EntityTitleDescriptionTrait;
use App\Entity\Traits\RelationsChecksumTrait;
use App\Repository\ThemeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ThemeRepository::class)]
#[ORM\EntityListeners([\App\EventListener\ThemeDoctrineEventListener::class])]
#[ORM\Index(fields: ['changed'], name: 'changed_idx')]
class Theme extends AbstractTenantScopedEntity implements RelationsChecksumInterface
{
    use EntityTitleDescriptionTrait;
    use RelationsChecksumTrait;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT)]
    private string $cssStyles = '';

    #[ORM\OneToOne(targetEntity: Media::class)]
    private ?Media $logo = null;

    /**
     * @var Collection<int, Slide>
     */
    #[ORM\OneToMany(mappedBy: 'theme', targetEntity: Slide::class)]
    private Collection $slides;

    public function __construct()
    {
        $this->slides = new ArrayCollection();
    }

    public function getCssStyles(): string
    {
        return $this->cssStyles;
    }

    public function setCssStyles(string $cssStyles): self
    {
        $this->cssStyles = $cssStyles;

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
            $this->slides[] = $slide;
            $slide->setTheme($this);
        }

        return $this;
    }

    public function removeSlide(Slide $slide): self
    {
        if ($this->slides->removeElement($slide)) {
            // set the owning side to null (unless already changed)
            if ($slide->getTheme() === $this) {
                $slide->setTheme(null);
            }
        }

        return $this;
    }

    /**
     * @return Media
     */
    public function getLogo(): ?Media
    {
        return $this->logo;
    }

    public function setLogo(?Media $medium): self
    {
        $this->logo = $medium;

        return $this;
    }

    public function removeLogo(): self
    {
        $this->logo = null;

        return $this;
    }
}
