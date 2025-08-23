---
hide:
    - navigation
---

# Limitations

!!! warning ""
    The limitations mentionned below exists for now as fixing them will take time. But they can be overcome, and will be in due time!

## HTTP calls

Currently HTTP calls are done via `curl` but it can't be integrated in the same loop as other streams. To allow the coordination of multiple tasks when doing HTTP calls the system use a timeout of `10ms` and switches between tasks at this max rate.

To fix this limitation a new implementation entirely based on PHP streams needs to be created.

Meanwhile if your goal is to make multiple concurrent HTTP calls you don't need this package. [`innmind/http-transport`](https://innmind.org/documentation/getting-started/concurrency/http/) already supports concurrent calls on it's own (without the limitation mentionned above).

## SQL queries

SQL queries executed via `$os->remote()->sql()` are still executed synchronously (as it uses `PDO`).

To fix this limitation a new implementation entirely based on PHP streams needs to be created.

## Scaling

It seems that the current implementation of this package has a [limit of around 100K concurrent tasks](https://bsky.app/profile/baptouuuu.bsky.social/post/3lwr7pei2ek2f) before it starts slowing down.

A simple script scheduling 100k tasks that each halts the process for 10 second will take ~13s.
