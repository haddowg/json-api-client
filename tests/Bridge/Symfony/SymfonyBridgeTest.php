<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Bridge\Symfony;

use haddowg\JsonApiClient\Bridge\ClientConfig;
use haddowg\JsonApiClient\Bridge\Codegen;
use haddowg\JsonApiClient\Bridge\Symfony\CheckCommand;
use haddowg\JsonApiClient\Bridge\Symfony\GenerateCommand;
use haddowg\JsonApiClient\Bridge\Symfony\JsonApiClientBundle;
use haddowg\JsonApiClient\Bridge\Symfony\JsonApiClients;
use haddowg\JsonApiClient\Bridge\Symfony\Run;
use haddowg\JsonApiClient\Testing\FakeTransport;
use haddowg\JsonApiClient\Tests\Fixtures\StubClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(JsonApiClientBundle::class)]
#[CoversClass(JsonApiClients::class)]
#[CoversClass(GenerateCommand::class)]
#[CoversClass(CheckCommand::class)]
#[CoversClass(Run::class)]
#[CoversClass(ClientConfig::class)]
#[CoversClass(Codegen::class)]
final class SymfonyBridgeTest extends TestCase
{
    public function testTheGeneratedClientIsAServiceOfItsOwnClassOverSymfonysHttpClient(): void
    {
        $container = self::container([
            'clients' => ['music' => ['client' => StubClient::class, 'base_url' => 'https://music.example', 'token' => 'abc']],
        ], new MockResponse('{"data":[]}', ['http_code' => 200]));

        $client = $container->get(StubClient::class);

        self::assertInstanceOf(StubClient::class, $client);
        $clients = $container->get(JsonApiClients::class);
        self::assertInstanceOf(JsonApiClients::class, $clients);
        self::assertSame($client, $clients->client());
        self::assertInstanceOf(Psr18Client::class, $client->options->transport());
        self::assertSame(['Authorization' => 'Bearer abc'], $client->options->headers());

        $response = $client->options->transport()->sendRequest($client->options->requestFactory()->createRequest('GET', 'https://music.example/albums'));
        self::assertSame('{"data":[]}', (string) $response->getBody());
    }

    public function testFakeSwapsTheTransportForOneATestCanAssertOn(): void
    {
        $container = self::container([
            'fake' => true,
            'clients' => ['music' => ['client' => StubClient::class, 'base_url' => 'https://music.example']],
        ]);

        $fake = $container->get(FakeTransport::class);
        $client = $container->get(StubClient::class);
        self::assertInstanceOf(FakeTransport::class, $fake);
        self::assertInstanceOf(StubClient::class, $client);

        $fake->respondWith(200, ['data' => null]);
        $client->options->transport()->sendRequest($client->options->requestFactory()->createRequest('GET', 'https://music.example/albums/1'));

        $fake->assertSent(static fn(RequestInterface $r): bool => $r->getUri()->getPath() === '/albums/1');
    }

    public function testAHeaderProviderServiceIsCalledOnEveryRequest(): void
    {
        $builder = self::builder([
            'clients' => ['music' => ['client' => StubClient::class, 'base_url' => 'https://music.example', 'header_provider' => 'app.tokens']],
        ]);
        $builder->register('app.tokens', TokenProvider::class);
        $builder->compile();

        $client = $builder->get(StubClient::class);
        self::assertInstanceOf(StubClient::class, $client);

        self::assertSame(['Authorization' => 'Bearer token-1'], $client->options->headers());
        self::assertSame(['Authorization' => 'Bearer token-2'], $client->options->headers());
    }

    public function testTheCheckCommandComparesTheServedDocumentWithTheGeneratedClient(): void
    {
        $output = \sys_get_temp_dir() . '/json-api-client-' . \bin2hex(\random_bytes(4));
        \mkdir($output);
        \file_put_contents($output . '/autoload.php', '/* @generated from an OpenAPI document with sha256 ' . \hash('sha256', '{"openapi":"3.1.0"}') . ' */');

        try {
            $container = self::container([
                'clients' => ['music' => [
                    'client' => StubClient::class,
                    'base_url' => 'https://music.example',
                    'spec' => 'https://music.example/openapi.json',
                    'output' => $output,
                ]],
            ], new MockResponse('{"openapi":"3.1.0"}'), new MockResponse('{"openapi":"3.1.1"}'));

            $clients = $container->get(JsonApiClients::class);
            self::assertInstanceOf(JsonApiClients::class, $clients);

            $tester = new CommandTester(new CheckCommand($clients));
            self::assertSame(0, $tester->execute([]), $tester->getDisplay());
            self::assertStringContainsString('The "music" client is up to date.', $tester->getDisplay());

            self::assertSame(1, $tester->execute(['client' => 'music']));
            self::assertStringContainsString('The "music" client is out of date', $tester->getDisplay());
        } finally {
            \unlink($output . '/autoload.php');
            \rmdir($output);
        }
    }

    public function testGenerateNamesTheMissingCodegen(): void
    {
        $container = self::container(['clients' => ['music' => [
            'client' => StubClient::class,
            'base_url' => 'https://music.example',
            'spec' => 'openapi.json',
            'output' => 'out',
        ]]]);
        $clients = $container->get(JsonApiClients::class);
        self::assertInstanceOf(JsonApiClients::class, $clients);

        $tester = new CommandTester(new GenerateCommand($clients));

        self::assertSame(1, $tester->execute([]));
        self::assertStringContainsString('haddowg/json-api-client-codegen', $tester->getDisplay());
    }

    public function testAnUnknownClientNameIsRefused(): void
    {
        $container = self::container(['clients' => ['music' => ['client' => StubClient::class, 'base_url' => 'https://music.example']]]);
        $clients = $container->get(JsonApiClients::class);
        self::assertInstanceOf(JsonApiClients::class, $clients);

        $tester = new CommandTester(new CheckCommand($clients));

        self::assertSame(1, $tester->execute(['client' => 'nope']));
        self::assertStringContainsString('No JSON:API client is configured as "nope". Configured: music.', $tester->getDisplay());
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function container(array $config, MockResponse ...$responses): ContainerBuilder
    {
        $builder = self::builder($config, ...$responses);
        $builder->compile();

        return $builder;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function builder(array $config, MockResponse ...$responses): ContainerBuilder
    {
        $builder = new ContainerBuilder();
        $builder->setParameter('kernel.environment', 'test');
        $builder->setParameter('kernel.build_dir', \sys_get_temp_dir());
        $extension = (new JsonApiClientBundle())->getContainerExtension();
        self::assertNotNull($extension);
        $builder->registerExtension($extension);
        $builder->loadFromExtension('json_api_client', $config);
        $builder->register('http_client', MockHttpClient::class)->setArguments([$responses]);

        return $builder;
    }
}

/**
 * An invokable header provider service, as an application would register one.
 */
final class TokenProvider
{
    private int $calls = 0;

    /**
     * @return array<string, string>
     */
    public function __invoke(): array
    {
        return ['Authorization' => 'Bearer token-' . ++$this->calls];
    }
}
