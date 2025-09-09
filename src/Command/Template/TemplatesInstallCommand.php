<?php

declare(strict_types=1);

namespace App\Command\Template;

use App\Model\TemplateData;
use App\Service\TemplateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:templates:install',
    description: 'Install template(s)',
)]
class TemplatesInstallCommand extends Command
{
    public function __construct(
        private readonly TemplateService $templateService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Install all available templates');
        $this->addOption('update', 'u', InputOption::VALUE_NONE, 'Update already installed templates');
        $this->addArgument('templateUlid', InputArgument::OPTIONAL, 'Install the template with the given ULID');
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $all = $input->getOption('all');
        $update = $input->getOption('update');

        $templates = $this->templateService->getAllTemplates();

        if ($all) {
            foreach ($templates as $templateToInstall) {
                $this->templateService->installTemplate($templateToInstall, $update);
            }

            $io->success('Installed all available templates');

            return Command::SUCCESS;
        }

        $templateUlid = $input->getArgument('templateUlid');

        if (null === $templateUlid) {
            $io->warning('Template ULID not supplied.');

            return Command::INVALID;
        }

        $templatesFound = array_find($templates, fn (TemplateData $templateData): bool => $templateData->id === $templateUlid);

        if (1 !== count($templatesFound)) {
            $io->error('Template not found.');

            return Command::FAILURE;
        }

        $templateToInstall = $templatesFound[0];

        $this->templateService->installTemplate($templateToInstall);
        $io->success('Template '.$templateToInstall->title.' installed');

        return Command::SUCCESS;
    }
}
