<?php

declare(strict_types=1);

namespace Factory;

use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Client\WorkflowOptions;
use Temporal\Client\WorkflowStubInterface;
use Temporal\Support\Factory\WorkflowStub;
use Temporal\Support\Tests\Stub\Workflow\AttributedWithoutInterface;

final class WorkflowStubTest extends TestCase
{
    public function testDefaultsFromAttributes(): void
    {
        $input = new class {
            public string $type;
            public WorkflowOptions $options;
        };

        $clientMock = self::createMock(WorkflowClientInterface::class);
        $clientMock->method('newWorkflowStub')
            ->willReturnCallback(function (string $class, WorkflowOptions $options) use ($input) {
                $input->type = $class;
                $input->options = $options;
                return $input;
            });
        WorkflowStub::workflow(
            $clientMock,
            type: AttributedWithoutInterface::class,
        );

        self::assertSame(AttributedWithoutInterface::class, $input->type);
        self::assertSame('test-queue', $input->options->taskQueue);
        self::assertSame(3, $input->options->retryOptions->maximumAttempts);
        self::assertSame(10.0, $input->options->retryOptions->backoffCoefficient);
        self::assertEquals(
            [RuntimeException::class],
            $input->options->retryOptions->nonRetryableExceptions,
        );
        self::assertSame('0.5.0', $input->options->retryOptions->initialInterval->format('%i.%s.%f'));
        self::assertSame('8.20.0', $input->options->retryOptions->maximumInterval->format('%i.%s.%f'));
    }

    public function testAttributeOverrides(): void
    {
        $input = new class {
            public string $type;
            public WorkflowOptions $options;
        };

        $clientMock = self::createMock(WorkflowClientInterface::class);
        $clientMock->method('newWorkflowStub')
            ->willReturnCallback(function (string $class, WorkflowOptions $options) use ($input) {
                $input->type = $class;
                $input->options = $options;
                return $input;
            });
        WorkflowStub::workflow(
            $clientMock,
            type: AttributedWithoutInterface::class,
            taskQueue: 'test-queue-override',
            retryAttempts: 0,
            retryInitInterval: 10,
            retryMaxInterval: 200,
            retryBackoff: 5.0,
            nonRetryables: [LogicException::class],
        );

        self::assertSame(AttributedWithoutInterface::class, $input->type);
        self::assertSame('test-queue-override', $input->options->taskQueue);
        self::assertSame(0, $input->options->retryOptions->maximumAttempts);
        self::assertSame(5.0, $input->options->retryOptions->backoffCoefficient);
        self::assertEquals(
            [LogicException::class, RuntimeException::class],
            $input->options->retryOptions->nonRetryableExceptions,
        );
        self::assertSame('0.10.0', $input->options->retryOptions->initialInterval->format('%i.%s.%f'));
        self::assertSame('3.20.0', $input->options->retryOptions->maximumInterval->format('%i.%s.%f'));
    }

    public function testUntypedWorkflowCreated(): void
    {
        $clientMock = self::createMock(WorkflowClientInterface::class);
        $wf = WorkflowStub::workflow($clientMock, 'foo-bar');

        self::assertInstanceOf(WorkflowStubInterface::class, $wf);
    }
}
