<?php

declare(strict_types=1);

namespace App\Command\Template;

use App\Enum\ResourceTypeEnum;
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
            $templates = $this->templateService->getTemplates();

            $coreTemplateCount = count(array_filter($templates, fn (TemplateData $template) => $template->type === ResourceTypeEnum::CORE));

            if (0 === $coreTemplateCount) {
                $io->error('No core templates found.');

                return Command::INVALID;
            }

            if ($status) {
                $numberOfTemplates = count($templates);
                $numberOfInstalledTemplates = count(array_filter($templates, fn ($entry): bool => $entry->installed));
                $text = $numberOfInstalledTemplates.' / '.$numberOfTemplates.' templates installed.';

                $io->success($text);
            } else {
                $io->table(['ID', 'Title', 'Status', 'Type'], array_map(fn (TemplateData $templateData) => [
                    $templateData->id,
                    $templateData->title,
                    $templateData->installed ? 'Installed' : 'Not Installed',
                    $templateData->type->value,
                ], $templates));
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::INVALID;
        }
    }
}
