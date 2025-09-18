<?php

declare(strict_types=1);

namespace App\Utils;

use App\Enum\ResourceTypeEnum;
use App\Exceptions\NotImplementedException;
use App\Model\ScreenLayoutData;
use App\Model\TemplateData;
use App\Repository\ScreenLayoutRepository;
use App\Repository\TemplateRepository;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Uid\Ulid;

readonly class ResourceLoader
{
    public function __construct(
        private ScreenLayoutRepository $screenLayoutRepository,
        private TemplateRepository $templateRepository,
    ) {}

    public function getResourceInDirectory(string $path, string $resourceType, ResourceTypeEnum $type): array
    {
        $finder = new Finder();

        if (is_dir($path)) {
            $finder->files()->followLinks()->ignoreUnreadableDirs()->in($path)->depth('== 0')->name('*.json');

            if ($finder->hasResults()) {
                return match ($resourceType) {
                    ScreenLayoutData::class, TemplateData::class => $this->getResourceData($finder, $resourceType, $type),
                    default => throw new NotImplementedException(),
                };
            }
        }

        return [];
    }

    private function getResourceData(iterable $finder, string $resourceType, ResourceTypeEnum $type): array
    {
        // Validate template json.
        $schemaStorage = new SchemaStorage();

        $jsonSchemaObject = match ($resourceType) {
            ScreenLayoutData::class => $this->getScreenLayoutJsonSchema(),
            TemplateData::class => $this->getTemplateJsonSchema(),
            default => throw new NotImplementedException(),
        };

        $schemaStorage->addSchema('file://contentSchema', $jsonSchemaObject);
        $validator = new Validator(new Factory($schemaStorage));

        $results = [];

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

            switch ($resourceType) {
                case ScreenLayoutData::class:
                    $entity = $this->screenLayoutRepository->find(Ulid::fromString($content->id));

                    $results[] = new ScreenLayoutData(
                        $content->id,
                        $content->title,
                        $type,
                        $content->grid->rows,
                        $content->grid->columns,
                        $entity,
                        null !== $entity,
                        $content->regions,
                    );

                    break;
                case TemplateData::class:
                    $entity = $this->templateRepository->find(Ulid::fromString($content->id));

                    $results[] = new TemplateData(
                        $content->id,
                        $content->title,
                        $content->adminForm,
                        $content->options,
                        $entity,
                        null !== $entity,
                        $type,
                    );
                    break;
            }
        }

        return $results;
    }

    /**
     * Supplies json schema for validation of template data.
     *
     * @throws \JsonException
     */
    public function getTemplateJsonSchema(): object
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

    /**
     * Supplies json schema for validation of screen layout data.
     *
     * @throws \JsonException
     */
    private function getScreenLayoutJsonSchema(): object
    {
        $jsonSchema = <<<'JSON'
        {
          "$schema": "https://json-schema.org/draft/2020-12/schema",
          "$id": "https://os2display.dk/config-schema.json",
          "title": "Config file schema",
          "description": "Schema for defining config files for screen layouts",
          "type": "object",
          "properties": {
            "id": {
              "description": "Ulid of the screen layout",
              "type": "string"
            },
            "title": {
              "description": "The title of the screen layout",
              "type": "string"
            },
            "grid": {
              "description": "Grid properties",
              "type": "object",
              "properties": {
                "rows": {
                  "type": "integer",
                  "description": "Number of rows"
                },
                "columns": {
                  "type": "integer",
                  "description": "Number of columns"
                }
              }
            },
            "regions": {
              "description": "The regions of the screen layout",
              "type": "array",
              "items": {
                "type": "object",
                "description": "Region",
                "properties": {
                  "id": {
                    "description": "Ulid of the region",
                    "type": "string"
                  },
                  "title": {
                    "description": "The title of the region",
                    "type": "string"
                  },
                  "gridArea": {
                    "description": "Grid area value",
                    "type": "array",
                    "items": {
                      "type": "string"
                    }
                  }
                }
              }
            }
          },
          "required": ["id", "title", "grid", "regions"]
        }
        JSON;

        return json_decode($jsonSchema, false, 512, JSON_THROW_ON_ERROR);
    }
}
