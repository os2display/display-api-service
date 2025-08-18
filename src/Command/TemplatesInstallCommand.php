<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\TemplateData;
use App\Service\TemplateService;
use Doctrine\ORM\EntityManagerInterface;
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
        $this->addOption('all', 'a', InputOption::VALUE_NONE, "Install all available templates");
        $this->addArgument('templateUlid', InputArgument::OPTIONAL, "Install the template with the given ULID");
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $all = $input->getOption('all');

        $templates = $this->templateService->getAllTemplates();

        if ($all) {
            foreach ($templates as $templateToInstall) {
                $this->templateService->installTemplate($templateToInstall);
            }

            $io->success("Installed all available templates");
            return Command::SUCCESS;
        }

        $templateUlid = $input->getArgument('templateUlid');

        if (!$templateUlid) {
            $io->warning("Template ULID not supplied.");
            return Command::INVALID;
        }

        $templatesFound = array_find($templates, function (TemplateData $templateData) use ($templateUlid): bool {
            return $templateData->id === $templateUlid;
        });

        if (count($templatesFound) !== 1) {
            $io->error("Template not found.");
            return Command::FAILURE;
        }

        $templateToInstall = $templatesFound[0];

        $this->templateService->installTemplate($templateToInstall);
        $io->success("Template " .$templateToInstall->title . " installed");

        return Command::SUCCESS;
    }
}
