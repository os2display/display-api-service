<?php

declare(strict_types=1);

namespace App\Command\Template;

use App\Repository\ScreenLayoutRepository;
use App\Repository\ScreenRepository;
use App\Repository\SlideRepository;
use App\Repository\TemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Ulid;

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
            $io->error("No ulid supplied");
            return Command::INVALID;
        }

        $template = $this->templateRepository->findOneBy(['id' => Ulid::fromString($ulid)]);

        if (!$template) {
            $io->error('Template not installed. Aborting.');

            return self::INVALID;
        }

        $slides = $this->slideRepository->findBy(['template' => $template]);
        $numberOfSlides = count($slides);

        if ($numberOfSlides > 0) {
            $message = "Aborting. Template is bound to $numberOfSlides following slides:\n\n";

            foreach ($slides as $slide) {
                $id = $slide->getId();
                $message .= "$id\n";
            }

            $io->error($message);

            return self::INVALID;
        }

        $this->entityManager->remove($template);

        $this->entityManager->flush();

        $io->success('Template removed.');

        return Command::SUCCESS;
    }
}
