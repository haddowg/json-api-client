<?php

declare(strict_types=1);

/*
 * The music catalog, browsed from the command line: `php examples/music-catalog/catalog.php`.
 *
 * It runs the generated client against the catalog's own OpenAPI document, answering through
 * ExampleServer, so it needs no server. Point ClientOptions at a running music-catalog server
 * (and drop the transport) to browse the real thing.
 */

use haddowg\JsonApiClient\ClientOptions;
use haddowg\JsonApiClient\Examples\MusicCatalog\AlbumTracksRelatedQuery;
use haddowg\JsonApiClient\Examples\MusicCatalog\MusicCatalogClient;
use haddowg\JsonApiClient\Examples\MusicCatalog\Support\ExampleServer;
use Nyholm\Psr7\Factory\Psr17Factory;

require __DIR__ . '/../../vendor/autoload.php';

$psr17 = new Psr17Factory();
$client = new MusicCatalogClient(new ClientOptions(
    baseUrl: 'https://music.example',
    transport: ExampleServer::fromFile(__DIR__ . '/openapi.json', $psr17, $psr17),
    requestFactory: $psr17,
    streamFactory: $psr17,
));

$albums = $client->albums->query()
    ->withArtist()
    ->withTracks(fn(AlbumTracksRelatedQuery $t) => $t->sort('trackNumber'))
    ->sort('-releasedAt')
    ->get();

foreach ($albums as $album) {
    echo \sprintf("%s by %s (%s)\n", $album->title(), $album->artist()?->name() ?? 'unknown', $album->releasedAt()?->format('Y') ?? 'unreleased');

    foreach ($album->tracks() as $track) {
        echo \sprintf("  %d. %s\n", $track->trackNumber(), $track->title());
    }
}
