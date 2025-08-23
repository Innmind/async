# Config

## Limit tasks concurrency

By default all the tasks scheduled by a scope will be started immediately. But depending on the number you'll schedule this can take a lot of resources. To avoid the process taking too much RAM you can limit the number of tasks being run at a point in time

```php hl_lines="2"
Scheduler::of($os)
    ->limitConcurrencyTo($size)
    ->sink(null)
    ->with(new Scope);
```

`#!php $size` can be any int above `#!php 2`. As soon a task finished it will pick a new one from the previously scheduled ones.

??? info
    As a point of reference, a simple script sheduling 100k tasks that halt the process for 10s will take 1.9Go of RAM (1M tasks will use 19Go).
