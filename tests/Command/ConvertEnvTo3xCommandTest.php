<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\Utils\ConvertEnvTo3xCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ConvertEnvTo3xCommandTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $envBackup = [];

    protected function tearDown(): void
    {
        foreach ($this->envBackup as $name => $original) {
            if (false === $original) {
                unset($_ENV[$name]);
            } else {
                $_ENV[$name] = $original;
            }
        }
        $this->envBackup = [];

        parent::tearDown();
    }

    public function testEveryEnvVarInDotEnvIsCovered(): void
    {
        $dotEnv = file_get_contents(dirname(__DIR__, 2).'/.env');
        $this->assertNotFalse($dotEnv);

        $this->assertSame(1, preg_match_all('/^([A-Z][A-Z0-9_]*)=/m', $dotEnv, $matches) >= 1 ? 1 : 0);

        $covered = array_merge(array_keys(ConvertEnvTo3xCommand::ENV_MAP), ConvertEnvTo3xCommand::NON_APP_ENV);
        foreach ($matches[1] as $name) {
            $this->assertContains($name, $covered, sprintf('Env var "%s" from .env is not covered by %s::ENV_MAP — add it with its 3.x name (or to NON_APP_ENV if it is not application config).', $name, ConvertEnvTo3xCommand::class));
        }
    }

    public function testRenamesLoadedEnvVarsTo3xNames(): void
    {
        $this->setEnv('APP_ENV', 'prod');
        $this->setEnv('APP_DEFAULT_DATE_FORMAT', 'Y-m-d');
        $this->setEnv('APP_ACTIVATION_CODE_EXPIRE_INTERNAL', 'P2D');
        $this->setEnv('APP_KEY_VAULT_SOURCE', 'ENVIRONMENT');
        $this->setEnv('APP_KEY_VAULT_JSON', '{}');
        $this->setEnv('HTTP_CLIENT_LOG_LEVEL', 'error');
        $this->setEnv('DATABASE_URL', 'mysql://db:db@mariadb:3306/db?serverVersion=10.11.5-MariaDB');

        $tester = $this->createCommandTester(new MockHttpClient());
        $exitCode = $tester->execute(['--output' => 'env', '--skip-config-json' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $display = $tester->getDisplay();

        $this->assertStringContainsString('APP_ENV=prod', $display);
        $this->assertStringContainsString('DEFAULT_DATE_FORMAT=Y-m-d', $display);
        $this->assertStringContainsString('ACTIVATION_CODE_EXPIRE_INTERVAL=P2D', $display);
        $this->assertStringContainsString('KEY_VAULT_SOURCE=ENVIRONMENT', $display);
        $this->assertStringContainsString('KEY_VAULT_JSON=', $display);
        $this->assertStringContainsString('LOG_LEVEL_OUTBOUND_HTTP=error', $display);
        $this->assertStringContainsString('DATABASE_URL=mysql://db:db@mariadb:3306/db?serverVersion=10.11.5-MariaDB', $display);

        $this->assertStringNotContainsString('APP_DEFAULT_DATE_FORMAT=', $display);
        $this->assertStringNotContainsString('APP_ACTIVATION_CODE_EXPIRE_INTERNAL=', $display);
        $this->assertStringNotContainsString('HTTP_CLIENT_LOG_LEVEL=', $display);
    }

    public function testConvertsAdminAndClientConfigJson(): void
    {
        $requestedUrls = [];
        $client = new MockHttpClient(function (string $method, string $url) use (&$requestedUrls): MockResponse {
            $requestedUrls[] = $url;

            // The bodies mirror what confd renders in 2.x: booleans and
            // null as strings.
            if (str_contains($url, '/admin/config.json')) {
                return new MockResponse((string) json_encode([
                    'api' => '/',
                    'touchButtonRegions' => 'true',
                    'previewClient' => 'https://preview.example.com',
                    'showScreenStatus' => 'false',
                    'rejseplanenApiKey' => 'null',
                    'loginMethods' => [
                        ['type' => 'oidc', 'enabled' => 'true', 'provider' => 'internal', 'label' => null, 'icon' => 'faCity'],
                        ['type' => 'oidc', 'enabled' => 'false', 'provider' => 'external', 'label' => '', 'icon' => 'mitID'],
                        ['type' => 'username-password', 'enabled' => 'true', 'provider' => 'username-password', 'label' => null],
                    ],
                ]), ['response_headers' => ['content-type' => 'application/json']]);
            }

            return new MockResponse((string) json_encode([
                'apiEndpoint' => '/',
                'loginCheckTimeout' => 20000,
                'configFetchInterval' => 600000,
                'refreshTokenTimeout' => 60000,
                'releaseTimestampIntervalTimeout' => 600000,
                'dataStrategy' => ['type' => 'pull', 'config' => ['interval' => 30000]],
                'colorScheme' => ['type' => 'library', 'lat' => 56.0, 'lng' => 10.0],
                'schedulingInterval' => 60000,
                'debug' => false,
            ]), ['response_headers' => ['content-type' => 'application/json']]);
        });

        $tester = $this->createCommandTester($client);
        $exitCode = $tester->execute(['--output' => 'env', '--app-url' => 'https://display.example.com']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame([
            'https://display.example.com/admin/config.json',
            'https://display.example.com/client/config.json',
        ], $requestedUrls);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('ADMIN_TOUCH_BUTTON_REGIONS=true', $display);
        $this->assertStringContainsString('ADMIN_SHOW_SCREEN_STATUS=false', $display);
        $this->assertStringContainsString('ADMIN_ENHANCED_PREVIEW=true', $display);
        // "null" means not configured.
        $this->assertStringNotContainsString('ADMIN_REJSEPLANEN_APIKEY', $display);
        // The disabled external method is dropped, and the obsolete
        // "enabled" key stripped from the remaining methods.
        $this->assertStringContainsString('ADMIN_LOGIN_METHODS=', $display);
        $this->assertStringNotContainsString('"provider":"external"', $display);
        $this->assertStringNotContainsString('"enabled"', $display);
        $this->assertStringContainsString('"provider":"internal"', $display);
        $this->assertStringContainsString('"provider":"username-password"', $display);

        $this->assertStringContainsString('CLIENT_LOGIN_CHECK_TIMEOUT=20000', $display);
        $this->assertStringContainsString('CLIENT_REFRESH_TOKEN_TIMEOUT=60000', $display);
        $this->assertStringContainsString('CLIENT_RELEASE_TIMESTAMP_INTERVAL_TIMEOUT=600000', $display);
        $this->assertStringContainsString('CLIENT_SCHEDULING_INTERVAL=60000', $display);
        $this->assertStringContainsString('CLIENT_PULL_STRATEGY_INTERVAL=30000', $display);
        $this->assertStringContainsString('"type":"library"', $display);
        $this->assertStringContainsString('CLIENT_DEBUG=false', $display);
    }

    public function testInfersAppUrlFromOidcRedirectUri(): void
    {
        $this->setEnv('INTERNAL_OIDC_REDIRECT_URI', 'https://display.example.com/admin/redirect');

        $requestedUrls = [];
        $client = new MockHttpClient(function (string $method, string $url) use (&$requestedUrls): MockResponse {
            $requestedUrls[] = $url;

            return new MockResponse('{}', ['response_headers' => ['content-type' => 'application/json']]);
        });

        $tester = $this->createCommandTester($client);
        $exitCode = $tester->execute(['--output' => 'env']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame([
            'https://display.example.com/admin/config.json',
            'https://display.example.com/client/config.json',
        ], $requestedUrls);
    }

    public function testWarnsWhenAppUrlCannotBeInferred(): void
    {
        // Placeholder values (the committed .env defaults) must not be
        // mistaken for a URL.
        $this->setEnv('INTERNAL_OIDC_REDIRECT_URI', 'INTERNAL_OIDC_REDIRECT_URI');
        $this->setEnv('EXTERNAL_OIDC_REDIRECT_URI', 'EXTERNAL_OIDC_REDIRECT_URI');
        $this->setEnv('OIDC_CLI_REDIRECT', 'APP_CLI_REDIRECT_URI');
        $this->setEnv('COMPOSE_DOMAIN', '');

        $tester = $this->createCommandTester(new MockHttpClient());
        $exitCode = $tester->execute(['--output' => 'env']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Could not infer the 2.x app URL', $tester->getDisplay(true));
    }

    public function testComposeOutput(): void
    {
        $this->setEnv('APP_ENV', 'prod');

        $tester = $this->createCommandTester(new MockHttpClient());
        $exitCode = $tester->execute(['--output' => 'compose', '--skip-config-json' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $display = $tester->getDisplay();

        $this->assertStringContainsString('environment:', $display);
        $this->assertStringContainsString("  - 'APP_ENV=prod'", $display);
    }

    public function testWritesToFile(): void
    {
        $this->setEnv('APP_ENV', 'prod');

        $file = tempnam(sys_get_temp_dir(), 'env3x');
        $this->assertNotFalse($file);

        try {
            $tester = $this->createCommandTester(new MockHttpClient());
            $exitCode = $tester->execute(['--output' => 'env', '--file' => $file, '--skip-config-json' => true]);

            $this->assertSame(Command::SUCCESS, $exitCode);

            $written = file_get_contents($file);
            $this->assertNotFalse($written);
            $this->assertStringContainsString('APP_ENV=prod', $written);
        } finally {
            unlink($file);
        }
    }

    public function testRejectsInvalidOutputFormat(): void
    {
        $tester = $this->createCommandTester(new MockHttpClient());

        $this->assertSame(Command::INVALID, $tester->execute(['--output' => 'json']));
    }

    public function testRejectsFileWithScreenOutput(): void
    {
        $tester = $this->createCommandTester(new MockHttpClient());

        $this->assertSame(Command::INVALID, $tester->execute(['--file' => '/tmp/foo']));
    }

    private function createCommandTester(HttpClientInterface $httpClient): CommandTester
    {
        return new CommandTester(new ConvertEnvTo3xCommand($httpClient));
    }

    private function setEnv(string $name, string $value): void
    {
        if (!array_key_exists($name, $this->envBackup)) {
            $original = array_key_exists($name, $_ENV) && is_string($_ENV[$name]) ? $_ENV[$name] : false;
            $this->envBackup[$name] = $original;
        }

        $_ENV[$name] = $value;
    }
}
