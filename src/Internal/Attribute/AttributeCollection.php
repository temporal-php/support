<?php

declare(strict_types=1);

namespace Temporal\Sugar\Internal\Attribute;

/**
 * @internal
 */
final class AttributeCollection
{
    /** @var array<class-string, list<object>> */
    private array $attributes;

    /**
     * @template T of object
     * @param array<class-string<T>, list<T>> $attributes
     */
    public function __construct(array $attributes)
    {
        // Additionally index attributes by class name
        $addedAttributes = [];
        foreach ($attributes as $list) {
            foreach ($list as $attribute) {
                if (\array_key_exists($attribute::class, $attributes)) {
                    continue;
                }

                $addedAttributes[$attribute::class] ??= [];
                $addedAttributes[$attribute::class][] = $attribute;
            }
        }

        $this->attributes = $attributes + $addedAttributes;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T|null
     */
    public function first(string $class): ?object
    {
        $this->makeIndex($class);
        /** @var T|null $result */
        $result = $this->attributes[$class][0] ?? null;
        return $result;
    }

    public function has(string $class): bool
    {
        return $this->count($this->attributes[$class]) > 0;
    }

    /**
     * @return int<0, max>
     */
    public function count(string $class): int
    {
        $this->makeIndex($class);
        return \count($this->attributes[$class]);
    }

    private function makeIndex(string $class): void
    {
        if (\array_key_exists($class, $this->attributes)) {
            return;
        }

        $result = [];
        foreach ($this->attributes as $list) {
            foreach ($list as $attribute) {
                if ($attribute instanceof $class) {
                    $result[] = $attribute;
                }
            }
        }

        $this->attributes[$class] = $result;
    }
}
