<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Support;

/**
 * The sentinel that defaults every optional member of a generated write DTO.
 *
 * PHP has no way to express "this argument was not passed", and JSON:API `PATCH` needs
 * absent to stay distinct from an explicit `null` — an omitted member leaves the server
 * value alone, a `null` member clears it. So optional members are typed `string|Missing`
 * and default to {@see Missing::Value}; the serialiser drops every member still holding it.
 *
 * Callers never write it. It appears only in generated signatures and in the guard the
 * serialiser applies (`$value instanceof Missing`).
 */
enum Missing
{
    case Value;
}
