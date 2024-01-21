<?php

declare(strict_types=1);

namespace Temporal\Support\Tests\Stub\Activity;

use RuntimeException;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;
use Temporal\Support\Attribute\RetryPolicy;
use Temporal\Support\Attribute\TaskQueue;

#[TaskQueue(name: 'test-queue')]
#[RetryPolicy(attempts: 3, initInterval: 5, maxInterval: 500, backoff: 10, nonRetryables: [RuntimeException::class])]
#[ActivityInterface]
final class AttributedWithoutInterface
{
    #[ActivityMethod]
    public function handle()
    {
    }
}
