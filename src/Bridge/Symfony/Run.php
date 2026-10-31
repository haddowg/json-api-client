<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Bridge\Symfony;

use haddowg\JsonApiClient\Bridge\ClientConfig;
use haddowg\JsonApiClient\Bridge\Codegen;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
    public static function each(InputInterface $input, OutputInterface $output, JsonApiClients $clients, callable $step): int
    {
        $errors = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        $name = $input->getArgument('client');

        try {
            $configs = \is_string($name) ? [$clients->config($name)] : \array_values($clients->configs());
        } catch (\InvalidArgumentException $e) {
            $errors->writeln('<error>' . $e->getMessage() . '</error>');

            return Codegen::FAILURE;
        }

        if ($configs === []) {
            $errors->writeln('<error>No JSON:API clients are configured under json_api_client.clients.</error>');

            return Codegen::FAILURE;
        }

        $codegen = new Codegen($clients->transport(), $clients->requestFactory());
        $result = Codegen::OK;

        foreach ($configs as $config) {
            $out = \fopen('php://memory', 'w+');
            $err = \fopen('php://memory', 'w+');
            if ($out === false || $err === false) {
                return Codegen::FAILURE;
            }

            if ($step($codegen, $config, $out, $err) !== Codegen::OK) {
                $result = Codegen::FAILURE;
            }

            \rewind($out);
            \rewind($err);
            $output->write((string) \stream_get_contents($out));
            $errors->write((string) \stream_get_contents($err));
        }

        return $result;
    }
}
