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
#[ORM\Index(fields: ['changed'], name: 'template_changed_idx')]
class Template extends AbstractBaseEntity implements MultiTenantInterface, RelationsChecksumInterface
{
    use MultiTenantTrait;
    use RelationsChecksumTrait;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false, options: ['default' => ''])]
    private string $title = '';

    /*
     * The three properties below ($icon, $resources, $description) are
     * carried over from 2.7 with their original mapping and PHP-level
     * defaults so the consolidated end-of-2.8 schema matches both fresh
     * installs and 2.x → 3.0 upgraders, and so Doctrine emits a value
     * for each on every INSERT (the columns are NOT NULL with no DB
     * default). They are intentionally write-only here — no getters,
     * no setters, no API exposure (`description` is filtered out of the
     * GetCollection search filter via `template.search_filter` in
     * `config/services.yaml`).
     *
     * TODO[3.1]: delete these three properties together with the deferred
     * column-drop migration. Both must land in the same change so the
     * entity and the schema stay in sync.
     */

    /** @deprecated TODO[3.1]: drop together with the column. */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: false, options: ['default' => ''])]
    private string $icon = '';

    /**
     * @deprecated TODO[3.1]: drop together with the column.
     *
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $resources = [];

    /** @deprecated TODO[3.1]: drop together with the column. */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: false, options: ['default' => ''])]
    private string $description = '';

    /**
     * @var Collection<int, Slide>
     */
    #[ORM\OneToMany(mappedBy: 'template', targetEntity: Slide::class, fetch: 'EXTRA_LAZY')]
    private Collection $slides;

    public function __construct()
    {
        $this->slides = new ArrayCollection();
        $this->tenants = new ArrayCollection();

        parent::__construct();
    }

    /**
     * @return Collection<int, Slide>
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
