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

        $content = file_get_contents($input->getArgument('filepath'));

        $config = json_decode($content, true);

        if ('admin' === $type) {
            $io->info('Insert the following lines in .env.local:');

            $rejseplanenApiKey = $config['rejseplanenApiKey'] ?? '';
            $showScreenStatus = var_export($config['showScreenStatus'] ?? false, true);
            $touchButtonRegions = var_export($config['touchButtonRegions'] ?? false, true);
            $enhancedPreview = var_export(!empty($config['previewClient']), true);
            $loginMethods = $config['loginMethods'] ?? [];

            // Remove enabled field since this is unused in v3.
            foreach ($loginMethods as &$method) {
                unset($method['enabled']);
            }

            $env = "###> Admin configuration ###\n";
            $env .= 'ADMIN_REJSEPLANEN_APIKEY="'.$rejseplanenApiKey."\"\n";
            $env .= 'ADMIN_SHOW_SCREEN_STATUS='.$showScreenStatus."\n";
            $env .= 'ADMIN_TOUCH_BUTTON_REGIONS='.$touchButtonRegions."\n";
            $env .= "ADMIN_LOGIN_METHODS='".json_encode($loginMethods)."'\n";
            $env .= 'ADMIN_ENHANCED_PREVIEW='.$enhancedPreview."\n";
            $env .= "###< Admin configuration ###\n";

            $output->writeln($env);
        } elseif ('client' === $type) {
            $env = "Insert the following lines in .env.local:\n\n\n";

            $loginCheckTimeout = $config['loginCheckTimeout'] ?? 20000;
            $refreshTokenTimeout = $config['refreshTokenTimeout'] ?? 300000;
            $releaseTimestampIntervalTimeout = $config['releaseTimestampIntervalTimeout'] ?? 600000;
            $schedulingInterval = $config['schedulingInterval'] ?? 60000;
            $pullStrategyInterval = $config['dataStrategy']['config']['interval'] ?? 90000;
            $debug = var_export($config['debug'] ?? false, true);

            $colorScheme = $config['colorScheme'] ?? null;
            $colorSchemeValue = null !== $colorScheme ? "'".json_encode($colorScheme)."'" : '';

            $env .= "###> Client configuration ###\n";
            $env .= 'CLIENT_LOGIN_CHECK_TIMEOUT='.$loginCheckTimeout."\n";
            $env .= 'CLIENT_REFRESH_TOKEN_TIMEOUT='.$refreshTokenTimeout."\n";
            $env .= 'CLIENT_RELEASE_TIMESTAMP_INTERVAL_TIMEOUT='.$releaseTimestampIntervalTimeout."\n";
            $env .= 'CLIENT_SCHEDULING_INTERVAL='.$schedulingInterval."\n";
            $env .= 'CLIENT_PULL_STRATEGY_INTERVAL='.$pullStrategyInterval."\n";
            $env .= 'CLIENT_COLOR_SCHEME='.$colorSchemeValue."\n";
            $env .= 'CLIENT_DEBUG='.$debug."\n";
            $env .= "###< Client configuration ###\n";

            $output->writeln($env);
        }

        return Command::SUCCESS;
    }
}
