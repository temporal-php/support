<?php

declare(strict_types=1);

namespace Temporal\Support\Tests\Stub\Workflow;

use RuntimeException;
use Temporal\Support\Attribute\RetryPolicy;
use Temporal\Support\Attribute\TaskQueue;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[TaskQueue(name: 'test-queue')]
#[RetryPolicy(attempts: 3, initInterval: 5, maxInterval: 500, backoff: 10, nonRetryables: [RuntimeException::class])]
#[WorkflowInterface]
final class AttributedWithoutInterface
{
    #[WorkflowMethod]
    public function handle()
    {
    }
}
