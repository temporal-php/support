<?php

declare(strict_types=1);

namespace Temporal\Sugar\Factory;

use DateInterval;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Client\WorkflowOptions;
use Temporal\Common\IdReusePolicy;
use Temporal\Internal\Client\WorkflowProxy;
use Temporal\Internal\Workflow\ChildWorkflowProxy;
use Temporal\Sugar\Attribute\RetryPolicy;
use Temporal\Sugar\Attribute\TaskQueue;
use Temporal\Sugar\Internal\Attribute\AttributeCollection;
use Temporal\Sugar\Internal\Attribute\AttributeForWorkflow;
use Temporal\Sugar\Internal\Attribute\AttributeReader;
use Temporal\Sugar\Internal\RetryOptions;
use Temporal\Workflow;
use Temporal\Workflow\ChildWorkflowCancellationType as ChildCancelType;
use Temporal\Workflow\ParentClosePolicy;
use Throwable;

final class WorkflowStub
{
    /**
     * Note: mustn't be used in a Workflow context. Client context only.
     *
     * @template T of object
     *
     * @param class-string<T> $workflow
     * @param non-empty-string|null $taskQueue
     * @param int<0, max>|null $retryAttempts Maximum number of attempts. When exceeded the retries stop even
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
     * @param DateInterval|string|int $startDelay Time to wait before dispatching the first Workflow task.
     *        If the Workflow gets a Signal before the delay, a Workflow task will be dispatched and the rest
     *        of the delay will be ignored. A Signal from {@see WorkflowClientInterface::startWithSignal()}
     *        won't trigger a workflow task. Cannot be set the same time as a $cronSchedule.
     *        Int value in seconds.
     * @param bool $eagerStart Eager Workflow Dispatch is a mechanism that minimizes the duration from
     *        starting a workflow to the processing of the first workflow task, making Temporal more suitable
     *        for latency sensitive applications.
     *        Eager Workflow Dispatch can be enabled if the server supports it and a local worker is available
     *        the task is fed directly to the worker.
     * @param \Stringable|string|null $workflowId If null, then UUID will be generated.
     * @param string|null $cronSchedule Optional cron schedule for workflow. {@see CronSchedule::$interval} for
     *        more info about cron format.
     * @param array<non-empty-string, mixed> $searchAttributes Specifies additional indexed information in result
     *        of list workflow.
     * @param list<mixed> $memo Specifies additional non-indexed information in result of list workflow.
     *
     * @return T|WorkflowProxy
     */
    public static function workflow(
        WorkflowClientInterface $workflowClient,
        string $workflow,
        ?string $taskQueue = null,
        ?int $retryAttempts = null,
        \DateInterval|string|int|null $retryInitInterval = null,
        \DateInterval|string|int|null $retryMaxInterval = null,
        ?float $retryBackoff = null,
        array $nonRetryables = [],
        \DateInterval|string|int $executionTimeout = 0,
        \DateInterval|string|int $runTimeout = 0,
        int $taskTimeout = 10,
        \DateInterval|string|int $startDelay = 0,
        bool $eagerStart = false,
        \Stringable|string|null $workflowId = null,
        IdReusePolicy $workflowIdReusePolicy = IdReusePolicy::Unspecified,
        ?string $cronSchedule = null,
        array $searchAttributes = [],
        array $memo = [],
    ): object {
        $attributes = self::readAttributes($workflow);

        // Retry options
        $retryOptions = RetryOptions::create(
            retryAttempts: $retryAttempts,
            retryInitInterval: $retryInitInterval,
            retryMaxInterval: $retryMaxInterval,
            retryBackoff: $retryBackoff,
            nonRetryables: $nonRetryables,
            attribute: $attributes->first(RetryPolicy::class),
        );

        $options = WorkflowOptions::new()->withRetryOptions($retryOptions);
        $taskQueue ??= $attributes->first(TaskQueue::class)?->name;
        // Start options
        $startDelay === 0 or $options = $options->withWorkflowStartDelay($startDelay);
        $eagerStart and $options = $options->withEagerStart(true);
        $cronSchedule === null or $options = $options->withCronSchedule($cronSchedule);
        // Timeouts
        $executionTimeout === 0 or $options = $options->withWorkflowExecutionTimeout($executionTimeout);
        $runTimeout === 0 or $options = $options->withWorkflowRunTimeout($executionTimeout);
        $taskTimeout !== 10 and $options = $options->withWorkflowTaskTimeout(\max(60, $taskTimeout));
        // Workflow ID
        $workflowId === null or $options = $options->withWorkflowId((string)$workflowId);
        $workflowIdReusePolicy === IdReusePolicy::Unspecified or $options = $options
            ->withWorkflowIdReusePolicy($workflowIdReusePolicy);
        // Metadata
        $searchAttributes === [] or $options = $options->withSearchAttributes($searchAttributes);
        $memo === [] or $options = $options->withMemo($memo);

        return $workflowClient->newWorkflowStub($workflow, $options);
    }

    /**
     * Note: must be used in Workflow context only.
     *
     * @template T of object
     *
     * @param class-string<T> $workflow
     * @param non-empty-string|null $taskQueue Task queue to use for workflow tasks. It should match a task queue
     *        specified when creating a {@see Worker} that hosts the workflow code.
     * @param non-empty-string|null $namespace Specify namespace in which workflow should be started.
     * @param int<0, max>|null $retryAttempts Maximum number of attempts. When exceeded the retries stop even
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
        ?int $retryAttempts = null,
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
        $attributes = self::readAttributes($workflow);

        // Retry options
        $retryOptions = RetryOptions::create(
            $retryAttempts,
            $retryInitInterval,
            $retryMaxInterval,
            $retryBackoff,
            $nonRetryables,
            attribute: $attributes->first(RetryPolicy::class),
        );

        $options = Workflow\ChildWorkflowOptions::new()->withRetryOptions($retryOptions);

        $taskQueue ??= $attributes->first(TaskQueue::class)?->name;
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
        $taskTimeout !== 10 and $options = $options->withWorkflowTaskTimeout(\max(60, $taskTimeout));
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
     * @param class-string $class Workflow class name.
     */
    private static function readAttributes(string $class): AttributeCollection
    {
        return AttributeReader::collectionFromClass($class, [AttributeForWorkflow::class]);
    }
}
