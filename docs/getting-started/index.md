# Getting started

## Installation

```sh
composer require innmind/async
```

## Setup

```php title="async.php"
<?php
declare(strict_types = 1);

require 'path/to/vendor/autoload.php';

use Innmind\Async\{
    Scheduler,
    Scope\Continuation,
};
use Innmind\OperatingSystem\{
    Factory,
    OperatingSystem,
};

$os = Factory::build();
Scheduler::of($os)
    ->sink(null) #(1)
    ->with(function(
        $_, #(2)
        OperatingSystem $os,
        Continuation $continuation,
    ): Continuation {
        // this is the scope that will schedule tasks

        return $continuation;
    });
```

1. You'll learn in a [later chapter](../scopes/lifecycle.md#carry-a-value) what this value is. For now leave it like this.
2. You'll learn in a [later chapter](../scopes/lifecycle.md#carry-a-value) what this value is. For now leave it like this.

You can run this script via `php async.php` in your terminal. For now it executes an infinite loop that does nothing.

You'll see in the next chapter what you can inside the loop.
