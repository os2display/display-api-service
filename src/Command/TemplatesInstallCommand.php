<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Template;
use App\Service\TemplateService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AssignedGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Ulid;

#[AsCommand(
    name: 'app:templates:install',
    description: 'Install template(s)',
)]
class TemplatesInstallCommand extends Command
{
    public function __construct(
        private readonly TemplateService $templateService, private readonly EntityManagerInterface $entityManager,
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
            foreach ($templates as $templateArray) {
                if (!$templateArray['installed']) {
                    $this->templateService->installTemplate($templateArray);
                }
            }

            $io->success("Installed all available templates");
            return Command::SUCCESS;
        }

        $templateUlid = $input->getArgument('templateUlid');

        if (!$templateUlid) {
            $io->warning("Template ULID not supplied.");
            return Command::INVALID;
        }

        $templateToInstall = array_find($templates, function (array $template) use ($templateUlid): bool {
            return $template['id'] === $templateUlid;
        });

        if ($templateToInstall !== null) {
            if ($templateToInstall['installed']) {
                $io->warning("Template ULID already installed");
                return Command::INVALID;
            } else {
                $this->templateService->installTemplate($templateToInstall);
                $io->success("Template " .$templateToInstall['title'] . " installed");
            }
        } else {
            $io->warning("Template files not found.");
            return Command::INVALID;
        }

        return Command::SUCCESS;
    }

}
