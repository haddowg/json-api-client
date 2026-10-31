<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Bridge\Laravel;

use haddowg\JsonApiClient\Bridge\ClientConfig;
use haddowg\JsonApiClient\Bridge\Codegen;
use Illuminate\Console\Command;

/**
 * `php artisan json-api-client:generate [client]`: regenerate one configured client, or all of them.
 */
final class GenerateCommand extends Command
{
    protected $signature = 'json-api-client:generate {client? : The configured client to generate; all of them when omitted}';

    protected $description = 'Generate a typed JSON:API client from its server\'s OpenAPI document';

    public function handle(JsonApiClients $clients): int
    {
        return Run::each($this, $clients, static fn(Codegen $codegen, ClientConfig $config, $out, $err): int => $codegen->generate($config, $out, $err));
    }
}
