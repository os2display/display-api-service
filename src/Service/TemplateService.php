<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Template;
use App\Model\TemplateData;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AssignedGenerator;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Uid\Ulid;

class TemplateService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

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
        if ($template === null) {
            return;
        }

        $template->setTitle($templateData->title);

        $this->entityManager->flush();
    }

    public function getAllTemplates(): array
    {
        return array_merge($this->getCoreTemplates(), $this->getCustomTemplates());
    }

    public function getCoreTemplates(): array
    {
        $finder = new Finder();

        if (is_dir('assets/shared/templates')) {
            $finder->files()->followLinks()->ignoreUnreadableDirs()->in('assets/shared/templates')->depth('== 0')->name('*.json');

            if ($finder->hasResults()) {
                return $this->getTemplates($finder);
            }
        }

        return [];
    }

    public function getCustomTemplates(): array
    {
        $finder = new Finder();

        if (is_dir('assets/shared/custom-templates')) {
            $finder->files()->followLinks()->ignoreUnreadableDirs()->in('assets/shared/custom-templates')->depth('== 0')->name('*.json');

            if ($finder->hasResults()) {
                return $this->getTemplates($finder, true);
            }
        }

        return [];
    }

    public function getTemplates(iterable $finder, bool $customTemplates = false): array
    {
        $templates = [];

        // Validate template json.
        $schemaStorage = new SchemaStorage();
        $jsonSchemaObject = $this->getSchema();
        $schemaStorage->addSchema('file://contentSchema', $jsonSchemaObject);
        $validator = new Validator(new Factory($schemaStorage));

        foreach ($finder as $file) {
            $content = json_decode((string) $file->getContents());
            $validator->validate($content, $jsonSchemaObject);

            if (!$validator->isValid()) {
                $message = 'JSON file '.$file->getFilename()." does not validate. Violations:\n";
                foreach ($validator->getErrors() as $error) {
                    $message .= sprintf("\n[%s] %s", $error['property'], $error['message']);
                }

                throw new \Exception($message);
            }

            if (!Ulid::isValid($content->id)) {
                throw new \Exception('The Ulid is not valid');
            }

            $repository = $this->entityManager->getRepository(Template::class);
            $template = $repository->findOneBy(['id' => Ulid::fromString($content->id)]);

            $templates[] = new TemplateData(
                $content->id,
                $content->title,
                $content->adminForm,
                $content->options,
                $template,
                null !== $template,
                $customTemplates ? 'Custom' : 'Core',
            );
        }

        return $templates;
    }

    /**
     * Supplies json schema for validation.
     *
     * @return mixed
     *   Json schema
     *
     * @throws \JsonException
     */
    public function getSchema(): object
    {
        $jsonSchema = <<<'JSON'
        {
          "$schema": "https://json-schema.org/draft/2020-12/schema",
          "$id": "https://os2display.dk/config-schema.json",
          "title": "Config file schema",
          "description": "Schema for defining config files for templates",
          "type": "object",
          "properties": {
            "id": {
              "description": "Ulid",
              "type": "string"
            },
            "title": {
              "description": "The title of the template",
              "type": "string"
            },
            "options": {
              "description": "Template options",
              "type": "object"
            },
            "adminForm": {
              "description": "The admin form description",
              "type": "array",
              "items": {
                "type": "object",
                "description": "Form element"
              }
            }
          },
          "required": ["id", "title", "options", "adminForm"]
        }
        JSON;

        return json_decode($jsonSchema, false, 512, JSON_THROW_ON_ERROR);
    }
}
