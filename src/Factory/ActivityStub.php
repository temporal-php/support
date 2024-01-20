<?php

declare(strict_types=1);

namespace Temporal\Sugar\Factory;

use DateInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Internal\Workflow\ActivityProxy;
use Temporal\Sugar\Internal\RetryOptions;
use Temporal\Workflow;
use Throwable;

final class ActivityStub
{
    /**
     * Note: must be used in Workflow context only.
     *
     * @template T of object
     *
     * @param class-string<T> $activity
     * @param non-empty-string|null $taskQueue
     * @param int<0, max> $retryAttempts Maximum number of attempts. When exceeded the retries stop even
     *        if not expired yet. If not set or set to 0, it means unlimited, and rely on activity
     *        {@see ActivityOptions::$scheduleToCloseTimeout} to stop.
     * @param DateInterval|string|int|null $retryInitInterval Backoff interval for the first retry.
     *        If $retryBackoff is 1.0 then it is used for all retries.
     *        Int value in seconds.
     * @param DateInterval|string|int|null $retryMaxInterval Maximum backoff interval between retries.
     *        Exponential backoff leads to interval increase. This value is the cap of the interval.
     *        Int value in seconds.
     *        Default is 100x of $retryInitInterval.
     * @param float|null $retryBackoff Coefficient used to calculate the next retry backoff interval.
     *        The next retry interval is previous interval multiplied by this coefficient.
     *        Note: Must be greater than 1.0
     * @param list<class-string<Throwable>> $nonRetryables Non-retriable errors. Temporal server will stop retry
     *        if error type matches this list.
     * @param DateInterval|string|int $scheduleToStartTimeout Time activity can stay in task queue before it
     *        is picked up by a worker. If $scheduleToCloseTimeout is not provided then
     *        both this and $startToCloseTimeout are required.
     * @param DateInterval|string|int $startToCloseTimeout Maximum activity execution time after it was sent
     *        to a worker. If $scheduleToCloseTimeout is not provided then both this
     *        and $scheduleToStartTimeout are required.
     * @param DateInterval|string|int $scheduleToCloseTimeout Overall timeout workflow is willing to wait for
     *        activity to complete. It includes time in a task queue ($scheduleToStartTimeout) plus activity
     *        execution time ($startToCloseTimeout).
     *        Either this option or both $scheduleToStartTimeout and $startToCloseTimeout are required.
     * @param DateInterval|string|int $heartbeatTimeout
     * @param \Stringable|non-empty-string|null $activityId Business level activity ID, this is not needed
     *        for most of the cases. If you have to specify this, then talk to the temporal team.
     *        This is something will be done in the future.
     * @param int $cancellationType Whether to wait for canceled activity to be completed (activity can be failed,
     *        completed, cancel accepted). {@see \Temporal\Activity\ActivityCancellationType}
     *
     * @return T|ActivityProxy
     */
    public static function activity(
        string $activity,
        ?string $taskQueue = null,
        int $retryAttempts = 0,
        \DateInterval|string|int|null $retryInitInterval = null,
        \DateInterval|string|int|null $retryMaxInterval = null,
        ?float $retryBackoff = null,
        array $nonRetryables = [],
        \DateInterval|string|int $scheduleToStartTimeout = 0,
        \DateInterval|string|int $startToCloseTimeout = 0,
        \DateInterval|string|int $scheduleToCloseTimeout = 0,
        \DateInterval|string|int $heartbeatTimeout = 0,
        \Stringable|string|null $activityId = null,
        int $cancellationType = 0,
    ): object {
        // Retry options
        $retryOptions = RetryOptions::create(
            $retryAttempts,
            $retryInitInterval,
            $retryMaxInterval,
            $retryBackoff,
            $nonRetryables,
        );

        $options = ActivityOptions::new()->withRetryOptions($retryOptions);

        $taskQueue === null or $options = $options->withTaskQueue($taskQueue);
        // Timeouts
        $scheduleToStartTimeout === 0 or $options = $options->withScheduleToStartTimeout($scheduleToStartTimeout);
        $startToCloseTimeout === 0 or $options = $options->withStartToCloseTimeout($startToCloseTimeout);
        $scheduleToCloseTimeout === 0 or $options = $options->withScheduleToCloseTimeout($scheduleToCloseTimeout);
        $heartbeatTimeout === 0 or $options = $options->withHeartbeatTimeout($heartbeatTimeout);
        // Activity ID
        $activityId === null or $options = $options->withActivityId((string)$activityId);
        $cancellationType === null or $options = $options->withCancellationType($cancellationType);

        return Workflow::newActivityStub($activity, $options);
    }
}
