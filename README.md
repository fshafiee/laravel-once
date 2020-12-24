
# Laravel Once - Task rollup for Laravel framework
This package allows defering tasks to the end of the request. It also rolls identical tasks so that they are processed effectively once.

## What prompted the development of this package?

There are cases where the initialization process of a resource creates and touches many other related resources. In addition, it's very common to have listeners setup on those resources in order to trigger and handle side-effects. This combination of chunky operation and granular event management can introduce some issues:

1. Some of these events may result in an identical side-effct. Without a rollup stage, application resources are wasted on doing redundant operation.
2. In some cases those side-effects that are triggered early in the operation, and may access uninitialized data. This can cause issues and bugs that are hard to track.
3. These side-effects themselves could trigger procedures in other systems (a PubSub system). We don't want to trigger their operation prematurely.

In addition:

1. Silencing events and retriggering them manually would mess up the codebase and introduce all kinds of logic branches that were hard to test and maintain.
2. Queuing and sending the operations to background won't remedy any of the aforementioned problems. It would only improve the preceived performance of API calls (which is good practice to adopt, regardless).

These observation warranted a rollup pattern to make sure side-effects are process only once in context of a request. And since these are sideffects, we know that the response of the request should not depend on them.

---

## How To

First, you need to define a rollable task by extending `Laravel\Once\Tasks\AutoDispatchedTask`. You need to define `__construct` and the `perform` methods. Any dependencies that you might have during the for `perform` method, but be passed to `__construct` method as a dependency.

```php

namespace App\Jobs\Rollables;

use App\Jobs\UpdateAuthorCache;
use App\Models\Author;
use Laravel\Once\Tasks\AutoDispatchedTask;

class UpdateAuthorCacheRollableTask extends AutoDispatchedTask
{
    public $authorId;

    public function __construct(string $authorId)
    {
        /**
         * Make sure parent::_construct() method is called.
         * or else the task won't be automatically added
         * to the task backlog, and you'd need to add it manually
         * by resolve the service.
         */
        parent::__construct();
        $this->authorId = $authorId;
    }

    public function perform()
    {
        $author = Author::find($this->authorId);
        if ($author) {
            UpdateAuthorCache::update($author);
            /**
             * You could also dispatch to a queue in order to process it
             * asynchronously. It depends on your design.
             */
        }
    }
}
```

We can then instantiate new rollable task. Since our tasks are extending `AutoDispatchedRollableTask`, they'll automatically be added to task backlog.

```php
use App\Jobs\Rollables\UpdateAuthorCacheRollableTask;
use App\Events\SomeAuthorRelatedEvent;

class SomeListener
{
    public function handle(SomeAuthorRelatedEvent $event)
    {
        new UpdateAuthorCacheRollableTask($event->getAuthorId());
    }
}
```

If a new task with identical dependencies is provided to `OnceService->add` method (e.g. two `UpdateAuthorCacheRollableTask` with same `$authorId`), it will be filtered before tasks are committed, which prevents redundant execution of the same logic. Otherwise, it will be processed as it is considered to be different unit of operation.

The backlog of work units are processed when `OnceSerivce->commit` method is called, which happens at the end of request lifecycle in `OnceMiddleware`.

### Caveats
In command line environments, or Job(where HTTP request lifecycle is not available), `OnceSerivce->commit` should be called manually.

```php
resolve(OnceSerivce::class)->commit();
```

This is already handled for queued jobs if you use the service provider of the package:

```php
Queue::after(function (JobProcessed $event) {
    resolve(OnceService::class)->commit();
});
```

---

## A note on Laravel 8.x unique jobs

While [Laravel 8.x supports unique jobs](https://laravel.com/docs/8.x/queues#unique-jobs), it still does not satisfy our requirements:

1. It's only concerned with what's in the queue which is only effective for slow queues (not enough consumers, delayed jobs, etc.).
2. We can still end up processing jobs for resources that has not been fully initialized yet (before the transaction is complete).
3. It does not address the concern synchronous operations, since only queued operations can benefit from it, with the caveat mentioned in first point.

There's also the matter queue driver support, which is irrelevant for this package.

As result, these two approaches are complementary to each other, addressing similar but different aspects of "effectively-once processing".


