<?php

declare(strict_types=1);

namespace Temporal\Sugar\Stub;

use DateInterval;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Client\WorkflowOptions;
use Temporal\Common\IdReusePolicy;
use Temporal\Internal\Client\WorkflowProxy;
use Temporal\Sugar\Internal\RetryOptions;
use Throwable;

/**
 * Note: mustn't be used in a Workflow context.
 */
final class ClientFactory
{
    /**
     * @template T of object
     *
     * @param class-string<T> $workflow
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
        int $retryAttempts = 0,
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
        // Retry options
        $retryOptions = RetryOptions::create(
            $retryAttempts,
            $retryInitInterval,
            $retryMaxInterval,
            $retryBackoff,
            $nonRetryables,
        );

        $options = WorkflowOptions::new()->withRetryOptions($retryOptions);
        $taskQueue === null or $options = $options->withTaskQueue($taskQueue);
        // Start options
        $startDelay === 0 or $options = $options->withWorkflowStartDelay($startDelay);
        $eagerStart and $options = $options->withEagerStart(true);
        $cronSchedule === null or $options = $options->withCronSchedule($cronSchedule);
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

        return $workflowClient->newWorkflowStub($workflow, $options);
    }
}
