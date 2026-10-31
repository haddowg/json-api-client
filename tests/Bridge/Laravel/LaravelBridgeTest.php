<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Bridge\Laravel;

use haddowg\JsonApiClient\Bridge\ClientConfig;
use haddowg\JsonApiClient\Bridge\Codegen;
use haddowg\JsonApiClient\Bridge\Laravel\CheckCommand;
use haddowg\JsonApiClient\Bridge\Laravel\GenerateCommand;
use haddowg\JsonApiClient\Bridge\Laravel\JsonApiClients;
use haddowg\JsonApiClient\Bridge\Laravel\JsonApiClientServiceProvider;
use haddowg\JsonApiClient\Bridge\Laravel\Run;
use haddowg\JsonApiClient\Tests\Fixtures\StubClient;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository as RepositoryContract;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

#[CoversClass(JsonApiClientServiceProvider::class)]
#[CoversClass(JsonApiClients::class)]
#[CoversClass(GenerateCommand::class)]
#[CoversClass(CheckCommand::class)]
#[CoversClass(Run::class)]
#[CoversClass(ClientConfig::class)]
#[CoversClass(Codegen::class)]
final class LaravelBridgeTest extends TestCase
{
    public function testTheGeneratedClientIsBoundAsItsOwnClassOverLaravelsHttpClient(): void
    {
        $app = self::app(['music' => self::music()]);
        $http = $app->make(Factory::class);
        $http->fake(['*' => Factory::response(['data' => []], 200, ['Content-Type' => 'application/vnd.api+json'])]);

        $client = $app->make(StubClient::class);

        self::assertInstanceOf(StubClient::class, $client);
        self::assertSame($client, $app->make(StubClient::class));
        self::assertSame($client, $app->make(JsonApiClients::class)->client());
        self::assertSame('https://music.example', $client->options->baseUrl);

        $request = $client->options->requestFactory()->createRequest('GET', 'https://music.example/albums');
        $response = $client->options->transport()->sendRequest($request);

        self::assertSame(200, $response->getStatusCode());
        $http->assertSent(static fn(Request $r): bool => $r->url() === 'https://music.example/albums');
    }

    public function testAHeaderProviderIsResolvedOnEveryRequest(): void
    {
        $clients = self::app(['music' => self::music()])->make(JsonApiClients::class);
        $calls = 0;
        $clients->headersUsing('music', static function () use (&$calls): array {
            return ['Authorization' => 'Bearer token-' . ++$calls];
        });

        $options = $clients->options('music');

        self::assertSame(['Authorization' => 'Bearer token-1'], $options->headers());
        self::assertSame(['Authorization' => 'Bearer token-2'], $options->headers());
    }

    public function testAnUnknownClientNameIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No JSON:API client is configured as "nope". Configured: music.');

        self::app(['music' => self::music()])->make(JsonApiClients::class)->client('nope');
    }

    public function testTheCheckCommandComparesTheServedDocumentWithTheGeneratedClient(): void
    {
        $output = \sys_get_temp_dir() . '/json-api-client-' . \bin2hex(\random_bytes(4));
        \mkdir($output);
        \file_put_contents($output . '/autoload.php', '/* @generated from an OpenAPI document with sha256 ' . \hash('sha256', '{"openapi":"3.1.0"}') . ' */');

        try {
            $app = self::app(['music' => self::music(['spec' => 'https://music.example/openapi.json', 'output' => $output])]);
            $http = $app->make(Factory::class);
            $http->fake(['music.example/openapi.json' => $http->sequence()->push('{"openapi":"3.1.0"}')->push('{"openapi":"3.1.1"}')]);

            [$code, $text] = self::command($app, new CheckCommand());
            self::assertSame(0, $code, $text);
            self::assertStringContainsString('The "music" client is up to date.', $text);

            [$code, $text] = self::command($app, new CheckCommand(), ['client' => 'music']);
            self::assertSame(1, $code);
            self::assertStringContainsString('The "music" client is out of date', $text);
        } finally {
            \unlink($output . '/autoload.php');
            \rmdir($output);
        }
    }

    public function testGenerateNamesTheMissingCodegen(): void
    {
        [$code, $text] = self::command(self::app(['music' => self::music(['spec' => 'openapi.json', 'output' => 'out'])]), new GenerateCommand());

        self::assertSame(1, $code);
        self::assertStringContainsString('haddowg/json-api-client-codegen', $text);
    }

    public function testACommandWithNoClientsConfiguredSaysSo(): void
    {
        [$code, $text] = self::command(self::app([]), new CheckCommand());

        self::assertSame(1, $code);
        self::assertStringContainsString('No JSON:API clients are configured', $text);
    }

    /**
     * @param array<string, mixed> $extra
     *
     * @return array<string, mixed>
     */
    private static function music(array $extra = []): array
    {
        return ['client' => StubClient::class, 'base_url' => 'https://music.example', ...$extra];
    }

    /**
     * @param array<string, array<string, mixed>> $clients
     */
    private static function app(array $clients): Container
    {
        $app = new class extends Container {
            public function runningUnitTests(): bool
            {
                return true;
            }
        };
        $config = new Repository(['json-api-client' => ['default' => 'music', 'clients' => $clients]]);
        $app->instance('config', $config);
        $app->instance(RepositoryContract::class, $config);
        $app->singleton(Factory::class);

        JsonApiClientServiceProvider::bind($app);

        return $app;
    }

    /**
     * @param array<string, string> $arguments
     *
     * @return array{int, string}
     */
    private static function command(Container $app, \Illuminate\Console\Command $command, array $arguments = []): array
    {
        $command->setLaravel($app);
        $output = new BufferedOutput();
        $code = $command->run(new ArrayInput($arguments), $output);

        return [$code, $output->fetch()];
    }
}
