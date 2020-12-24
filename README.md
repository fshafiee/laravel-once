# Laravel Once - Task rollup for Laravel framework
![PHP Composer](https://github.com/fshafiee/laravel-once/workflows/PHP%20Composer/badge.svg)

This package allows defering tasks to the end of the request. It also rolls up identical tasks so that they are processed effectively once.

## What prompted the development of this package?

There are cases where the initialization process of a resource creates and touches many other related resources.
In addition, it's very common to have listeners setup on those resources in order to trigger and handle side-effects.
This combination of chunky operation and granular event management can introduce some issues:

1. Some of these events may result in an identical side-effct. Without a rollup stage, application resources are wasted on doing redundant operation.
2. In some cases those side-effects that are triggered early in the operation, and may access uninitialized data. This can cause issues and bugs that are hard to track.
3. These side-effects themselves could trigger procedures in other systems (a PubSub system). We don't want to trigger their operation prematurely.

In addition:

1. Silencing events and retriggering them manually would mess up the codebase and introduce all kinds of logic branches that were hard to test and maintain.
2. Queuing and sending the operations to background won't remedy any of the aforementioned problems. It would only improve the preceived performance of API calls (which is good practice to adopt, regardless).

These observation warranted a rollup pattern to make sure side-effects are process only once in context of a request.
And since these are sideffects, we know that the response of the request should not depend on them.

---

## Installing the package

Add the package to your project via composer:

```bash
composer require fshafiee/laravel-once
```

## Defining Rollable Tasks

First, you need to define a rollable task by extending `Laravel\Once\Tasks\AutoDispatchedTask`.
You must define `__construct` and `perform` methods.
Any dependencies that the task has to fulfill it operation during `perform`,
must be be passed to `__construct` method and assigned to an istance variable.
The instance properties will determine the uniquness of each task.

If a new task with identical dependencies is provided to `OnceService->add` method, it will be filtered out before tasks are committed.
This prevents redundant execution of the same logic.

The tasks backlog are processed when `OnceSerivce->commit` method is called, which happens at the end of request lifecycle in `OnceMiddleware`, when kernel wants to shutdown.

Take this example. We want to handle cache revalidation of an **Author** object. The cached data also includes the **Book**s, embedded with each **Author**. Every change on the author and its book, triggers a cache revalidation. There's also an API that allows publishers to add or update authors and their books in bulk. Here's how we could re-arrange the code:

1. Create a rollable task to update author cache.

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

        UpdateAuthorCache::update($this->authorId);
        /**
         * You could also dispatch to a queue in order to process it
         * asynchronously. It depends on your design.
         */
    }
}
```

2. Instatiate a rollable task wherever the logic encapsulated by `perform` method was called previously.

Considering there's a subscriber for a this single side-effect:

```php
namespace App\Subscribers\AuthorCacheSubscriber;

use App\Events\AuthorCreated;
use App\Events\AuthorUpdated;
use App\Events\BookCreated;
use App\Events\BookUpdated;

use App\Jobs\Rollables\UpdateAuthorCacheRollableTask;

class SomeListener
{
     /**
     * Register the listeners for the subscriber.
     *
     * @param  Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(AuthorCreated::class,   self::class.'@handle');
        $events->listen(AuthorUpdated::class,   self::class.'@handle');
        $events->listen(BookCreated::class,     self::class.'@handle');
        $events->listen(BookUpdated::class,     self::class.'@handle');
    }

    public function handle($event)
    {
        /**
         * Instead of:
         *      UpdateAuthorCache::update($event->getAuthorId());
         * We have:
         */
        new UpdateAuthorCacheRollableTask($event->getAuthorId());
    }
}
```

As you can see, once you define the rollable task, it can be treated as a drop-in replacement which makes refactoring a lot easier.

### Caveats
In command line environments (where HTTP request lifecycle is not available), `OnceSerivce->commit` should be called manually.

```php
resolve(OnceSerivce::class)->commit();
```

This is already handled for queued jobs if you use the service provider of the package.
If you examine the service provider, you'd find following lines:

```php
Queue::after(function (JobProcessed $event) {
    resolve(OnceService::class)->commit();
});
```

If you decide not to use the service provider, or these tasks are generated outside the context of Jobs or HTTP Requests (for exampl)

---

## A note on Laravel 8.x unique jobs

While [Laravel 8.x supports unique jobs](https://laravel.com/docs/8.x/queues#unique-jobs), it still does not satisfy our requirements:

1. It's only concerned with what's in the queue which is only effective for slow queues (not enough consumers, delayed jobs, etc.).
2. We can still end up processing jobs for resources that has not been fully initialized yet (before the transaction is complete).
3. It does not address the concern synchronous operations, since only queued operations can benefit from it, with the caveat mentioned in first point.

There's also the matter queue driver support, which is irrelevant for this package.

As result, these two approaches are complementary to each other, addressing similar but different aspects of "effectively-once processing".


