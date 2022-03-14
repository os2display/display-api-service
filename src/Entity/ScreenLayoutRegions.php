<?php

namespace App\Entity;

use App\Entity\Interfaces\MultiTenantInterface;
use App\Entity\Tenant\PlaylistScreenRegion;
use App\Entity\Traits\MultiTenantTrait;
use App\Repository\ScreenLayoutRegionsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=ScreenLayoutRegionsRepository::class)
 */
class ScreenLayoutRegions extends AbstractBaseEntity implements MultiTenantInterface
{
    use MultiTenantTrait;

    /**
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : ""})
     * @Groups({"read"})
     */
    private string $title = '';

    /**
     * @ORM\Column(type="array", nullable=false)
     * @Groups({"read"})
     */
    private array $gridArea = [];

    /**
     * @ORM\OneToMany(targetEntity=PlaylistScreenRegion::class, mappedBy="region", orphanRemoval=true)
     */
    private Collection $playlistScreenRegions;

    /**
     * @ORM\ManyToOne(targetEntity=ScreenLayout::class, inversedBy="regions")
     */
    private ScreenLayout $screenLayout;

    public function __construct()
    {
        $this->playlistScreenRegions = new ArrayCollection();
        $this->tenants = new ArrayCollection();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getGridArea(): array
    {
        return $this->gridArea;
    }

    public function setGridArea(array $gridArea): self
    {
        $this->gridArea = $gridArea;

        return $this;
    }

    /**
     * @return Collection|PlaylistScreenRegion[]
     */
    public function getPlaylistScreenRegions(): Collection
    {
        return $this->playlistScreenRegions;
    }

    public function addPlaylistScreenRegion(PlaylistScreenRegion $playlistScreenRegion): self
    {
        if (!$this->playlistScreenRegions->contains($playlistScreenRegion)) {
            $this->playlistScreenRegions->add($playlistScreenRegion);
            $playlistScreenRegion->setRegion($this);
        }

        return $this;
    }

    public function removePlaylistScreenRegion(PlaylistScreenRegion $playlistScreenRegion): self
    {
        if ($this->playlistScreenRegions->removeElement($playlistScreenRegion)) {
            // set the owning side to null (unless already changed)
            if ($playlistScreenRegion->getRegion() === $this) {
                $playlistScreenRegion->removeRegion();
            }
        }

        return $this;
    }

    public function removeAllPlaylistScreenRegions(): self
    {
        foreach ($this->playlistScreenRegions as $playlistScreenRegion) {
            // set the owning side to null (unless already changed)
            if ($playlistScreenRegion->getRegion() === $this) {
                $playlistScreenRegion->removeRegion();
            }
        }

        $this->playlistScreenRegions->clear();

        return $this;
    }

    public function getScreenLayout(): ?ScreenLayout
    {
        return $this->screenLayout;
    }

    public function setScreenLayout(?ScreenLayout $screenLayout): self
    {
        $this->screenLayout = $screenLayout;

        return $this;
    }
}
