<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Template;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AssignedGenerator;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Ulid;

#[AsCommand(
    name: 'app:template:load',
    description: 'Load a template from a json file',
)]
class LoadTemplateCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('filename', InputArgument::REQUIRED, 'json file to load. Can be a local file or a URL');
        $this->addOption('path-from-filename', 'p', InputOption::VALUE_NONE, 'Set path to component and admin from filename. Assumes that the config file loaded has the naming format: [templateName]-config[.*].json.', null);
        $this->addOption('timestamp', 't', InputOption::VALUE_NONE, 'Add a timestamp to the component and admin urls: ?ts=. Only applies if path-from-filename option is active.', null);
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $successMessage = 'Template updated';

        try {
            /** @var string $filename */
            $filename = $input->getArgument('filename');

            $content = json_decode(file_get_contents($filename), false, 512, JSON_THROW_ON_ERROR);

            // Validate template json.
            $schemaStorage = new SchemaStorage();
            $jsonSchemaObject = $this->getSchema();
            $schemaStorage->addSchema('file://contentSchema', $jsonSchemaObject);
            $validator = new Validator(new Factory($schemaStorage));
            $validator->validate($content, $jsonSchemaObject);

            if ($validator->isValid()) {
                $io->info('The supplied JSON validates against the schema.');
            } else {
                $message = "JSON does not validate. Violations:\n";
                foreach ($validator->getErrors() as $error) {
                    $message .= sprintf("\n[%s] %s", $error['property'], $error['message']);
                }

                $io->error($message);

                return Command::INVALID;
            }

            if (!Ulid::isValid($content->id)) {
                $io->error('The Ulid is not valid');

                return Command::INVALID;
            }

            $repository = $this->entityManager->getRepository(Template::class);
            $template = $repository->findOneBy(['id' => Ulid::fromString($content->id)]);

            if (!$template) {
                $template = new Template();
                $metadata = $this->entityManager->getClassMetaData($template::class);
                $metadata->setIdGenerator(new AssignedGenerator());

                $ulid = Ulid::fromString($content->id);

                $template->setId($ulid);

                $this->entityManager->persist($template);
                $successMessage = 'Template added';
            }

            $template->setIcon($content->icon);

            $resources = get_object_vars($content->resources);

            if ($input->getOption('path-from-filename')) {
                // Set paths to component and admin from filename.
                $resources['component'] = preg_replace("/-config.*\.json$/", '.js', $filename);
                $resources['admin'] = preg_replace("/-config.*\.json$/", '-admin.json', $filename);

                if ($input->getOption('timestamp')) {
                    $resources['component'] = $resources['component'].'?ts='.time();
                    $resources['admin'] = $resources['admin'].'?ts='.time();
                }
            }

            $template->setResources($resources);
            $template->setTitle($content->title);
            $template->setDescription($content->description);

            $this->entityManager->flush();

            $io->success($successMessage);

            return Command::SUCCESS;
        } catch (\JsonException) {
            $io->error('Invalid json');

            return Command::INVALID;
        }
    }

    /**
     * Supplies json schema for validation.
     *
     * @return mixed
     *   Json schema
     *
     * @throws \JsonException
     */
    private function getSchema(): mixed
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
            "description": {
              "description": "A description of the template",
              "type": "string"
            },
            "icon": {
              "description": "An icon for the template",
              "type": "string"
            },
            "resources": {
              "type": "object",
              "properties": {
                "schema": {
                  "description": "Path to the json schema for the content",
                  "type": "string"
                },
                "component": {
                  "description": "Path to the react remote component that renders the content",
                  "type": "string"
                },
                "admin": {
                  "description": "Path to the json array describing the content form in the administration interface",
                  "type": "string"
                },
                "assets": {
                  "description": "Assets that are needed by the template",
                  "type": "array",
                  "items": {
                    "type": "object",
                    "description": "Asset item",
                    "properties": {
                      "type": {
                        "type": "string",
                        "url": "string"
                      }
                    }
                  }
                },
                "options": {
                  "description": "Default option values for the template",
                  "type": "object"
                },
                "content": {
                  "description": "Default content values for the template",
                  "type": "object"
                }
              },
              "required": ["schema", "component", "admin", "assets", "options", "content"]
            }
          },
          "required": ["id", "icon", "description", "resources", "title"]
        }
        JSON;

        return json_decode($jsonSchema, false, 512, JSON_THROW_ON_ERROR);
    }
}
