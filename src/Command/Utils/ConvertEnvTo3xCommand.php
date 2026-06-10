<?php

declare(strict_types=1);

namespace App\Command\Utils;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:utils:convert-env-to-3x',
    description: 'Converts the loaded 2.x configuration (env + admin/client config.json) to 3.x environment configuration',
)]
class ConvertEnvTo3xCommand extends Command
{
    public const string OUTPUT_SCREEN = 'screen';
    public const string OUTPUT_ENV = 'env';
    public const string OUTPUT_COMPOSE = 'compose';

    /**
     * Maps every environment variable read by the 2.x application (as loaded
     * in the running app, cf. .env) to its 3.x name. Most names are
     * unchanged; the renames follow the 3.x UPGRADE.md rename table.
     */
    public const array ENV_MAP = [
        // symfony/framework-bundle
        'APP_ENV' => 'APP_ENV',
        'APP_DEBUG' => 'APP_DEBUG',
        'APP_SECRET' => 'APP_SECRET',
        'TRUSTED_PROXIES' => 'TRUSTED_PROXIES',
        // doctrine/doctrine-bundle
        'DATABASE_URL' => 'DATABASE_URL',
        // nelmio/cors-bundle
        'CORS_ALLOW_ORIGIN' => 'CORS_ALLOW_ORIGIN',
        // App
        'APP_DEFAULT_DATE_FORMAT' => 'DEFAULT_DATE_FORMAT',
        'APP_ACTIVATION_CODE_EXPIRE_INTERNAL' => 'ACTIVATION_CODE_EXPIRE_INTERVAL',
        'APP_KEY_VAULT_SOURCE' => 'KEY_VAULT_SOURCE',
        'APP_KEY_VAULT_JSON' => 'KEY_VAULT_JSON',
        // lexik/jwt-authentication-bundle
        'JWT_SECRET_KEY' => 'JWT_SECRET_KEY',
        'JWT_PUBLIC_KEY' => 'JWT_PUBLIC_KEY',
        'JWT_PASSPHRASE' => 'JWT_PASSPHRASE',
        'JWT_TOKEN_TTL' => 'JWT_TOKEN_TTL',
        'JWT_SCREEN_TOKEN_TTL' => 'JWT_SCREEN_TOKEN_TTL',
        // gesdinet/jwt-refresh-token-bundle
        'JWT_REFRESH_TOKEN_TTL' => 'JWT_REFRESH_TOKEN_TTL',
        'JWT_SCREEN_REFRESH_TOKEN_TTL' => 'JWT_SCREEN_REFRESH_TOKEN_TTL',
        // itk-dev/openid-connect-bundle, internal provider
        'INTERNAL_OIDC_METADATA_URL' => 'INTERNAL_OIDC_METADATA_URL',
        'INTERNAL_OIDC_CLIENT_ID' => 'INTERNAL_OIDC_CLIENT_ID',
        'INTERNAL_OIDC_CLIENT_SECRET' => 'INTERNAL_OIDC_CLIENT_SECRET',
        'INTERNAL_OIDC_REDIRECT_URI' => 'INTERNAL_OIDC_REDIRECT_URI',
        'INTERNAL_OIDC_LEEWAY' => 'INTERNAL_OIDC_LEEWAY',
        'INTERNAL_OIDC_CLAIM_NAME' => 'INTERNAL_OIDC_CLAIM_NAME',
        'INTERNAL_OIDC_CLAIM_EMAIL' => 'INTERNAL_OIDC_CLAIM_EMAIL',
        'INTERNAL_OIDC_CLAIM_GROUPS' => 'INTERNAL_OIDC_CLAIM_GROUPS',
        // itk-dev/openid-connect-bundle, external provider
        'EXTERNAL_OIDC_METADATA_URL' => 'EXTERNAL_OIDC_METADATA_URL',
        'EXTERNAL_OIDC_CLIENT_ID' => 'EXTERNAL_OIDC_CLIENT_ID',
        'EXTERNAL_OIDC_CLIENT_SECRET' => 'EXTERNAL_OIDC_CLIENT_SECRET',
        'EXTERNAL_OIDC_REDIRECT_URI' => 'EXTERNAL_OIDC_REDIRECT_URI',
        'EXTERNAL_OIDC_LEEWAY' => 'EXTERNAL_OIDC_LEEWAY',
        'EXTERNAL_OIDC_HASH_SALT' => 'EXTERNAL_OIDC_HASH_SALT',
        'EXTERNAL_OIDC_CLAIM_ID' => 'EXTERNAL_OIDC_CLAIM_ID',
        'OIDC_CLI_REDIRECT' => 'OIDC_CLI_REDIRECT',
        // redis
        'REDIS_CACHE_PREFIX' => 'REDIS_CACHE_PREFIX',
        'REDIS_CACHE_DSN' => 'REDIS_CACHE_DSN',
        // Calendar Api Feed Source
        'CALENDAR_API_FEED_SOURCE_LOCATION_ENDPOINT' => 'CALENDAR_API_FEED_SOURCE_LOCATION_ENDPOINT',
        'CALENDAR_API_FEED_SOURCE_RESOURCE_ENDPOINT' => 'CALENDAR_API_FEED_SOURCE_RESOURCE_ENDPOINT',
        'CALENDAR_API_FEED_SOURCE_EVENT_ENDPOINT' => 'CALENDAR_API_FEED_SOURCE_EVENT_ENDPOINT',
        'CALENDAR_API_FEED_SOURCE_CUSTOM_MAPPINGS' => 'CALENDAR_API_FEED_SOURCE_CUSTOM_MAPPINGS',
        'CALENDAR_API_FEED_SOURCE_EVENT_MODIFIERS' => 'CALENDAR_API_FEED_SOURCE_EVENT_MODIFIERS',
        'CALENDAR_API_FEED_SOURCE_DATE_FORMAT' => 'CALENDAR_API_FEED_SOURCE_DATE_FORMAT',
        'CALENDAR_API_FEED_SOURCE_DATE_TIMEZONE' => 'CALENDAR_API_FEED_SOURCE_DATE_TIMEZONE',
        'CALENDAR_API_FEED_SOURCE_CACHE_EXPIRE_SECONDS' => 'CALENDAR_API_FEED_SOURCE_CACHE_EXPIRE_SECONDS',
        'EVENTDATABASE_API_V2_CACHE_EXPIRE_SECONDS' => 'EVENTDATABASE_API_V2_CACHE_EXPIRE_SECONDS',
        // Http Client
        'HTTP_CLIENT_TIMEOUT' => 'HTTP_CLIENT_TIMEOUT',
        'HTTP_CLIENT_MAX_DURATION' => 'HTTP_CLIENT_MAX_DURATION',
        'HTTP_CLIENT_LOG_LEVEL' => 'LOG_LEVEL_OUTBOUND_HTTP',
        // Screen info tracking
        'TRACK_SCREEN_INFO' => 'TRACK_SCREEN_INFO',
        'TRACK_SCREEN_INFO_UPDATE_INTERVAL_SECONDS' => 'TRACK_SCREEN_INFO_UPDATE_INTERVAL_SECONDS',
    ];

