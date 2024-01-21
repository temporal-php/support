<?php

declare(strict_types=1);

namespace Temporal\Support\Tests\Unit\Internal\Attribute\Stub\Attributed;

use Temporal\Support\Attribute\TaskQueue;

#[TaskQueue(name: 'test-queue-parent-parent-interface')]
interface ParentParentInterfaceAttributed
{
}
