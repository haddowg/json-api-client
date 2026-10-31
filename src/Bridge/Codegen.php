<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Bridge;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * What the bridges' `json-api-client:generate` and `json-api-client:check` commands do, without the
 * framework around them.
 *
 * Generating runs `haddowg/json-api-client-codegen`, which is a dev dependency and so may not be
 * installed. Checking needs no codegen: the generated `autoload.php` records the sha256 of the
 * document it was generated from, so a check fetches the document and compares hashes. That is
 * enough to fail CI when the server's contract has moved on and the committed client has not.
 *
 * @internal driven by the bridges' console commands
 */
final class Codegen
{
    public const int OK = 0;

    public const int FAILURE = 1;

    private const string CODEGEN = 'haddowg\JsonApiCodegen\Console\Command';

    private const string HASH = '/@generated from an OpenAPI document with sha256 ([0-9a-f]{64})/';

    public function __construct(
        private readonly ?ClientInterface $http = null,
        private readonly ?RequestFactoryInterface $requests = null,
    ) {}

    /**
     * Generate the client into its output directory.
     *
     * @param resource $stdout
     * @param resource $stderr
     */
    public function generate(ClientConfig $config, $stdout, $stderr): int
    {
        if (!\class_exists(self::CODEGEN)) {
            \fwrite($stderr, 'Generating a client needs haddowg/json-api-client-codegen. Install it with `composer require --dev haddowg/json-api-client-codegen`.' . \PHP_EOL);

            return self::FAILURE;
        }

        [$spec, $output] = $this->paths($config, $stderr);
        if ($spec === null || $output === null) {
            return self::FAILURE;
        }

        $document = $this->load($spec, $config, $stderr);
        if ($document === null) {
            return self::FAILURE;
        }

        $file = \tempnam(\sys_get_temp_dir(), 'openapi-');
        if ($file === false || \file_put_contents($file, $document) === false) {
            \fwrite($stderr, 'Could not write the OpenAPI document to a temporary file.' . \PHP_EOL);

            return self::FAILURE;
        }

        try {
            $arguments = [$file, $output, '--namespace=' . $config->namespace(), '--client=' . $config->clientName()];
            if ($config->target !== null) {
                $arguments[] = '--target=' . $config->target;
            }

            $command = self::CODEGEN;
            /** @var int $code */
            $code = (new $command($stdout, $stderr))->run($arguments);

            return $code;
        } finally {
            @\unlink($file);
        }
    }

    /**
     * Check the committed client was generated from the document the server serves now.
     *
     * @param resource $stdout
     * @param resource $stderr
     */
    public function check(ClientConfig $config, $stdout, $stderr): int
    {
        [$spec, $output] = $this->paths($config, $stderr);
        if ($spec === null || $output === null) {
            return self::FAILURE;
        }

        $generated = self::generatedHash($output);
        if ($generated === null) {
            \fwrite($stderr, \sprintf('No generated client in %s. Run json-api-client:generate first.', $output) . \PHP_EOL);

            return self::FAILURE;
        }

        $document = $this->load($spec, $config, $stderr);
        if ($document === null) {
            return self::FAILURE;
        }

        if (\hash('sha256', $document) !== $generated) {
            \fwrite($stderr, \sprintf('The "%s" client is out of date: %s has changed since it was generated. Run json-api-client:generate and commit the result.', $config->name, $spec) . \PHP_EOL);

            return self::FAILURE;
        }

        \fwrite($stdout, \sprintf('The "%s" client is up to date.', $config->name) . \PHP_EOL);

        return self::OK;
    }

    /**
     * The document hash a generated client records, or null when `$output` holds none.
     */
    public static function generatedHash(string $output): ?string
    {
        $autoload = @\file_get_contents($output . '/autoload.php');

        if ($autoload === false || \preg_match(self::HASH, $autoload, $m) !== 1) {
            return null;
        }

        return $m[1];
    }

    /**
     * @param resource $stderr
     *
     * @return array{string, string}|array{null, null}
     */
    private function paths(ClientConfig $config, $stderr): array
    {
        if ($config->spec === null || $config->output === null) {
            \fwrite($stderr, \sprintf('The "%s" client needs "spec" and "output" configured to be generated or checked.', $config->name) . \PHP_EOL);

            return [null, null];
        }

        return [$config->spec, $config->output];
    }

    /**
     * Read the document from a file, or fetch it over HTTP with the client's own headers.
     *
     * @param resource $stderr
     */
    private function load(string $spec, ClientConfig $config, $stderr): ?string
    {
        if (\preg_match('#^https?://#i', $spec) !== 1) {
            $document = @\file_get_contents($spec);
            if ($document === false) {
                \fwrite($stderr, \sprintf('Could not read the OpenAPI document %s.', $spec) . \PHP_EOL);

                return null;
            }

            return $document;
        }

        if ($this->http === null || $this->requests === null) {
            \fwrite($stderr, \sprintf('Fetching %s needs an HTTP client.', $spec) . \PHP_EOL);

            return null;
        }

        $request = $this->requests->createRequest('GET', $spec)->withHeader('Accept', 'application/vnd.oai.openapi+json, application/json');
        foreach ($config->options()->headers() as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        try {
            $response = $this->http->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            \fwrite($stderr, \sprintf('Could not fetch %s: %s', $spec, $e->getMessage()) . \PHP_EOL);

            return null;
        }

        if ($response->getStatusCode() !== 200) {
            \fwrite($stderr, \sprintf('Fetching %s answered %d.', $spec, $response->getStatusCode()) . \PHP_EOL);

            return null;
        }

        return (string) $response->getBody();
    }
}
