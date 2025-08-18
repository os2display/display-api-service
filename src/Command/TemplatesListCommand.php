<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\TemplateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:templates:list',
    description: 'List templates',
)]
class TemplatesListCommand extends Command
{
    public function __construct(
        private readonly TemplateService $templateService,
    ) {
        parent::__construct();
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $templates = $this->templateService->getCoreTemplates();

            if (count($templates) === 0) {
                $io->error("No core templates found.");

                return Command::INVALID;
            }

            $customTemplates = $this->templateService->getCustomTemplates();

            $io->table(['ID', 'Title', 'Status', 'Type'], array_map(fn (array $templateArray) => [
                $templateArray['id'],
                $templateArray['title'],
                $templateArray['installed'] ? 'Installed' : 'Not Installed',
                $templateArray['type'],
            ], array_merge($templates, $customTemplates)));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::INVALID;
        }
    }

}
