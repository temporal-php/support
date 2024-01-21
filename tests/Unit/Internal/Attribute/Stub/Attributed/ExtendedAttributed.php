<?php

declare(strict_types=1);

namespace Temporal\Support\Tests\Unit\Internal\Attribute\Stub\Attributed;

use Temporal\Support\Attribute\TaskQueue;

#[TaskQueue(name: 'test-queue-extended')]
final class ExtendedAttributed extends AbstractAttributed
{
}
