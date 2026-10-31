<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Bridge;

use haddowg\JsonApiClient\Bridge\ClientConfig;
use haddowg\JsonApiClient\Bridge\Codegen;
use haddowg\JsonApiClient\Testing\FakeTransport;
use haddowg\JsonApiClient\Tests\Fixtures\StubClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

#[CoversClass(Codegen::class)]
#[CoversClass(ClientConfig::class)]
final class CodegenTest extends TestCase
{
    private const string DOCUMENT = '{"openapi":"3.1.0","info":{"title":"Music","version":"1"}}';

    private string $output;

    protected function setUp(): void
    {
        $this->output = \sys_get_temp_dir() . '/json-api-client-' . \bin2hex(\random_bytes(4));
        \mkdir($this->output);
    }

    protected function tearDown(): void
    {
        foreach (\glob($this->output . '/*') ?: [] as $file) {
            \unlink($file);
        }
        \rmdir($this->output);
    }

    public function testACheckPassesWhenTheDocumentHashMatches(): void
    {
        $this->generatedFrom(self::DOCUMENT);
        $spec = $this->output . '/openapi.json';
        \file_put_contents($spec, self::DOCUMENT);

        [$code, $out, $err] = self::execute(static fn(Codegen $c, $o, $e): int => $c->check(self::config($spec, \dirname($spec)), $o, $e));

        self::assertSame(Codegen::OK, $code, $err);
        self::assertSame('The "music" client is up to date.' . \PHP_EOL, $out);
    }

    public function testACheckFailsWhenTheServedDocumentHasMoved(): void
    {
        $this->generatedFrom(self::DOCUMENT);
        $fake = (new FakeTransport(new Psr17Factory(), new Psr17Factory()))->respondWith(200, '{"openapi":"3.1.0","changed":true}');
        $codegen = new Codegen($fake, new Psr17Factory());

        [$code, , $err] = self::execute(fn(Codegen $c, $o, $e): int => $codegen->check(self::config('https://music.example/openapi.json', $this->output, 'secret'), $o, $e));

        self::assertSame(Codegen::FAILURE, $code);
        self::assertStringContainsString('The "music" client is out of date: https://music.example/openapi.json has changed', $err);
        $fake->assertSent(static fn(RequestInterface $r): bool => $r->getHeaderLine('Authorization') === 'Bearer secret'
            && \str_contains($r->getHeaderLine('Accept'), 'application/vnd.oai.openapi+json'));
    }

    public function testACheckFailsWithoutAGeneratedClient(): void
    {
        [$code, , $err] = self::execute(fn(Codegen $c, $o, $e): int => $c->check(self::config('openapi.json', $this->output), $o, $e));

        self::assertSame(Codegen::FAILURE, $code);
        self::assertStringContainsString('No generated client in ' . $this->output, $err);
    }

    public function testACheckFailsWhenTheServerRefusesTheDocument(): void
    {
        $this->generatedFrom(self::DOCUMENT);
        $fake = (new FakeTransport(new Psr17Factory(), new Psr17Factory()))->respondWith(401, '{}');

        [$code, , $err] = self::execute(fn(Codegen $c, $o, $e): int => (new Codegen($fake, new Psr17Factory()))->check(self::config('https://music.example/openapi.json', $this->output), $o, $e));

        self::assertSame(Codegen::FAILURE, $code);
        self::assertStringContainsString('Fetching https://music.example/openapi.json answered 401.', $err);
    }

    public function testAClientWithoutASpecOrOutputCannotBeChecked(): void
    {
        $config = new ClientConfig('music', StubClient::class, 'https://music.example');

        [$code, , $err] = self::execute(static fn(Codegen $c, $o, $e): int => $c->check($config, $o, $e));

        self::assertSame(Codegen::FAILURE, $code);
        self::assertStringContainsString('needs "spec" and "output" configured', $err);
    }

    public function testGeneratingWithoutTheCodegenInstalledSaysHowToInstallIt(): void
    {
        [$code, , $err] = self::execute(fn(Codegen $c, $o, $e): int => $c->generate(self::config('openapi.json', $this->output), $o, $e));

        self::assertSame(Codegen::FAILURE, $code);
        self::assertStringContainsString('composer require --dev haddowg/json-api-client-codegen', $err);
    }

    public function testTheCodegenNamesComeFromTheClientClass(): void
    {
        $config = new ClientConfig('music', 'App\Api\MusicCatalogClient', 'https://music.example');

        self::assertSame('MusicCatalog', $config->clientName());
        self::assertSame('App\Api', $config->namespace());
    }

    public function testATokenAndStaticHeadersGoOnEveryRequestBeforeTheProvidersHeaders(): void
    {
        $config = ClientConfig::fromArray('music', [
            'client' => StubClient::class,
            'base_url' => 'https://music.example',
            'token' => 'abc',
            'headers' => ['X-Tenant' => 'one'],
        ]);

        $options = $config->options(headers: static fn(): array => ['X-Tenant' => 'two']);

        self::assertSame(['X-Tenant' => 'two', 'Authorization' => 'Bearer abc'], $options->headers());
        self::assertSame('https://music.example', $options->baseUrl);
    }

    public function testValidationReadsTheLocalSpec(): void
    {
        $config = ClientConfig::fromArray('music', [
            'client' => StubClient::class,
            'base_url' => 'https://music.example',
            'spec' => __DIR__ . '/../Fixtures/validation.openapi.json',
            'validate' => true,
        ]);

        self::assertNotNull($config->options()->validator());
        self::assertNull((new ClientConfig('music', StubClient::class, 'https://music.example'))->options()->validator());
    }

    public function testValidationAgainstARemoteSpecIsRefused(): void
    {
        $config = new ClientConfig('music', StubClient::class, 'https://music.example', spec: 'https://music.example/openapi.json', validate: true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('its "spec" must be a local file');

        $config->options();
    }

    public function testAMisconfiguredClientIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The JSON:API client "music" needs both "client" and "base_url".');

        ClientConfig::fromArray('music', ['client' => StubClient::class]);
    }

    private function generatedFrom(string $document): void
    {
        \file_put_contents($this->output . '/autoload.php', "<?php\n/*\n * @generated from an OpenAPI document with sha256 " . \hash('sha256', $document) . "\n */\n");
    }

    private static function config(string $spec, string $output, ?string $token = null): ClientConfig
    {
        return new ClientConfig('music', StubClient::class, 'https://music.example', $token, [], $spec, $output);
    }

    /**
     * @param callable(Codegen, resource, resource): int $step
     *
     * @return array{int, string, string}
     */
    private static function execute(callable $step): array
    {
        $out = \fopen('php://memory', 'w+');
        $err = \fopen('php://memory', 'w+');
        self::assertIsResource($out);
        self::assertIsResource($err);

        $code = $step(new Codegen(), $out, $err);
        \rewind($out);
        \rewind($err);

        return [$code, (string) \stream_get_contents($out), (string) \stream_get_contents($err)];
    }
}
