# Terminology

## Scheduler

A `Scheduler` is the object responsible to coordonate the execution of a [Scope](#scope) and the [Tasks](#task) it schedule.

When trying to execute the scope and the tasks it will look for why them have been suspended. A scope or the tasks can be suspended when asking for the process to halt or watching for IO (files or sockets). These suspensions happen through the use of [`innmind/operating-system`](https://innmind.org/OperatingSystem/).

In order to create a scheduler you need an instance of this operating system. By default this abstraction is synchronous. It's the job of the scheduler to create copies of this operating system object and pass them to the scope and tasks.

## Scope

A Scope is a function, run asynchronously, responsible to scheduling new asynchronous [Tasks](#task). It can do so indefinitively, the default behaviour, or choose to either stop and let the tasks finish or ask to be called again once a task result is available.

The scope is also responsible to [carry a value](#carried-value). Each time the scope is called it has access to the last carried value and has the possibility to change its value for the next call.

The scope is run asynchronously because it will usually be the place you'll watch for a socket server to accept new connections and schedule tasks to handle these connections. Or a more simpler case it to build it as a timer that will shedule tasks every X amount of time.

## Task

A Task is a function that must accept an instance of the [operating system](https://innmind.org/OperatingSystem/). As described above, it's thanks to this object that the function can run asynchronously.

If a function never uses this operating system object, then it **cannot** run asynchronously.

Like any other function it can return any value. When it does, the value will be made available to the scope the next time it's called.

## Carried value

A carried value can be any PHP variable. This value is passed to the [Scope](#scope) each time this function is called, and can change it for the next call.

When the scope decides to finish running, the [Scheduler](#scheduler) will return the last value specified by the scope.

You can use this value to gather the tasks results, compute a new value or print to [the console](https://innmind.org/CLI/).
