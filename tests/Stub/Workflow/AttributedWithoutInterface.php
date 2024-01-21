<?php

declare(strict_types=1);

namespace Temporal\Sugar\Tests\Stub\Workflow;

use RuntimeException;
use Temporal\Sugar\Attribute\RetryPolicy;
use Temporal\Sugar\Attribute\TaskQueue;
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
