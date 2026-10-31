<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Bridge\Laravel;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the configured clients, each bound as its generated class, and the
 * `json-api-client:generate` and `json-api-client:check` commands.
 */
final class JsonApiClientServiceProvider extends ServiceProvider
{
    private const string CONFIG = __DIR__ . '/../../../config/json-api-client.php';

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG, 'json-api-client');

        self::bind($this->app);
    }

    /**
     * Bind the client registry, and each configured client as its generated class.
     *
     * @internal the provider's registration, separated so it runs on any container
     */
    public static function bind(Container $app): void
    {
        $app->singleton(JsonApiClients::class, static function (Container $app): JsonApiClients {
            $config = $app->make(Repository::class);
            /** @var array<string, array<array-key, mixed>> $clients */
            $clients = (array) $config->get('json-api-client.clients', []);
            $default = $config->get('json-api-client.default', 'default');

            return new JsonApiClients($app->make(Factory::class), $clients, \is_string($default) ? $default : 'default');
        });

        $config = $app->make(Repository::class)->get('json-api-client.clients', []);
        foreach (\is_array($config) ? $config : [] as $name => $client) {
            $class = \is_array($client) ? ($client['client'] ?? null) : null;
            if (\is_string($class) && $class !== '') {
                $app->singleton($class, static fn(Container $app): object => $app->make(JsonApiClients::class)->client((string) $name));
            }
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([self::CONFIG => $this->app->configPath('json-api-client.php')], 'json-api-client-config');
            $this->commands([GenerateCommand::class, CheckCommand::class]);
        }
    }
}
