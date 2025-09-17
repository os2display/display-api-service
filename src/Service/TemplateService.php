<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Template;
use App\Enum\ResourceTypeEnum;
use App\Model\TemplateData;
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
        private readonly ResourceLoader $loader,
    ) {}

    public function getTemplates(): array
    {
        $core = $this->loader->getResourceJsonInDirectory($this::CORE_TEMPLATES_PATH, TemplateData::class, ResourceTypeEnum::CORE);
        $custom = $this->loader->getResourceJsonInDirectory($this::CUSTOM_TEMPLATES_PATH, TemplateData::class, ResourceTypeEnum::CUSTOM);

        return array_merge($core, $custom);
    }

    public function installTemplate(TemplateData $templateData, bool $update = false): void
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

    public function updateTemplate(TemplateData $templateData): void
    {
        $template = $templateData->templateEntity;

        // Ignore templates that do not exist in the database.
        if (null === $template) {
            return;
        }

        $template->setTitle($templateData->title);

        $this->entityManager->flush();
    }
}
