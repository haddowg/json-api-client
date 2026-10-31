<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Bridge\Laravel;

use haddowg\JsonApiClient\Bridge\ClientConfig;
use haddowg\JsonApiClient\Bridge\Codegen;
use Illuminate\Console\Command;

/**
 * `php artisan json-api-client:check [client]`: fail when a committed client was generated from a
 * different document than its server serves now. Needs no codegen, so it runs anywhere.
 */
final class CheckCommand extends Command
{
    protected $signature = 'json-api-client:check {client? : The configured client to check; all of them when omitted}';

    protected $description = 'Check each generated JSON:API client still matches its server\'s OpenAPI document';

    public function handle(JsonApiClients $clients): int
    {
        return Run::each($this, $clients, static fn(Codegen $codegen, ClientConfig $config, $out, $err): int => $codegen->check($config, $out, $err));
    }
}
