<?php

declare(strict_types=1);

namespace Temporal\Support\Internal;

use DateInterval;
use Temporal\Support\Attribute\RetryPolicy as RetryPolicyAttribute;
use Throwable;

/**
 * @internal
 */
final class RetryOptions
{
    /**
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
     * @param list<class-string<Throwable>> $nonRetryables Non-retryable errors. Temporal server will stop retry
     *        if error type matches this list.
     * @param RetryPolicyAttribute|null $attribute Fallback values from the attribute.
     */
    public static function create(
        ?int $retryAttempts = null,
        \DateInterval|string|int|null $retryInitInterval = null,
        \DateInterval|string|int|null $retryMaxInterval = null,
        ?float $retryBackoff = null,
        array $nonRetryables = [],
        ?RetryPolicyAttribute $attribute = null,
    ): \Temporal\Common\RetryOptions {
        if ($attribute !== null) {
            $retryAttempts ??= $attribute->attempts;
            $retryInitInterval ??= $attribute->initInterval;
            $retryMaxInterval ??= $attribute->maxInterval;
            $retryBackoff ??= $attribute->backoff;
            $nonRetryables = \array_unique(\array_merge($nonRetryables, $attribute->nonRetryables));
        }

        $retryOptions = \Temporal\Common\RetryOptions::new();
        $retryAttempts === 0 or $retryOptions = $retryOptions->withMaximumAttempts($retryAttempts);
        $retryInitInterval === 0 or $retryOptions = $retryOptions->withInitialInterval($retryInitInterval);
        $retryMaxInterval === 0 or $retryOptions = $retryOptions->withMaximumInterval($retryMaxInterval);
        $retryBackoff >= 1.0 and $retryOptions = $retryOptions->withBackoffCoefficient($retryBackoff);
        $nonRetryables === [] or $retryOptions = $retryOptions->withNonRetryableExceptions($nonRetryables);
        return $retryOptions;
    }
}