    /**
     * Variables present in the 2.x .env that are docker compose
     * orchestration config, not application config. They are deliberately
     * not part of ENV_MAP and are reported through the infrastructure
     * advisory instead.
     */
    public const array NON_APP_ENV = [
        'COMPOSE_PROJECT_NAME',
        'COMPOSE_DOMAIN',
    ];

    /**
     * Environment prefixes the Symfony application cannot read. Where each
     * belongs in a 3.x deployment (os2display-docker-server file layout).
     */
    private const array INFRA_PREFIXES = [
        'COMPOSE_' => 'compose orchestration — belongs in the docker host .env, never in the application env',
        'PHP_' => 'php-fpm container config — set on the phpfpm container (.env.php in os2display-docker-server)',
        'NGINX_' => 'nginx container config — set on the nginx container (.env.nginx in os2display-docker-server)',
        'MARIADB_' => 'database container config — set on the mariadb container (.env.mariadb in os2display-docker-server)',
        'MYSQL_' => 'database container config — set on the mariadb container (.env.mariadb in os2display-docker-server)',
    ];

    /**
     * PHP_*-named values that are CGI artefacts or php docker image build
     * constants — never operator configuration, so excluded from the
     * infrastructure advisory.
     */
    private const array INFRA_NOISE = [
        'PHP_SELF',
        'PHP_AUTH_USER',
        'PHP_AUTH_PW',
        'PHP_AUTH_DIGEST',
        'PHP_VERSION',
        'PHP_INI_DIR',
        'PHP_CFLAGS',
        'PHP_CPPFLAGS',
        'PHP_LDFLAGS',
        'PHP_SHA256',
        'PHP_URL',
        'PHP_ASC_URL',
        'PHP_EXTRA_CONFIGURE_ARGS',
        'PHP_EXTRA_BUILD_DEPS',
        'PHPIZE_DEPS',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output format (screen, env or compose)', self::OUTPUT_SCREEN)
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Write the result to this file instead of stdout (env and compose output only)')
            ->addOption('app-url', 'u', InputOption::VALUE_REQUIRED, 'Base URL of the 2.x installation, e.g. https://display.example.com. Inferred from the loaded environment when omitted.')
            ->addOption('skip-config-json', null, InputOption::VALUE_NONE, 'Do not fetch admin/config.json and client/config.json')
            ->setHelp(<<<'HELP'
            Converts the configuration of a running 2.x installation to 3.x environment
            configuration. The values <info>loaded</info> in the running application — whether they
            come from real environment variables, a docker compose environment block or
            .env/.env.local files — are treated as canonical, and every variable the 2.x
            application reads is written out under its 3.x name.

            Unless <comment>--skip-config-json</comment> is given, the command also fetches the canonical
            admin and client configuration from <comment><app-url>/admin/config.json</comment> and
            <comment><app-url>/client/config.json</comment> and converts them to the 3.x ADMIN_* and
            CLIENT_* variables. The base URL is inferred from the loaded OIDC redirect
            URIs (or COMPOSE_DOMAIN) and can be overridden with <comment>--app-url</comment>.
            HELP);
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        // Keep stdout clean when the document itself goes there, so the
        // result can be piped or redirected; notes and warnings go to stderr.
        $errIo = $io->getErrorStyle();

        $format = $input->getOption('output');
        if (!in_array($format, [self::OUTPUT_SCREEN, self::OUTPUT_ENV, self::OUTPUT_COMPOSE], true)) {
            $errIo->error(sprintf('Invalid output format "%s". Valid formats: %s.', $format, implode(', ', [self::OUTPUT_SCREEN, self::OUTPUT_ENV, self::OUTPUT_COMPOSE])));

            return Command::INVALID;
        }

        $file = $input->getOption('file');
        if (null !== $file && self::OUTPUT_SCREEN === $format) {
            $errIo->error('--file requires --output=env or --output=compose.');

            return Command::INVALID;
        }

        $sections = [
            [
                'title' => 'Converted from the loaded 2.x environment',
                'lines' => $this->convertLoadedEnv(),
            ],
        ];

        if (!$input->getOption('skip-config-json')) {
            $appUrl = $input->getOption('app-url');
            $baseUrl = is_string($appUrl) && '' !== $appUrl ? rtrim($appUrl, '/') : $this->inferAppUrl();

            if (null === $baseUrl) {
                $errIo->warning('Could not infer the 2.x app URL from the loaded environment. Pass --app-url=https://... to include the admin/client config.json conversion, or --skip-config-json to silence this warning.');
            } else {
                foreach (['admin', 'client'] as $type) {
                    $url = sprintf('%s/%s/config.json', $baseUrl, $type);
                    $config = $this->fetchConfig($url, $errIo);
                    if (null !== $config) {
                        $sections[] = [
                            'title' => sprintf('%s configuration (from %s)', ucfirst($type), $url),
                            'lines' => 'admin' === $type ? $this->convertAdminConfig($config) : $this->convertClientConfig($config),
                        ];
                    }
                }
            }
        }

        $infra = $this->collectInfraEnv();
        $document = self::OUTPUT_COMPOSE === $format
            ? $this->renderCompose($sections, $infra)
            : $this->renderEnv($sections, $infra);

        if (null !== $file) {
            if (false === file_put_contents($file, $document)) {
                $errIo->error(sprintf('Could not write to "%s".', $file));

                return Command::FAILURE;
            }
            $errIo->success(sprintf('Wrote %s configuration to %s. Review the result before use.', $format, $file));

            return Command::SUCCESS;
        }

        if (self::OUTPUT_SCREEN === $format) {
            $io->title('2.x configuration converted to 3.x environment configuration');
            $io->writeln($document);
            $io->note('Review the result, then save it with --output=env --file=<path> (or --output=compose) and use it as the starting point for the 3.x .env.local.');

            return Command::SUCCESS;
        }

        $output->writeln($document);

        return Command::SUCCESS;
    }

