<?php

declare(strict_types=1);

namespace Temporal\Sugar\Tests\Unit\Factory;

use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Temporal\Sugar\Factory\ActivityStub;
use Temporal\Sugar\Tests\Stub\Activity\AttributedWithoutInterface;
use Temporal\Workflow;

final class ActivityStubTest extends TestCase
{
    protected function setUp(): void
    {
        $stub = $this->createStub(Workflow\WorkflowContextInterface::class);
        $stub->method('newActivityStub')
            ->willReturnCallback(function ($class, $options) {
                return (object)[
                    'class' => $class,
                    'options' => $options,
                ];
            });
        Workflow::setCurrentContext($stub);
        parent::setUp();
    }

    public function testDefaultsFromAttributes()
    {
        /** @var \Temporal\Activity\ActivityOptions $options */
        $options = ActivityStub::activity(
            activity: AttributedWithoutInterface::class,
        )->options;

        $this->assertSame('test-queue', $options->taskQueue);
        $this->assertSame(3, $options->retryOptions->maximumAttempts);
        $this->assertSame(10.0, $options->retryOptions->backoffCoefficient);
        $this->assertSame([RuntimeException::class], $options->retryOptions->nonRetryableExceptions);
        $this->assertSame('5.0', $options->retryOptions->initialInterval->format('%s.%f'));
        $this->assertSame('500.0', $options->retryOptions->maximumInterval->format('%s.%f'));
    }

    public function testAttributeOverrides()
    {
        /** @var \Temporal\Activity\ActivityOptions $options */
        $options = ActivityStub::activity(
            activity: AttributedWithoutInterface::class,
            taskQueue: 'test-queue-override',
            retryAttempts: 0,
            retryInitInterval: 10,
            retryMaxInterval: 200,
            retryBackoff: 5.0,
            nonRetryables: [LogicException::class],
        )->options;

        $this->assertSame('test-queue-override', $options->taskQueue);
        $this->assertSame(0, $options->retryOptions->maximumAttempts);
        $this->assertSame(5.0, $options->retryOptions->backoffCoefficient);
        $this->assertEquals(
            [LogicException::class, RuntimeException::class],
            $options->retryOptions->nonRetryableExceptions,
        );
        $this->assertSame('10.0', $options->retryOptions->initialInterval->format('%s.%f'));
        $this->assertSame('200.0', $options->retryOptions->maximumInterval->format('%s.%f'));
    }
}
