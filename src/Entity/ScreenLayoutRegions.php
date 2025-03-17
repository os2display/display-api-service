<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\MultiTenantInterface;
use App\Entity\Interfaces\RelationsChecksumInterface;
use App\Entity\Tenant\PlaylistScreenRegion;
use App\Entity\Traits\MultiTenantTrait;
use App\Entity\Traits\RelationsChecksumTrait;
use App\Repository\ScreenLayoutRegionsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ScreenLayoutRegionsRepository::class)]
#[ORM\EntityListeners([\App\EventListener\ScreenLayoutRegionsDoctrineEventListener::class])]
#[ORM\Index(fields: ['changed'], name: 'changed_idx')]
class ScreenLayoutRegions extends AbstractBaseEntity implements MultiTenantInterface, RelationsChecksumInterface
{
    use MultiTenantTrait;
    use RelationsChecksumTrait;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: false, options: ['default' => ''])]
    #[Groups(['read'])]
    private string $title = '';

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON, nullable: false)]
    #[Groups(['read'])]
    private array $gridArea = [];

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    #[Groups(['read'])]
    private ?string $type = null;

    #[ORM\ManyToOne(targetEntity: ScreenLayout::class, inversedBy: 'regions')]
    private ?ScreenLayout $screenLayout = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Tenant\PlaylistScreenRegion>
     */
    #[ORM\OneToMany(targetEntity: PlaylistScreenRegion::class, cascade: ['persist', 'remove'], mappedBy: 'region', orphanRemoval: true)]
    private Collection $playlistScreenRegions;

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
     * @return Collection
     */
    public function getPlaylistScreenRegions(): Collection
    {
        return $this->playlistScreenRegions;
    }

    public function setPlaylistScreenRegions(Collection $playlistScreenRegions): void
    {
        foreach ($this->playlistScreenRegions as $playlistScreenRegion) {
            if (false === $playlistScreenRegions->contains($playlistScreenRegion)) {
                $this->removePlaylistScreenRegion($playlistScreenRegion);
            }
        }
        foreach ($playlistScreenRegions as $playlistScreenRegion) {
            $this->addPlaylistScreenRegion($playlistScreenRegion);
        }
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }
}
