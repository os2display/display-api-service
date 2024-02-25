<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\MultiTenantInterface;
use App\Entity\Interfaces\RelationsChecksumInterface;
use App\Entity\Tenant\Screen;
use App\Entity\Traits\EntityTitleDescriptionTrait;
use App\Entity\Traits\MultiTenantTrait;
use App\Entity\Traits\RelationsChecksumTrait;
use App\Repository\ScreenLayoutRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScreenLayoutRepository::class)]
#[ORM\EntityListeners([\App\EventListener\ScreenLayoutDoctrineEventListener::class])]
#[ORM\Index(fields: ['changed'], name: 'changed_idx')]
class ScreenLayout extends AbstractBaseEntity implements MultiTenantInterface, RelationsChecksumInterface
{
    use MultiTenantTrait;
    use EntityTitleDescriptionTrait;
    use RelationsChecksumTrait;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: false, options: ['default' => 0])]
    private int $gridRows = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: false, options: ['default' => 0])]
    private int $gridColumns = 0;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Tenant\Screen>|\App\Entity\Tenant\Screen[]
     */
    #[ORM\OneToMany(targetEntity: Screen::class, mappedBy: 'screenLayout')]
    private Collection $screens;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\ScreenLayoutRegions>|\App\Entity\ScreenLayoutRegions[]
     */
    #[ORM\OneToMany(targetEntity: ScreenLayoutRegions::class, mappedBy: 'screenLayout')]
    private Collection $regions;

    public function __construct()
    {
        $this->screens = new ArrayCollection();
        $this->regions = new ArrayCollection();
        $this->tenants = new ArrayCollection();
    }

    public function getGridRows(): int
    {
        return $this->gridRows;
    }

    public function setGridRows(int $gridRows): self
    {
        $this->gridRows = $gridRows;

        return $this;
    }

    public function getGridColumns(): int
    {
        return $this->gridColumns;
    }

    public function setGridColumns(int $gridColumns): self
    {
        $this->gridColumns = $gridColumns;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getScreens(): Collection
    {
        return $this->screens;
    }

    public function addScreen(Screen $screen): self
    {
        if (!$this->screens->contains($screen)) {
            $this->screens->add($screen);
            $screen->setScreenLayout($this);
        }

        return $this;
    }

    public function removeScreen(Screen $screen): self
    {
        if ($this->screens->removeElement($screen)) {
            // Set the owning side to null (unless already changed)
            if ($screen->getScreenLayout() === $this) {
                $screen->setScreenLayout(null);
            }
        }

        return $this;
    }

    public function removeAllScreen(): self
    {
        foreach ($this->screens as $screen) {
            // Set the owning side to null (unless already changed)
            if ($screen->getScreenLayout() === $this) {
                $screen->setScreenLayout(null);
            }
        }

        $this->screens->clear();

        return $this;
    }

    /**
     * @return Collection
     */
    public function getRegions(): Collection
    {
        return $this->regions;
    }

    public function addRegion(ScreenLayoutRegions $region): self
    {
        if (!$this->regions->contains($region)) {
            $this->regions[] = $region;
            $region->setScreenLayout($this);
        }

        return $this;
    }

    public function removeRegion(ScreenLayoutRegions $region): self
    {
        if ($this->regions->removeElement($region)) {
            // set the owning side to null (unless already changed)
            if ($region->getScreenLayout() === $this) {
                $region->setScreenLayout(null);
            }
        }

        return $this;
    }

    public function removeAllRegion(): self
    {
        foreach ($this->regions as $region) {
            // set the owning side to null (unless already changed)
            if ($region->getScreenLayout() === $this) {
                $region->setScreenLayout(null);
            }
        }

        $this->regions->clear();

        return $this;
    }
}
