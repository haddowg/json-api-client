<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Bridge\Laravel;

use GuzzleHttp\Psr7\HttpFactory;
use haddowg\JsonApiClient\Bridge\ClientConfig;
use haddowg\JsonApiClient\Bridge\Codegen;
use Illuminate\Console\Command;

/**
 * Runs a codegen step over the client the command names, or every configured client.
 *
 * @internal
 */
final class Run
{
    /**
     * @param callable(Codegen, ClientConfig, resource, resource): int $step
     */
    public static function each(Command $command, JsonApiClients $clients, callable $step): int
    {
        $name = $command->argument('client');

        try {
            $configs = \is_string($name) ? [$clients->config($name)] : \array_values($clients->configs());
        } catch (\InvalidArgumentException $e) {
            $command->error($e->getMessage());

            return Codegen::FAILURE;
        }

        if ($configs === []) {
            $command->error('No JSON:API clients are configured in config/json-api-client.php.');

            return Codegen::FAILURE;
        }

        $result = Codegen::OK;
        foreach ($configs as $config) {
            $out = \fopen('php://memory', 'w+');
            $err = \fopen('php://memory', 'w+');
            if ($out === false || $err === false) {
                return Codegen::FAILURE;
            }

            $codegen = new Codegen($clients->options($config->name)->transport(), new HttpFactory());
            if ($step($codegen, $config, $out, $err) !== Codegen::OK) {
                $result = Codegen::FAILURE;
            }

            foreach ([[$out, 'info'], [$err, 'error']] as [$stream, $method]) {
                \rewind($stream);
                foreach (\explode(\PHP_EOL, \trim((string) \stream_get_contents($stream))) as $line) {
                    if ($line !== '') {
                        $command->{$method}($line);
                    }
                }
            }
        }

        return $result;
    }
}
