This repository features a docker environment to provide isolated PHP environment for development and testing purposes.

1. Make sure Docker is installed on you machine.
2. Start the docker service: `docker-compose up -d laravel-once`
3. Install the dependencies: `docker-compose exec laravel-once composer install`

Now you can use the container to:

1. Run the tests:

```bash
> docker-compose exec laravel-once ./vendor/bin/phpunit tests
```

2. Validate the code-style

```bash
> docker-compose exec laravel-once ./vendor/bin/phpcs --standard=PSR2 src

# In order to fix the issue automatically: 

> docker-compose exec laravel-once ./vendor/bin/phpcbf --standard=PSR2 src
```
