<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Pagination;

/**
 * The paginator a collection endpoint uses, as the descriptor records it.
 *
 * The kind decides which {@see Page} subclass a collection carries, and therefore which
 * accessors exist: `->after` is a static error on a page-numbered collection because the
 * paginator is part of the type, not a nullable field.
 */
enum PaginatorKind: string
{
    /** `page[number]` / `page[size]`. */
    case PageNumber = 'page';

    /** `page[offset]` / `page[limit]`. */
    case Offset = 'offset';

    /** `page[after]` / `page[before]` / `page[size]`. */
    case Cursor = 'cursor';

    /** The endpoint returns the whole collection; there is no page to speak of. */
    case None = 'none';
}
