<?php

declare(strict_types=1);

namespace Temporal\Sugar\Attribute;

use Temporal\Sugar\Internal\AttributeForActivity;
use Temporal\Sugar\Internal\AttributeForWorkflow;

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
