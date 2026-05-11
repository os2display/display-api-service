<?php

declare(strict_types=1);

namespace App\Command\Template;

use App\Exceptions\NotAcceptableException;
use App\Exceptions\NotFoundException;
use App\Repository\SlideRepository;
use App\Repository\TemplateRepository;
use App\Service\TemplateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:templates:remove',
    description: 'Remove a template',
)]
class TemplatesRemoveCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TemplateRepository $templateRepository,
        private readonly SlideRepository $slideRepository,
        private readonly TemplateService $templateService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('ulid', InputArgument::REQUIRED, 'The ulid of the template to remove');
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $ulid = $input->getArgument('ulid');

        if (!$ulid) {
            $io->error('No ulid supplied');

            return Command::INVALID;
        }

        try {
            $this->templateService->remove($ulid);
        } catch (NotFoundException|NotAcceptableException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success('Template removed.');

        return Command::SUCCESS;
    }
}
