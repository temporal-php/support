<?php

declare(strict_types=1);

namespace Temporal\Sugar\Tests\Unit\Internal;

use PHPUnit\Framework\TestCase;
use Temporal\Sugar\Attribute\TaskQueue;
use Temporal\Sugar\Internal\AttributeReader;

class AttributeReaderTest extends TestCase
{
    public function testFromClass(): void
    {
        $result = AttributeReader::fromClass(
            \Temporal\Sugar\Tests\Stub\Attributed\SimpleClass::class,
            [TaskQueue::class]
        );

        $this->assertArrayHasKey(TaskQueue::class, $result);
        $this->assertIsArray($result[TaskQueue::class]);
        $this->assertCount(1, $result[TaskQueue::class]);
        $this->assertInstanceOf(TaskQueue::class, $result[TaskQueue::class][0]);
        $this->assertEquals('test-queue', $result[TaskQueue::class][0]->name);
    }

    public function testFromExtendedClassWithInheritanceWithMerge(): void
    {
        $result = AttributeReader::fromClass(
            \Temporal\Sugar\Tests\Stub\Attributed\ExtendedAttributed::class,
            [TaskQueue::class],
            merge: true,
        );

        $this->assertArrayHasKey(TaskQueue::class, $result);
        $this->assertIsArray($result[TaskQueue::class]);
        $this->assertCount(5, $result[TaskQueue::class]);
        $this->assertInstanceOf(TaskQueue::class, $result[TaskQueue::class][0]);
        $this->assertInstanceOf(TaskQueue::class, $result[TaskQueue::class][1]);
        $this->assertInstanceOf(TaskQueue::class, $result[TaskQueue::class][3]);
        $this->assertEquals('test-queue-extended', $result[TaskQueue::class][0]->name);
        $this->assertEquals('test-queue-abstract', $result[TaskQueue::class][1]->name);
        $this->assertEquals('test-queue-interface', $result[TaskQueue::class][2]->name);
        $this->assertEquals('test-queue-parent-interface', $result[TaskQueue::class][3]->name);
        $this->assertEquals('test-queue-parent-parent-interface', $result[TaskQueue::class][4]->name);
    }

    public function testFromInterfaceWithInheritance(): void
    {
        $result = AttributeReader::fromClass(
            \Temporal\Sugar\Tests\Stub\Attributed\InterfaceAttributed::class,
            [TaskQueue::class],
            merge: true,
        );

        $this->assertArrayHasKey(TaskQueue::class, $result);
        $this->assertIsArray($result[TaskQueue::class]);
        $this->assertCount(3, $result[TaskQueue::class]);
        $this->assertInstanceOf(TaskQueue::class, $result[TaskQueue::class][0]);
        $this->assertInstanceOf(TaskQueue::class, $result[TaskQueue::class][1]);
        $this->assertInstanceOf(TaskQueue::class, $result[TaskQueue::class][2]);
        $this->assertEquals('test-queue-interface', $result[TaskQueue::class][0]->name);
        $this->assertEquals('test-queue-parent-interface', $result[TaskQueue::class][1]->name);
        $this->assertEquals('test-queue-parent-parent-interface', $result[TaskQueue::class][2]->name);
    }
}
