<?php

declare(strict_types=1);

/*
 * Loads the generated client once `composer generate-example` has written it. It is not
 * committed, so composer.json registers this file rather than `generated/autoload.php`, and a
 * checkout that has not generated it yet still loads everything else.
 */

if (\is_file(__DIR__ . '/generated/autoload.php')) {
    require_once __DIR__ . '/generated/autoload.php';
}
