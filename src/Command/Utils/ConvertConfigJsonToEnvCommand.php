<?php

declare(strict_types=1);

namespace App\Command\Utils;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:utils:convert-config-json-to-env',
    description: 'Converts a config json (admin/client) from 2.x to .env variables used in 3.x',
    description: 'Converts a config json file (admin/client) from 2.x to .env variables used in 3.x',
)]
class ConvertConfigJsonToEnvCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('filepath', InputArgument::REQUIRED, 'Path to the file to convert');
        $this->addArgument('filepath', InputArgument::REQUIRED, 'Path to the file or URL to convert');
        $this->addOption('type', 't', InputOption::VALUE_REQUIRED, 'Type of the config (admin or client).', null, ['admin', 'client']);
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $type = $input->getOption('type');

        if (!in_array($type, ['admin', 'client'])) {
            $io->error('Invalid type');

            return Command::INVALID;
        }

        try {
            $content = file_get_contents($input->getArgument('filepath'));

            if (!$content) {
                throw new \Exception('Error reading file');
            }

            $config = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception|\JsonException $e) {
            $io->error($e->getMessage());

            return Command::INVALID;
        }

        if ('admin' === $type) {
            $io->success('Insert the following lines in .env.local');

            $rejseplanenApiKey = $config['rejseplanenApiKey'] ?? null;
            $showScreenStatus = $config['showScreenStatus'] ?? null;
            $touchButtonRegions = $config['touchButtonRegions'] ?? null;
            $enhancedPreview = $config['previewClient'] ?? null;
            $loginMethods = $config['loginMethods'] ?? null;

            if (null !== $loginMethods) {
                // Remove enabled field since this is unused in v3.
                foreach ($loginMethods as &$method) {
                    unset($method['enabled']);
                }
            }

            $io->writeln('###> Admin configuration ###');
            null !== $rejseplanenApiKey && $io->writeln('ADMIN_REJSEPLANEN_APIKEY="'.$rejseplanenApiKey.'"');
            null !== $showScreenStatus && $io->writeln('ADMIN_SHOW_SCREEN_STATUS='.var_export($showScreenStatus, true));
            null !== $touchButtonRegions && $io->writeln('ADMIN_TOUCH_BUTTON_REGIONS='.var_export($touchButtonRegions, true));
            null !== $loginMethods && $io->writeln("ADMIN_LOGIN_METHODS='".json_encode($loginMethods)."'");
            // This is a conversion from an url to boolean value. If the url is not empty, it is interpreted as true.
            !empty($enhancedPreview) && $io->writeln('ADMIN_ENHANCED_PREVIEW=true');
            $io->writeln('###< Admin configuration ###');
        } elseif ('client' === $type) {
            $io->success('Insert the following lines in .env.local');

            $loginCheckTimeout = $config['loginCheckTimeout'] ?? null;
            $refreshTokenTimeout = $config['refreshTokenTimeout'] ?? null;
            $releaseTimestampIntervalTimeout = $config['releaseTimestampIntervalTimeout'] ?? null;
            $schedulingInterval = $config['schedulingInterval'] ?? null;
            $pullStrategyInterval = $config['dataStrategy']['config']['interval'] ?? null;
            $debug = $config['debug'] ?? null;

            $colorScheme = $config['colorScheme'] ?? null;
            $colorSchemeValue = null !== $colorScheme ? "'".json_encode($colorScheme)."'" : null;

            $io->writeln('###> Client configuration ###');
            null !== $loginCheckTimeout && $io->writeln('CLIENT_LOGIN_CHECK_TIMEOUT='.$loginCheckTimeout);
            null !== $refreshTokenTimeout && $io->writeln('CLIENT_REFRESH_TOKEN_TIMEOUT='.$refreshTokenTimeout);
            null !== $releaseTimestampIntervalTimeout && $io->writeln('CLIENT_RELEASE_TIMESTAMP_INTERVAL_TIMEOUT='.$releaseTimestampIntervalTimeout);
            null !== $schedulingInterval && $io->writeln('CLIENT_SCHEDULING_INTERVAL='.$schedulingInterval);
            null !== $pullStrategyInterval && $io->writeln('CLIENT_PULL_STRATEGY_INTERVAL='.$pullStrategyInterval);
            null !== $colorSchemeValue && $io->writeln('CLIENT_COLOR_SCHEME='.$colorSchemeValue);
            null !== $debug && $io->writeln('CLIENT_DEBUG=true');
            $io->writeln('###< Client configuration ###');
        }

        return Command::SUCCESS;
    }
}
