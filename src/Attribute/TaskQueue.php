<?php

declare(strict_types=1);

namespace Temporal\Support\Attribute;

use Temporal\Support\Internal\Attribute\AttributeForActivity;
use Temporal\Support\Internal\Attribute\AttributeForWorkflow;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class TaskQueue implements AttributeForWorkflow, AttributeForActivity
{
    /**
     * @param non-empty-string $name Task queue name.
     */
    public function __construct(
        public readonly string $name,
    ) {
    }
}
