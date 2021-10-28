<?php

namespace App\Command;

use App\Entity\Template;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:template:load',
    description: 'Load a template from a json file',
)]
class LoadTemplateCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('filename', InputArgument::REQUIRED, 'json file to load');
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($filename = $input->getArgument('filename')) {
            try {
                $content = json_decode(file_get_contents($filename), false, 512, JSON_THROW_ON_ERROR);

                // Test that resources.admin and resources.component are set.
                if (!isset($content->resources->admin)) {
                    $io->info('Aborting - resources should contain an "admin" entry');
                    return Command::INVALID;
                }
                if (!isset($content->resources->component)) {
                    $io->info('Aborting - resources should contain a "component" entry');
                    return Command::INVALID;
                }

                $template = new Template();
                $template->setIcon($content->icon);
                // @TODO: Resource should be an object.
                $template->setResources(get_object_vars($content->resources));
                $template->setTitle($content->title);
                $template->setDescription($content->description);

                $this->entityManager->persist($template);
                $this->entityManager->flush();

                $id = $template->getId();
                $io->success("Template added with id: ${id}");
                return Command::SUCCESS;
            } catch (\JsonException $exception) {
                $io->info('Aborting - Invalid json');
                return Command::INVALID;
            }
        } else {
            $io->info('Aborting - No filename specified.');
            return Command::INVALID;
        }
    }
}