    /**
     * Converts every loaded 2.x application variable to its 3.x name.
     *
     * @return list<array{string, string}>
     */
    private function convertLoadedEnv(): array
    {
        $lines = [];

        foreach (self::ENV_MAP as $name2x => $name3x) {
            $value = $this->lookupEnv($name2x);
            if (null !== $value) {
                $lines[] = [$name3x, $value];
            }
        }

        return $lines;
    }

    /**
     * Looks up a loaded value the way Symfony's env processor does:
     * $_ENV first, then $_SERVER, then the process environment.
     */
    private function lookupEnv(string $name): ?string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        return is_string($value) ? $value : null;
    }

    /**
     * Infers the public base URL of the 2.x installation from the loaded
     * environment. The OIDC redirect URIs point at the public domain on
     * a configured installation; COMPOSE_DOMAIN covers itk-dev
     * development setups.
     */
    private function inferAppUrl(): ?string
    {
        foreach (['INTERNAL_OIDC_REDIRECT_URI', 'EXTERNAL_OIDC_REDIRECT_URI', 'OIDC_CLI_REDIRECT'] as $name) {
            $value = $this->lookupEnv($name);
            if (null === $value) {
                continue;
            }

            $parts = parse_url($value);
            if (!is_array($parts)) {
                continue;
            }

            $scheme = $parts['scheme'] ?? '';
            $host = $parts['host'] ?? '';
            if (in_array($scheme, ['http', 'https'], true) && '' !== $host) {
                $url = $scheme.'://'.$host;
                if (isset($parts['port'])) {
                    $url .= ':'.$parts['port'];
                }

                return $url;
            }
        }

        $domain = $this->lookupEnv('COMPOSE_DOMAIN');
        if (null !== $domain && '' !== $domain) {
            return 'https://'.$domain;
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchConfig(string $url, SymfonyStyle $errIo): ?array
    {
        try {
            return $this->httpClient->request('GET', $url)->toArray();
        } catch (\Throwable $throwable) {
            $errIo->warning(sprintf('Could not fetch %s (%s). The corresponding section is omitted — pass --app-url to point at the public 2.x URL, or use app:utils:convert-config-json-to-env in 3.x with a config.json file.', $url, $throwable->getMessage()));

            return null;
        }
    }

    /**
     * Converts a 2.x admin config.json to the 3.x ADMIN_* variables.
     *
     * The 2.x file is generated by confd, which renders booleans and null
     * as the strings "true"/"false"/"null" — both the JSON and string
     * representations are accepted here.
     *
     * @param array<string, mixed> $config
     *
     * @return list<array{string, string}>
     */
    private function convertAdminConfig(array $config): array
    {
        $lines = [];

        $rejseplanenApiKey = $config['rejseplanenApiKey'] ?? null;
        if (!$this->isNullish($rejseplanenApiKey) && is_scalar($rejseplanenApiKey)) {
            $lines[] = ['ADMIN_REJSEPLANEN_APIKEY', (string) $rejseplanenApiKey];
        }

        $showScreenStatus = $this->toBool($config['showScreenStatus'] ?? null);
        if (null !== $showScreenStatus) {
            $lines[] = ['ADMIN_SHOW_SCREEN_STATUS', $showScreenStatus ? 'true' : 'false'];
        }

        $touchButtonRegions = $this->toBool($config['touchButtonRegions'] ?? null);
        if (null !== $touchButtonRegions) {
            $lines[] = ['ADMIN_TOUCH_BUTTON_REGIONS', $touchButtonRegions ? 'true' : 'false'];
        }

        $loginMethods = $config['loginMethods'] ?? null;
        if (is_array($loginMethods)) {
            $converted = [];
            foreach ($loginMethods as $method) {
                if (!is_array($method)) {
                    continue;
                }
                // In 2.x a method with enabled=false is hidden; in 3.x
                // presence in ADMIN_LOGIN_METHODS means enabled, so disabled
                // methods are dropped and the obsolete key stripped.
                if (array_key_exists('enabled', $method) && false === $this->toBool($method['enabled'])) {
                    continue;
                }
                unset($method['enabled']);
                $converted[] = $method;
            }
            $lines[] = ['ADMIN_LOGIN_METHODS', json_encode($converted, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)];
        }

        // 2.x configured a preview client URL; in 3.x this is a boolean
        // toggle for the built-in preview.
        if (!$this->isNullish($config['previewClient'] ?? null)) {
            $lines[] = ['ADMIN_ENHANCED_PREVIEW', 'true'];
        }

        return $lines;
    }

    /**
     * Converts a 2.x client config.json to the 3.x CLIENT_* variables.
     *
     * @param array<string, mixed> $config
     *
     * @return list<array{string, string}>
     */
    private function convertClientConfig(array $config): array
    {
        $lines = [];

        $scalars = [
            'loginCheckTimeout' => 'CLIENT_LOGIN_CHECK_TIMEOUT',
            'refreshTokenTimeout' => 'CLIENT_REFRESH_TOKEN_TIMEOUT',
            'releaseTimestampIntervalTimeout' => 'CLIENT_RELEASE_TIMESTAMP_INTERVAL_TIMEOUT',
            'schedulingInterval' => 'CLIENT_SCHEDULING_INTERVAL',
        ];
        foreach ($scalars as $key => $name3x) {
            $value = $config[$key] ?? null;
            if (!$this->isNullish($value) && is_scalar($value)) {
                $lines[] = [$name3x, (string) $value];
            }
        }

        $dataStrategy = $config['dataStrategy'] ?? null;
        $dataStrategyConfig = is_array($dataStrategy) ? ($dataStrategy['config'] ?? null) : null;
        $pullInterval = is_array($dataStrategyConfig) ? ($dataStrategyConfig['interval'] ?? null) : null;
        if (!$this->isNullish($pullInterval) && is_scalar($pullInterval)) {
            $lines[] = ['CLIENT_PULL_STRATEGY_INTERVAL', (string) $pullInterval];
        }

        $colorScheme = $config['colorScheme'] ?? null;
        if (is_array($colorScheme)) {
            $lines[] = ['CLIENT_COLOR_SCHEME', json_encode($colorScheme, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)];
        }

        $debug = $this->toBool($config['debug'] ?? null);
        if (null !== $debug) {
            $lines[] = ['CLIENT_DEBUG', $debug ? 'true' : 'false'];
        }

        return $lines;
    }

    /**
     * Collects loaded variables the Symfony application cannot read
     * (container/orchestration config), keyed by name, with the advice on
     * where they belong in a 3.x deployment.
     *
     * @return array<string, array{string, string}> name => [value, advice]
     */
    private function collectInfraEnv(): array
    {
        $found = [];

        // Real process env (compose environment blocks land here) merged
        // with the dotenv-loaded vars in $_ENV.
        $candidates = array_merge(getenv(), $_ENV);

        /** @var mixed $value */
        foreach ($candidates as $name => $value) {
            if (!is_string($value) || in_array($name, self::INFRA_NOISE, true)) {
                continue;
            }
            foreach (self::INFRA_PREFIXES as $prefix => $advice) {
                if (str_starts_with($name, $prefix)) {
                    $found[$name] = [$value, $advice];
                    break;
                }
            }
        }

        ksort($found);

        return $found;
    }

    /**
     * @param list<array{title: string, lines: list<array{string, string}>}> $sections
     * @param array<string, array{string, string}> $infra
     */
    private function renderEnv(array $sections, array $infra): string
    {
        $out = [];

        foreach ($sections as $section) {
            $out[] = sprintf('###> %s ###', $section['title']);
            foreach ($section['lines'] as [$name, $value]) {
                $out[] = $name.'='.$this->quoteEnvValue($value);
            }
            $out[] = sprintf('###< %s ###', $section['title']);
            $out[] = '';
        }

        foreach ($this->renderInfraAdvisory($infra) as $line) {
            $out[] = '' === $line ? '' : '# '.$line;
        }

        return rtrim(implode(PHP_EOL, $out)).PHP_EOL;
    }

    /**
     * @param list<array{title: string, lines: list<array{string, string}>}> $sections
     * @param array<string, array{string, string}> $infra
     */
    private function renderCompose(array $sections, array $infra): string
    {
        $out = [];
        $out[] = '# Merge into the api service in your compose file.';
        $out[] = 'environment:';

        foreach ($sections as $section) {
            $out[] = '  # '.$section['title'];
            foreach ($section['lines'] as [$name, $value]) {
                // YAML single-quoted scalar; embedded single quotes are
                // escaped by doubling.
                $out[] = sprintf("  - '%s=%s'", $name, str_replace("'", "''", $value));
            }
        }

        $out[] = '';
        foreach ($this->renderInfraAdvisory($infra) as $line) {
            $out[] = '' === $line ? '' : '# '.$line;
        }

        return rtrim(implode(PHP_EOL, $out)).PHP_EOL;
    }

    /**
     * @param array<string, array{string, string}> $infra
     *
     * @return list<string>
     */
    private function renderInfraAdvisory(array $infra): array
    {
        if ([] === $infra) {
            return [];
        }

        $lines = [
            'The following loaded variables are NOT read by the Symfony application',
            'and must not go in the application env. They configure the container',
            'stack — put them where noted:',
        ];

        $grouped = [];
        foreach ($infra as $name => [$value, $advice]) {
            $grouped[$advice][] = sprintf('%s=%s', $name, $value);
        }

        foreach ($grouped as $advice => $vars) {
            $lines[] = '';
            $lines[] = $advice.':';
            foreach ($vars as $var) {
                $lines[] = '    '.$var;
            }
        }

        return $lines;
    }

    /**
     * Quotes a value for use in a dotenv file when needed.
     */
    private function quoteEnvValue(string $value): string
    {
        if (1 === preg_match('#^[A-Za-z0-9_./:%@+,=~^?&-]*$#', $value)) {
            return $value;
        }

        if (!str_contains($value, "'")) {
            return "'".$value."'";
        }

        return '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
    }

    private function isNullish(mixed $value): bool
    {
        return null === $value || '' === $value || 'null' === $value;
    }

    /**
     * Interprets JSON and confd-rendered ("true"/"false") booleans.
     */
    private function toBool(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return null;
    }
}
