<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Http;

use Http\Discovery\Exception\NotFoundException;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Finds the PSR-18 client and PSR-17 factories an application already has, when it has not
 * said which to use.
 *
 * `php-http/discovery` is a convenience, never a requirement: it is not a hard dependency, and
 * an application that passes its own implementations never reaches this. Everything here
 * returns null rather than throwing, so the caller can raise an error that says what to
 * install instead of a class-not-found several frames down.
 *
 * @internal
 */
final class Discovery
{
    private static ?ClientInterface $client = null;

    private static ?RequestFactoryInterface $requestFactory = null;

    private static ?StreamFactoryInterface $streamFactory = null;

    public static function httpClient(): ?ClientInterface
    {
        if (self::$client !== null) {
            return self::$client;
        }

        if (!\class_exists(Psr18ClientDiscovery::class)) {
            return null;
        }

        try {
            return self::$client = Psr18ClientDiscovery::find();
        } catch (NotFoundException) {
            return null;
        }
    }

    public static function requestFactory(): ?RequestFactoryInterface
    {
        if (self::$requestFactory !== null) {
            return self::$requestFactory;
        }

        if (!\class_exists(Psr17FactoryDiscovery::class)) {
            return null;
        }

        try {
            return self::$requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        } catch (NotFoundException) {
            return null;
        }
    }

    public static function streamFactory(): ?StreamFactoryInterface
    {
        if (self::$streamFactory !== null) {
            return self::$streamFactory;
        }

        if (!\class_exists(Psr17FactoryDiscovery::class)) {
            return null;
        }

        try {
            return self::$streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * Drop everything found so far. Only useful to a test that changes what is installable.
     */
    public static function forget(): void
    {
        self::$client = null;
        self::$requestFactory = null;
        self::$streamFactory = null;
    }
}
