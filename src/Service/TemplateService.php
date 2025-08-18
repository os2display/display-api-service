<?php

namespace App\Service;

use App\Entity\Template;
use Doctrine\ORM\EntityManagerInterface;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use PHPUnit\Util\Exception;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Uid\Ulid;

class TemplateService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
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
            $content = json_decode($file->getContents());
            $validator->validate($content, $jsonSchemaObject);

            if (!$validator->isValid()) {
                $message = "JSON file " . $file->getFilename() . " does not validate. Violations:\n";
                foreach ($validator->getErrors() as $error) {
                    $message .= sprintf("\n[%s] %s", $error['property'], $error['message']);
                }

                throw new Exception($message);
            }

            if (!Ulid::isValid($content->id)) {
                throw new Exception('The Ulid is not valid');
            }

            $repository = $this->entityManager->getRepository(Template::class);
            $template = $repository->findOneBy(['id' => Ulid::fromString($content->id)]);

            $templates[] = [
                'id' => $content->id,
                'title' => $content->title,
                'templateEntity' => $template,
                'installed' => $template !== null,
                'type' => $customTemplates ? 'Custom' : 'Core',
            ];
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
