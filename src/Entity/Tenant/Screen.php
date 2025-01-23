<?php

declare(strict_types=1);

namespace App\Entity\Tenant;

use App\Entity\Interfaces\RelationsChecksumInterface;
use App\Entity\ScreenLayout;
use App\Entity\ScreenUser;
use App\Entity\Traits\EntityTitleDescriptionTrait;
use App\Entity\Traits\RelationsChecksumTrait;
use App\Repository\ScreenRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScreenRepository::class)]
#[ORM\Index(fields: ['changed'], name: 'changed_idx')]
class Screen extends AbstractTenantScopedEntity implements RelationsChecksumInterface
{
    use EntityTitleDescriptionTrait;
    use RelationsChecksumTrait;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['default' => 0])]
    private int $size = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: false, options: ['default' => ''])]
    private string $resolution = '';

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: false, options: ['default' => ''])]
    private string $orientation = '';

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true, options: ['default' => ''])]
    private string $location = '';

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    private ?bool $enableColorSchemeChange = null;

    #[ORM\ManyToOne(targetEntity: ScreenLayout::class, inversedBy: 'screens')]
    #[ORM\JoinColumn(nullable: false)]
    private ScreenLayout $screenLayout;

    #[ORM\OneToOne(mappedBy: 'screen', targetEntity: ScreenUser::class, orphanRemoval: true)]
    private ?ScreenUser $screenUser = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Tenant\ScreenCampaign>
     */
    #[ORM\OneToMany(mappedBy: 'screen', targetEntity: ScreenCampaign::class, orphanRemoval: true)]
    private Collection $screenCampaigns;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Tenant\PlaylistScreenRegion>
     */
    #[ORM\OneToMany(mappedBy: 'screen', targetEntity: PlaylistScreenRegion::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['weight' => \Doctrine\Common\Collections\Order::Ascending->value])]
    private Collection $playlistScreenRegions;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Tenant\ScreenGroup>
     */
    #[ORM\ManyToMany(targetEntity: ScreenGroup::class, mappedBy: 'screens')]
    private Collection $screenGroups;

    public function __construct()
    {
        $this->playlistScreenRegions = new ArrayCollection();
        $this->screenCampaigns = new ArrayCollection();
        $this->screenGroups = new ArrayCollection();
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getResolution(): string
    {
        return $this->resolution;
    }

    public function setResolution(string $resolution): self
    {
        $this->resolution = $resolution;

        return $this;
    }

    public function getOrientation(): string
    {
        return $this->orientation;
    }

    public function setOrientation(string $orientation): self
    {
        $this->orientation = $orientation;

        return $this;
    }

    public function getScreenLayout(): ScreenLayout
    {
        return $this->screenLayout;
    }

    public function setScreenLayout(ScreenLayout $screenLayout): self
    {
        $this->screenLayout = $screenLayout;

        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getPlaylistScreenRegions(): Collection
    {
        return $this->playlistScreenRegions;
    }

    public function addPlaylistScreenRegion(PlaylistScreenRegion $playlistScreenRegion): self
    {
        if (!$this->playlistScreenRegions->contains($playlistScreenRegion)) {
            $this->playlistScreenRegions->add($playlistScreenRegion);
            $playlistScreenRegion->setScreen($this);
        }

        return $this;
    }

    public function removePlaylistScreenRegion(PlaylistScreenRegion $playlistScreenRegion): self
    {
        if ($this->playlistScreenRegions->removeElement($playlistScreenRegion)) {
            // set the owning side to null (unless already changed)
            if ($playlistScreenRegion->getScreen() === $this) {
                $playlistScreenRegion->removeScreen();
            }
        }

        return $this;
    }

    public function removeAllPlaylistScreenRegions(): self
    {
        foreach ($this->getPlaylistScreenRegions() as $playlistScreenRegion) {
            // set the owning side to null (unless already changed)
            if ($playlistScreenRegion->getScreen() === $this) {
                $playlistScreenRegion->removeScreen();
            }
        }

        $this->playlistScreenRegions->clear();

        return $this;
    }

    public function setScreenGroups(Collection $screenGroups): void
    {
        foreach ($this->screenGroups as $screenGroup) {
            if (false === $screenGroups->contains($screenGroup)) {
                $this->removeScreenGroup($screenGroup);
            }
        }
        foreach ($screenGroups as $screenGroup) {
            $this->addScreenGroup($screenGroup);
        }
    }

    /**
     * @return Collection
     */
    public function getScreenGroups(): Collection
    {
        return $this->screenGroups;
    }

    public function addScreenGroup(ScreenGroup $screenGroup): self
    {
        if (!$this->screenGroups->contains($screenGroup)) {
            $this->screenGroups->add($screenGroup);
            $screenGroup->addScreen($this);
        }

        return $this;
    }

    public function removeScreenGroup(ScreenGroup $screenGroup): self
    {
        if ($this->screenGroups->removeElement($screenGroup)) {
            $screenGroup->removeScreen($this);
        }

        return $this;
    }

    public function removeAllScreenGroup(): self
    {
        foreach ($this->getScreenGroups() as $screenGroup) {
            // set the owning side to null (unless already changed)
            if ($screenGroup->getScreens()->contains($this)) {
                $screenGroup->getScreens()->removeElement($this);
            }
        }

        $this->screenGroups->clear();

        return $this;
    }

    public function getScreenUser(): ?ScreenUser
    {
        return $this->screenUser;
    }

    public function setScreenUser(ScreenUser $screenUser): self
    {
        // set the owning side of the relation if necessary
        if ($screenUser->getScreen() !== $this) {
            $screenUser->setScreen($this);
        }

        $this->screenUser = $screenUser;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getScreenCampaigns(): Collection
    {
        return $this->screenCampaigns;
    }

    public function addScreenCampaign(ScreenCampaign $screenCampaign): self
    {
        if (!$this->screenCampaigns->contains($screenCampaign)) {
            $this->screenCampaigns[] = $screenCampaign;
            $screenCampaign->setScreen($this);
        }

        return $this;
    }

    public function removeScreenCampaign(ScreenCampaign $screenCampaign): self
    {
        if ($this->screenCampaigns->removeElement($screenCampaign)) {
            // set the owning side to null (unless already changed)
            if ($screenCampaign->getScreen() === $this) {
                $screenCampaign->setScreen(null);
            }
        }

        return $this;
    }

    public function getEnableColorSchemeChange(): ?bool
    {
        return $this->enableColorSchemeChange;
    }

    public function setEnableColorSchemeChange(?bool $enableColorSchemeChange): self
    {
        $this->enableColorSchemeChange = $enableColorSchemeChange;

        return $this;
    }
}
