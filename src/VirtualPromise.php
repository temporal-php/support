<?php

declare(strict_types=1);

namespace Temporal\Support;

use React\Promise\PromiseInterface;

/**
 * @template T
 * @yield T
 * @extends PromiseInterface<T>
 *
 * Don't implement this interface and don't use it in a business logic, it is just for type hinting.
 *
 * Use the interface as a return type in activities to have type hints in workflows.
 * Supported by Psalm and PHPStorm.
 *
 * Example:
 *
 * ```php
 * #[ActivityInterface]
 * class MyActivity {
 *     /**
 *      * @param non-empty-string $name
 *      * @return VirtualPromise<non-empty-string>
 *      *\/
 *     #[ActivityMethod]
 *     public function greet(string $name) {
 *         return "Hello, {$name}!";
 *     }
 * }
 * ```
 */
interface VirtualPromise extends PromiseInterface
{
}
