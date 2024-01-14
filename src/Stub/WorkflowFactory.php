<?php

declare(strict_types=1);

namespace Temporal\Sugar\Stub;

use DateInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\IdReusePolicy;
use Temporal\Internal\Workflow\ActivityProxy;
use Temporal\Internal\Workflow\ChildWorkflowProxy;
use Temporal\Sugar\Internal\RetryOptions;
use Temporal\Workflow;
use Temporal\Workflow\ChildWorkflowCancellationType as ChildCancelType;
use Temporal\Workflow\ParentClosePolicy;
use Throwable;

/**
 * Note: must be used in Workflow context only.
 */
final class WorkflowFactory
{
    /**
     * @template T of object
     *
     * @param class-string<T> $workflow
     * @param non-empty-string|null $taskQueue Task queue to use for workflow tasks. It should match a task queue
     *        specified when creating a {@see Worker} that hosts the workflow code.
     * @param non-empty-string|null $namespace Specify namespace in which workflow should be started.
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
     * @param DateInterval|string|int $executionTimeout The maximum time that parent workflow is willing to wait
     *        for a child execution (which includes retries and continue as new calls).
     *        If exceeded the child is automatically terminated by the Temporal service.
     *        Int value in seconds.
     * @param DateInterval|string|int $runTimeout The time after which workflow run is automatically terminated by
     *        the Temporal service.
     *        Do not rely on the run timeout for business level timeouts.
     *        It is preferred to use in workflow timers for this purpose.
     *        Int value in seconds.
     * @param int<10, 60> $taskTimeout Maximum execution time of a single workflow task. Int value in seconds.
     *        Default is 10 seconds. The maximum accepted value is 60 seconds.
     * @param ParentClosePolicy::POLICY_* $parentClosePolicy
     * @param ChildCancelType::WAIT_CANCELLATION_COMPLETED|ChildCancelType::TRY_CANCEL $childCancellationType In case
     *        of a child workflow cancellation it fails with a {@see FailedCancellationException}.
     *        The type defines at which point the exception is thrown.
     * @param \Stringable|string|null $workflowId If null, then UUID will be generated.
     * @param string|null $cronSchedule Optional cron schedule for workflow. {@see CronSchedule::$interval} for
     *        more info about cron format.
     * @param array<non-empty-string, mixed> $searchAttributes Specifies additional indexed information in result
     *        of list workflow.
     * @param list<mixed> $memo Specifies additional non-indexed information in result of list workflow.
     *
     * @return T|ChildWorkflowProxy
     */
    public static function childWorkflow(
        string $workflow,
        ?string $taskQueue = null,
        ?string $namespace = null,
        int $retryAttempts = 0,
        \DateInterval|string|int|null $retryInitInterval = null,
        \DateInterval|string|int|null $retryMaxInterval = null,
        ?float $retryBackoff = null,
        array $nonRetryables = [],
        \DateInterval|string|int $executionTimeout = 0,
        \DateInterval|string|int $runTimeout = 0,
        int $taskTimeout = 10,
        int $parentClosePolicy = ParentClosePolicy::POLICY_UNSPECIFIED,
        int $childCancellationType = ChildCancelType::TRY_CANCEL,
        \Stringable|string|null $workflowId = null,
        IdReusePolicy $workflowIdReusePolicy = IdReusePolicy::Unspecified,
        ?string $cronSchedule = null,
        array $searchAttributes = [],
        array $memo = [],
    ): object {
        // Retry options
        $retryOptions = RetryOptions::create(
            $retryAttempts,
            $retryInitInterval,
            $retryMaxInterval,
            $retryBackoff,
            $nonRetryables,
        );

        $options = Workflow\ChildWorkflowOptions::new()->withRetryOptions($retryOptions);

        $taskQueue === null or $options = $options->withTaskQueue($taskQueue);
        $namespace === null or $options = $options->withNamespace($namespace);
        // Start and close options
        $cronSchedule === null or $options = $options->withCronSchedule($cronSchedule);
        $parentClosePolicy === ParentClosePolicy::POLICY_UNSPECIFIED or $options = $options
            ->withParentClosePolicy($parentClosePolicy);
        $childCancellationType === ChildCancelType::TRY_CANCEL or $options = $options
            ->withChildWorkflowCancellationType($childCancellationType);

        // Timeouts
        $executionTimeout === 0 or $options = $options->withWorkflowExecutionTimeout($executionTimeout);
        $runTimeout === 0 or $options = $options->withWorkflowRunTimeout($executionTimeout);
        $taskTimeout > 10 and $options = $options->withWorkflowTaskTimeout(\max(60, $taskTimeout));
        // Workflow ID
        $workflowId === null or $options = $options->withWorkflowId((string)$workflowId);
        $workflowIdReusePolicy === IdReusePolicy::Unspecified or $options = $options
            ->withWorkflowIdReusePolicy($workflowIdReusePolicy);
        // Metadata
        $searchAttributes === [] or $options = $options->withSearchAttributes($searchAttributes);
        $memo === [] or $options = $options->withMemo($memo);

        return Workflow::newChildWorkflowStub($workflow, $options);
    }

    /**
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
