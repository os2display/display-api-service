<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Template;
use App\Enum\ResourceTypeEnum;
use App\Exceptions\NotAcceptableException;
use App\Exceptions\NotFoundException;
use App\Model\InstallStatus;
use App\Model\TemplateData;
use App\Repository\SlideRepository;
use App\Repository\TemplateRepository;
use App\Utils\ResourceLoader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AssignedGenerator;
use Symfony\Component\Uid\Ulid;

class TemplateService
{
    public const string CORE_TEMPLATES_PATH = 'assets/shared/templates';
    public const string CUSTOM_TEMPLATES_PATH = 'assets/shared/custom-templates';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TemplateRepository $templateRepository,
        private readonly SlideRepository $slideRepository,
        private readonly ResourceLoader $loader,
    ) {}

    public function getAll(): array
    {
        $core = $this->loader->getResourceInDirectory($this::CORE_TEMPLATES_PATH, TemplateData::class, ResourceTypeEnum::CORE);
        $custom = $this->loader->getResourceInDirectory($this::CUSTOM_TEMPLATES_PATH, TemplateData::class, ResourceTypeEnum::CUSTOM);

        return array_merge($core, $custom);
    }

    public function installAll(bool $update): void
    {
        $templates = $this->getAll();

        foreach ($templates as $templateToInstall) {
            $this->install($templateToInstall, $update);
        }
    }

    public function installById(string $ulidString, bool $update = false): void
    {
        $templateToInstall = array_find($this->getAll(), fn (TemplateData $templateData): bool => $templateData->id === $ulidString);

        if (null === $templateToInstall) {
            throw new NotFoundException();
        }

        $this->install($templateToInstall, $update);
    }

    public function install(TemplateData $templateData, bool $update = false): void
    {
        $template = $templateData->templateEntity;

        if (null === $template) {
            $template = new Template();

            $metadata = $this->entityManager->getClassMetaData($template::class);
            $metadata->setIdGenerator(new AssignedGenerator());

            $ulid = Ulid::fromString($templateData->id);
            $template->setId($ulid);

            $this->entityManager->persist($template);
        }

        if ($update) {
            $template->setTitle($templateData->title);
        }

        $this->entityManager->flush();
    }

    public function updateAll(): void
    {
        $templates = $this->getAll();

        foreach ($templates as $templateToUpdate) {
            $this->update($templateToUpdate);
        }
    }

    public function update(TemplateData $templateData): void
    {
        $template = $templateData->templateEntity;

        // Ignore templates that do not exist in the database.
        if (null === $template) {
            return;
        }

        $template->setTitle($templateData->title);

        $this->entityManager->flush();
    }

    public function remove(string $ulidString): void
    {
        $template = $this->templateRepository->findOneBy(['id' => Ulid::fromString($ulidString)]);

        if (!$template) {
            throw new NotFoundException('Template not installed. Aborting.');
        }

        $slides = $this->slideRepository->findBy(['template' => $template]);
        $numberOfSlides = count($slides);

        if ($numberOfSlides > 0) {
            $message = "Aborting. Template is bound to $numberOfSlides following slides:\n\n";

            foreach ($slides as $slide) {
                $id = $slide->getId();
                $message .= "$id\n";
            }

            throw new NotAcceptableException($message);
        }

        $this->entityManager->remove($template);

        $this->entityManager->flush();
    }

    public function getInstallStatus(): InstallStatus
    {
        $templates = $this->getAll();
        $numberOfTemplates = count($templates);
        $numberOfInstalledTemplates = count(array_filter($templates, fn ($entry): bool => $entry->installed));

        return new InstallStatus($numberOfTemplates, $numberOfInstalledTemplates);
    }
}
