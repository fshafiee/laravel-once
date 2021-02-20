# Laravel Once - Task rollup for Laravel framework

![Build Status](https://github.com/fshafiee/laravel-once/workflows/Package%20Build/badge.svg)

<p align="center">
  <img
    alt="Laravel Once Banner"
    height="148"
    src="https://repository-images.githubusercontent.com/324225031/fae0c400-4659-11eb-9d9d-02044b1efd9a"
  />
</p>

This package allows deferring tasks to the end of the request. It also rolls up identical tasks so that they are processed only once per request
or desired time window.

## Getting Started
### Installing the package

Add the package to your project via composer:

```bash
composer require fshafiee/laravel-once
```

### Defining Rollable Tasks

All you gotta do is to create a new class that extends `LaravelOnce\Tasks\AutoDispatchedTask`, which is an abstract class.
You must define `__construct` and `perform` methods.
Every time a new instance of this rollable class is created, it is automatically added to the backlog.

The dependencies that are needed to fulfill the `perform` operation, must be passed to `__construct` and assigned to an instance variable.

#### An example...

We want to handle cache revalidation of **Author** objects. Each cached object also has the **Book**s, embedded in the object. As result, every change on authors and their books should trigger the cache revalidation. There's also an API that allows publishers to add or update authors and their books in bulk. As result, it is very likely to trigger cache revalidation in a very short burst. Here's how we could arrange the code:

1. Create a rollable task to update author cache.

```PHP
namespace App\Jobs\Rollables;

use App\Jobs\UpdateAuthorCache;
use App\Models\Author;
use LaravelOnce\Tasks\AutoDispatchedTask;

class UpdateAuthorCacheOnce extends AutoDispatchedTask
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

        UpdateAuthorCache::revalidate($this->authorId);
        /**
         * You could also dispatch the job to a queue in
         * order to process it asynchronously. It'll be
         * dispatched only once at the end of the request.
         */
    }
}
```

2. Instatiate a rollable task wherever the logic encapsulated by the `perform` method was previously called.

Considering that there is a subscriber for this single side-effect:

```php
namespace App\Subscribers;

use App\Events\AuthorCreated;
use App\Events\AuthorUpdated;
use App\Events\BookCreated;
use App\Events\BookUpdated;
// ...

use App\Jobs\Rollables\UpdateAuthorCacheOnce;

class AuthorCacheSubscriber
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
        // ... the rest of the event bindings
    }

    public function handle($event)
    {
        /**
         * Instead of:
         *   UpdateAuthorCache::revalidate($event->getAuthorId());
         * We have:
         */
        new UpdateAuthorCacheOnce($event->getAuthorId());
    }
}
```

As you can see, the rollable tasks can be treated as drop-in replacements, if done right.

### Debouncing Task
In addition to rolling up similar tasks in context of a single request, you can do it even between different requests in a desired time window.
Imagine a heavy task like updating a product catalog when the product details have changed. Instead of doing updates after each modification, you can dispatch a `DebouncingTask` as soon as the first update occurrs, with the desired wait time. If during this time window, users make other updates, the timer will reset. When the wait time elapses, the task will be performed.

```php
namespace App\Jobs\Rollables;

use App\Jobs\UpdateProductsCatalogue;
use LaravelOnce\Tasks\DebouncingTask;

class UpdateUsersProductsCatalogue extends DebouncingTask
{
    public $userId;

    public function __construct(string $userId)
    {
        /**
         * Make sure parent::_construct() method is called.
         * or else the task won't be automatically added
         * to the task backlog, and you'd need to add it manually
         * by resolve the service.
         */
        parent::__construct();
        $this->userId = $userId;
    }

    public function perform()
    {

        UpdateProductsCatalogue::forUser($this->userId);
        /**
         * You could also dispatch the job to a queue in
         * order to process it asynchronously. It'll be
         * dispatched only once at the end of the debounce
         * wait time 
         */
    }
    
    public function wait() : int
    {
        return 900;
    }
}
```
***note***: In order to use  `DebouncingTask` you need an active queue connection that supports `delay`.  Therefore, the `sync` queue driver is incompatible with this feature.

### Caveats
Behind the scenes, every time an instance of `AutoDispatchedTask` is created, it resolves the `OnceSerivce` from the container,
adds its own reference to the backlog using `OnceSerivce->add` method. These tasks are then processed in FIFO manner in a terminable middleware by resolving the service and invoking the `commit` method.
As result, in command line environments (where HTTP request lifecycle is not available), `OnceSerivce->commit` should be called manually.

```php
resolve(OnceSerivce::class)->commit();
```

Keep in mind that calling `commit` is already handled for queued jobs.
If you examine `OnceServiceProvider`, you'd find following lines:

```php
Queue::after(function (JobProcessed $event) {
    resolve(OnceService::class)->commit();
});
```

If you decide not to use the package service provider, or these rollable tasks are generated outside the context of jobs or HTTP requests
(e.g. cron jobs, adhoc scripts, etc.), you need to `commit` the tasks manually.

---

## A Few Notes...

### What prompted the development of this package?

There are cases where the initialization process of a resource creates and touches many other related resources.
In addition, it's very common to have listeners setup on those resources in order to trigger and handle side-effects.
This combination of chunky operation and granular event management can introduce some issues:

1. Some of these events may result in an identical side-effct. Without a rollup stage, application resources are wasted on doing redundant operation.
2. In some cases those side-effects that are triggered early in the operation, and may access uninitialized data. This can cause issues and bugs that are hard to track.
3. These side-effects themselves could trigger procedures in other systems (a PubSub system). We don't want to trigger their operation prematurely.

In addition:

1. Silencing events and retriggering them manually would mess up the codebase and introduce all kinds of logic branches that are hard to test and maintain.
2. Queuing and sending the operations to background won't remedy any of the aforementioned problems. It would only improve the preceived performance of API calls (which is a good practice and should be adopted, regardless of the subject).

These observations warranted a task rollup manager pattern to ensure side-effects are processed only once in context of a request.
And since these are "side-ffects", they should not influnence the main logic and response of the operation, hence the **terminable middleware**.

### What about Laravel 8.x unique jobs?

While [Laravel 8.x supports unique jobs](https://laravel.com/docs/8.x/queues#unique-jobs), it still does not satisfy our requirements:

1. It's only concerned with what's currently in the queue, which is only effective for slow queues (not enough consumers, delayed jobs, etc.).
2. We can still end up processing jobs for resources that has not been fully initialized yet.
3. It does not address the concern in synchronous operations, since only queued operations can benefit from it, with the caveat mentioned in the first point.

There's also the matter queue driver support.

It seems like these two approaches are complementary to each other, addressing similar but different aspects of "effectively-once processing".
