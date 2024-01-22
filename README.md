<div align="center">
    <h1 align="center">Temporal PHP Support</h1>
    <div>Enhance your development experience with Temporal</div>
</div>

<br />

The package includes attributes, helpers, factories, interfaces, interceptors, 
etc. to enhance the developer experience when using the [Temporal PHP SDK](https://github.com/temporalio/sdk-php).


- [Installation](#installation)
- [Usage](#usage)
- [Contributing](#contributing)

## Installation

To install the package in your PHP application, add it as a dev dependency
to your project using Composer:

```bash
composer require temporal-php/support
```

[![PHP](https://img.shields.io/packagist/php-v/temporal-php/support.svg?style=flat-square&logo=php)](https://packagist.org/packages/temporal-php/support)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/temporal-php/support.svg?style=flat-square&logo=packagist)](https://packagist.org/packages/temporal-php/support)
[![License](https://img.shields.io/packagist/l/temporal-php/support.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/temporal-php/support.svg?style=flat-square)](https://packagist.org/packages/temporal-php/support)


## Usage

### Activity and Worker factories

### VirtualPromise interface

Every time we use `yield` in a Workflow to wait for an action to complete, a Promise is actually yielded.
At this point, the IDE and static analyzer usually get lost in type definitions,
and we experience difficulties and inconveniences because of this.
However, if the Promise interface had the `@yield` annotation, we could explain to the IDE what type of value we expect to be sent back into the generator from the coroutine.
Since ReactPHP [isn't yet planning](https://github.com/orgs/reactphp/discussions/536) to add the `@yield` annotation to their promises (Temporal PHP uses ReactPHP promises),
we suggest using our solution for typing - `VirtualPromise`.

```php
use Temporal\Support\VirtualPromise;

#[\Temporal\Activity\ActivityInterface]
class HelloService {
    /**
     * @param non-empty-string $name
     *
     * @return VirtualPromise<non-empty-string>
     */
    public function greet(string $name) {
        // ...
    }
}

#[\Temporal\Workflow\WorkflowInterface]
class WorkflowClass {
    #[\Temporal\Workflow\WorkflowMethod]
    public function run(string $name) {
        $activity = \Temporal\Support\Factory\ActivityStub::activity(HelloService::class);

        // IDE will know that $name is a non-empty-string
        $name = yield $activity->greet($name);
        // ...
    }
}
```

> Warning: don't implement the `VirtualPromise` interface yourself, use it only as a type hint.

> PHPStorm and Psalm can handle the @yield annotation, but PHPStan can't yet ([issue](https://github.com/phpstan/phpstan/issues/4245)).

### Attributes

## Contributing

We believe in the power of community-driven development. Here's how you can contribute:

- **Report Bugs:** Encounter a glitch? Let us know on our [issue tracker](https://github.com/temporal-php/support/issues).
- **Feature Suggestions:** Have ideas to improve the package? [Create a feature request](https://github.com/temporal-php/support/issues)!
- **Code Contributions:** Submit a pull request to help us improve the codebase. You can find a list of
  issues labeled "help wanted" [here](https://github.com/temporal-php/support/issues?q=is%3Aopen+is%3Aissue+label%3A%22help+wanted%22).
- **Spread the Word:** Share your experience with the package on social media and encourage others to contribute. 
- **Donate:** Support our work by becoming a patron or making a one-time donation  
  [![roxblnfk](https://img.shields.io/endpoint.svg?url=https%3A%2F%2Fshieldsio-patreon.vercel.app%2Fapi%3Fusername%3Droxblnfk%26type%3Dpatrons&label=roxblnfk&style=flat-square)](https://patreon.com/roxblnfk)
  [![butschster](https://img.shields.io/endpoint.svg?url=https%3A%2F%2Fshieldsio-patreon.vercel.app%2Fapi%3Fusername%3Dbutschster%26type%3Dpatrons&label=butschster&style=flat-square)](https://patreon.com/butschster)




<!--

Quality badges:

[![Tests Status](https://img.shields.io/github/actions/workflow/status/temporal-php/support/testing.yml?label=tests&style=flat-square)](https://github.com/temporal-php/support/actions/workflows/testing.yml?query=workflow%3Atesting%3Amaster)
[![Dependency status](https://php.package.health/packages/temporal-php/support/dev-master/status.svg)](https://php.package.health/packages/temporal-php/support/dev-master)

# (tests coverage)
# (types coverage)
# (psalm level)
# (static analysis)
# (mutation)
# (scrutinizer score)
# (code style)
-->
