<?php

namespace App\Command;

use App\Repository\ScreenLayoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Ulid;

#[AsCommand(
    name: 'app:screen-layouts:remove',
    description: 'Remove a screen layouts',
)]
class RemoveScreenLayoutCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ScreenLayoutRepository $screenLayoutRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('filename', InputArgument::REQUIRED, 'json file to load. Can be a local file or a URL');
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $filename = $input->getArgument('filename');
            $content = json_decode(file_get_contents($filename), false, 512, JSON_THROW_ON_ERROR);

            if (isset($content->id) && Ulid::isValid($content->id)) {
                $screenLayout = $this->screenLayoutRepository->findOneBy(['id' => Ulid::fromString($content->id)]);

                if (!$screenLayout) {
                    $io->error('Screen layout not installed. Aborting.');

                    return self::INVALID;
                }

                foreach ($screenLayout->getRegions() as $region) {
                    $this->entityManager->remove($region);
                }

                $this->entityManager->remove($screenLayout);
            } else {
                $io->error('The screen layout should have an id (ulid)');

                return Command::INVALID;
            }

            $this->entityManager->flush();

            $io->success('Screen layout removed');

            return Command::SUCCESS;
        } catch (\JsonException $exception) {
            $io->error('Invalid json');

            return Command::INVALID;
        }
    }
}
