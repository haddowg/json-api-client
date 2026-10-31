<?php

declare(strict_types=1);

/*
 * The generated JSON:API clients this application talks to, by name.
 *
 * Each one is bound in the container as its generated class, so type-hint that class to get the
 * configured client. `default` names the one `JsonApiClients::client()` returns with no name.
 */
return [
    'default' => 'default',

    'clients' => [
        // 'default' => [
        //     // The generated root client class. Its namespace is where the code is generated.
        //     'client' => App\Api\MusicCatalogClient::class,
        //     'base_url' => env('MUSIC_API_URL', 'https://music.example'),
        //     // Sent as `Authorization: Bearer …`. For a token that refreshes, bind a header
        //     // provider instead (see JsonApiClients::headersUsing()).
        //     'token' => env('MUSIC_API_TOKEN'),
        //     'headers' => [],
        //     // Where json-api-client:generate reads the OpenAPI document from (a URL or a path)
        //     // and writes the generated code to.
        //     'spec' => env('MUSIC_API_URL', 'https://music.example') . '/openapi.json',
        //     'output' => base_path('generated/music'),
        //     // `8.3` or `8.4`; defaults to the PHP version running the command.
        //     'target' => null,
        // ],
    ],
];
