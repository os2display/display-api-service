<?php

declare(strict_types=1);

namespace App\Command\Template;

use App\Service\TemplateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:templates:update',
    description: 'Update installed templates',
)]
class TemplatesUpdateCommand extends Command
{
    public function __construct(
        private readonly TemplateService $templateService,
    ) {
        parent::__construct();
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $templates = $this->templateService->getAllTemplates();

        foreach ($templates as $templateToUpdate) {
            $this->templateService->updateTemplate($templateToUpdate);
        }

        $io->success('Updated all installed templates');

        return Command::SUCCESS;
    }
}
