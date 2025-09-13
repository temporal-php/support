<?php

declare(strict_types=1);

namespace Temporal\Support\Tests\Unit\Factory;

use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Temporal\Client\WorkflowOptions;
use Temporal\Support\Factory\WorkflowStub;
use Temporal\Support\Tests\Stub\Workflow\AttributedWithoutInterface;
use Temporal\Workflow;
use Temporal\Workflow\ChildWorkflowStubInterface;

final class ChildWorkflowStubTest extends TestCase
{
    protected function setUp(): void
    {
        $stub = $this->createStub(Workflow\WorkflowContextInterface::class);
        $stub->method('newChildWorkflowStub')
            ->willReturnCallback(function ($class, $options) {
                return (object)[
                    'class' => $class,
                    'options' => $options,
                ];
            });
        Workflow::setCurrentContext($stub);
        parent::setUp();
    }

    public function testDefaultsFromAttributes(): void
    {
        /** @var WorkflowOptions $options */
        $options = WorkflowStub::childWorkflow(
            type: AttributedWithoutInterface::class,
        )->options;

        $this->assertSame('test-queue', $options->taskQueue);
        $this->assertSame(3, $options->retryOptions->maximumAttempts);
        $this->assertSame(10.0, $options->retryOptions->backoffCoefficient);
        $this->assertSame([RuntimeException::class], $options->retryOptions->nonRetryableExceptions);
        $this->assertSame('0.5.0', $options->retryOptions->initialInterval->format('%i.%s.%f'));
        $this->assertSame('8.20.0', $options->retryOptions->maximumInterval->format('%i.%s.%f'));
    }

    public function testAttributeOverrides(): void
    {
        /** @var WorkflowOptions $options */
        $options = WorkflowStub::childWorkflow(
            type: AttributedWithoutInterface::class,
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
        $this->assertSame('0.10.0', $options->retryOptions->initialInterval->format('%i.%s.%f'));
        $this->assertSame('3.20.0', $options->retryOptions->maximumInterval->format('%i.%s.%f'));
    }

    public function testUntypedChildCreated(): void
    {
        $wf = WorkflowStub::childWorkflow(type: 'foo-bar');

        self::assertInstanceOf(ChildWorkflowStubInterface::class, $wf);
    }
}
