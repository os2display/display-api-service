<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\TemplateData;
use App\Service\TemplateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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

    protected function configure(): void
    {
        $this->addOption('status', 's', InputOption::VALUE_NONE, 'Get status of installed templates.');
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $status = $input->getOption('status');

        try {
            $templates = $this->templateService->getCoreTemplates();

            if (0 === count($templates)) {
                $io->error('No core templates found.');

                return Command::INVALID;
            }

            $customTemplates = $this->templateService->getCustomTemplates();

            $allTemplates = array_merge($templates, $customTemplates);

            if ($status) {
                $numberOfTemplates = count($allTemplates);
                $numberOfInstallledTemplates = count(array_filter($allTemplates, fn ($entry) => $entry->installed));
                $text = $numberOfInstallledTemplates.' / '.$numberOfTemplates.' templates installed.';

                if ($numberOfInstallledTemplates === $numberOfTemplates) {
                    $io->success($text);
                } else {
                    $io->warning($text);

                    return Command::FAILURE;
                }
            } else {
                $io->table(['ID', 'Title', 'Status', 'Type'], array_map(fn (TemplateData $templateData) => [
                    $templateData->id,
                    $templateData->title,
                    $templateData->installed ? 'Installed' : 'Not Installed',
                    $templateData->type,
                ], $allTemplates));
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::INVALID;
        }
    }
}
