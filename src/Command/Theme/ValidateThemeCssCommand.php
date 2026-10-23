<?php

declare(strict_types=1);

namespace App\Command\Theme;

use App\Repository\ThemeRepository;
use App\Validator\ThemeCss;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Lists themes whose css fails the checks run when a theme is saved.
 *
 * Such a theme cannot be saved, not even for a title change, until its css is
 * fixed. Run before upgrading (see UPGRADE.md).
 */
#[AsCommand(
    name: 'app:theme:validate-css',
    description: 'List themes whose css fails the checks run when a theme is saved',
)]
class ValidateThemeCssCommand extends Command
{
    public function __construct(
        private readonly ThemeRepository $themeRepository,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $rows = [];
        foreach ($this->themeRepository->findAll() as $theme) {
            foreach ($this->validator->validate($theme->getCssStyles(), new ThemeCss()) as $violation) {
                $rows[] = [$theme->getId(), $theme->getTitle(), $theme->getTenant()->getTitle(), $violation->getMessage()];
            }
        }

        if ([] === $rows) {
            $io->success('All themes pass the css checks.');

            return Command::SUCCESS;
        }

        $io->table(['ID', 'Title', 'Tenant', 'Problem'], $rows);
        $io->error(sprintf('%d theme(s) cannot be saved until their css is fixed.', \count($rows)));

        return Command::FAILURE;
    }
}
