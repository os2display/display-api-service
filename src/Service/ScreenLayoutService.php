<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ScreenLayout;
use App\Entity\ScreenLayoutRegions;
use App\Model\ScreenLayoutData;
use App\Repository\ScreenLayoutRegionsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AssignedGenerator;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Uid\Ulid;

class ScreenLayoutService
{
    public const string CORE_SCREEN_LAYOUTS_PATH = 'assets/shared/screen-layouts';
    public const string CUSTOM_SCREEN_LAYOUTS_PATH = 'assets/shared/custom-screen-layouts';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ScreenLayoutRegionsRepository $layoutRegionsRepository,
    ) {}

    public function getAllScreenLayouts(): array
    {
        return array_merge($this->getCoreScreenLayouts(), $this->getCustomScreenLayouts());
    }

    public function getCoreScreenLayouts(): array
    {
        $finder = new Finder();

        if (is_dir($this::CORE_SCREEN_LAYOUTS_PATH)) {
            $finder->files()->followLinks()->ignoreUnreadableDirs()->in($this::CORE_SCREEN_LAYOUTS_PATH)->depth('== 0')->name('*.json');

            if ($finder->hasResults()) {
                return $this->getScreenLayouts($finder);
            }
        }

        return [];
    }

    public function getCustomScreenLayouts(): array
    {
        $finder = new Finder();

        if (is_dir($this::CUSTOM_SCREEN_LAYOUTS_PATH)) {
            $finder->files()->followLinks()->ignoreUnreadableDirs()->in($this::CUSTOM_SCREEN_LAYOUTS_PATH)->depth('== 0')->name('*.json');

            if ($finder->hasResults()) {
                return $this->getScreenLayouts($finder, true);
            }
        }

        return [];
    }

    public function installScreenLayout(ScreenLayoutData $screenLayoutData, bool $update = false, bool $cleanupRegions = false): void
    {
        $screenLayout = $screenLayoutData->screenLayoutEntity;

        if (null === $screenLayout) {
            $screenLayout = new ScreenLayout();

            $metadata = $this->entityManager->getClassMetaData(ScreenLayout::class);
            $metadata->setIdGenerator(new AssignedGenerator());

            $ulid = Ulid::fromString($screenLayoutData->id);
            $screenLayout->setId($ulid);

            $this->entityManager->persist($screenLayout);
        }

        if ($update) {
            $screenLayout->setTitle($screenLayoutData->title);
        }

        $screenLayout->setGridColumns($screenLayoutData->gridColumns);
        $screenLayout->setGridRows($screenLayoutData->gridRows);

        $existingRegions = $screenLayout->getRegions();

        $processedRegionIds = [];

        foreach ($screenLayoutData->regions as $localRegion) {
            $region = $this->layoutRegionsRepository->findOneBy(['id' => Ulid::fromString($localRegion->id)]);

            if (!$region) {
                $region = new ScreenLayoutRegions();

                $metadata = $this->entityManager->getClassMetaData($region::class);
                $metadata->setIdGenerator(new AssignedGenerator());

                $ulid = Ulid::fromString($localRegion->id);

                $region->setId($ulid);

                $this->entityManager->persist($region);

                $screenLayout->addRegion($region);
            }

            $region->setGridArea($localRegion->gridArea);
            $region->setTitle($localRegion->title);

            if (isset($localRegion->type)) {
                $region->setType($localRegion->type);
            }

            $processedRegionIds[] = $region->getId();
        }

        if ($cleanupRegions) {
            foreach ($existingRegions as $existingRegion) {
                // Remove all regions that are not present in the json.
                if (!in_array($existingRegion->getId(), $processedRegionIds)) {
                    foreach ($existingRegion->getPlaylistScreenRegions() as $playlistScreenRegion) {
                        $this->entityManager->remove($playlistScreenRegion);
                    }

                    $this->entityManager->remove($existingRegion);
                }
            }
        }

        $this->entityManager->flush();
    }

    public function getScreenLayouts(iterable $finder, bool $custom = false): array
    {
        $screenLayouts = [];

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

            $repository = $this->entityManager->getRepository(ScreenLayout::class);
            $screenLayout = $repository->findOneBy(['id' => Ulid::fromString($content->id)]);

            $screenLayouts[] = new ScreenLayoutData(
                $content->id,
                $content->title,
                $custom ? 'Custom' : 'Core',
                $content->grid->rows,
                $content->grid->columns,
                $screenLayout,
                null !== $screenLayout,
                $content->regions,
            );
        }

        return $screenLayouts;
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
