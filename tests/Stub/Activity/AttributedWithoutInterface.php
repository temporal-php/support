<?php

declare(strict_types=1);

namespace Temporal\Sugar\Tests\Stub\Activity;

use RuntimeException;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;
use Temporal\Sugar\Attribute\RetryPolicy;
use Temporal\Sugar\Attribute\TaskQueue;

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
