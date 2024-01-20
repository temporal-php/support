<?php

declare(strict_types=1);

namespace Temporal\Sugar\Internal;

/**
 * @internal
 */
final class AttributeReader
{
    /**
     * @param class-string $class
     * @param list<class-string> $attributes
     *
     * @return array<class-string, list<object>>
     */
    public static function fromClass(
        string $class,
        array $attributes,
        bool $merge = true,
        bool $inheritance = true,
        bool $interfaces = true,
    ): array {
        $reflection = new \ReflectionClass($class);
        if ($reflection->isInternal()) {
            return [];
        }

        $result = self::fetchAttributes($reflection, $attributes);

        if (!$inheritance) {
            return $result;
        }

        if ($parent = $reflection->getParentClass()) {
            $attrs = self::fromClass($parent->getName(), $attributes, $merge, true, false);
            $result = $merge
                ? \array_merge_recursive($result, $attrs)
                : $result + $attrs;
        }

        if (!$interfaces) {
            return $result;
        }

        foreach (self::sortInterfaces($reflection) as $interface) {
            $attrs = self::fromClass($interface, $attributes, $merge, false, false);
            $result = $merge
                ? \array_merge_recursive($result, $attrs)
                : $result + $attrs;
        }

        return $result;
    }

    /**
     * @param \ReflectionClass $reflection
     * @param array<class-string> $filter
     * @return array<class-string, list<object>>
     */
    private static function fetchAttributes(\ReflectionClass $reflection, array $filter): array
    {
        $className = $reflection->getName();
        /** @var array<class-string, array<class-string, list<object>>> $cache */
        static $cache = [];

        $result = [];
        $cache[$className] ??= [];
        $classAttrs = &$cache[$className];

        foreach ($filter as $attributeClass) {
            if (\array_key_exists($attributeClass, $classAttrs)) {
                $result[$attributeClass] = $classAttrs[$attributeClass];
                continue;
            }

            $attrs = $reflection->getAttributes($attributeClass, \ReflectionAttribute::IS_INSTANCEOF);
            $result[$attributeClass] = $classAttrs[$attributeClass] = \array_map(
                static fn(\ReflectionAttribute $attribute) => $attribute->newInstance(),
                $attrs
            );
            unset($attrs);
        }

        return $result;
    }

    private static function sortInterfaces(\ReflectionClass $class): array
    {
        $result = [];
        foreach ($class->getInterfaces() as $reflection) {
            $result[$reflection->getName()] = \count($reflection->getInterfaces());
        }

        \arsort($result);

        return \array_keys($result);
    }
}
