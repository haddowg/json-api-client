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
 * `bin/console json-api-client:check [client]`: fail when a committed client was generated from a
 * different document than its server serves now. Needs no codegen, so it runs anywhere.
 */
#[AsCommand(name: 'json-api-client:check', description: 'Check each generated JSON:API client still matches its server\'s OpenAPI document')]
final class CheckCommand extends Command
{
    public function __construct(private readonly JsonApiClients $clients)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('client', InputArgument::OPTIONAL, 'The configured client to check; all of them when omitted');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return Run::each($input, $output, $this->clients, static fn(Codegen $codegen, ClientConfig $config, $out, $err): int => $codegen->check($config, $out, $err));
    }
}
