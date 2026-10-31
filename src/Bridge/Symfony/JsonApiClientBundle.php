<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Bridge\Symfony;

use haddowg\JsonApiClient\Testing\FakeTransport;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Registers each configured client as a service of its generated class, built over Symfony's
 * HTTP client, and the `json-api-client:generate` and `json-api-client:check` commands.
 *
 * ```yaml
 * json_api_client:
 *     clients:
 *         music:
 *             client: App\Api\MusicCatalogClient
 *             base_url: '%env(MUSIC_API_URL)%'
 *             token: '%env(MUSIC_API_TOKEN)%'
 *             spec: '%env(MUSIC_API_URL)%/openapi.json'
 *             output: '%kernel.project_dir%/generated/music'
 * ```
 *
 * In tests, `fake: true` swaps the transport for a {@see FakeTransport}, which is then a public
 * service to queue answers on and assert against.
 */
final class JsonApiClientBundle extends AbstractBundle
{
    protected string $extensionAlias = 'json_api_client';

    private const string PREFIX = 'haddowg.json_api_client.';

    public function configure(DefinitionConfigurator $definition): void
    {
        $root = $definition->rootNode();
        if (!$root instanceof \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition) {
            return;
        }

        $root
            ->children()
                ->scalarNode('default')
                    ->info('The client a JsonApiClients::client() call with no name returns. Defaults to the first one configured.')
                    ->defaultNull()
                ->end()
                ->booleanNode('fake')
                    ->info('Answer every request from a FakeTransport, for tests.')
                    ->defaultFalse()
                ->end()
                ->arrayNode('clients')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('client')->info('The generated root client class.')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('base_url')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('token')->info('Sent as a bearer Authorization header.')->defaultNull()->end()
                            ->arrayNode('headers')->useAttributeAsKey('name')->scalarPrototype()->end()->end()
                            ->scalarNode('header_provider')->info('An invokable service returning headers, called on every request.')->defaultNull()->end()
                            ->scalarNode('spec')->info('The OpenAPI document, as a URL or a path.')->defaultNull()->end()
                            ->scalarNode('output')->info('The directory the generated client is committed to.')->defaultNull()->end()
                            ->enumNode('target')->values(['8.3', '8.4', null])->defaultNull()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param array<array-key, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        /** @var array{default: string|null, fake: bool, clients: array<string, array{client: string, header_provider: string|null}&array<string, mixed>>} $config */
        $clients = $config['clients'];
        $services = $container->services();

        $psr17 = null;
        $transport = null;
        if (\class_exists(Psr18Client::class)) {
            $builder->register(self::PREFIX . 'psr18', Psr18Client::class)
                ->setArguments([new Reference('http_client', ContainerInterface::NULL_ON_INVALID_REFERENCE)]);
            $psr17 = new Reference(self::PREFIX . 'psr18');
            $transport = $psr17;
        }

        if ($config['fake']) {
            $builder->register(FakeTransport::class, FakeTransport::class)
                ->setPublic(true);
            $transport = new Reference(FakeTransport::class);
        }

        $providers = [];
        $settings = [];
        foreach ($clients as $name => $client) {
            if ($client['header_provider'] !== null) {
                $providers[$name] = new Reference($client['header_provider']);
            }
            unset($client['header_provider']);
            $settings[$name] = $client;
        }

        $builder->register(JsonApiClients::class, JsonApiClients::class)
            ->setArguments([
                $settings,
                $config['default'] ?? (\array_key_first($settings) ?? 'default'),
                $transport,
                $psr17,
                $psr17,
                $providers === [] ? null : new ServiceLocatorArgument($providers),
            ])
            ->setPublic(true);

        foreach ($clients as $name => $client) {
            $builder->setDefinition($client['client'], (new Definition($client['client']))
                ->setFactory([new Reference(JsonApiClients::class), 'client'])
                ->setArguments([$name])
                ->setPublic(true));
        }

        $services->set(GenerateCommand::class)->args([new Reference(JsonApiClients::class)])->tag('console.command');
        $services->set(CheckCommand::class)->args([new Reference(JsonApiClients::class)])->tag('console.command');
    }
}
