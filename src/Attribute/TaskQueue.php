<?php

declare(strict_types=1);

namespace Temporal\Sugar\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class TaskQueue
{
    /**
     * @param non-empty-string $name Task queue name.
     */
    public function __construct(
        public readonly string $name,
    ) {
    }
}
