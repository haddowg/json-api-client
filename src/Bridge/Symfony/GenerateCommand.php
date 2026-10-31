<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Bridge\Symfony;

use haddowg\JsonApiClient\Bridge\ClientConfig;
use haddowg\JsonApiClient\Bridge\Codegen;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * `bin/console json-api-client:generate [client]`: regenerate one configured client, or all of them.
 */
#[AsCommand(name: 'json-api-client:generate', description: 'Generate a typed JSON:API client from its server\'s OpenAPI document')]
final class GenerateCommand extends Command
{
    public function __construct(private readonly JsonApiClients $clients)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('client', InputArgument::OPTIONAL, 'The configured client to generate; all of them when omitted');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return Run::each($input, $output, $this->clients, static fn(Codegen $codegen, ClientConfig $config, $out, $err): int => $codegen->generate($config, $out, $err));
    }
}
