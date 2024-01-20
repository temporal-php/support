<?php

declare(strict_types=1);

namespace Temporal\Sugar\Attribute;

use DateInterval;
use Temporal\Sugar\Internal\Attribute\AttributeForActivity;
use Temporal\Sugar\Internal\Attribute\AttributeForWorkflow;
use Throwable;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class RetryPolicy implements AttributeForWorkflow, AttributeForActivity
{
    /**
     * @param int<0, max> $attempts Maximum number of attempts. When exceeded the retries stop even
     *        if not expired yet. If not set or set to 0, it means unlimited, and rely on activity
     *        {@see ActivityOptions::$scheduleToCloseTimeout} to stop.
     * @param DateInterval|string|int|null $initInterval Backoff interval for the first retry.
     *        If $retryBackoff is 1.0 then it is used for all retries.
     *        Int value in seconds.
     * @param DateInterval|string|int|null $maxInterval Maximum backoff interval between retries.
     *        Exponential backoff leads to interval increase. This value is the cap of the interval.
     *        Int value in seconds.
     *        Default is 100x of $retryInitInterval.
     * @param float|null $backoff Coefficient used to calculate the next retry backoff interval.
     *        The next retry interval is previous interval multiplied by this coefficient.
     *        Note: Must be greater than 1.0
     * @param list<class-string<Throwable>> $nonRetryables Non-retryable errors. Temporal server will stop retry
     *        if error type matches this list.
     */
    public function __construct(
        public readonly int $attempts = 0,
        public readonly \DateInterval|string|int|null $initInterval = null,
        public readonly \DateInterval|string|int|null $maxInterval = null,
        public readonly ?float $backoff = null,
        public readonly array $nonRetryables = []
    ) {
    }
}
