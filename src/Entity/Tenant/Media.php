<?php

declare(strict_types=1);

namespace App\Entity\Tenant;

use App\Entity\Interfaces\RelationsChecksumInterface;
use App\Entity\Traits\EntityTitleDescriptionTrait;
use App\Entity\Traits\RelationsChecksumTrait;
use App\Repository\MediaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: MediaRepository::class)]
#[ORM\EntityListeners([\App\EventListener\MediaDoctrineEventListener::class])]
#[ORM\Index(fields: ['changed'], name: 'changed_idx')]
class Media extends AbstractTenantScopedEntity implements RelationsChecksumInterface
{
    use EntityTitleDescriptionTrait;
    use RelationsChecksumTrait;

    /**
     * @Vich\UploadableField(mapping="media_object", fileNameProperty="filePath")
     */
    #[Vich\UploadableField(mapping: 'media_object', fileNameProperty: 'filePath', size: 'size')]
    #[Assert\File(maxSize: '200000k', mimeTypes: ['image/jpeg', 'image/png', 'image/svg+xml', 'video/webm', 'video/mp4', 'image/gif'], mimeTypesMessage: 'Please upload a valid image format: jpeg, svg, gif or png, or video format: webm or mp4')]
    public ?File $file = null;

    #[ORM\Column(nullable: true)]
    public ?string $filePath = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true, options: ['default' => ''])]
    private string $license = '';

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['default' => 0])]
    private int $width = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['default' => 0])]
    private int $height = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['default' => 0])]
    private int $size = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, options: ['default' => ''])]
    private string $mimeType = '';

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, options: ['default' => ''])]
    private string $sha = '';

    /**
     * @var Collection<int, Slide>
     */
    #[ORM\ManyToMany(targetEntity: Slide::class, mappedBy: 'media')]
    private Collection $slides;

    public function __construct()
    {
        $this->slides = new ArrayCollection();
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function setHeight(int $height): self
    {
        $this->height = $height;

        return $this;
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

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getSha(): string
    {
        return $this->sha;
    }

    public function setSha(string $sha): self
    {
        $this->sha = $sha;

        return $this;
    }

    public function setFile(?File $file = null): self
    {
        $this->file = $file;

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFilePath(?string $filePath): self
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function getLicense(): string
    {
        return $this->license;
    }

    public function setLicense(string $license): self
    {
        $this->license = $license;

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
            $slide->addMedium($this);
        }

        return $this;
    }

    public function removeSlide(Slide $slide): self
    {
        if ($this->slides->removeElement($slide)) {
            $slide->removeMedium($this);
        }

        return $this;
    }

    public function removeAllSlides(): self
    {
        foreach ($this->slides as $slide) {
            $slide->removeMedium($this);
        }

        $this->slides->clear();

        return $this;
    }
}
